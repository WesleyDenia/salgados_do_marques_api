<?php

namespace App\Http\Resources;

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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
