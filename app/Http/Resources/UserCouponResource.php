<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCouponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'external_code' => $this->external_code,
            'external_id'   => $this->external_id,
            'type'         => $this->type,
            'loyalty_reward_id' => $this->loyalty_reward_id,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'expires_at'  => optional($this->expires_at)->toIso8601String(),
            'active'      => $this->active,
            'status'      => $this->status,
            'user'        => [
                'id'    => $this->user->id ?? null,
                'name'  => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ],
            'coupon'      => new CouponResource($this->whenLoaded('coupon')),
        ];
    }
}
