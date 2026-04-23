<?php

namespace App\Services\Erp\Vendus;

use App\Jobs\ProcessVendusDiscountCardImportJob;
use App\Models\VendusDiscountCardImport;
use Illuminate\Support\Facades\Log;
use App\Models\UserCoupon;

class VendusCouponSyncService
{
    public function __construct(
        protected VendusHttpClient $http
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
        Log::info('[Vendus] POST /discountcards payload', $payload);

        $resp = $this->http->client()->post('/discountcards/', $payload);

        Log::info('[Vendus] POST /discountcards resp', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);

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

        Log::error('[Vendus] Falha ao criar cupom', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);

        return null;
    }

    public function update(UserCoupon $userCoupon): bool
    {
        if (!$userCoupon->external_id) return false;

        $payload = $this->toVendusPayload($userCoupon);
        $id = $userCoupon->external_id;

        $resp = $this->http->client()->send('PATCH', "/discountcards/{$id}", ['json' => $payload]);
        Log::info('[Vendus] PATCH /discountcards', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);

        return $resp->successful();
    }

    public function syncUsedCoupons(): void
    {
        try {
            $resp = $this->http->client()->get('/discountcards/');

            Log::info('[Vendus] GET /discountcards resp', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);

            if (!$resp->successful()) {
                Log::error('[Vendus] Falha ao buscar discountcards', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),                    
                ]);
                return;
            }

            $data = $resp->json();

            // Pode vir como "data", "discountcards" ou lista direta
            $list = $data['discountcards'] ?? $data['data'] ?? $data;
            Log::info('[Vendus] Processando lista de cupons', 
            [
                'coupons' => $list
            ]);

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
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function downloadAndQueue(array $erpCoupon): void
    {
        $code = $erpCoupon['code'] ?? null;
        $externalId = isset($erpCoupon['id']) ? (string) $erpCoupon['id'] : null;
        $status = strtolower((string) ($erpCoupon['status'] ?? ''));
        $wasUsed = $status === 'done' || !empty($erpCoupon['date_used']);

        if (!$code && !$externalId) {
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

        Log::info('[Vendus] Cupom baixado do Vendus', [
            'vendus_discount_card_import_id' => $import->id,
            'external_id' => $externalId,
            'external_code' => $code,
            'queued' => $wasUsed,
        ]);
    }
}
