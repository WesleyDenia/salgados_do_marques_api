<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderSearchRequest;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;

class OrderAdminController extends Controller
{
    public function __construct(protected OrderService $service) {}

    public function index(OrderSearchRequest $request)
    {
        $orders = $this->service->paginateForAdmin($request->validated(), 20);

        return OrderResource::collection($orders);
    }

    public function daily(OrderSearchRequest $request)
    {
        $planning = $this->service->dailyPlanningDataset($request->validated());

        return response()->json([
            'data' => OrderResource::collection($planning['orders'])->resolve($request),
            'filters' => $planning['filters'],
            'slot_labels' => $planning['slotLabels'],
            'selected_day_label' => $planning['selectedDayLabel'],
            'summary' => $planning['summary'],
        ]);
    }

    public function show(Order $order)
    {
        $order = $this->service->findForAdmin($order);

        return new OrderResource($order);
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        $order = $this->service->updateForAdmin($order, $request->validated());

        return new OrderResource($order);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, Order $order)
    {
        $order = $this->service->updateStatus($order, $request->input('status'));

        return new OrderResource($order);
    }
}
