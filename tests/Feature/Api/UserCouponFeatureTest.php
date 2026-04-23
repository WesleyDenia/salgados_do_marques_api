<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_vendus_coupon_sync_marks_done_discountcard_as_used(): void
    {
        config([
            'services.vendus.base_url' => 'https://vendus.test/ws/v1.1',
            'services.vendus.token' => 'test-token',
        ]);

        $user = User::factory()->create();
        $coupon = Coupon::create([
            'title' => 'Cupom teste',
            'body' => 'Desconto',
            'code' => 'LOCAL-1',
            'recurrence' => 'none',
            'active' => true,
            'type' => 'money',
            'amount' => 10,
        ]);

        $userCoupon = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'type' => 'regular',
            'external_id' => '123',
            'external_code' => 'ERP-123',
            'usage_limit' => 1,
            'usage_count' => 0,
            'active' => true,
            'status' => 'pending',
        ]);

        Http::fake([
            'vendus.test/ws/v1.1/discountcards/*' => Http::response([
                'discountcards' => [
                    [
                        'id' => 123,
                        'code' => 'ERP-123',
                        'status' => 'done',
                        'date_used' => '2026-04-23',
                    ],
                ],
            ], 200),
        ]);

        app(VendusCouponSyncService::class)->syncUsedCoupons();

        $userCoupon->refresh();

        $this->assertSame('done', $userCoupon->status);
        $this->assertFalse($userCoupon->active);

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://vendus.test/ws/v1.1/discountcards/'
        );
    }

    public function test_vendus_coupon_sync_can_match_used_discountcard_by_external_id(): void
    {
        config([
            'services.vendus.base_url' => 'https://vendus.test/ws/v1.1',
            'services.vendus.token' => 'test-token',
        ]);

        $user = User::factory()->create();
        $coupon = Coupon::create([
            'title' => 'Cupom teste',
            'body' => 'Desconto',
            'code' => 'LOCAL-2',
            'recurrence' => 'none',
            'active' => true,
            'type' => 'money',
            'amount' => 10,
        ]);

        $userCoupon = UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'type' => 'regular',
            'external_id' => '456',
            'external_code' => 'OLD-CODE',
            'usage_limit' => 1,
            'usage_count' => 0,
            'active' => true,
            'status' => 'pending',
        ]);

        Http::fake([
            'vendus.test/ws/v1.1/discountcards/*' => Http::response([
                'discountcards' => [
                    [
                        'id' => 456,
                        'code' => 'ERP-456',
                        'status' => 'done',
                    ],
                ],
            ], 200),
        ]);

        app(VendusCouponSyncService::class)->syncUsedCoupons();

        $userCoupon->refresh();

        $this->assertSame('done', $userCoupon->status);
        $this->assertFalse($userCoupon->active);
    }
}
