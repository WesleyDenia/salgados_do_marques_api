<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderAvailabilityDatesRequest;
use App\Http\Requests\OrderAvailabilityHoursRequest;
use App\Http\Requests\OrderAvailabilityMinutesRequest;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function settings()
    {
        return response()->json([
            'data' => $this->orders->orderSettings(),
        ]);
    }

    public function availabilityDates(OrderAvailabilityDatesRequest $request)
    {
        return response()->json([
            'data' => $this->orders->availabilityDates($request->validated()),
        ]);
    }

    public function availabilityHours(OrderAvailabilityHoursRequest $request)
    {
        return response()->json([
            'data' => $this->orders->availabilityHours($request->validated()),
        ]);
    }

    public function availabilityMinutes(OrderAvailabilityMinutesRequest $request)
    {
        return response()->json([
            'data' => $this->orders->availabilityMinutes($request->validated()),
        ]);
    }

    public function index(Request $request)
    {
        $orders = $this->orders->listForUser($request->user()->id);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return new OrderResource($this->orders->findForUser($order));
    }

    public function store(OrderStoreRequest $request)
    {
        $order = $this->orders->createForUser($request->user(), $request->validated());

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return new OrderResource($this->orders->cancelForUser($order));
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
