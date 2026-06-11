<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanningSlotCapacityUpdateRequest;
use App\Http\Requests\Admin\PlanningSlotOperationalRulesUpdateRequest;
use App\Services\PlanningSlotCapacityService;

class AdminPlanningSlotCapacityController extends Controller
{
    public function __construct(protected PlanningSlotCapacityService $service) {}

    public function show()
    {
        return response()->json([
            'data' => $this->service->getAdminPayload(),
        ]);
    }

    public function update(PlanningSlotCapacityUpdateRequest $request)
    {
        return response()->json([
            'data' => $this->service->updateAdminPayload($request->validated()),
        ]);
    }

    public function showRules()
    {
        return response()->json([
            'data' => $this->service->getOperationalRulesPayload(),
        ]);
    }

    public function updateRules(PlanningSlotOperationalRulesUpdateRequest $request)
    {
        return response()->json([
            'data' => $this->service->updateOperationalRulesPayload($request->validated()),
        ]);
    }
}
