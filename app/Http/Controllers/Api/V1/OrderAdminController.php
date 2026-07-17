<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderSearchRequest;
use App\Http\Requests\OrderPartialWithdrawalStoreRequest;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Flavor;
use App\Models\Order;
use App\Services\OrderService;

class OrderAdminController extends Controller
{
    public function __construct(protected OrderService $service) {}

    /**
     * @return array<string, mixed>
     */
    protected function serializePartialWithdrawal($withdrawal): array
    {
        $flavorIds = collect($withdrawal->flavor_ids ?? [])
            ->map(fn ($flavorId) => (int) $flavorId)
            ->filter(fn (int $flavorId) => $flavorId > 0)
            ->values();
        $flavorNamesById = $flavorIds->isNotEmpty()
            ? Flavor::query()->whereIn('id', $flavorIds->all())->pluck('name', 'id')->all()
            : [];

        return [
            'id' => $withdrawal->id,
            'parent_order_item_id' => $withdrawal->parent_order_item_id,
            'generated_order_id' => $withdrawal->generated_order_id,
            'requested_units' => $withdrawal->requested_units,
            'flavor_ids' => $flavorIds->all(),
            'flavor_names' => $flavorIds
                ->map(fn (int $flavorId) => $flavorNamesById[$flavorId] ?? null)
                ->filter()
                ->values()
                ->all(),
            'scheduled_at' => $withdrawal->scheduled_at?->toIso8601String(),
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
        ];
    }

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
            'slot_occupancy' => $planning['slotOccupancy'],
        ]);
    }

    public function weekly(OrderSearchRequest $request)
    {
        $planning = $this->service->weeklyPlanningDataset($request->validated());

        return response()->json([
            'data' => OrderResource::collection($planning['orders'])->resolve($request),
            'filters' => $planning['filters'],
            'slot_labels' => $planning['slotLabels'],
            'selected_week_label' => $planning['selectedWeekLabel'],
            'summary' => $planning['summary'],
            'slot_occupancy' => $planning['slotOccupancy'],
            'day_summaries' => $planning['daySummaries'],
        ]);
    }

    public function period(OrderSearchRequest $request)
    {
        $planning = $this->service->periodPlanningDataset($request->validated());

        return response()->json([
            'data' => OrderResource::collection($planning['orders'])->resolve($request),
            'filters' => $planning['filters'],
            'slot_labels' => $planning['slotLabels'],
            'selected_period_label' => $planning['selectedPeriodLabel'],
            'summary' => $planning['summary'],
            'slot_occupancy' => $planning['slotOccupancy'],
            'day_summaries' => $planning['daySummaries'],
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

    public function storePartialWithdrawal(OrderPartialWithdrawalStoreRequest $request, Order $order)
    {
        $result = $this->service->createPartialWithdrawalForAdmin($order, $request->validated());

        return response()->json([
            'data' => [
                'withdrawal' => $this->serializePartialWithdrawal($result['withdrawal']),
                'withdrawals' => collect($result['withdrawals'] ?? [])
                    ->map(fn ($withdrawal) => $this->serializePartialWithdrawal($withdrawal))
                    ->values()
                    ->all(),
                'generated_order' => $result['generated_order']
                    ? (new OrderResource($result['generated_order']))->resolve($request)
                    : null,
                'parent_order' => (new OrderResource($result['parent_order']))->resolve($request),
            ],
        ]);
    }
}
