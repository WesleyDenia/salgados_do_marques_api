<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\User;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class UserCouponFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_my_coupons_store_delegates_sync_and_preserves_resource_contract(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $coupon = Coupon::create([
            'title' => 'Cupom teste',
            'body' => 'Desconto',
            'code' => 'WELCOME10',
            'recurrence' => 'none',
            'active' => true,
            'type' => 'money',
            'amount' => 10,
        ]);

        $sync = Mockery::mock(VendusCouponSyncService::class);
        $sync->shouldReceive('create')
            ->once()
            ->andReturn([
                'external_id' => 'erp-123',
                'external_code' => 'ERP-123',
                'status' => 'pending',
            ]);

        $this->app->instance(VendusCouponSyncService::class, $sync);

        $response = $this->postJson('/api/v1/my-coupons', [
            'coupon_id' => $coupon->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('coupon.id', $coupon->id)
            ->assertJsonPath('external_id', 'erp-123')
            ->assertJsonPath('external_code', 'ERP-123')
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('user_coupons', [
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'external_id' => 'erp-123',
            'external_code' => 'ERP-123',
        ]);
    }

    public function test_my_coupons_store_uses_form_request_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/my-coupons', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['coupon_id']);
    }
}
