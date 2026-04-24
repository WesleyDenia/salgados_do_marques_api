<?php

namespace App\Services;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\Coupon;
use App\Models\ErpSyncTask;
use App\Models\LoyaltyReward;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserCoupon;
use App\Repositories\LoyaltyRepository;
use App\Repositories\LoyaltyRewardRepository;
use App\Repositories\UserCouponRepository;
use App\Services\ErpSyncTaskService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LoyaltyRewardService
{
    public function __construct(
        protected LoyaltyRewardRepository $repository,
        protected LoyaltyRepository $loyaltyRepository,
        protected UserCouponRepository $userCouponRepository,
        protected ErpSyncTaskService $erpSyncTaskService,
        protected AdminImageService $images,
    ) {}

    public function list(?User $user = null)
    {
        $rewards = $this->repository->allActive();

        if ($user && $rewards->isNotEmpty()) {
            $rewardIds = $rewards->pluck('id');

            $userCoupons = UserCoupon::with(['coupon', 'latestErpTask'])
                ->where('user_id', $user->id)
                ->whereIn('loyalty_reward_id', $rewardIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('loyalty_reward_id');

            foreach ($rewards as $reward) {
                $coupon = $userCoupons->get($reward->id)?->first(function (UserCoupon $candidate) {
                    return in_array($candidate->status ?? UserCoupon::STATUS_PENDING, UserCoupon::ACTIVE_REUSABLE_STATUSES, true)
                        && $candidate->active;
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

    public function listAdmin(): LengthAwarePaginator
    {
        return $this->repository->query()
            ->orderBy('threshold')
            ->paginate(15);
    }

    public function createAdmin(array $data, ?UploadedFile $image = null): LoyaltyReward
    {
        $payload = $this->normalizeAdminPayload($data);

        if ($image instanceof UploadedFile) {
            $payload['image_url'] = $this->images->store($image, 'loyalty-rewards');
        }

        return $this->create($payload);
    }

    public function updateAdmin(LoyaltyReward $reward, array $data, ?UploadedFile $image = null): LoyaltyReward
    {
        $payload = $this->normalizeAdminPayload($data);
        $payload['image_url'] = $this->images->replace(
            $reward->image_url,
            $image,
            'loyalty-rewards',
            (bool) ($data['remove_image'] ?? false)
        );

        return $this->update($reward, $payload);
    }

    public function deleteAdmin(LoyaltyReward $reward): void
    {
        $this->images->delete($reward->image_url);
        $this->repository->delete($reward);
    }

    public function redeem(User $user, LoyaltyReward $reward, int $quantity = 1): UserCoupon
    {
        if (!$reward->active) {
            abort(422, 'Esta recompensa está inativa.');
        }

        if ($reward->value === null || $reward->value <= 0) {
            abort(422, 'Configure um valor válido para esta recompensa.');
        }

        $quantity = max(1, $quantity);

        return DB::transaction(function () use ($user, $reward, $quantity) {
            $expiresAt = $this->resolveExpirationDate();
            $totalValue = $reward->value * $quantity;
            $originKey = 'loyalty_reward:' . $reward->id;

            $userCoupon = UserCoupon::query()
                ->with(['coupon', 'latestErpTask'])
                ->lockForUpdate()
                ->where('user_id', $user->id)
                ->where('origin_key', $originKey)
                ->first();

            if ($userCoupon && in_array($userCoupon->status, UserCoupon::ACTIVE_REUSABLE_STATUSES, true)) {
                return $userCoupon;
            }

            $account = $this->loyaltyRepository->getOrCreateAccount($user);
            $requiredPoints = $reward->threshold * $quantity;

            if ($account->points < $requiredPoints) {
                abort(422, 'Pontos insuficientes para resgatar esta recompensa.');
            }

            if ($userCoupon) {
                $coupon = $userCoupon->coupon;

                if (!$coupon) {
                    $coupon = new Coupon();
                }

                $coupon->fill([
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
                $coupon->save();

                $userCoupon->forceFill([
                    'coupon_id' => $coupon->id,
                    'expires_at' => $expiresAt,
                    'active' => true,
                    'status' => UserCoupon::STATUS_PENDING_ERP,
                    'external_id' => null,
                    'external_code' => null,
                    'erp_sync_error' => null,
                    'erp_synced_at' => null,
                    'erp_sync_attempts' => 0,
                    'redeem_applied_at' => null,
                    'redeem_transaction_id' => null,
                    'usage_limit' => 1,
                    'usage_count' => 0,
                ])->save();
            } else {
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
                    'type' => 'loyalty',
                    'loyalty_reward_id' => $reward->id,
                    'origin_key' => $originKey,
                    'usage_limit' => 1,
                    'usage_count' => 0,
                    'expires_at' => $expiresAt,
                    'active' => true,
                    'status' => UserCoupon::STATUS_PENDING_ERP,
                ]);
            }

            $this->erpSyncTaskService->createOrReuseActive(
                ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD,
                ErpSyncTask::ENTITY_USER_COUPON,
                $userCoupon->id,
                [
                    'status' => ErpSyncTask::STATUS_QUEUED,
                    'queued_at' => now(),
                ]
            );

            CreateVendusDiscountCardJob::dispatch($userCoupon->id)->afterCommit();

            return $userCoupon->fresh(['coupon', 'latestErpTask']);
        });
    }

    protected function generatePlaceholderCode(User $user): string
    {
        return strtoupper('LOY-' . Str::random(6) . '-' . $user->id);
    }

    protected function normalizeAdminPayload(array $data): array
    {
        unset($data['image'], $data['remove_image']);
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['value'] = (float) $data['value'];

        return $data;
    }

    protected function resolveExpirationDate(): ?Carbon
    {
        $value = Setting::query()
            ->where('key', 'LOYALTY_EXPIRATION')
            ->value('value');

        if ($value === null) {
            $value = Setting::query()
                ->where('key', 'LOYLATY_EXPIRATION')
                ->value('value');
        }

        $days = is_numeric($value) ? (int) $value : null;

        if ($days === null || $days <= 0) {
            return null;
        }

        return now()->addDays($days);
    }
}
