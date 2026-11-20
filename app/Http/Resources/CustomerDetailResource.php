<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone_normalized ?? $this->phone,
            'nif' => $this->nif,
            'birth_date' => $this->birth_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'orders_count' => (int) ($this->orders_count ?? $this->orders?->count() ?? 0),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
        ];
    }
}
