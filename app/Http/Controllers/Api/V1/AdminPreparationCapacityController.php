<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreparationCapacityUpdateRequest;
use App\Http\Requests\Admin\PreparationSlotProductTimeStoreRequest;
use App\Http\Requests\Admin\PreparationSlotStoreRequest;
use App\Models\OperationalPreparationSlot;
use App\Models\ProductPreparationSetting;
use App\Services\PreparationCapacityService;
use Illuminate\Http\Request;

class AdminPreparationCapacityController extends Controller
{
    public function __construct(protected PreparationCapacityService $service) {}

    public function show(Request $request)
    {
        $request->validate(['store_id' => ['nullable', 'integer', 'exists:stores,id']]);

        return response()->json([
            'data' => $this->service->getAdminPayload($request->integer('store_id') ?: null),
        ]);
    }

    public function update(PreparationCapacityUpdateRequest $request)
    {
        return response()->json([
            'data' => $this->service->updateAdminPayload($request->validated()),
        ]);
    }

    public function storeSlot(PreparationSlotStoreRequest $request)
    {
        $data = $request->validated();
        $slot = $this->service->createSlot(
            (int) $data['store_id'],
            (string) $data['name'],
            isset($data['clone_from_slot_id']) ? (int) $data['clone_from_slot_id'] : null,
        );

        return response()->json([
            'data' => [
                'id' => $slot->id,
                'store_id' => $slot->store_id,
                'name' => $slot->name,
            ],
        ], 201);
    }

    public function storeProductTime(PreparationSlotProductTimeStoreRequest $request)
    {
        $data = $request->validated();
        $slot = OperationalPreparationSlot::query()
            ->whereKey((int) $data['operational_preparation_slot_id'])
            ->where('store_id', (int) $data['store_id'])
            ->firstOrFail();
        $setting = $this->service->saveProductTime(
            $slot,
            (int) $data['product_id'],
            (int) $data['batch_size'],
            (int) ceil(((float) $data['preparation_minutes']) * 60),
        );

        return response()->json([
            'data' => [
                'id' => $setting->id,
                'operational_preparation_slot_id' => $setting->operational_preparation_slot_id,
                'product_id' => $setting->product_id,
                'batch_size' => $setting->batch_size,
                'preparation_time_seconds' => $setting->preparation_time_seconds,
            ],
        ], 201);
    }

    public function updateSlot(PreparationSlotStoreRequest $request, OperationalPreparationSlot $slot)
    {
        $data = $request->validated();
        abort_unless($slot->store_id === (int) $data['store_id'], 404);

        $slot->update(['name' => trim((string) $data['name'])]);

        return response()->json([
            'data' => [
                'id' => $slot->id,
                'store_id' => $slot->store_id,
                'name' => $slot->name,
            ],
        ]);
    }

    public function destroySlot(Request $request, OperationalPreparationSlot $slot)
    {
        $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        abort_unless($slot->store_id === $request->integer('store_id'), 404);

        $slot->delete();

        return response()->noContent();
    }

    public function updateProductTime(
        PreparationSlotProductTimeStoreRequest $request,
        ProductPreparationSetting $setting,
    ) {
        $data = $request->validated();
        $slot = OperationalPreparationSlot::query()
            ->whereKey($setting->operational_preparation_slot_id)
            ->where('store_id', (int) $data['store_id'])
            ->firstOrFail();

        abort_unless(
            $setting->product_id === (int) $data['product_id']
                && $setting->operational_preparation_slot_id === $slot->id,
            404,
        );

        $setting->update([
            'batch_size' => (int) $data['batch_size'],
            'preparation_time_seconds' => (int) ceil(((float) $data['preparation_minutes']) * 60),
        ]);

        return response()->json(['data' => $this->productTimePayload($setting)]);
    }

    public function destroyProductTime(Request $request, ProductPreparationSetting $setting)
    {
        $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        $belongsToStore = OperationalPreparationSlot::query()
            ->whereKey($setting->operational_preparation_slot_id)
            ->where('store_id', $request->integer('store_id'))
            ->exists();
        abort_unless($belongsToStore, 404);

        $setting->delete();

        return response()->noContent();
    }

    private function productTimePayload(ProductPreparationSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'operational_preparation_slot_id' => $setting->operational_preparation_slot_id,
            'product_id' => $setting->product_id,
            'batch_size' => $setting->batch_size,
            'preparation_time_seconds' => $setting->preparation_time_seconds,
        ];
    }
}
