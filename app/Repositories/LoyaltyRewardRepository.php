<?php

namespace App\Repositories;

use App\Models\LoyaltyReward;

class LoyaltyRewardRepository extends BaseRepository
{
    public function __construct(LoyaltyReward $model)
    {
        parent::__construct($model);
    }

    public function allActive()
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('threshold')
            ->get();
    }
}

