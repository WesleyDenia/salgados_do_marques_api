<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Partner;
use App\Models\PartnerCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudRefactorFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = Category::create(['name' => 'Primeira', 'display_order' => 1, 'active' => true]);
        $second = Category::create(['name' => 'Segunda', 'display_order' => 2, 'active' => true]);

        $response = $this->actingAs($admin)->postJson(route('admin.categories.reorder'), [
            'order' => [
                ['id' => $first->id, 'position' => 2],
                ['id' => $second->id, 'position' => 1],
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertSame(2, $first->fresh()->display_order);
        $this->assertSame(1, $second->fresh()->display_order);
    }

    public function test_admin_can_create_partner_with_image_and_partner_campaign(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::create([
            'title' => 'Cupom Parceiro',
            'body' => 'Body',
            'code' => 'COUPON-10',
            'recurrence' => 'none',
            'active' => true,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => false,
        ]);

        $partnerResponse = $this->actingAs($admin)->post(route('admin.partners.store'), [
            'name' => 'Parceiro XPTO',
            'slug' => 'parceiro-xpto',
            'description' => 'Descrição',
            'active' => '1',
            'image' => UploadedFile::fake()->create('partner.jpg', 64, 'image/jpeg'),
        ]);

        $partnerResponse->assertRedirect(route('admin.partners.index'));
        $partner = Partner::query()->firstOrFail();
        $this->assertStringStartsWith('/storage/partners/', (string) $partner->image_url);

        $campaignResponse = $this->actingAs($admin)->post(route('admin.partner-campaigns.store'), [
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'public_name' => 'Campanha Primavera',
            'code' => ' primavera-10 ',
            'active' => '1',
        ]);

        $campaignResponse->assertRedirect(route('admin.partner-campaigns.index'));
        $this->assertDatabaseHas('partner_campaigns', [
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'code' => 'PRIMAVERA-10',
            'active' => true,
        ]);
    }

    public function test_admin_can_update_existing_partner_campaign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::create([
            'title' => 'Cupom Parceiro',
            'body' => 'Body',
            'code' => 'COUPON-10',
            'recurrence' => 'none',
            'active' => true,
            'type' => 'money',
            'amount' => 10,
            'is_loyalty_reward' => false,
        ]);
        $partner = Partner::create([
            'name' => 'Parceiro',
            'slug' => 'parceiro',
            'description' => 'Descrição',
            'active' => true,
        ]);
        $campaign = PartnerCampaign::create([
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'public_name' => 'Campanha',
            'code' => 'BASE-10',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.partner-campaigns.update', $campaign), [
            'partner_id' => $partner->id,
            'coupon_id' => $coupon->id,
            'public_name' => 'Campanha Atualizada',
            'code' => 'nova-10',
        ]);

        $response->assertRedirect(route('admin.partner-campaigns.index'));
        $this->assertDatabaseHas('partner_campaigns', [
            'id' => $campaign->id,
            'public_name' => 'Campanha Atualizada',
            'code' => 'NOVA-10',
            'active' => false,
        ]);
    }
}
