<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_campaign_price_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Coxinha', 'price' => 1.2, 'active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.price-lists.store'), [
            'name' => 'Natal',
            'priority' => 10,
            'starts_at' => '2026-12-01 00:00:00',
            'ends_at' => '2026-12-31 23:59:59',
            'active' => '1',
            'entries' => [['product_id' => $product->id, 'amount' => 1]],
        ]);

        $response->assertRedirect(route('admin.price-lists.index'));
        $this->assertDatabaseHas('price_lists', ['name' => 'Natal', 'priority' => 10]);
        $this->assertDatabaseHas('price_entries', ['product_id' => $product->id, 'amount' => 1]);
    }

    public function test_admin_cannot_remove_the_normal_price_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $normal = PriceList::query()->where('is_default', true)->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('admin.price-lists.destroy', $normal));

        $response->assertRedirect(route('admin.price-lists.index'));
        $this->assertDatabaseHas('price_lists', ['id' => $normal->id]);
    }

    public function test_admin_cannot_create_overlapping_campaigns_with_same_priority_for_the_same_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Coxinha', 'price' => 1.2, 'active' => true]);
        $existing = PriceList::create([
            'name' => 'Campanha A',
            'priority' => 10,
            'active' => true,
            'starts_at' => '2026-12-01 00:00:00',
            'ends_at' => '2026-12-31 23:59:59',
        ]);
        $existing->entries()->create(['product_id' => $product->id, 'amount' => 1]);

        $response = $this->from(route('admin.price-lists.create'))->actingAs($admin)->post(route('admin.price-lists.store'), [
            'name' => 'Campanha B',
            'priority' => 10,
            'starts_at' => '2026-12-10 00:00:00',
            'ends_at' => '2026-12-20 23:59:59',
            'active' => '1',
            'entries' => [['product_id' => $product->id, 'amount' => 0.9]],
        ]);

        $response->assertRedirect(route('admin.price-lists.create'));
        $response->assertSessionHasErrors('entries');
    }
}
