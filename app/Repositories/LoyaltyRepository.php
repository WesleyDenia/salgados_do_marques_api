<?php

namespace App\Repositories;

use App\Models\{LoyaltyAccount, LoyaltyTransaction, LoyaltyReward, User};

class LoyaltyRepository
{
    public function getAccountByUser(User $user): ?LoyaltyAccount
    {
        return LoyaltyAccount::where('user_id', $user->id)->first();
    }

    public function createAccount(User $user, int $initialPoints = 0): LoyaltyAccount
    {
        return LoyaltyAccount::create([
            'user_id' => $user->id,
            'points'  => $initialPoints,
        ]);
    }

    public function getOrCreateAccount(User $user): LoyaltyAccount
    {
        return $this->getAccountByUser($user) ?? $this->createAccount($user);
    }

    public function updatePoints(LoyaltyAccount $account, int $points): LoyaltyAccount
    {
        $account->update(['points' => $points]);
        return $account;
    }

    public function createTransaction(array $data): LoyaltyTransaction
    {
        return LoyaltyTransaction::create($data);
    }

    public function getNextReward(int $currentPoints): ?LoyaltyReward
    {
        return LoyaltyReward::where('active', true)
            ->where('threshold', '>', $currentPoints)
            ->orderBy('threshold')
            ->first();
    }

    public function getTransactionsByUser(User $user, int $perPage = 20)
    {
        return LoyaltyTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
