<?php

namespace App\Services;

use App\Models\OperationalPreparationSlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPreparationAllocation;
use App\Models\Product;
use App\Models\ProductPreparationSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;

class PreparationCapacityService
{
    public function __construct(protected PlanningSlotCapacityService $slotCapacities) {}

    /**
     * @return array<string, mixed>
     */
    public function getAdminPayload(): array
    {
        $slots = OperationalPreparationSlot::query()
            ->with('productSettings')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return [
            'slots' => $slots
                ->map(fn (OperationalPreparationSlot $slot): array => [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'active' => (bool) $slot->active,
                    'display_order' => (int) $slot->display_order,
                ])
                ->values()
                ->all(),
            'settings' => $slots
                ->flatMap(fn (OperationalPreparationSlot $slot): array => $slot->productSettings
                    ->map(fn (ProductPreparationSetting $setting): array => [
                        'id' => $setting->id,
                        'operational_preparation_slot_id' => $setting->operational_preparation_slot_id,
                        'product_id' => $setting->product_id,
                        'batch_size' => $setting->batch_size,
                        'preparation_time_seconds' => $setting->preparation_time_seconds,
                    ])
                    ->all())
                ->values()
                ->all(),
            'products' => Product::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateAdminPayload(array $input): array
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($input): void {
            $slotIdByIndex = [];
            $submittedSlotIds = [];

            foreach (array_values($input['slots'] ?? []) as $index => $slotInput) {
                $slot = isset($slotInput['id'])
                    ? OperationalPreparationSlot::query()->findOrFail((int) $slotInput['id'])
                    : new OperationalPreparationSlot();

                $slot->fill([
                    'name' => trim((string) $slotInput['name']),
                    'active' => (bool) ($slotInput['active'] ?? true),
                    'display_order' => (int) ($slotInput['display_order'] ?? $index),
                ]);
                $slot->save();

                $slotIdByIndex[$index] = $slot->id;
                $submittedSlotIds[] = $slot->id;
            }

            OperationalPreparationSlot::query()
                ->when(
                    $submittedSlotIds !== [],
                    fn ($query) => $query->whereNotIn('id', $submittedSlotIds)
                )
                ->update(['active' => false]);

            if ($submittedSlotIds !== []) {
                ProductPreparationSetting::query()
                    ->whereIn('operational_preparation_slot_id', $submittedSlotIds)
                    ->delete();
            }

            foreach ($input['settings'] ?? [] as $settingInput) {
                $slotId = null;

                if (isset($settingInput['operational_preparation_slot_id'])) {
                    $slotId = (int) $settingInput['operational_preparation_slot_id'];
                } elseif (isset($settingInput['slot_index'])) {
                    $slotId = $slotIdByIndex[(int) $settingInput['slot_index']] ?? null;
                }

                if (! $slotId || ! in_array($slotId, $submittedSlotIds, true)) {
                    continue;
                }

                ProductPreparationSetting::query()->create([
                    'operational_preparation_slot_id' => $slotId,
                    'product_id' => (int) $settingInput['product_id'],
                    'batch_size' => max(1, (int) ($settingInput['batch_size'] ?? 25)),
                    'preparation_time_seconds' => max(1, (int) $settingInput['preparation_time_seconds']),
                ]);
            }
        });

        return $this->getAdminPayload();
    }

