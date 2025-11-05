<?php

// app/Http/Resources/LoyaltyStatusResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyStatusResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'points'         => (int) $this['points'],
            'nextRewardAt'   => $this['next_reward_at'], // pode ser null
        ];
    }
}
