<?php

namespace App\Services;

use App\Models\PartnerCampaign;
use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\PartnerCampaignRepository;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function listAdmin(): LengthAwarePaginator
    {
        return $this->campaigns->query()
            ->with(['partner', 'coupon'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function createAdmin(array $data): PartnerCampaign
    {
        $data['active'] = (bool) ($data['active'] ?? false);

        return $this->campaigns->create($data);
    }

    public function updateAdmin(PartnerCampaign $campaign, array $data): PartnerCampaign
    {
        $data['active'] = (bool) ($data['active'] ?? false);

        return $this->campaigns->update($campaign, $data);
    }

    public function deleteAdmin(PartnerCampaign $campaign): void
    {
        $this->campaigns->delete($campaign);
    }
}
