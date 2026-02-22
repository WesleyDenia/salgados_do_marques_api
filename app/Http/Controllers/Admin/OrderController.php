<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderService $service) {}

    public function index(Request $request)
    {
        $statusKeys = array_keys($this->service->statusLabels());

        $filters = $request->validate([
            'status' => ['nullable', Rule::in($statusKeys)],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date', 'after_or_equal:scheduled_from'],
        ]);

        return view('admin.orders.index', [
            'orders' => $this->service->paginateForAdmin($filters, 20),
            'stores' => $this->service->listStoresForFilter(),
            'filters' => $filters,
            'statusLabels' => $this->service->statusLabels(),
        ]);
    }

    public function show(Order $order)
    {
        $order = $this->service->findForAdmin($order);

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => $this->service->statusLabels(),
            'allowedTransitions' => $this->service->allowedTransitions($order),
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
