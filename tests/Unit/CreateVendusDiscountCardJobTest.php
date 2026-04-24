<?php

namespace Tests\Unit;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\Coupon;
use App\Models\ErpSyncTask;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateVendusDiscountCardJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_does_not_call_vendus_again_when_coupon_already_has_external_code(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'title' => 'Cupom',
            'body' => 'Descricao',
            'code' => 'BASE-1',
            'recurrence' => 'none',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'active' => true,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => false,
        ]);

        $userCoupon = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'type' => 'partner',
            'origin_key' => 'partner_campaign:1',
            'active' => true,
            'status' => UserCoupon::STATUS_FAILED_ERP,
            'external_id' => 'vd-1',
            'external_code' => 'ERP-READY',
        ]);

        $vendus = \Mockery::mock(VendusCouponSyncService::class);
        $vendus->shouldNotReceive('create');
        $this->app->instance(VendusCouponSyncService::class, $vendus);

        $job = new CreateVendusDiscountCardJob($userCoupon->id);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseHas('user_coupons', [
            'id' => $userCoupon->id,
            'status' => UserCoupon::STATUS_SYNCED,
            'external_code' => 'ERP-READY',
        ]);
        $this->assertDatabaseHas('erp_sync_tasks', [
            'operation' => ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD,
            'entity_type' => ErpSyncTask::ENTITY_USER_COUPON,
            'entity_id' => $userCoupon->id,
            'status' => ErpSyncTask::STATUS_SYNCED,
        ]);
    }
}
