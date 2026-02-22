<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::query()->with(['items', 'store', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', (int) $filters['store_id']);
        }

        if (!empty($filters['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $filters['scheduled_from']);
        }

        if (!empty($filters['scheduled_to'])) {
            $query->where('scheduled_at', '<=', $filters['scheduled_to']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function findForAdmin(Order $order): Order
    {
        return $order->load(['items', 'store', 'user']);
    }

    public function updateStatus(Order $order, array $payload): Order
    {
        $order->update($payload);

        return $order->fresh(['items', 'store', 'user']);
    }

    public function listStoresForFilter(): Collection
    {
        return Store::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
