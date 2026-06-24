<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderTagUpsertRequest;
use App\Http\Resources\OrderTagResource;
use App\Models\ActivityLog;
use App\Models\OrderTag;
use App\Services\AdminOrderTagService;

class AdminOrderTagController extends Controller
{
    public function __construct(protected AdminOrderTagService $service) {}

    public function index()
    {
        return OrderTagResource::collection($this->service->list());
    }

    public function store(OrderTagUpsertRequest $request)
    {
        $orderTag = $this->service->create($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'subject_type' => 'OrderTag',
            'subject_id' => $orderTag->id,
            'action' => 'create_order_tag',
            'payload' => [
                'name' => $orderTag->name,
                'color' => $orderTag->color,
                'active' => $orderTag->active,
            ],
        ]);

        return new OrderTagResource($orderTag);
    }

    public function update(OrderTagUpsertRequest $request, OrderTag $orderTag)
    {
        $before = [
            'name' => $orderTag->name,
            'color' => $orderTag->color,
            'active' => $orderTag->active,
        ];

        $updatedTag = $this->service->update($orderTag, $request->validated());

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'subject_type' => 'OrderTag',
            'subject_id' => $updatedTag->id,
            'action' => 'update_order_tag',
            'payload' => [
                'before' => $before,
                'after' => [
                    'name' => $updatedTag->name,
                    'color' => $updatedTag->color,
                    'active' => $updatedTag->active,
                ],
            ],
        ]);

        return new OrderTagResource($updatedTag->loadCount('orders'));
    }
}
