<?php

namespace Tests\Unit;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\Coupon;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyReward;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\LoyaltyRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoyaltyRewardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeem_creates_pending_coupon_and_queues_vendus_job_without_debiting_points(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LoyaltyAccount::create([
            'user_id' => $user->id,
            'points' => 120,
        ]);
        $reward = LoyaltyReward::create([
            'name' => 'Recompensa Teste',
            'description' => 'Descricao',
            'threshold' => 30,
            'value' => 10,
            'active' => true,
        ]);

        /** @var LoyaltyRewardService $service */
        $service = $this->app->make(LoyaltyRewardService::class);
        $userCoupon = $service->redeem($user, $reward, 2);

        $this->assertSame(UserCoupon::STATUS_PENDING_ERP, $userCoupon->status);
        $this->assertNull($userCoupon->external_code);
        $this->assertSame(120, LoyaltyAccount::query()->where('user_id', $user->id)->value('points'));
        $this->assertDatabaseCount('loyalty_transactions', 0);
        $this->assertDatabaseHas('erp_sync_tasks', [
            'operation' => 'create_discount_card',
            'entity_type' => 'user_coupon',
            'entity_id' => $userCoupon->id,
            'status' => 'queued',
        ]);
        Queue::assertPushed(CreateVendusDiscountCardJob::class, fn (CreateVendusDiscountCardJob $job) => $job->userCouponId === $userCoupon->id);
    }

    public function test_redeem_returns_existing_active_coupon_for_same_reward(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Recompensa Teste',
            'description' => 'Descricao',
            'threshold' => 30,
            'value' => 10,
            'active' => true,
        ]);
        $coupon = Coupon::create([
            'title' => 'Cupom fidelidade',
            'body' => 'Body',
            'code' => 'INT-0001',
            'recurrence' => 'none',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'active' => true,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => true,
        ]);
        $existing = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'type' => 'loyalty',
            'loyalty_reward_id' => $reward->id,
            'origin_key' => 'loyalty_reward:' . $reward->id,
            'active' => true,
            'status' => UserCoupon::STATUS_PENDING_ERP,
        ]);

        /** @var LoyaltyRewardService $service */
        $service = $this->app->make(LoyaltyRewardService::class);
        $resolved = $service->redeem($user, $reward);

        $this->assertSame($existing->id, $resolved->id);
        Queue::assertNothingPushed();
    }

    public function test_redeem_reuses_cancelled_coupon_record_without_creating_orphan_coupon(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LoyaltyAccount::create([
            'user_id' => $user->id,
            'points' => 120,
        ]);
        $reward = LoyaltyReward::create([
            'name' => 'Recompensa Teste',
            'description' => 'Descricao',
            'threshold' => 30,
            'value' => 10,
            'active' => true,
        ]);
        $coupon = Coupon::create([
            'title' => 'Cupom antigo',
            'body' => 'Body',
            'code' => 'OLD-CODE',
            'recurrence' => 'none',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'active' => false,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => true,
        ]);
        $existing = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'type' => 'loyalty',
            'loyalty_reward_id' => $reward->id,
            'origin_key' => 'loyalty_reward:' . $reward->id,
            'active' => false,
            'status' => UserCoupon::STATUS_CANCELLED,
            'external_id' => 'old-id',
            'external_code' => 'OLD-ERP',
        ]);

        /** @var LoyaltyRewardService $service */
        $service = $this->app->make(LoyaltyRewardService::class);
        $recycled = $service->redeem($user, $reward, 2);

        $this->assertSame($existing->id, $recycled->id);
        $this->assertSame($coupon->id, $recycled->coupon_id);
        $this->assertSame(UserCoupon::STATUS_PENDING_ERP, $recycled->status);
        $this->assertNull($recycled->external_code);
        $this->assertSame(1, Coupon::query()->count());
        Queue::assertPushed(CreateVendusDiscountCardJob::class, 1);
    }
}
