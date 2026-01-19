<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'type' => $this->type,
            'accepts_orders' => $this->accepts_orders,
            'default_store' => $this->default_store,
            'distance_km' => $this->when(isset($this->distance_km), round((float) $this->distance_km, 1)),
        ];
    }
}
