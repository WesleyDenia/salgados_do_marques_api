<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with(['items', 'store'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findForUser(Order $order): Order
    {
        return $order->load(['items', 'store']);
    }

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

    public function createWithItems(
        int $userId,
        int $storeId,
        CarbonInterface $scheduledAtUtc,
        ?string $notes,
        array $lineItems
    ): Order {
        /** @var Order $order */
        $order = DB::transaction(function () use ($lineItems, $notes, $scheduledAtUtc, $storeId, $userId) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'store_id' => $storeId,
                'status' => 'placed',
                'scheduled_at' => $scheduledAtUtc,
                'total' => 0,
                'notes' => $notes,
            ]);

            $total = 0;

            foreach ($lineItems as $item) {
                $lineTotal = (float) $item['total'];

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'name_snapshot' => $item['name_snapshot'],
                    'price_snapshot' => $item['price_snapshot'],
                    'quantity' => $item['quantity'],
                    'options' => $item['options'],
                    'total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $order->update(['total' => $total]);

            return $order;
        });

        return $order->fresh(['items', 'store']);
    }

    public function cancel(Order $order, array $payload): Order
    {
        $order->update($payload);

        return $order->fresh(['items', 'store']);
    }
}
