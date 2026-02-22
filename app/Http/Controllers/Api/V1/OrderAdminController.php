<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function __construct(protected OrderService $service) {}

    public function index(Request $request)
    {
        $orders = $this->service->paginateForAdmin($request->only(['status', 'store_id']), 20);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order = $this->service->findForAdmin($order);

        return new OrderResource($order);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, Order $order)
    {
        $order = $this->service->updateStatus($order, $request->input('status'));

        return new OrderResource($order);
    }
}
