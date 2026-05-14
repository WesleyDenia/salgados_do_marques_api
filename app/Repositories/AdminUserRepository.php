<?php

namespace App\Repositories;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminUserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()
            ->with(['loyaltyAccount'])
            ->withCount(['orders', 'userCoupons'])
            ->whereIn('role', User::STAFF_ROLES)
            ->latest();

        $search = trim((string) ($filters['search'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%");
            });
        }

        if (in_array($role, User::STAFF_ROLES, true)) {
            $query->where('role', $role);
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('active', $status === 'active');
        }

        return $query
            ->paginate($perPage)
            ->appends($filters);
    }

    public function paginateForCustomers(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()
            ->with(['loyaltyAccount'])
            ->withCount(['orders', 'userCoupons'])
            ->where('role', User::ROLE_CLIENTE)
            ->latest();

        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('active', $status === 'active');
        }

        return $query
            ->paginate($perPage)
            ->appends($filters);
    }

    public function stats(): array
    {
        return [
            'total' => $this->query()->whereIn('role', User::STAFF_ROLES)->count(),
            'active' => $this->query()->whereIn('role', User::STAFF_ROLES)->where('active', true)->count(),
            'admins' => $this->query()->where('role', User::ROLE_ADMIN)->count(),
            'operacional' => $this->query()->where('role', User::ROLE_OPERACIONAL)->count(),
            'atendimento' => $this->query()->where('role', User::ROLE_ATENDIMENTO)->count(),
        ];
    }

    public function customerStats(): array
    {
        return [
            'total' => $this->query()->where('role', User::ROLE_CLIENTE)->count(),
            'active' => $this->query()->where('role', User::ROLE_CLIENTE)->where('active', true)->count(),
            'with_orders' => $this->query()
                ->where('role', User::ROLE_CLIENTE)
                ->whereHas('orders')
                ->count(),
            'with_loyalty' => $this->query()
                ->where('role', User::ROLE_CLIENTE)
                ->whereHas('loyaltyAccount', fn ($query) => $query->where('points', '>', 0))
                ->count(),
        ];
    }

    public function findForAdmin(User $user): User
    {
        return $user->load([
            'loyaltyAccount',
            'consents' => fn ($query) => $query->latest('consented_at')->latest(),
        ]);
    }

    public function userCoupons(User $user): Collection
    {
        return $user->userCoupons()
            ->with(['coupon', 'loyaltyReward', 'partnerCampaign.partner'])
            ->latest()
            ->get();
    }

    public function availableCouponsForUser(User $user, Collection $userCoupons): Collection
    {
        $assignedCouponIds = $userCoupons
            ->pluck('coupon_id')
            ->filter()
            ->unique()
            ->all();

        return Coupon::query()
            ->where('active', true)
            ->where(function ($query) {
                $query
                    ->where('is_loyalty_reward', false)
                    ->orWhereNull('is_loyalty_reward');
            })
            ->where(function ($query) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', Carbon::now());
            })
            ->when($assignedCouponIds !== [], fn ($query) => $query->whereNotIn('id', $assignedCouponIds))
            ->orderBy('title')
            ->get();
    }

    public function loyaltyTransactions(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $user->loyaltyTransactions()
            ->latest()
            ->paginate($perPage, ['*'], 'transactions_page')
            ->withQueryString();
    }

    public function orders(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $user->orders()
            ->with(['store'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'orders_page')
            ->withQueryString();
    }
}
