<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderSearchRequest;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Models\Order;
use App\Services\AdminFlavorService;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $service,
        protected AdminFlavorService $flavors,
    ) {}

    public function index(OrderSearchRequest $request)
    {
        $filters = $request->validated();

        return view('admin.orders.index', [
            'orders' => $this->service->paginateForAdmin($filters, 20),
            'stores' => $this->service->listStoresForFilter(),
            'filters' => $filters,
            'statusLabels' => $this->service->statusLabels(),
        ]);
    }

    public function daily(OrderSearchRequest $request)
    {
        $planning = $this->service->dailyPlanning($request->validated(), 20);

        return view('admin.orders.daily', [
            'orders' => $planning['orders'],
            'stores' => $this->service->listStoresForFilter(),
            'filters' => $planning['filters'],
            'statusLabels' => $this->service->statusLabels(),
            'slotLabels' => $planning['slotLabels'],
            'summary' => $planning['summary'],
            'selectedDayLabel' => $planning['selectedDayLabel'],
        ]);
    }

    public function show(Order $order)
    {
        $order = $this->service->findForAdmin($order);
        $flavorIds = $order->items
            ->flatMap(function ($item) {
                $flavors = $item->options['flavors'] ?? [];

                return is_array($flavors) ? $flavors : [];
            })
            ->all();

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => $this->service->statusLabels(),
            'allowedTransitions' => $this->service->allowedTransitions($order),
            'flavorNamesById' => $this->flavors->namesByIds($flavorIds),
        ]);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, Order $order)
    {
        $this->service->updateStatus($order, $request->input('status'));

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Status da encomenda atualizado com sucesso.');
    }
}
