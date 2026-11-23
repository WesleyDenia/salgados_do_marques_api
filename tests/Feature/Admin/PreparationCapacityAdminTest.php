<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\OperationalPreparationSlot;
use App\Models\Product;
use App\Models\ProductPreparationSetting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparationCapacityAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_cuba_for_the_selected_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore('Loja Norte');

        $this->actingAs($admin)
            ->post(route('admin.preparation-capacity.slots.store'), [
                'store_id' => $store->id,
                'name' => 'Cuba salgados pequenos',
            ])
            ->assertRedirect(route('admin.preparation-capacity.index', ['store_id' => $store->id]));

        $this->assertDatabaseHas('operational_preparation_slots', [
            'store_id' => $store->id,
            'name' => 'Cuba salgados pequenos',
            'active' => true,
        ]);
    }

    public function test_panel_lists_only_the_cubas_from_the_selected_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $northStore = $this->makeStore('Loja Norte');
        $southStore = $this->makeStore('Loja Sul');

        OperationalPreparationSlot::create([
            'store_id' => $northStore->id,
            'name' => 'Cuba Norte',
            'active' => true,
            'display_order' => 0,
        ]);
        OperationalPreparationSlot::create([
            'store_id' => $southStore->id,
            'name' => 'Cuba Sul',
            'active' => true,
            'display_order' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.preparation-capacity.index', ['store_id' => $northStore->id]))
            ->assertOk()
            ->assertSee('Cuba Norte')
            ->assertDontSee('Cuba Sul');
    }

    public function test_admin_can_clone_a_cuba_with_its_product_times(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore('Loja Sul');
        $product = $this->makeProduct('Mini coxinha');
        $source = OperationalPreparationSlot::create([
            'store_id' => $store->id,
            'name' => 'Cuba 1',
            'active' => true,
            'display_order' => 0,
        ]);
        ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $source->id,
            'product_id' => $product->id,
            'batch_size' => 30,
            'preparation_time_seconds' => 210,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.preparation-capacity.slots.store'), [
                'store_id' => $store->id,
                'name' => 'Cuba 2',
                'clone_from_slot_id' => $source->id,
            ])
            ->assertRedirect();

        $clone = OperationalPreparationSlot::query()
            ->where('store_id', $store->id)
            ->where('name', 'Cuba 2')
            ->firstOrFail();

        $this->assertDatabaseHas('product_preparation_settings', [
            'operational_preparation_slot_id' => $clone->id,
            'product_id' => $product->id,
            'batch_size' => 30,
            'preparation_time_seconds' => 210,
        ]);
    }

    public function test_admin_can_save_a_frying_time_in_minutes_for_a_cuba(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore('Loja Centro');
        $product = $this->makeProduct('Rissol');
        $slot = OperationalPreparationSlot::create([
            'store_id' => $store->id,
            'name' => 'Cuba rissóis',
            'active' => true,
            'display_order' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.preparation-capacity.times.store'), [
                'store_id' => $store->id,
                'operational_preparation_slot_id' => $slot->id,
                'product_id' => $product->id,
                'batch_size' => 18,
                'preparation_minutes' => '3.5',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_preparation_settings', [
            'operational_preparation_slot_id' => $slot->id,
            'product_id' => $product->id,
            'batch_size' => 18,
            'preparation_time_seconds' => 210,
        ]);
    }

    private function makeProduct(string $name): Product
    {
        $category = Category::create([
            'name' => 'Salgados ' . $name,
            'description' => 'Categoria de teste',
            'active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'description' => 'Produto de teste',
            'price' => 2.5,
            'active' => true,
        ]);
    }

    private function makeStore(string $name): Store
    {
        return Store::create([
            'name' => $name,
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => false,
            'pickup_weekly_schedule' => [],
            'pickup_date_exceptions' => [],
        ]);
    }
}
