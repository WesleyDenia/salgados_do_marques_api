<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Partner;
use App\Models\PartnerCampaign;
use App\Models\User;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerCampaignValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_validate_partner_code(): void
    {
        $user = User::factory()->create();
        $campaign = $this->createCampaign();
        Sanctum::actingAs($user);

        $vendus = \Mockery::mock(VendusCouponSyncService::class);
        $vendus->shouldReceive('create')
            ->once()
            ->andReturn([
                'external_id' => 'vendus-1',
                'external_code' => 'CODE-1',
                'status' => 'pending',
            ]);
        $this->app->instance(VendusCouponSyncService::class, $vendus);

        $response = $this->postJson('/api/v1/partner-campaigns/validate', [
            'code' => 'parceiro-10',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'partner')
            ->assertJsonPath('data.partner_campaign_id', $campaign->id)
            ->assertJsonPath('data.origin.type', 'partner')
            ->assertJsonPath('data.origin.partner.name', 'Parceiro Teste')
            ->assertJsonPath('data.coupon.title', 'Cupom Parceiro');
    }

    public function test_validation_endpoint_is_idempotent_while_coupon_is_pending(): void
    {
        $user = User::factory()->create();
        $campaign = $this->createCampaign();
        Sanctum::actingAs($user);

        $vendus = \Mockery::mock(VendusCouponSyncService::class);
        $vendus->shouldReceive('create')
            ->once()
            ->andReturn([
                'external_id' => 'vendus-1',
                'external_code' => 'CODE-1',
                'status' => 'pending',
            ]);
        $this->app->instance(VendusCouponSyncService::class, $vendus);

        $first = $this->postJson('/api/v1/partner-campaigns/validate', ['code' => 'PARCEIRO-10']);
        $second = $this->postJson('/api/v1/partner-campaigns/validate', ['code' => 'PARCEIRO-10']);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id')
        );
        $this->assertDatabaseHas('user_coupons', [
            'user_id' => $user->id,
            'partner_campaign_id' => $campaign->id,
        ]);
    }

    protected function createCampaign(): PartnerCampaign
    {
        $coupon = Coupon::create([
            'title' => 'Cupom Parceiro',
            'body' => 'Use em loja física.',
            'code' => 'BASE-PARCEIRO-10',
            'recurrence' => 'none',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'active' => true,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => false,
        ]);

        $partner = Partner::create([
            'name' => 'Parceiro Teste',
            'slug' => 'parceiro-teste',
            'description' => 'Descrição do parceiro',
            'active' => true,
        ]);

        return PartnerCampaign::create([
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'public_name' => 'Campanha Teste',
            'code' => 'PARCEIRO-10',
            'active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);
    }
}
