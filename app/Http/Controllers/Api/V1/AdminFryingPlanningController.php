<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FryingPlanningApiRequest;
use App\Models\Store;
use App\Services\FryingPlanningService;
use Carbon\CarbonInterface;

class AdminFryingPlanningController extends Controller
{
    public function __construct(protected FryingPlanningService $planning) {}

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
                                'customer_name' => $task['customer_name'],
                                'product_name' => $task['product_name'],
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

    private function dateTime(?CarbonInterface $dateTime): ?string
    {
        return $dateTime?->toIso8601String();
    }
}
