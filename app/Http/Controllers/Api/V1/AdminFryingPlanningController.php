<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FryingPlanningApiRequest;
use App\Models\Store;
use App\Services\FryingAlarmRegistrationService;
use App\Services\FryingPlanningService;
use Carbon\CarbonInterface;

class AdminFryingPlanningController extends Controller
{
    public function __construct(
        protected FryingPlanningService $planning,
        protected FryingAlarmRegistrationService $alarms,
    ) {}

    public function show(FryingPlanningApiRequest $request)
    {
        $data = $request->validated();
        $store = Store::query()->findOrFail((int) $data['store_id']);
        $plan = $this->planning->forDateAndStore((string) $data['date'], $store);

        return response()->json([
            'data' => [
                'date' => $plan['date'],
                'timezone' => $plan['timezone'],
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                ],
                'orders_count' => $plan['orders_count'],
                'tasks_count' => $plan['tasks_count'],
                'packaging_time_seconds' => $plan['packaging_time_seconds'],
                'earliest_start_at' => $this->dateTime($plan['earliest_start_at']),
                'warnings' => $plan['warnings'],
                'slots' => collect($plan['slots'])
                    ->map(fn (array $slot): array => [
                        'id' => $slot['id'],
                        'name' => $slot['name'],
                        'display_order' => $slot['display_order'],
                        'tasks' => collect($slot['tasks'])
                            ->map(fn (array $task): array => [
                                'allocation_id' => $task['allocation_id'],
                                'order_id' => $task['order_id'],
                                'order_item_id' => $task['order_item_id'],
                                'customer_name' => $task['customer_name'],
                                'customer_contact' => $task['customer_contact'],
                                'store_name' => $task['store_name'],
                                'payment_status' => $task['payment_status'],
                                'paid_amount' => $task['paid_amount'],
                                'order_total' => $task['order_total'],
                                'notes' => $task['notes'],
                                'product_name' => $task['product_name'],
                                'flavor_names' => $task['flavor_names'],
                                'is_frozen' => $task['is_frozen'],
                                'batch_units' => $task['batch_units'],
                                'preparation_time_seconds' => $task['preparation_time_seconds'],
                                'heating_time_seconds' => $task['heating_time_seconds'],
                                'pickup_at' => $this->dateTime($task['pickup_at']),
                                'ready_by' => $this->dateTime($task['ready_by']),
                                'heating_start_at' => $this->dateTime($task['heating_start_at']),
                                'start_at' => $this->dateTime($task['start_at']),
                                'finish_at' => $this->dateTime($task['finish_at']),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function registerAlarms(FryingPlanningApiRequest $request)
    {
        $data = $request->validated();
        $store = Store::query()->findOrFail((int) $data['store_id']);
        $result = $this->alarms->register((string) $data['date'], $store);

        return response()->json(['data' => $result]);
    }

    private function dateTime(?CarbonInterface $dateTime): ?string
    {
        return $dateTime?->toIso8601String();
    }
}
