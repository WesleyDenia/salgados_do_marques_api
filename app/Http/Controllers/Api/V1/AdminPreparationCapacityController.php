<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreparationCapacityUpdateRequest;
use App\Services\PreparationCapacityService;

class AdminPreparationCapacityController extends Controller
{
    public function __construct(protected PreparationCapacityService $service) {}

    public function show()
    {
        return response()->json([
            'data' => $this->service->getAdminPayload(),
        ]);
    }

    public function update(PreparationCapacityUpdateRequest $request)
    {
        return response()->json([
            'data' => $this->service->updateAdminPayload($request->validated()),
        ]);
    }
}
