<?php

namespace App\Services\Erp\Vendus;

use App\Jobs\ProcessVendusDiscountCardImportJob;
use App\Models\ErpSyncTask;
use App\Models\VendusDiscountCardImport;
use App\Services\ErpSyncTaskService;
use Illuminate\Support\Facades\Log;
use App\Models\UserCoupon;

class VendusCouponSyncService
{
    public function __construct(
        protected VendusHttpClient $http,
        protected ErpSyncTaskService $tasks
    ) {}

    protected function toVendusPayload(UserCoupon $userCoupon): array
    {
        $coupon = $userCoupon->coupon;

        $payload = [
            'amount'      => (string) ($coupon->amount ?? 0),
            'type'        => $coupon->type ?? 'money',
            'date_expire' => optional($coupon->ends_at)->toDateString(),
            'obs'         => $coupon->body ?? 'Cupom gerado via App Salgados do Marquês',
        ];
        
        if ($coupon->category && $coupon->category->external_id) {
            $payload['category'] = (int) $coupon->category->external_id;
        }

        return $payload;
    }

     public function create(UserCoupon $userCoupon): ?array
    {
        $payload = $this->toVendusPayload($userCoupon);
        Log::info('[Vendus] POST /discountcards request', [
            'endpoint' => 'POST /discountcards',
            'entity_type' => 'user_coupon',
            'entity_id' => $userCoupon->id,
            'coupon_id' => $userCoupon->coupon_id,
        ]);

        $resp = $this->http->client()->post('/discountcards/', $payload);

        Log::info('[Vendus] POST /discountcards response', VendusLogSanitizer::response($resp, 'POST /discountcards', [
            'entity_type' => 'user_coupon',
            'entity_id' => $userCoupon->id,
        ]));

        if ($resp->successful()) {
            $json = $resp->json();

            // normaliza o retorno (id + code + status + etc.)
            return [
                'external_id'   => $json['id'] ?? null,
                'external_code' => $json['code'] ?? null,
                'amount'        => $json['amount'] ?? null,
                'type'          => $json['type'] ?? null,
                'status'        => $json['status'] ?? null,
            ];
        }

        Log::error('[Vendus] Falha ao criar cupom', VendusLogSanitizer::response($resp, 'POST /discountcards', [
            'entity_type' => 'user_coupon',
            'entity_id' => $userCoupon->id,
        ]));

        return null;
    }

    public function update(UserCoupon $userCoupon): bool
    {
        if (!$userCoupon->external_id) return false;

        $payload = $this->toVendusPayload($userCoupon);
        $id = $userCoupon->external_id;

        $resp = $this->http->client()->send('PATCH', "/discountcards/{$id}", ['json' => $payload]);
        Log::info('[Vendus] PATCH /discountcards', VendusLogSanitizer::response($resp, 'PATCH /discountcards/{id}', [
            'entity_type' => 'user_coupon',
            'entity_id' => $userCoupon->id,
            'external_id' => $id,
        ]));

        return $resp->successful();
    }

    public function syncUsedCoupons(): void
    {
        try {
            $resp = $this->http->client()->get('/discountcards/');

            Log::info('[Vendus] GET /discountcards response', VendusLogSanitizer::response($resp, 'GET /discountcards'));

            if (!$resp->successful()) {
                Log::error('[Vendus] Falha ao buscar discountcards', VendusLogSanitizer::response($resp, 'GET /discountcards'));
                return;
            }

            $data = $resp->json();

            // Pode vir como "data", "discountcards" ou lista direta
            $list = $data['discountcards'] ?? $data['data'] ?? $data;
            if (!is_array($list) || empty($list)) {
                Log::info('ℹ[Vendus] Nenhum cupom retornado do ERP.');
                return;
            }

            Log::info('[Vendus] Cupons recebidos', ['count' => count($list)]);

            foreach ($list as $erpCoupon) {
                if (!is_array($erpCoupon)) {
                    continue;
                }

                $this->downloadAndQueue($erpCoupon);
            }
        } catch (\Throwable $e) {
            Log::error('[VendusCouponSyncService] Erro ao sincronizar usados', [
                'message' => VendusLogSanitizer::sanitizeMessage($e->getMessage()),
            ]);
        }
    }

    protected function downloadAndQueue(array $erpCoupon): void
    {
        $code = $erpCoupon['code'] ?? null;
        $externalId = isset($erpCoupon['id']) ? (string) $erpCoupon['id'] : null;
        $status = strtolower((string) ($erpCoupon['status'] ?? ''));
        $wasUsed = $status === 'done' || !empty($erpCoupon['date_used']);

        if (!$wasUsed || (!$code && !$externalId)) {
            return;
        }

        $existing = VendusDiscountCardImport::query()
            ->when($externalId, fn ($query) => $query->where('external_id', $externalId))
            ->when($externalId && $code, fn ($query) => $query->orWhere('external_code', $code))
            ->when(!$externalId && $code, fn ($query) => $query->where('external_code', $code))
            ->first();

        if ($existing) {
            $existing->update([
                'vendus_status' => $status ?: null,
                'date_used' => !empty($erpCoupon['date_used']) ? $erpCoupon['date_used'] : $existing->date_used,
                'payload' => $erpCoupon,
            ]);

            if ($wasUsed && $existing->sync_status === VendusDiscountCardImport::STATUS_DOWNLOADED) {
                $existing->update([
                    'sync_status' => VendusDiscountCardImport::STATUS_QUEUED,
                    'queued_at' => now(),
                ]);

                ProcessVendusDiscountCardImportJob::dispatch($existing->id);
                $this->tasks->createOrReuseActive(
                    ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
                    ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
                    $existing->id,
                    [
                        'external_id' => $externalId,
                        'external_code' => $code,
                        'status' => ErpSyncTask::STATUS_QUEUED,
                        'queued_at' => $existing->queued_at,
                    ]
                );
            }

            return;
        }

        $import = VendusDiscountCardImport::create([
            'external_id' => $externalId,
            'external_code' => $code,
            'vendus_status' => $status ?: null,
            'date_used' => !empty($erpCoupon['date_used']) ? $erpCoupon['date_used'] : null,
            'sync_status' => VendusDiscountCardImport::STATUS_DOWNLOADED,
            'payload' => $erpCoupon,
            'downloaded_at' => now(),
        ]);

        if ($wasUsed) {
            $import->update([
                'sync_status' => VendusDiscountCardImport::STATUS_QUEUED,
                'queued_at' => now(),
            ]);

            ProcessVendusDiscountCardImportJob::dispatch($import->id);
        }

        $this->tasks->createOrReuseActive(
            ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
            ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
            $import->id,
            [
                'external_id' => $externalId,
                'external_code' => $code,
                'status' => $wasUsed ? ErpSyncTask::STATUS_QUEUED : ErpSyncTask::STATUS_PENDING,
                'queued_at' => $wasUsed ? now() : null,
            ]
        );

        Log::info('[Vendus] Cupom baixado do Vendus', [
            'vendus_discount_card_import_id' => $import->id,
            'external_id' => $externalId,
            'external_code' => $code,
            'queued' => $wasUsed,
        ]);
    }
}
