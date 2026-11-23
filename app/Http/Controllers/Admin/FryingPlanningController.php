<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FryingPlanningRequest;
use App\Models\Store;
use App\Services\FryingPlanningService;
use Illuminate\View\View;

class FryingPlanningController extends Controller
{
    public function __construct(protected FryingPlanningService $planning)
    {
    }

    public function index(FryingPlanningRequest $request): View
    {
        $filters = $request->validated();
        $stores = Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $storeId = isset($filters['store_id'])
            ? (int) $filters['store_id']
            : $stores->first()?->id;
        $store = $storeId ? $stores->firstWhere('id', $storeId) : null;

        return view('admin.frying-planning.index', [
            'stores' => $stores,
            'filters' => [
                'date' => $filters['date'],
                'store_id' => $store?->id,
            ],
            'plan' => $store
                ? $this->planning->forDateAndStore($filters['date'], $store)
                : null,
        ]);
    }
}
