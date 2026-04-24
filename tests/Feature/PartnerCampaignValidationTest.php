<?php

namespace Tests\Feature;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\Coupon;
use App\Models\Partner;
use App\Models\PartnerCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerCampaignValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_validate_partner_code(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $campaign = $this->createCampaign();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/partner-campaigns/validate', [
            'code' => 'parceiro-10',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'partner')
            ->assertJsonPath('data.partner_campaign_id', $campaign->id)
            ->assertJsonPath('data.status', 'pending_erp')
            ->assertJsonPath('data.origin.type', 'partner')
            ->assertJsonPath('data.origin.partner.name', 'Parceiro Teste')
            ->assertJsonPath('data.coupon.title', 'Cupom Parceiro');
        Queue::assertPushed(CreateVendusDiscountCardJob::class, 1);
    }

    public function test_validation_endpoint_is_idempotent_while_coupon_is_pending(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $campaign = $this->createCampaign();
        Sanctum::actingAs($user);

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
        Queue::assertPushed(CreateVendusDiscountCardJob::class, 1);
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
