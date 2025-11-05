<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::query();

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->input('city') . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('is_active')) {
            $isActive = $request->boolean('is_active', null);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        $stores = $query
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.stores.index', [
            'stores' => $stores,
            'filters' => $request->only(['city', 'type', 'is_active']),
        ]);
    }

    public function create()
    {
        $store = new Store([
            'is_active' => true,
            'type' => 'principal',
        ]);

        return view('admin.stores.create', compact('store'));
    }

    public function store(StoreRequest $request)
    {
        $data = $this->normalizeData($request);

        Store::create($data);

        return redirect()
            ->route('admin.stores.index')
            ->with('status', 'Loja criada com sucesso.');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(StoreRequest $request, Store $store)
    {
        $data = $this->normalizeData($request);

        $store->update($data);

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

    protected function normalizeData(StoreRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['latitude'] = (float) $data['latitude'];
        $data['longitude'] = (float) $data['longitude'];

        return $data;
    }
}
