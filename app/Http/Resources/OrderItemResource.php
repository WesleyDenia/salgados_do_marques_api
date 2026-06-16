<?php

namespace App\Http\Resources;

use App\Models\Flavor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $flavorIds = $this->options['flavors'] ?? [];
        $flavorNamesById = ! empty($flavorIds)
            ? Flavor::whereIn('id', $flavorIds)->pluck('name', 'id')->all()
            : [];
        $flavorNames = collect($flavorIds)
            ->map(fn ($flavorId) => $flavorNamesById[(int) $flavorId] ?? null)
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant?->name,
            'name' => $this->name_snapshot,
            'price' => (float) $this->price_snapshot,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,
            'options' => array_merge($this->options ?? [], [
                'flavor_names' => $flavorNames,
            ]),
        ];
    }
}
