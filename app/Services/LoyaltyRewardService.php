<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\LoyaltyReward;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\LoyaltyRepository;
use App\Repositories\LoyaltyRewardRepository;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoyaltyRewardService
{
    public function __construct(
        protected LoyaltyRewardRepository $repository,
        protected LoyaltyRepository $loyaltyRepository,
        protected UserCouponRepository $userCouponRepository,
        protected VendusCouponSyncService $vendusCouponSyncService,
    ) {}

    public function list(?User $user = null)
    {
        $rewards = $this->repository->allActive();

        if ($user && $rewards->isNotEmpty()) {
            $rewardIds = $rewards->pluck('id');

            $userCoupons = UserCoupon::with('coupon')
                ->where('user_id', $user->id)
                ->whereIn('loyalty_reward_id', $rewardIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('loyalty_reward_id');

            foreach ($rewards as $reward) {
                $coupon = $userCoupons->get($reward->id)?->first(function (UserCoupon $candidate) {
                    $status = $candidate->status ?? 'pending';
                    return $status !== 'done' && $candidate->active;
                });

                if ($coupon) {
                    $reward->setRelation('userCoupon', $coupon);
                } else {
                    $reward->setRelation('userCoupon', null);
                }
            }
        }

        return $rewards;
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(LoyaltyReward $reward, array $data)
    {
        return $this->repository->update($reward, $data);
    }

    public function redeem(User $user, LoyaltyReward $reward, int $quantity = 1): UserCoupon
    {
        if (!$reward->active) {
            abort(422, 'Esta recompensa está inativa.');
        }

        if ($reward->value === null || $reward->value <= 0) {
            abort(422, 'Configure um valor válido para esta recompensa.');
        }

        $existing = UserCoupon::with('coupon')
            ->where('user_id', $user->id)
            ->where('loyalty_reward_id', $reward->id)
            ->where('type', 'loyalty')
            ->first();

        if ($existing && $existing->status !== 'done') {
            return $existing;
        }

        $quantity = max(1, $quantity);

        return DB::transaction(function () use ($user, $reward, $quantity) {
            $account = $this->loyaltyRepository->getOrCreateAccount($user);
            $requiredPoints = $reward->threshold * $quantity;

            if ($account->points < $requiredPoints) {
                abort(422, 'Pontos insuficientes para resgatar esta recompensa.');
            }

            $expiresAt = $this->resolveExpirationDate();
            $totalValue = $reward->value * $quantity;

            $coupon = Coupon::create([
                'title' => sprintf('Recompensa: %s', $reward->name),
                'body' => $reward->description ?? 'Cupom gerado via programa de fidelidade.',
                'code' => $this->generatePlaceholderCode($user),
                'image_url' => $reward->image_url,
                'recurrence' => 'none',
                'starts_at' => now(),
                'ends_at' => $expiresAt,
                'active' => true,
                'type' => 'money',
                'amount' => $totalValue,
                'is_loyalty_reward' => true,
            ]);

            $userCoupon = UserCoupon::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'loyalty_reward_id' => $reward->id,
                'type' => 'loyalty',
                'usage_limit' => 1,
                'usage_count' => 0,
                'expires_at' => $expiresAt,
                'active' => true,
                'status' => 'pending',
            ]);

            $newPoints = $account->points - $requiredPoints;
            $this->loyaltyRepository->updatePoints($account, $newPoints);

            $this->loyaltyRepository->createTransaction([
                'user_id' => $user->id,
                'type' => 'redeem',
                'points' => -$requiredPoints,
                'reason' => sprintf('Resgate da recompensa "%s"', $reward->name),
                'meta' => [
                    'loyalty_reward_id' => $reward->id,
                    'coupon_id' => $coupon->id,
                    'value' => $totalValue,
                    'quantity' => $quantity,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            ]);

            $userCoupon->setRelation('coupon', $coupon);

            $response = $this->vendusCouponSyncService->create($userCoupon);

            if (!$response || empty($response['external_code'])) {
                throw ValidationException::withMessages([
                    'reward' => 'Não foi possível gerar o cupom no Vendus.',
                ]);
            }

            $userCoupon = $this->userCouponRepository->syncFromErp($userCoupon, $response);

            if (!empty($response['external_code'])) {
                $coupon->update(['code' => $response['external_code']]);
            }

            return $userCoupon->fresh('coupon');
        });
    }

    protected function generatePlaceholderCode(User $user): string
    {
        return strtoupper('LOY-' . Str::random(6) . '-' . $user->id);
    }

    protected function resolveExpirationDate(): ?Carbon
    {
        $value = Setting::query()
            ->where('key', 'LOYLATY_EXPIRATION')
            ->value('value');

        $days = is_numeric($value) ? (int) $value : null;

        if ($days === null || $days <= 0) {
            return null;
        }

        return now()->addDays($days);
    }
}
