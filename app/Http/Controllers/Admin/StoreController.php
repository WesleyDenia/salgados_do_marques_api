<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(protected StoreService $stores) {}

    public function index(Request $request)
    {
        $stores = $this->stores->paginateForAdmin($request->only(['city', 'type', 'is_active']));

        return view('admin.stores.index', [
            'stores' => $stores,
            'filters' => $request->only(['city', 'type', 'is_active']),
            'storeService' => $this->stores,
        ]);
    }

    public function create()
    {
        $store = new Store([
            'is_active' => true,
            'type' => 'principal',
        ]);

        return view('admin.stores.create', [
            'store' => $store,
            'scheduleDays' => $this->stores->defaultWeeklySchedule(),
            'dateExceptions' => [],
        ]);
    }

    public function store(StoreRequest $request)
    {
        $this->stores->create($request->validated());

        return redirect()
            ->route('admin.stores.index')
            ->with('status', 'Loja criada com sucesso.');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', [
            'store' => $store,
            'scheduleDays' => $this->stores->normalizeWeeklySchedule($store->pickup_weekly_schedule),
            'dateExceptions' => $this->stores->normalizeDateExceptions($store->pickup_date_exceptions),
        ]);
    }

    public function update(StoreRequest $request, Store $store)
    {
        $this->stores->update($store, $request->validated());

        return redirect()
            ->route('admin.stores.index')
            ->with('status', 'Loja atualizada com sucesso.');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()
            ->route('admin.stores.index')
            ->with('status', 'Loja removida com sucesso.');
    }
}
