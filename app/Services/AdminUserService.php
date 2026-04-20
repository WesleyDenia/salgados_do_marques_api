<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AdminUserRepository;

class AdminUserService
{
    public function __construct(
        protected AdminUserRepository $repository,
    ) {}

    public function paginateForAdmin(array $filters, int $perPage = 20)
    {
        return $this->repository->paginateForAdmin($filters, $perPage);
    }

    public function stats(): array
    {
        return $this->repository->stats();
    }

    public function detailData(User $user): array
    {
        $user = $this->repository->findForAdmin($user);
        $userCoupons = $this->repository->userCoupons($user);
        $availableCoupons = $this->repository->availableCouponsForUser($user, $userCoupons);
        $transactions = $this->repository->loyaltyTransactions($user, 12);

        return [
            'user' => $user,
            'userCoupons' => $userCoupons,
            'availableCoupons' => $availableCoupons,
            'transactions' => $transactions,
            'loyaltyPoints' => $user->loyaltyAccount?->points ?? 0,
            'couponStats' => [
                'total' => $userCoupons->count(),
                'available' => $userCoupons->where('active', true)->where('status', '!=', 'done')->count(),
                'used' => $userCoupons->where('status', 'done')->count(),
            ],
        ];
    }
}