    public function allocateOrder(Order $order, bool $allowOverflow = false): void
    {
        $order->loadMissing(['items.variant', 'preparationAllocations']);

        OrderPreparationAllocation::query()
            ->where('order_id', $order->id)
            ->delete();

        if (! $order->scheduled_at || ! $order->slot) {
            return;
        }

        $activeSlots = $this->activeSlots();

        if ($activeSlots->isEmpty()) {
            return;
        }

        $scheduledDate = $order->scheduled_at->copy()->timezone('Europe/Lisbon')->format('Y-m-d');
        $loads = $this->existingLoads($scheduledDate, (string) $order->slot, (int) $order->id, $activeSlots);
        $settings = $this->settingsForProducts(
            $order->items->pluck('product_id')->filter()->map(fn ($productId): int => (int) $productId)->unique()->all(),
            $activeSlots
        );

        $batchIndex = 1;
        $allocations = [];

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $productSettings = $settings[(int) $item->product_id] ?? collect();

            if ($productSettings->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => sprintf('Configure o tempo de preparo do artigo "%s" nas cubas ativas.', $item->name_snapshot),
                ]);
            }

            $remainingUnits = $this->resolveOrderItemUnits($item);

            while ($remainingUnits > 0) {
                $eligibleSettings = $productSettings
                    ->filter(fn (ProductPreparationSetting $setting): bool => $setting->batch_size > 0)
                    ->sortBy(fn (ProductPreparationSetting $setting): array => [
                        $loads[$setting->operational_preparation_slot_id] ?? 0,
                        $setting->operational_preparation_slot_id,
                    ])
                    ->values();

                /** @var ProductPreparationSetting|null $setting */
                $setting = $eligibleSettings->first();

                if (! $setting) {
                    throw ValidationException::withMessages([
                        'items' => sprintf('Configure pelo menos uma cuba ativa para o artigo "%s".', $item->name_snapshot),
                    ]);
                }

                $batchUnits = min($remainingUnits, (int) $setting->batch_size);
                $loads[$setting->operational_preparation_slot_id] =
                    ($loads[$setting->operational_preparation_slot_id] ?? 0)
                    + (int) $setting->preparation_time_seconds;
                $remainingUnits -= $batchUnits;

                $allocations[] = [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'operational_preparation_slot_id' => $setting->operational_preparation_slot_id,
                    'scheduled_slot' => $order->slot,
                    'scheduled_for_date' => $scheduledDate,
                    'batch_index' => $batchIndex++,
                    'batch_units' => $batchUnits,
                    'preparation_time_seconds' => $setting->preparation_time_seconds,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($allocations === []) {
            return;
        }

        $maxLoadSeconds = max($loads);
        $capacitySeconds = $this->slotCapacitySeconds((string) $order->slot);

        if (! $allowOverflow && $capacitySeconds > 0 && $maxLoadSeconds > $capacitySeconds) {
            throw ValidationException::withMessages([
                'allow_preparation_capacity_overflow' => 'A carga de preparo excede a capacidade do slot. Ative a exceção de preparo para permitir esta encomenda.',
            ]);
        }

        OrderPreparationAllocation::query()->insert($allocations);
    }

    /**
     * @param  iterable<int, Order>  $orders
     * @return array<string, array<string, mixed>>
     */
    public function summarizeOrders(iterable $orders): array
    {
        $summary = [];

        foreach ($orders as $order) {
            $slot = $order->slot ?: 'sem_slot';
            $summary[$slot] ??= [
                'scheduled_slot' => $slot,
                'total_preparation_time_seconds' => 0,
                'max_preparation_time_seconds' => 0,
                'allocations_count' => 0,
                'preparation_slots' => [],
            ];

            foreach ($order->preparationAllocations ?? [] as $allocation) {
                $slotId = (int) $allocation->operational_preparation_slot_id;
                $summary[$slot]['allocations_count']++;
                $summary[$slot]['total_preparation_time_seconds'] += (int) $allocation->preparation_time_seconds;
                $summary[$slot]['preparation_slots'][$slotId] ??= [
                    'id' => $slotId,
                    'name' => $allocation->preparationSlot?->name ?? sprintf('Cuba %d', $slotId),
                    'preparation_time_seconds' => 0,
                    'batches' => 0,
                    'units' => 0,
                ];
                $summary[$slot]['preparation_slots'][$slotId]['preparation_time_seconds'] += (int) $allocation->preparation_time_seconds;
                $summary[$slot]['preparation_slots'][$slotId]['batches']++;
                $summary[$slot]['preparation_slots'][$slotId]['units'] += (int) $allocation->batch_units;
            }
        }

        foreach ($summary as $slot => $entry) {
            $preparationSlots = collect($entry['preparation_slots'])->values();
            $summary[$slot]['max_preparation_time_seconds'] = (int) $preparationSlots
                ->max('preparation_time_seconds');
            $summary[$slot]['preparation_slots'] = $preparationSlots->all();
        }

        return $summary;
    }

    /**
     * @return Collection<int, OperationalPreparationSlot>
     */
    protected function activeSlots(): Collection
    {
        return OperationalPreparationSlot::query()
            ->where('active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, OperationalPreparationSlot>  $activeSlots
     * @return array<int, int>
     */
    protected function existingLoads(string $scheduledDate, string $scheduledSlot, int $ignoreOrderId, Collection $activeSlots): array
    {
        $loads = array_fill_keys($activeSlots->pluck('id')->map(fn ($id): int => (int) $id)->all(), 0);
        $existing = OrderPreparationAllocation::query()
            ->selectRaw('operational_preparation_slot_id, SUM(preparation_time_seconds) as load_seconds')
            ->whereDate('scheduled_for_date', $scheduledDate)
            ->where('scheduled_slot', $scheduledSlot)
            ->where('order_id', '!=', $ignoreOrderId)
            ->whereIn('operational_preparation_slot_id', array_keys($loads))
            ->whereHas('order', function ($query): void {
                $query->whereIn('status', PlanningSlotCapacityService::CAPACITY_CONSUMING_STATUSES);
            })
            ->groupBy('operational_preparation_slot_id')
            ->pluck('load_seconds', 'operational_preparation_slot_id');

        foreach ($existing as $slotId => $loadSeconds) {
            $loads[(int) $slotId] = (int) $loadSeconds;
        }

        return $loads;
    }

    /**
     * @param  array<int, int>  $productIds
     * @param  Collection<int, OperationalPreparationSlot>  $activeSlots
     * @return array<int, SupportCollection<int, ProductPreparationSetting>>
     */
    protected function settingsForProducts(array $productIds, Collection $activeSlots): array
    {
        if ($productIds === []) {
            return [];
        }

        return ProductPreparationSetting::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('operational_preparation_slot_id', $activeSlots->pluck('id')->all())
            ->get()
            ->groupBy('product_id')
            ->all();
    }

    protected function resolveOrderItemUnits(OrderItem $item): int
    {
        if ($item->variant?->unit_count !== null && (int) $item->variant->unit_count > 0) {
            return (int) $item->quantity * (int) $item->variant->unit_count;
        }

        return (int) $item->quantity;
    }

    protected function slotCapacitySeconds(string $slot): int
    {
        $window = $this->slotCapacities->slotWindow($slot);

        if ($window === null) {
            return 0;
        }

        return max(0, (($window['end'] - $window['start']) + 1) * 60);
    }
}
