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
    protected function buildAdminQuery(array $filters)
    {
        $query = Order::query()->with(['items', 'store', 'user']);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                // If it looks like a numeric ID and has NO leading zeros, search by Key
                if (ctype_digit($search) && !str_starts_with($search, '0')) {
                    $builder->whereKey((int) $search);
                }

                // Optimization: Use prefix search if possible for indices
                $builder
                    ->orWhere('customer_name', 'like', $search . '%')
                    ->orWhere('customer_contact', 'like', $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', $search . '%')
                            ->orWhere('phone', 'like', $search . '%');
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['slot'])) {
            $query->where('slot', $filters['slot']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', (int) $filters['store_id']);
        }

        if (!empty($filters['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $filters['scheduled_from']);
        }

        if (!empty($filters['scheduled_to'])) {
            // Boundary fix: ensuring coverage of the specified end time
            $query->where('scheduled_at', '<=', $filters['scheduled_to']);
        }

        return $query->orderBy('scheduled_at')->orderBy('id');
    }

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
        return $this->buildAdminQuery($filters)
            ->paginate($perPage)
            ->appends($filters);
    }

    public function listForAdmin(array $filters): Collection
    {
        return $this->buildAdminQuery($filters)->get();
    }

    public function listScheduledForStoreDay(
        int $storeId,
        CarbonInterface $scheduledFromUtc,
        CarbonInterface $scheduledToUtc,
        ?int $ignoreOrderId = null
    ): Collection {
        $query = Order::query()
            ->with(['items', 'store', 'user'])
            ->where('store_id', $storeId)
            ->whereBetween('scheduled_at', [$scheduledFromUtc, $scheduledToUtc])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($ignoreOrderId !== null) {
            $query->whereKeyNot($ignoreOrderId);
        }

        return $query->get();
    }

    public function countScheduledBySlotForStoreDay(
        int $storeId,
        CarbonInterface $scheduledFromUtc,
        CarbonInterface $scheduledToUtc,
        array $statuses,
        ?int $ignoreOrderId = null
    ): array {
        $query = Order::query()
            ->select('slot')
            ->selectRaw('count(*) as count')
            ->where('store_id', $storeId)
            ->whereBetween('scheduled_at', [$scheduledFromUtc, $scheduledToUtc])
            ->whereIn('status', $statuses)
            ->groupBy('slot');

        if ($ignoreOrderId !== null) {
            $query->whereKeyNot($ignoreOrderId);
        }

        return $query->pluck('count', 'slot')->all();
    }

    public function findForAdmin(Order $order): Order
    {
        return $order->load(['items', 'store', 'user', 'history.user']);
    }

    public function updateStatus(Order $order, array $payload, ?array $history = null): Order
    {
        DB::transaction(function () use ($history, $order, $payload): void {
            $order->update($payload);
            $this->createHistoryRecord($order, $history);
        });

        return $order->fresh(['items', 'store', 'user', 'history.user']);
    }

    public function updateWithItems(Order $order, array $payload, array $lineItems, ?array $history = null): Order
    {
        /** @var Order $updatedOrder */
        $updatedOrder = DB::transaction(function () use ($history, $lineItems, $order, $payload) {
            $order->update($payload);
            $order->items()->delete();

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
            $this->createHistoryRecord($order, $history);

            return $order;
        });

        return $updatedOrder->fresh(['items', 'store', 'user', 'history.user']);
    }

    public function listStoresForFilter(): Collection
    {
        return Store::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function createWithItems(
        ?int $userId,
        int $storeId,
        ?string $customerName,
        ?string $customerContact,
        ?string $paymentStatus,
        ?string $slot,
        CarbonInterface $scheduledAtUtc,
        ?string $notes,
        array $lineItems
    ): Order {
        /** @var Order $order */
        $order = DB::transaction(function () use ($customerContact, $customerName, $lineItems, $notes, $paymentStatus, $scheduledAtUtc, $slot, $storeId, $userId) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'customer_name' => $customerName,
                'customer_contact' => $customerContact,
                'store_id' => $storeId,
                'status' => 'placed',
                'payment_status' => $paymentStatus,
                'slot' => $slot,
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

        return $order->fresh(['items', 'store', 'user']);
    }

    public function cancel(Order $order, array $payload): Order
    {
        $order->update($payload);

        return $order->fresh(['items', 'store', 'user']);
    }

    protected function createHistoryRecord(Order $order, ?array $history): void
    {
        if ($history === null || empty($history['changes'])) {
            return;
        }

        $order->history()->create([
            'user_id' => $history['user_id'] ?? null,
            'action' => $history['action'] ?? 'updated',
            'changes' => $history['changes'],
        ]);
    }
}
