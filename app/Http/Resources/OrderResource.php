<?php

namespace App\Http\Resources;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'slot' => $this->slot,
            'customer_name' => $this->customer_name,
            'customer_contact' => $this->customer_contact,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'store' => new StoreResource($this->whenLoaded('store')),
            'user' => $this->whenLoaded('user', function () {
                if (!$this->user) {
                    return null;
                }

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'tags' => OrderTagResource::collection($this->whenLoaded('tags')),
            'history' => $this->whenLoaded('history', function () {
                return $this->history->map(fn ($history) => [
                    'id' => $history->id,
                    'user_id' => $history->user_id,
                    'user' => $history->relationLoaded('user') && $history->user ? [
                        'id' => $history->user->id,
                        'name' => $history->user->name,
                        'email' => $history->user->email,
                    ] : null,
                    'action' => $history->action,
                    'changes' => $history->changes,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values();
            }),
            'can_edit' => app(OrderService::class)->canEdit($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
