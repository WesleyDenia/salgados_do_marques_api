<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(protected DashboardRepository $repository) {}

    public function metrics(): array
    {
        $usedCoinsRaw = $this->repository->sumUsedCoinsRaw();

        return [
            'users_count' => $this->repository->countUsers(),
            'coins_generated_total' => $this->repository->sumGeneratedCoins(),
            'coins_used_total' => abs($usedCoinsRaw),
            'coins_used_raw' => $usedCoinsRaw,
            'coins_available_total' => $this->repository->sumAvailableCoins(),
        ];
    }
}
