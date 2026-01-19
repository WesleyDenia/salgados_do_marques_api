<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit_count' => $this->unit_count,
            'max_flavors' => $this->max_flavors,
            'price' => (float) $this->price,
            'active' => $this->active,
            'display_order' => $this->display_order,
        ];
    }
}
