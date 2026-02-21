<?php

namespace App\Repositories;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\User;

class DashboardRepository
{
    public function countUsers(): int
    {
        return User::query()->count();
    }

    public function sumGeneratedCoins(): int
    {
        return (int) LoyaltyTransaction::query()
            ->where('points', '>', 0)
            ->sum('points');
    }

    public function sumUsedCoinsRaw(): int
    {
        return (int) LoyaltyTransaction::query()
            ->where('points', '<', 0)
            ->sum('points');
    }

    public function sumAvailableCoins(): int
    {
        return (int) LoyaltyAccount::query()->sum('points');
    }
}
