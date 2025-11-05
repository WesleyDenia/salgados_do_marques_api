<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'code' => $this->code,
            'image_url' => $this->image_url,
            'recurrence' => $this->recurrence,
            'starts_at' => optional($this->starts_at)->toIso8601String(),
            'ends_at' => optional($this->ends_at)->toIso8601String(),
            'active' => $this->active,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'is_loyalty_reward' => (bool) $this->is_loyalty_reward,
        ];
    }
}
