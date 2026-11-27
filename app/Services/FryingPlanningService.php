<?php

namespace App\Services;

use App\Models\OperationalPreparationSlot;
use App\Models\Flavor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductPreparationSetting;
use App\Models\Store;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FryingPlanningService
{
    private const PLANNABLE_STATUSES = ['placed', 'accepted'];

    private const PACKAGING_TIME_SETTING_KEY = 'FRYING_PACKAGING_MINUTES';

    private const COOLING_TIME_SETTING_KEY = 'FRYING_COOLING_MINUTES';

    private const DEFAULT_PACKAGING_TIME_MINUTES = 15;

    private const DEFAULT_COOLING_TIME_MINUTES = 15;

    public function __construct(protected SettingService $settings) {}

    /**
     * Calculates from the current cuba configuration rather than allocations
     * saved when an order was created. Therefore a cuba or time change applies
     * immediately to existing and new orders.
     *
     * @return array<string, mixed>
     */
    public function forDateAndStore(string $date, Store $store): array
    {
        $timezone = (string) $this->settings->get('ORDER_TIMEZONE', $this->settings->get('order_timezone', 'Europe/Lisbon'));
        $packagingTimeSeconds = $this->packagingTimeSeconds();
        $coolingTimeSeconds = $this->coolingTimeSeconds();
        $dayStart = Carbon::createFromFormat('Y-m-d', $date, $timezone)->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();

        $orders = Order::query()
            ->with(['items.variant', 'store', 'user'])
            ->where('store_id', $store->id)
            ->whereIn('status', self::PLANNABLE_STATUSES)
            ->where(function ($query) use ($date, $dayStart, $dayEnd): void {
                $query->where('pickup_date', $date)
                    ->orWhere(function ($legacyQuery) use ($dayStart, $dayEnd): void {
                        $legacyQuery->whereNull('pickup_date')->whereBetween('scheduled_at', [$dayStart->copy()->utc(), $dayEnd->copy()->utc()]);
                    });
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();
        $flavorNamesById = $this->flavorNamesById($orders);

        $preparationSlots = OperationalPreparationSlot::query()
            ->with('productSettings.slot')
            ->where('active', true)
            ->where(function ($query) use ($store): void {
                // Null-store cubas are supported only for installations still
                // carrying the pre-store configuration.
                $query->where('store_id', $store->id)->orWhereNull('store_id');
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $settingsByProduct = $preparationSlots
            ->flatMap(fn (OperationalPreparationSlot $slot) => $slot->productSettings)
            ->groupBy('product_id');

        $work = [];
        $warnings = [];
        if ($orders->isNotEmpty() && $preparationSlots->isEmpty()) {
            $warnings[] = 'Não existem cubas ativas configuradas para esta loja.';
        }

        foreach ($orders as $order) {
            $readyBy = $this->readyBy($order, $timezone, $packagingTimeSeconds);
            if ($readyBy === null) {
                $warnings[] = sprintf('A encomenda #%d não tem hora de levantamento definida.', $order->id);

                continue;
            }

            foreach ($order->items as $item) {
                $productId = $item->product_id === null ? null : (int) $item->product_id;
                /** @var Collection<int, ProductPreparationSetting> $productSettings */
                $productSettings = $productId === null ? collect() : ($settingsByProduct->get($productId) ?? collect());
                if ($productSettings->isEmpty()) {
                    $warnings[] = sprintf('Configure o tempo de fritura de "%s" nas cubas ativas para incluir a encomenda #%d.', $item->name_snapshot, $order->id);

                    continue;
                }

                $units = $this->unitsFor($item);
                if ($units < 1) {
                    continue;
                }

                $work[] = [
                    'order' => $order,
                    'item' => $item,
                    'flavor_names' => $this->flavorNamesFor($item, $flavorNamesById),
                    'ready_by' => $readyBy->copy(),
                    'remaining_units' => $units,
                    'settings' => $productSettings->values(),
                ];
            }
        }

        // Reserve latest collections first. For a given cuba, its cursor then
        // forces each earlier batch backwards and prevents any overlap.
        usort($work, function (array $left, array $right): int {
            $dueComparison = $right['ready_by']->getTimestamp() <=> $left['ready_by']->getTimestamp();
            if ($dueComparison !== 0) {
                return $dueComparison;
            }

            return $right['order']->id <=> $left['order']->id;
        });

        /** @var array<int, CarbonInterface> $availableUntilBySlot */
        $availableUntilBySlot = [];
        /** @var array<int, array<string, mixed>> $slots */
        $slots = [];
        $taskId = 1;

        foreach ($work as $entry) {
            /** @var Order $order */
            $order = $entry['order'];
            /** @var OrderItem $item */
            $item = $entry['item'];
            /** @var CarbonInterface $readyBy */
            $readyBy = $entry['ready_by'];
            /** @var Collection<int, ProductPreparationSetting> $productSettings */
            $productSettings = $entry['settings'];
            /** @var array<int, string> $flavorNames */
            $flavorNames = $entry['flavor_names'];
            $remainingUnits = (int) $entry['remaining_units'];

            while ($remainingUnits > 0) {
                $setting = $this->bestSettingForDeadline($productSettings, $readyBy, $availableUntilBySlot);
                if ($setting === null || $setting->slot === null) {
                    $warnings[] = sprintf('Não existe uma cuba disponível para "%s" na encomenda #%d.', $item->name_snapshot, $order->id);
                    break;
                }

                $slot = $setting->slot;
                $slotId = (int) $slot->id;
                $availableUntil = $availableUntilBySlot[$slotId] ?? $readyBy;
                $finishAt = $availableUntil->lessThan($readyBy) ? $availableUntil->copy() : $readyBy->copy();
                $startAt = $finishAt->copy()->subSeconds((int) $setting->preparation_time_seconds);
                $availableUntilBySlot[$slotId] = $startAt;
                $batchUnits = min($remainingUnits, max(1, (int) $setting->batch_size));
                $remainingUnits -= $batchUnits;

                $slots[$slotId] ??= [
                    'id' => $slotId,
                    'name' => $slot->name,
                    'display_order' => (int) $slot->display_order,
                    'heating_time_seconds' => (int) $slot->heating_time_seconds,
                    'tasks' => [],
                ];
                $slots[$slotId]['tasks'][] = [
                    // This is a transient planning identifier, never a stale
                    // allocation created with an old cuba configuration.
                    'allocation_id' => $taskId++,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'customer_name' => $order->customerNameForDisplay(),
                    'customer_contact' => $order->customerContactForDisplay(),
                    'store_name' => $order->store?->name,
                    'payment_status' => $order->payment_status,
                    'paid_amount' => (float) $order->paid_amount,
                    'order_total' => (float) $order->total,
                    'notes' => $order->notes,
                    'product_name' => $item->name_snapshot ?? 'Artigo não identificado',
                    'flavor_names' => $flavorNames,
                    'is_frozen' => (bool) ($item->options['is_frozen'] ?? false),
                    'batch_units' => $batchUnits,
                    'preparation_time_seconds' => (int) $setting->preparation_time_seconds,
                    'pickup_at' => $order->scheduledAtForDisplay($timezone),
                    'ready_by' => $readyBy,
                    'start_at' => $startAt,
                    'finish_at' => $finishAt,
                ];
            }
        }

        $slots = collect($slots)
            ->map(fn (array $slot): array => $this->applyHeatingSchedule($slot, $coolingTimeSeconds))
            ->sortBy([['display_order', 'asc'], ['id', 'asc']])
            ->values();
        $tasks = $slots->flatMap(fn (array $slot) => $slot['tasks']);

        return [
            'date' => $date,
            'timezone' => $timezone,
            'store' => $store,
            'orders_count' => $orders->count(),
            'tasks_count' => $tasks->count(),
            'packaging_time_seconds' => $packagingTimeSeconds,
            'earliest_start_at' => $tasks->isEmpty()
                ? null
                : $this->planningStartAt(
                    $tasks->sortBy(fn (array $task) => $this->planningStartAt($task)->getTimestamp())->first()
                ),
            'slots' => $slots->all(),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Chooses the cuba whose free cursor is latest. This packs work as late as
     * possible while preserving earlier time for orders that still need it.
     *
     * @param  Collection<int, ProductPreparationSetting>  $settings
     * @param  array<int, CarbonInterface>  $availableUntilBySlot
     */
    private function bestSettingForDeadline(Collection $settings, CarbonInterface $readyBy, array $availableUntilBySlot): ?ProductPreparationSetting
    {
        return $settings
            ->filter(fn (ProductPreparationSetting $setting): bool => $setting->slot !== null)
            ->sort(function (ProductPreparationSetting $left, ProductPreparationSetting $right) use ($readyBy, $availableUntilBySlot): int {
                $leftSlotId = (int) $left->operational_preparation_slot_id;
                $rightSlotId = (int) $right->operational_preparation_slot_id;
                $leftFinish = $availableUntilBySlot[$leftSlotId] ?? $readyBy;
                $rightFinish = $availableUntilBySlot[$rightSlotId] ?? $readyBy;
                $finishComparison = $rightFinish->getTimestamp() <=> $leftFinish->getTimestamp();
                if ($finishComparison !== 0) {
                    return $finishComparison;
                }

                $displayComparison = ((int) $left->slot->display_order) <=> ((int) $right->slot->display_order);

                return $displayComparison !== 0 ? $displayComparison : $leftSlotId <=> $rightSlotId;
            })
            ->first();
    }

    private function unitsFor(OrderItem $item): int
    {
        if ($item->variant?->unit_count !== null && (int) $item->variant->unit_count > 0) {
            return (int) $item->quantity * (int) $item->variant->unit_count;
        }

        return (int) $item->quantity;
    }

    /**
     * @param Collection<int, Order> $orders
     * @return array<int, string>
     */
    private function flavorNamesById(Collection $orders): array
    {
        $flavorIds = $orders
            ->flatMap(fn (Order $order) => $order->items)
            ->flatMap(fn (OrderItem $item) => $item->options['flavors'] ?? [])
            ->map(fn ($flavorId) => (int) $flavorId)
            ->filter(fn (int $flavorId) => $flavorId > 0)
            ->unique()
            ->values();

        if ($flavorIds->isEmpty()) {
            return [];
        }

        return Flavor::query()
            ->whereIn('id', $flavorIds->all())
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, $id): array => [(int) $id => $name])
            ->all();
    }

    /**
     * @param array<int, string> $flavorNamesById
     * @return array<int, string>
     */
    private function flavorNamesFor(OrderItem $item, array $flavorNamesById): array
    {
        return collect($item->options['flavors'] ?? [])
            ->map(fn ($flavorId) => $flavorNamesById[(int) $flavorId] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Insert the heating blocks after all product batches are assigned to a
     * cuba. Rebuilding backwards keeps those blocks from overlapping a prior
     * batch and can consequently move earlier work further back in time.
     *
     * @param  array<string, mixed>  $slot
     * @return array<string, mixed>
     */
    private function applyHeatingSchedule(array $slot, int $coolingTimeSeconds): array
    {
        $tasks = $slot['tasks'];
        $heatingSeconds = (int) ($slot['heating_time_seconds'] ?? 0);
        $needsHeating = [];

        // A first frying starts from a cold cuba. Further heating blocks are
        // enabled after the configured idle time has allowed the cuba to cool.
        $chronological = collect($tasks)
            ->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
            ->values();
        if ($heatingSeconds > 0 && $chronological->isNotEmpty()) {
            $needsHeating[(int) $chronological->first()['allocation_id']] = true;
        }

        do {
            $scheduled = $this->scheduleSlotBackwards($tasks, $needsHeating, $heatingSeconds);
            $updatedHeating = $this->heatingRequirements($scheduled, $heatingSeconds, $coolingTimeSeconds);
            $changed = $updatedHeating !== $needsHeating;
            $needsHeating = $updatedHeating;
            $tasks = $scheduled;
        } while ($changed);

        $slot['tasks'] = collect($tasks)
            ->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
            ->values()
            ->all();

        return $slot;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @param  array<int, bool>  $needsHeating
     * @return array<int, array<string, mixed>>
     */
    private function scheduleSlotBackwards(array $tasks, array $needsHeating, int $heatingSeconds): array
    {
        usort($tasks, function (array $left, array $right): int {
            $dueComparison = $right['ready_by']->getTimestamp() <=> $left['ready_by']->getTimestamp();

            return $dueComparison !== 0
                ? $dueComparison
                : $right['allocation_id'] <=> $left['allocation_id'];
        });

        $availableUntil = null;
        foreach ($tasks as &$task) {
            /** @var CarbonInterface $readyBy */
            $readyBy = $task['ready_by'];
            $finishAt = $availableUntil === null || $availableUntil->greaterThan($readyBy)
                ? $readyBy->copy()
                : $availableUntil->copy();
            $startAt = $finishAt->copy()->subSeconds((int) $task['preparation_time_seconds']);
            $taskId = (int) $task['allocation_id'];
            $task['heating_time_seconds'] = isset($needsHeating[$taskId]) ? $heatingSeconds : 0;
            $task['heating_start_at'] = isset($needsHeating[$taskId])
                ? $startAt->copy()->subSeconds($heatingSeconds)
                : null;
            $task['start_at'] = $startAt;
            $task['finish_at'] = $finishAt;
            $availableUntil = $task['heating_start_at'] ?? $startAt;
        }
        unset($task);

        return $tasks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, bool>
     */
    private function heatingRequirements(array $tasks, int $heatingSeconds, int $coolingTimeSeconds): array
    {
        if ($heatingSeconds === 0 || $tasks === []) {
            return [];
        }

        $chronological = collect($tasks)
            ->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
            ->values();
        $requirements = [(int) $chronological->first()['allocation_id'] => true];

        for ($index = 1; $index < $chronological->count(); $index++) {
            $previous = $chronological[$index - 1];
            $current = $chronological[$index];
            $idleSeconds = $current['start_at']->getTimestamp() - $previous['finish_at']->getTimestamp();

            if ($idleSeconds >= $coolingTimeSeconds) {
                $requirements[(int) $current['allocation_id']] = true;
            }
        }

        return $requirements;
    }

    /** @param array<string, mixed> $task */
    private function planningStartAt(array $task): CarbonInterface
    {
        return $task['heating_start_at'] ?? $task['start_at'];
    }

    private function readyBy(Order $order, string $timezone, int $packagingTimeSeconds): ?CarbonInterface
    {
        $pickupAt = $order->scheduledAtForDisplay($timezone);
        if ($pickupAt === null) {
            return null;
        }

        return $pickupAt->copy()->subSeconds($packagingTimeSeconds);
    }

    private function packagingTimeSeconds(): int
    {
        return $this->minutesToSeconds(
            self::PACKAGING_TIME_SETTING_KEY,
            self::DEFAULT_PACKAGING_TIME_MINUTES,
        );
    }

    private function coolingTimeSeconds(): int
    {
        return $this->minutesToSeconds(
            self::COOLING_TIME_SETTING_KEY,
            self::DEFAULT_COOLING_TIME_MINUTES,
        );
    }

    private function minutesToSeconds(string $key, int $defaultMinutes): int
    {
        return max(0, (int) $this->settings->get($key, $defaultMinutes)) * 60;
    }
}
