<?php

namespace Tests\Unit;

use App\Jobs\CreateVendusDiscountCardJob;
use App\Models\Coupon;
use App\Models\Partner;
use App\Models\PartnerCampaign;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\PartnerCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartnerCampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_code_creates_partner_coupon_and_is_idempotent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $campaign = $this->createCampaign();

        /** @var PartnerCampaignService $service */
        $service = $this->app->make(PartnerCampaignService::class);

        $first = $service->validateCode($user, '  parceiro-10  ');
        $second = $service->validateCode($user, 'PARCEIRO-10');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('partner', $first->type);
        $this->assertSame(UserCoupon::STATUS_PENDING_ERP, $first->status);
        $this->assertNull($first->external_code);
        $this->assertDatabaseHas('user_coupons', [
            'user_id' => $user->id,
            'partner_campaign_id' => $campaign->id,
            'type' => 'partner',
            'status' => UserCoupon::STATUS_PENDING_ERP,
        ]);
        $this->assertDatabaseHas('erp_sync_tasks', [
            'operation' => 'create_discount_card',
            'entity_type' => 'user_coupon',
            'entity_id' => $first->id,
            'status' => 'queued',
        ]);
        Queue::assertPushed(CreateVendusDiscountCardJob::class, 1);
    }

    public function test_validate_code_rejects_inactive_partner(): void
    {
        $user = User::factory()->create();
        $campaign = $this->createCampaign();
        $campaign->partner()->update(['active' => false]);

        $this->expectException(ValidationException::class);

        $service = $this->app->make(PartnerCampaignService::class);
        $service->validateCode($user, 'PARCEIRO-10');
    }

    public function test_validate_code_rejects_expired_campaign(): void
    {
        $user = User::factory()->create();
        $this->createCampaign([
            'starts_at' => now()->subDays(4),
            'ends_at' => now()->subDay(),
        ]);

        $this->expectException(ValidationException::class);

        $service = $this->app->make(PartnerCampaignService::class);
        $service->validateCode($user, 'PARCEIRO-10');
    }

    protected function createCampaign(array $campaignOverrides = []): PartnerCampaign
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

        return PartnerCampaign::create(array_merge([
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'public_name' => 'Campanha Teste',
            'code' => 'PARCEIRO-10',
            'active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ], $campaignOverrides));
    }
}
