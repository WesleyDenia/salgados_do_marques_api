<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Support\Facades\Log;

class UserCouponService
{
    public function __construct(
        protected UserCouponRepository $repository,
        protected VendusCouponSyncService $vendusSyncService,
    ) {}

    public function listForUser(User $user, ?string $status = null)
    {
        return $this->repository->forUser($user->id, $status);
    }

    public function activateForUser(User $user, int $couponId): UserCoupon
    {
        $userCoupon = $this->repository->activateForUser($user->id, $couponId);

        $couponResponse = $this->vendusSyncService->create($userCoupon);

        Log::info('[Vendus] create returned', [
            'user_coupon_id' => $userCoupon->id,
            'has_response' => $couponResponse !== null,
        ]);

        if ($couponResponse) {
            $userCoupon = $this->repository->syncFromErp($userCoupon, $couponResponse);
        }

        return $userCoupon;
    }

    public function decrementForUser(User $user, int $couponId): void
    {
        $this->repository->decrementForUser($user->id, $couponId);
    }
}
