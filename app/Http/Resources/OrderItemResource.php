<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'name' => $this->name_snapshot,
            'price' => (float) $this->price_snapshot,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,
            'options' => $this->options,
        ];
    }
}
