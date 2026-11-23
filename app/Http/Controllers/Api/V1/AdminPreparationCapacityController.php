<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreparationCapacityUpdateRequest;
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
}
