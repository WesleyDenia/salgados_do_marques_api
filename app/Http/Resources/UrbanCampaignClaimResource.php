<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrbanCampaignClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'coupon_type' => $this->coupon_type,
            'code' => $this->code,
            'external_id' => $this->external_id,
            'status' => $this->status,
            'is_correct' => (bool) $this->is_correct,
            'discount_type' => $this->discount_type,
            'amount' => (float) $this->amount,
            'generated_at' => optional($this->generated_at)->toIso8601String(),
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'erp_error' => $this->erp_sync_error,
        ];
    }
}
