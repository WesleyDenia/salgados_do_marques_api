<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\LoyaltyRepository;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    public function __construct(
        protected LoyaltyRepository $repository
    ) {}    

    public function grantWelcomeBonus(User $user): int
    {
        if ($user->loyalty_synced) {
            return 0;
        }

        $bonus = Setting::where('key', 'welcome_bonus_points')->value('value');
        $bonusPoints = $bonus ? (int) $bonus : 100;

        DB::transaction(function () use ($user, $bonusPoints) {
            $account = $this->repository->getOrCreateAccount($user);
            $this->repository->updatePoints($account, $account->points + $bonusPoints);

            $this->repository->createTransaction([
                'user_id' => $user->id,
                'type' => 'earn',
                'points' => $bonusPoints,
                'reason' => 'Bônus de boas-vindas',
                'meta' => ['source' => 'welcome_bonus'],
            ]);

            $user->update([
                'loyalty_synced' => true,
                'loyalty_synced_at' => now(),
            ]);
        });

        return $bonusPoints;
    }



    public function getStatus(User $user): array
    {
        $account = $this->repository->getOrCreateAccount($user);
        $next = $this->repository->getNextReward($account->points);

        return [
            'points' => $account->points,
            'next_reward_at' => $next?->threshold,
        ];
    }

    public function earnPoints(User $user, int $points, ?string $reason = null, array $meta = []): void
    {
        DB::transaction(function () use ($user, $points, $reason, $meta) {
            $account = $this->repository->getOrCreateAccount($user);
            $newPoints = $account->points + max(0, $points);
            $this->repository->updatePoints($account, $newPoints);

            $this->repository->createTransaction([
                'user_id' => $user->id,
                'type' => 'earn',
                'points' => $points,
                'reason' => $reason,
                'meta' => $meta,
            ]);
        });
    }

    public function redeemPoints(User $user, int $points, ?string $reason = null, array $meta = []): void
    {
        DB::transaction(function () use ($user, $points, $reason, $meta) {
            $account = $this->repository->getOrCreateAccount($user);

            if ($points > $account->points) {
                abort(422, 'Pontos insuficientes.');
            }

            $newPoints = $account->points - $points;
            $this->repository->updatePoints($account, $newPoints);

            $this->repository->createTransaction([
                'user_id' => $user->id,
                'type' => 'redeem',
                'points' => -$points,
                'reason' => $reason,
                'meta' => $meta,
            ]);
        });
    }

    public function getTransactions(User $user)
    {
        return $this->repository->getTransactionsByUser($user);
    }

    public function listTransactions(User $user, int $perPage = 20)
    {
        return $this->repository->getTransactionsByUser($user, $perPage);
    }
}
