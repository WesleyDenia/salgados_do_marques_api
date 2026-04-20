<?php

namespace App\Services\Erp\Vendus;

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
            // 🔹 Busca todos os discountcards (sem filtros)
            $resp = $this->http->client()->get('/discountcards');

            if (!$resp->successful()) {
                Log::error('[Vendus] Falha ao buscar discountcards', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                    'token'  => $this->http->exposeToken(),
                ]);
                return;
            }

            $data = $resp->json();

            // Pode vir como "data", "discountcards" ou lista direta
            $list = $data['discountcards'] ?? $data['data'] ?? $data;
            Log::info('🔍 [Vendus] Processando lista de cupons', 
            [
                'coupons' => $list
            ]);

            if (!is_array($list) || empty($list)) {
                Log::info('ℹ[Vendus] Nenhum cupom retornado do ERP.');
                return;
            }

            Log::info('[Vendus] Cupons recebidos', ['count' => count($list)]);

            foreach ($list as $erpCoupon) {
                $code = $erpCoupon['code'] ?? null;
                $status = $erpCoupon['status'] ?? null;

                if (!$code) continue;

                // 🔹 Processa apenas cupons finalizados
                if ($status === 'done') {
                    $userCoupon = UserCoupon::where('external_code', $code)->first();

                    if ($userCoupon) {
                        $userCoupon->update([
                            'status' => 'done',
                            'active' => false,
                        ]);

                        Log::info('[Sync] Cupom marcado como utilizado', [
                            'user_coupon_id' => $userCoupon->id,
                            'external_code'  => $code,
                        ]);
                    } else {
                        Log::warning('[Sync] Cupom "done" não encontrado localmente', [
                            'external_code' => $code,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('[VendusCouponSyncService] Erro ao sincronizar usados', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }


}
