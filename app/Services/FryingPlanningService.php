<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPreparationAllocation;
use App\Models\Store;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FryingPlanningService
{
    /**
     * An order marked ready has already left the frying plan. Cancelled and
     * rejected orders must never reserve a cuba in the operational timeline.
     *
     * @var array<int, string>
     */
    private const PLANNABLE_STATUSES = ['placed', 'accepted'];

    public function __construct(protected SettingService $settings)
    {
    }

    /**
     * Build the plan backwards from the latest delivery window. Each cuba has
     * its own cursor, so two cubas can fry in parallel while work assigned to
     * the same cuba never overlaps.
     *
     * @return array<string, mixed>
     */
    public function forDateAndStore(string $date, Store $store): array
    {
        $timezone = (string) $this->settings->get(
            'ORDER_TIMEZONE',
            $this->settings->get('order_timezone', 'Europe/Lisbon')
        );
        $dayStart = Carbon::createFromFormat('Y-m-d', $date, $timezone)->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();

        $orders = Order::query()
            ->with([
                'preparationAllocations.orderItem',
                'preparationAllocations.preparationSlot',
            ])
            ->where('store_id', $store->id)
            ->whereIn('status', self::PLANNABLE_STATUSES)
            ->where(function ($query) use ($date, $dayStart, $dayEnd): void {
                $query->where('pickup_date', $date)
                    ->orWhere(function ($legacyQuery) use ($dayStart, $dayEnd): void {
                        $legacyQuery->whereNull('pickup_date')
                            ->whereBetween('scheduled_at', [
                                $dayStart->copy()->utc(),
                                $dayEnd->copy()->utc(),
                            ]);
                    });
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        $jobs = [];
        $warnings = [];

        foreach ($orders as $order) {
            $readyBy = $this->readyBy($order, $timezone);

            if ($readyBy === null) {
                $warnings[] = sprintf('A encomenda #%d não tem hora de levantamento definida.', $order->id);
                continue;
            }

            if ($order->preparationAllocations->isEmpty()) {
                $warnings[] = sprintf('A encomenda #%d não tem lotes de fritura atribuídos.', $order->id);
                continue;
            }

            foreach ($order->preparationAllocations as $allocation) {
                if ($allocation->preparationSlot === null) {
                    $warnings[] = sprintf('Um lote da encomenda #%d não tem cuba disponível.', $order->id);
                    continue;
                }

                $jobs[] = [
                    'allocation' => $allocation,
                    'order' => $order,
                    'ready_by' => $readyBy->copy(),
                ];
            }
        }

        // The newest delivery window is scheduled first. If earlier work has
        // to move back to avoid an overlap, the per-cuba cursor enforces it.
        usort($jobs, function (array $left, array $right): int {
            $dueComparison = $right['ready_by']->getTimestamp() <=> $left['ready_by']->getTimestamp();

            if ($dueComparison !== 0) {
                return $dueComparison;
            }

            /** @var OrderPreparationAllocation $leftAllocation */
            $leftAllocation = $left['allocation'];
            /** @var OrderPreparationAllocation $rightAllocation */
            $rightAllocation = $right['allocation'];

            return $rightAllocation->id <=> $leftAllocation->id;
        });

        /** @var array<int, CarbonInterface> $availableUntilBySlot */
        $availableUntilBySlot = [];
        /** @var array<int, array<string, mixed>> $slots */
        $slots = [];

        foreach ($jobs as $job) {
            /** @var OrderPreparationAllocation $allocation */
            $allocation = $job['allocation'];
            /** @var Order $order */
            $order = $job['order'];
            $slot = $allocation->preparationSlot;
            $slotId = (int) $allocation->operational_preparation_slot_id;
            /** @var CarbonInterface $readyBy */
            $readyBy = $job['ready_by'];
            $availableUntil = $availableUntilBySlot[$slotId] ?? $readyBy;
            $finishAt = $availableUntil->lessThan($readyBy)
                ? $availableUntil->copy()
                : $readyBy->copy();
            $startAt = $finishAt->copy()->subSeconds((int) $allocation->preparation_time_seconds);
            $availableUntilBySlot[$slotId] = $startAt;

            $slots[$slotId] ??= [
                'id' => $slotId,
                'name' => $slot->name,
                'display_order' => (int) $slot->display_order,
                'tasks' => [],
            ];
            $slots[$slotId]['tasks'][] = [
                'allocation_id' => $allocation->id,
                'order_id' => $order->id,
                'customer_name' => $order->customerNameForDisplay(),
                'product_name' => $allocation->orderItem?->name_snapshot ?? 'Artigo não identificado',
                'batch_units' => (int) $allocation->batch_units,
                'preparation_time_seconds' => (int) $allocation->preparation_time_seconds,
                'pickup_at' => $order->scheduledAtForDisplay($timezone),
                'ready_by' => $readyBy,
                'start_at' => $startAt,
                'finish_at' => $finishAt,
            ];
        }

        $slots = collect($slots)
            ->map(function (array $slot): array {
                $slot['tasks'] = collect($slot['tasks'])
                    ->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
                    ->values()
                    ->all();

                return $slot;
            })
            ->sortBy([
                ['display_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $tasks = $slots->flatMap(fn (array $slot) => $slot['tasks']);

        return [
            'date' => $date,
            'timezone' => $timezone,
            'store' => $store,
            'orders_count' => $orders->count(),
            'tasks_count' => $tasks->count(),
            'earliest_start_at' => $tasks->isEmpty()
                ? null
                : $tasks->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
                    ->first()['start_at'],
            'slots' => $slots->all(),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function readyBy(Order $order, string $timezone): ?CarbonInterface
    {
        $pickupAt = $order->scheduledAtForDisplay($timezone);

        if ($pickupAt === null) {
            return null;
        }

        $minutes = ((int) $pickupAt->format('H') * 60) + (int) $pickupAt->format('i');
        $roundedMinutes = intdiv($minutes, 30) * 30;

        return $pickupAt->copy()->startOfDay()->addMinutes($roundedMinutes);
    }
}
