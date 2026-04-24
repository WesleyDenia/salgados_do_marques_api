<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Coupon;
use App\Models\PartnerCampaign;
use App\Models\UserCoupon;
use Illuminate\Support\Facades\Log;

class UserCouponRepository extends BaseRepository
{
    public function __construct(UserCoupon $model)
    {
        parent::__construct($model);
    }

    /**
     * Lista todos os cupons de um usuário (com os dados do cupom).
     */
    public function forUser(int $userId, ?string $status = null)
    {
        $query = $this->model
            ->with(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask'])
            ->where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Sincroniza dados vindos do ERP (Vendus) no banco local.
     * Atualiza o external_id, external_code e logs de controle.
     */
    public function syncFromErp(UserCoupon $userCoupon, array $couponResponse): UserCoupon
    {
        $resolvedStatus = $couponResponse['status']
            ?? (!empty($couponResponse['external_code']) ? UserCoupon::STATUS_SYNCED : null);

        Log::info('🔄 [UserCouponRepository] Sincronizando cupom do ERP', [
            'user_coupon_id' => $userCoupon->id,
            'external_id' => $couponResponse['external_id'] ?? null,
            'external_code' => $couponResponse['external_code'] ?? null,
            'status' => $resolvedStatus,
        ]);

        // segurança contra sobrescrita indevida de ID
        if (
            $userCoupon->external_id &&
            isset($couponResponse['external_id']) &&
            $userCoupon->external_id !== $couponResponse['external_id']
        ) {
            Log::warning("⚠️ Inconsistência: Tentativa de sobrescrever external_id existente", [
                'user_coupon_id' => $userCoupon->id,
                'current' => $userCoupon->external_id,
                'new' => $couponResponse['external_id'],
            ]);

            // Apenas retorna sem atualizar
            return $userCoupon;
        }

        // Atualiza apenas os campos relevantes
        $fieldsToUpdate = collect([
            'external_id'   => $couponResponse['external_id'] ?? null,
            'external_code' => $couponResponse['external_code'] ?? null,
            'status'        => $resolvedStatus,
            'erp_sync_error' => null,
            'erp_synced_at' => !empty($couponResponse['external_code']) ? now() : null,
        ])->filter(fn ($v) => !is_null($v))->toArray();

        if (!empty($fieldsToUpdate)) {
            $userCoupon->fill($fieldsToUpdate);

            if ($userCoupon->isDirty()) {
                $userCoupon->save();

                Log::info('🔗 [UserCouponRepository] ERP sync (Eloquent)', [
                    'user_coupon_id' => $userCoupon->id,
                    'updates' => $fieldsToUpdate,
                ]);
            }
        }

        if (!empty($couponResponse['external_code'])) {
            $userCoupon->coupon()->update(['code' => $couponResponse['external_code']]);
        }

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }


    /**
     * Ativa (ou cria) o cupom para o usuário autenticado.
     * Garante idempotência e verifica validade do cupom.
     */
    public function activateForUser(int $userId, int $couponId): UserCoupon
    {
        $coupon = Coupon::findOrFail($couponId);
        $now = Carbon::now();

        if (!$coupon->active) {
            abort(422, 'Este cupom está inativo.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            abort(422, 'Este cupom ainda não está disponível.');
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            abort(422, 'Este cupom já expirou.');
        }

        // Cria ou retorna existente
        $userCoupon = $this->model->firstOrCreate(
            ['user_id' => $userId, 'coupon_id' => $couponId],
            [
                'type' => 'regular',
                'usage_limit' => $coupon->usage_limit ?? 1,
                'usage_count' => 0,
                'expires_at'  => $coupon->ends_at,
                'active'      => true,
                'status'      => 'pending',
            ]
        );

        // Se já existia e estava inativo, reativa
        if (!$userCoupon->active) {
            $userCoupon->update(['active' => true]);
        }

        return $userCoupon->load('coupon');
    }

    public function findActivePartnerCoupon(int $userId, int $campaignId): ?UserCoupon
    {
        return $this->model
            ->with(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask'])
            ->where('user_id', $userId)
            ->where('partner_campaign_id', $campaignId)
            ->whereIn('status', UserCoupon::ACTIVE_REUSABLE_STATUSES)
            ->first();
    }

    public function createOrGetPartnerCoupon(int $userId, PartnerCampaign $campaign): UserCoupon
    {
        $userCoupon = $this->model->firstOrCreate(
            [
                'user_id' => $userId,
                'origin_key' => $this->partnerOriginKey($campaign->id),
            ],
            [
                'coupon_id' => $campaign->coupon_id,
                'type' => 'partner',
                'partner_campaign_id' => $campaign->id,
                'origin_key' => $this->partnerOriginKey($campaign->id),
                'usage_limit' => 1,
                'usage_count' => 0,
                'expires_at' => $campaign->coupon?->ends_at,
                'active' => true,
                'status' => UserCoupon::STATUS_PENDING_ERP,
            ]
        );

        if (!$userCoupon->active && in_array($userCoupon->status, UserCoupon::ACTIVE_REUSABLE_STATUSES, true)) {
            $userCoupon->update(['active' => true]);
        }

        return $userCoupon->load(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    public function findActiveLoyaltyCoupon(int $userId, int $rewardId): ?UserCoupon
    {
        return $this->model
            ->with(['coupon', 'user', 'latestErpTask'])
            ->where('user_id', $userId)
            ->where('origin_key', $this->loyaltyOriginKey($rewardId))
            ->whereIn('status', UserCoupon::ACTIVE_REUSABLE_STATUSES)
            ->first();
    }

    public function findAnyLoyaltyCoupon(int $userId, int $rewardId): ?UserCoupon
    {
        return $this->model
            ->with(['coupon', 'user', 'latestErpTask'])
            ->where('user_id', $userId)
            ->where('origin_key', $this->loyaltyOriginKey($rewardId))
            ->first();
    }

    public function createOrGetLoyaltyCoupon(int $userId, int $rewardId, array $attributes): UserCoupon
    {
        $defaults = array_merge($attributes, [
            'type' => 'loyalty',
            'loyalty_reward_id' => $rewardId,
            'origin_key' => $this->loyaltyOriginKey($rewardId),
            'status' => UserCoupon::STATUS_PENDING_ERP,
            'active' => true,
            'usage_limit' => 1,
            'usage_count' => 0,
        ]);

        $userCoupon = $this->model->firstOrCreate(
            [
                'user_id' => $userId,
                'origin_key' => $this->loyaltyOriginKey($rewardId),
            ],
            $defaults
        );

        return $userCoupon->load(['coupon', 'user', 'latestErpTask']);
    }

    public function markErpSyncing(UserCoupon $userCoupon): UserCoupon
    {
        $userCoupon->forceFill([
            'status' => UserCoupon::STATUS_SYNCING_ERP,
            'erp_sync_error' => null,
            'erp_sync_attempts' => ((int) $userCoupon->erp_sync_attempts) + 1,
        ])->save();

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    public function markErpFailed(UserCoupon $userCoupon, string $message): UserCoupon
    {
        $userCoupon->forceFill([
            'status' => UserCoupon::STATUS_FAILED_ERP,
            'erp_sync_error' => mb_strimwidth($message, 0, 1000, '...'),
        ])->save();

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    public function markManualReview(UserCoupon $userCoupon, ?string $note = null): UserCoupon
    {
        $userCoupon->forceFill([
            'status' => UserCoupon::STATUS_MANUAL_REVIEW,
            'erp_sync_error' => $note ?: 'Revisão manual necessária.',
        ])->save();

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    public function markCancelled(UserCoupon $userCoupon, ?string $note = null): UserCoupon
    {
        $userCoupon->forceFill([
            'active' => false,
            'status' => UserCoupon::STATUS_CANCELLED,
            'erp_sync_error' => $note ?: 'Cupom cancelado manualmente.',
        ])->save();

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    public function markPendingErp(UserCoupon $userCoupon): UserCoupon
    {
        $userCoupon->forceFill([
            'active' => true,
            'status' => UserCoupon::STATUS_PENDING_ERP,
            'erp_sync_error' => null,
        ])->save();

        return $userCoupon->refresh()->loadMissing(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
    }

    protected function loyaltyOriginKey(int $rewardId): string
    {
        return 'loyalty_reward:' . $rewardId;
    }

    protected function partnerOriginKey(int $campaignId): string
    {
        return 'partner_campaign:' . $campaignId;
    }

    /**
     * Marcar como usado (decrementa o usage_count).
     */
    public function decrementForUser(int $userId, int $couponId): void
    {
        $userCoupon = $this->model
            ->where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->first();

        if (!$userCoupon) {
            Log::warning("⚠️ Tentativa de marcar uso para cupom inexistente", [
                'user_id' => $userId,
                'coupon_id' => $couponId,
            ]);
            return;
        }

        // Incrementa o uso
        $userCoupon->usage_count = ($userCoupon->usage_count ?? 0) + 1;

        // Se atingiu o limite, marca como 'done'
        if ($userCoupon->usage_limit && $userCoupon->usage_count >= $userCoupon->usage_limit) {
            $userCoupon->status = 'done';
            $userCoupon->active = false;
        }

        $userCoupon->save();

        Log::info("✅ [UserCouponRepository] Uso incrementado", [
            'user_coupon_id' => $userCoupon->id,
            'usage_count' => $userCoupon->usage_count,
            'status' => $userCoupon->status,
        ]);
    }

}
