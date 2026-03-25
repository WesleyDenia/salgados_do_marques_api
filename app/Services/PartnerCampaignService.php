<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\PartnerCampaignRepository;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerCampaignService
{
    public function __construct(
        protected PartnerCampaignRepository $campaigns,
        protected UserCouponRepository $userCoupons,
        protected VendusCouponSyncService $vendusCouponSyncService,
    ) {}

    public function validateCode(User $user, string $code): UserCoupon
    {
        $campaign = $this->campaigns->eligibleByCode($code);

        if (!$campaign) {
            throw ValidationException::withMessages([
                'code' => 'Código de parceiro inválido ou indisponível.',
            ]);
        }

        $existing = $this->userCoupons->findActivePartnerCoupon($user->id, $campaign->id);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $campaign) {
            $userCoupon = $this->userCoupons->createOrGetPartnerCoupon($user->id, $campaign);

            if ($userCoupon->external_code) {
                return $userCoupon;
            }

            $response = $this->vendusCouponSyncService->create($userCoupon);

            if (!$response || empty($response['external_code'])) {
                throw ValidationException::withMessages([
                    'code' => 'Não foi possível gerar o cupom do parceiro neste momento.',
                ]);
            }

            return $this->userCoupons->syncFromErp($userCoupon, $response)
                ->load(['coupon', 'partnerCampaign.partner', 'user']);
        });
    }
}
