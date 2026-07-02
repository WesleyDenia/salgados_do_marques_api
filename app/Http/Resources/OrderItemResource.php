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
        $unitCount = $this->variant?->unit_count;
        $originalUnits = $unitCount !== null
            ? ((int) $this->quantity * (int) $unitCount)
            : (int) $this->quantity;
        $withdrawnUnits = $this->relationLoaded('partialWithdrawals')
            ? (int) $this->partialWithdrawals
                ->where('status', '!=', \App\Models\OrderPartialWithdrawal::STATUS_CANCELLED)
                ->sum('requested_units')
            : 0;
        $remainingUnits = max(0, $originalUnits - $withdrawnUnits);
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
            'parent_order_item_id' => $this->parent_order_item_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant?->name,
            'variant_unit_count' => $unitCount !== null ? (int) $unitCount : null,
            'name' => $this->name_snapshot,
            'price' => (float) $this->price_snapshot,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,
            'original_units' => $originalUnits,
            'withdrawn_units' => $withdrawnUnits,
            'remaining_units' => $remainingUnits,
            'can_withdraw_partially' => $this->parent_order_item_id === null
                && $originalUnits >= 25
                && $originalUnits % 25 === 0,
            'options' => array_merge($this->options ?? [], [
                'flavor_names' => $flavorNames,
            ]),
        ];
    }
}
