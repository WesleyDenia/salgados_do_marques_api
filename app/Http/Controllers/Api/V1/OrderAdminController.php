<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with(['items', 'store', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'store', 'user']);

        return new OrderResource($order);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, Order $order)
    {
        $status = $request->input('status');

        $payload = ['status' => $status];

        if ($status === 'canceled' && $order->cancelled_at === null) {
            $payload['cancelled_at'] = Carbon::now('UTC');
        }

        $order->update($payload);
        $order->load(['items', 'store', 'user']);

        return new OrderResource($order);
    }
}
