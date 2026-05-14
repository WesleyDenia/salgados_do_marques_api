<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AdminUserRepository;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function __construct(
        protected AdminUserRepository $repository,
    ) {}

    public function paginateForAdmin(array $filters, int $perPage = 20)
    {
        return $this->repository->paginateForAdmin($filters, $perPage);
    }

    public function paginateForCustomers(array $filters, int $perPage = 20)
    {
        return $this->repository->paginateForCustomers($filters, $perPage);
    }

    public function stats(): array
    {
        return $this->repository->stats();
    }

    public function customerStats(): array
    {
        return $this->repository->customerStats();
    }

    public function detailData(User $user): array
    {
        $user = $this->repository->findForAdmin($user);
        $userCoupons = $this->repository->userCoupons($user);
        $availableCoupons = $this->repository->availableCouponsForUser($user, $userCoupons);
        $transactions = $this->repository->loyaltyTransactions($user, 12);
        $orders = $this->repository->orders($user, 12);

        return [
            'user' => $user,
            'userCoupons' => $userCoupons,
            'availableCoupons' => $availableCoupons,
            'transactions' => $transactions,
            'orders' => $orders,
            'loyaltyPoints' => $user->loyaltyAccount?->points ?? 0,
            'couponStats' => [
                'total' => $userCoupons->count(),
                'available' => $userCoupons->where('active', true)->where('status', '!=', 'done')->count(),
                'used' => $userCoupons->where('status', 'done')->count(),
            ],
        ];
    }

    public function update(User $user, array $data): User
    {
        $password = $data['password'] ?? null;
        unset($data['password']);

        $erpFields = ['name', 'email', 'nif', 'phone', 'street', 'city', 'postal_code'];
        $erpPayloadChanged = collect($erpFields)
            ->contains(fn (string $field) => array_key_exists($field, $data) && $user->{$field} !== $data[$field]);

        $data['active'] = (bool) ($data['active'] ?? false);

        if ($erpPayloadChanged) {
            $data['erp_sync_status'] = 'pending';
            $data['erp_sync_error'] = null;
        }

        $user->update($data);

        if ($password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        }

        return $user->refresh();
    }

    public function create(array $data): User
    {
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return $user->refresh();
    }

    public function delete(User $user, User $actingUser): void
    {
        if ($user->is($actingUser)) {
            throw new \InvalidArgumentException('Você não pode excluir o próprio usuário logado.');
        }

        $user->delete();
    }

    public function updateOwnPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();
    }
}
