<?php

namespace App\Services;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\ErpSyncTask;
use App\Models\PartnerCampaign;
use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\PartnerCampaignRepository;
use App\Repositories\UserCouponRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerCampaignService
{
    public function __construct(
        protected PartnerCampaignRepository $campaigns,
        protected UserCouponRepository $userCoupons,
        protected ErpSyncTaskService $erpSyncTaskService,
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

            $this->erpSyncTaskService->createOrReuseActive(
                ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD,
                ErpSyncTask::ENTITY_USER_COUPON,
                $userCoupon->id,
                [
                    'status' => ErpSyncTask::STATUS_QUEUED,
                    'queued_at' => now(),
                ]
            );

            CreateVendusDiscountCardJob::dispatch($userCoupon->id)->afterCommit();

            return $userCoupon->load(['coupon', 'partnerCampaign.partner', 'user', 'latestErpTask']);
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
