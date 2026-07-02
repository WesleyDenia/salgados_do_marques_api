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
            'parent_order_id' => $this->parent_order_id,
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
            'parent_order' => $this->whenLoaded('parentOrder', function () {
                if (! $this->parentOrder) {
                    return null;
                }

                return [
                    'id' => $this->parentOrder->id,
                    'status' => $this->parentOrder->status,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                if (! $this->user) {
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
            'partial_withdrawals' => $this->whenLoaded('partialWithdrawals', function () {
                return $this->partialWithdrawals->map(function ($withdrawal) {
                    $flavorIds = collect($withdrawal->flavor_ids ?? [])
                        ->map(fn ($flavorId) => (int) $flavorId)
                        ->filter(fn (int $flavorId) => $flavorId > 0)
                        ->values();
                    $flavorNamesById = $flavorIds->isNotEmpty()
                        ? \App\Models\Flavor::query()->whereIn('id', $flavorIds->all())->pluck('name', 'id')->all()
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
                        'completed_at' => $withdrawal->completed_at?->toIso8601String(),
                        'cancelled_at' => $withdrawal->cancelled_at?->toIso8601String(),
                    ];
                })->values();
            }),
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
