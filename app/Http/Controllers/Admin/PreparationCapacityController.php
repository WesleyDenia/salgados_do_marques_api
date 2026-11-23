<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreparationSlotProductTimeStoreRequest;
use App\Http\Requests\Admin\PreparationSlotStoreRequest;
use App\Models\OperationalPreparationSlot;
use App\Models\Product;
use App\Models\Store;
use App\Services\PreparationCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreparationCapacityController extends Controller
{
    public function __construct(protected PreparationCapacityService $service)
    {
    }

    public function index(Request $request): View
    {
        $stores = Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedStoreId = $request->integer('store_id') ?: $stores->first()?->id;

        abort_unless(
            $selectedStoreId !== null && $stores->contains('id', $selectedStoreId),
            404,
        );

        return view('admin.preparation-capacity.index', [
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'slots' => OperationalPreparationSlot::query()
                ->with(['productSettings.product'])
                ->where('store_id', $selectedStoreId)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(),
            'products' => Product::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function storeSlot(PreparationSlotStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->service->createSlot(
            (int) $data['store_id'],
            (string) $data['name'],
            isset($data['clone_from_slot_id']) ? (int) $data['clone_from_slot_id'] : null,
        );

        return redirect()
            ->route('admin.preparation-capacity.index', ['store_id' => $data['store_id']])
            ->with('status', isset($data['clone_from_slot_id'])
                ? 'Cuba clonada com os tempos de fritura.'
                : 'Cuba adicionada com sucesso.');
    }

    public function storeProductTime(PreparationSlotProductTimeStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $slot = OperationalPreparationSlot::query()
            ->whereKey((int) $data['operational_preparation_slot_id'])
            ->where('store_id', (int) $data['store_id'])
            ->firstOrFail();

        $seconds = (int) ceil(((float) $data['preparation_minutes']) * 60);
        $this->service->saveProductTime(
            $slot,
            (int) $data['product_id'],
            (int) $data['batch_size'],
            $seconds,
        );

        return redirect()
            ->route('admin.preparation-capacity.index', ['store_id' => $data['store_id']])
            ->with('status', 'Tempo de fritura guardado com sucesso.');
    }
}
