<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class LoyaltySummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        $rewards = $this['rewards'] instanceof Collection
            ? $this['rewards']
            : collect($this['rewards']);

        $rewardsData = LoyaltyRewardResource::collection($rewards)->toArray($request);
        $milestones = $rewards->pluck('threshold')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'points' => (int) ($this['points'] ?? 0),
            'nextRewardAt' => $this['next_reward_at'] ?? null,
            'rewards' => $rewardsData,
            'milestones' => $milestones,
        ];
    }
}
