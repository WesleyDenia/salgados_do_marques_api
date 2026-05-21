<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminUpdateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_an_existing_order_for_correction(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();
        $replacementStore = $this->createStore([
            'name' => 'Loja Norte',
            'default_store' => false,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => 'Sem picante',
        ]);

        $category = Category::create([
            'name' => 'Salgados',
            'description' => 'Categoria de teste',
            'active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Coxinha',
            'price' => 2.50,
            'active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}", [
            'customer_name' => 'Maria Santos',
            'customer_contact' => '919111222',
            'store_id' => $replacementStore->id,
            'scheduled_at' => '2026-07-16T13:00:00+01:00',
            'payment_status' => 'paid',
            'slot' => 'tarde',
            'notes' => 'Corrigir morada',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.customer_name', 'Maria Santos')
            ->assertJsonPath('data.customer_contact', '919111222')
            ->assertJsonPath('data.store.id', $replacementStore->id)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.slot', 'tarde')
            ->assertJsonPath('data.notes', 'Corrigir morada')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 4);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Maria Santos',
            'customer_contact' => '919111222',
            'store_id' => $replacementStore->id,
            'payment_status' => 'paid',
            'slot' => 'tarde',
            'notes' => 'Corrigir morada',
        ]);
    }

    public function test_admin_cannot_update_an_order_after_the_operational_edit_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:31:00', 'Europe/Lisbon'));
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->createStore();
        $category = Category::create([
            'name' => 'Salgados',
            'description' => 'Categoria de teste',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Coxinha',
            'price' => 2.50,
            'active' => true,
        ]);
        $order = Order::create([
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => Carbon::create(2026, 7, 15, 13, 0, 0, 'Europe/Lisbon')->timezone('UTC'),
            'total' => 12.50,
            'notes' => 'Sem picante',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}", [
            'customer_name' => 'Maria Santos',
            'customer_contact' => '919111222',
            'store_id' => $store->id,
            'scheduled_at' => '2026-07-15T13:00:00+01:00',
            'payment_status' => 'paid',
            'slot' => 'tarde',
            'notes' => 'Corrigir morada',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_order_resource_marks_orders_outside_the_window_as_not_editable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:31:00', 'Europe/Lisbon'));
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->createStore();
        $order = Order::create([
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'accepted',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => Carbon::create(2026, 7, 15, 13, 0, 0, 'Europe/Lisbon')->timezone('UTC'),
            'total' => 12.50,
            'notes' => 'Sem picante',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()->assertJsonPath('data.can_edit', false);
    }

    protected function setEditWindow(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'order_cancel_minutes'],
            ['value' => 60, 'type' => 'integer', 'editable' => true],
        );
        Setting::query()->updateOrCreate(
            ['key' => 'order_timezone'],
            ['value' => 'Europe/Lisbon', 'type' => 'string', 'editable' => true],
        );
    }

    protected function createStore(array $overrides = []): Store
    {
        return Store::create(array_merge([
            'name' => 'Loja Centro',
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'tuesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'thursday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'friday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'saturday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'sunday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
            'pickup_date_exceptions' => [],
        ], $overrides));
    }
}
