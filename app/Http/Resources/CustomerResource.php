<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone_normalized ?? $this->phone,
            'orders_count' => (int) $this->orders_count,
            'orders_total' => (float) ($this->orders_total ?? 0),
        ];
    }
}
