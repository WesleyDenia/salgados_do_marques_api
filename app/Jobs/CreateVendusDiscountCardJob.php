<?php

namespace App\Jobs;

use App\Models\Coupon;
use App\Models\ErpSyncTask;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\UserCoupon;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use App\Services\ErpSyncTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CreateVendusDiscountCardJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public int $userCouponId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->userCouponId;
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(
        VendusCouponSyncService $vendus,
        ErpSyncTaskService $tasks,
        UserCouponRepository $userCoupons
    ): void {
        $userCoupon = UserCoupon::query()
            ->with(['coupon', 'loyaltyReward', 'partnerCampaign', 'user'])
            ->find($this->userCouponId);

        if (!$userCoupon) {
            return;
        }

        if (in_array($userCoupon->status, [UserCoupon::STATUS_DONE, UserCoupon::STATUS_CANCELLED], true)) {
            return;
        }

        $task = $tasks->createOrReuseActive(
            ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD,
            ErpSyncTask::ENTITY_USER_COUPON,
            $userCoupon->id,
            [
                'status' => ErpSyncTask::STATUS_QUEUED,
                'external_id' => $userCoupon->external_id,
                'external_code' => $userCoupon->external_code,
                'queued_at' => now(),
            ]
        );

        if (filled($userCoupon->external_code)) {
            $userCoupon = $userCoupons->syncFromErp($userCoupon, [
                'external_id' => $userCoupon->external_id,
                'external_code' => $userCoupon->external_code,
                'status' => UserCoupon::STATUS_SYNCED,
            ]);

            $this->applyLoyaltyRedeemIfNeeded($userCoupon);
            $tasks->markSynced($task, [
                'external_id' => $userCoupon->external_id,
                'external_code' => $userCoupon->external_code,
            ]);

            return;
        }

        $task = $tasks->markProcessing($task);
        $userCoupon = $userCoupons->markErpSyncing($userCoupon);

        try {
            $response = $vendus->create($userCoupon);

            if (!$response || empty($response['external_code'])) {
                throw new RuntimeException('Nao foi possivel criar o cupom no Vendus.');
            }

            $userCoupon = $userCoupons->syncFromErp($userCoupon, [
                ...$response,
                'status' => UserCoupon::STATUS_SYNCED,
            ]);

            if ($userCoupon->coupon instanceof Coupon) {
                $userCoupon->coupon->update(['code' => $userCoupon->external_code]);
            }

            $this->applyLoyaltyRedeemIfNeeded($userCoupon);

            $tasks->markSynced($task, [
                'external_id' => $userCoupon->external_id,
                'external_code' => $userCoupon->external_code,
            ]);
        } catch (Throwable $exception) {
            $userCoupons->markErpFailed($userCoupon, $exception->getMessage());
            $tasks->markFailed($task, $exception->getMessage());

            throw $exception;
        }
    }

    protected function applyLoyaltyRedeemIfNeeded(UserCoupon $userCoupon): void
    {
        if ($userCoupon->type !== 'loyalty' || !$userCoupon->loyaltyReward) {
            return;
        }

        DB::transaction(function () use ($userCoupon): void {
            $lockedCoupon = UserCoupon::query()
                ->with('loyaltyReward')
                ->lockForUpdate()
                ->find($userCoupon->id);

            if (!$lockedCoupon || $lockedCoupon->redeem_applied_at || !$lockedCoupon->external_code) {
                return;
            }

            /** @var LoyaltyAccount $account */
            $account = LoyaltyAccount::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['user_id' => $lockedCoupon->user_id],
                    ['points' => 0]
                );

            $reward = $lockedCoupon->loyaltyReward;
            $pointsToDebit = $this->resolveRewardPointsToDebit($lockedCoupon);

            if ($account->points < $pointsToDebit) {
                throw new RuntimeException('Saldo insuficiente para aplicar o debito final da recompensa.');
            }

            $account->update([
                'points' => $account->points - $pointsToDebit,
            ]);

            $transaction = LoyaltyTransaction::create([
                'user_id' => $lockedCoupon->user_id,
                'type' => 'redeem',
                'points' => -$pointsToDebit,
                'reason' => sprintf('Resgate da recompensa "%s"', $reward->name),
                'meta' => [
                    'loyalty_reward_id' => $reward->id,
                    'user_coupon_id' => $lockedCoupon->id,
                    'coupon_id' => $lockedCoupon->coupon_id,
                    'external_code' => $lockedCoupon->external_code,
                ],
            ]);

            $lockedCoupon->forceFill([
                'redeem_applied_at' => now(),
                'redeem_transaction_id' => $transaction->id,
            ])->save();
        });
    }

    protected function resolveRewardPointsToDebit(UserCoupon $userCoupon): int
    {
        $reward = $userCoupon->loyaltyReward;

        if (!$reward || $reward->threshold <= 0) {
            throw new RuntimeException('Recompensa sem threshold valido para debito.');
        }

        $rewardValue = (float) ($reward->value ?? 0);
        $couponAmount = (float) ($userCoupon->coupon?->amount ?? 0);

        if ($rewardValue <= 0 || $couponAmount <= 0) {
            return (int) $reward->threshold;
        }

        $quantity = max(1, (int) round($couponAmount / $rewardValue));

        return (int) $reward->threshold * $quantity;
    }
}
