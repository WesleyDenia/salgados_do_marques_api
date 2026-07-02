<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPartialWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_register_a_partial_withdrawal_and_generate_a_child_order(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        [$order, $parentItem, $store] = $this->makeParentOrder(quantity: 100);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/admin/orders/{$order->id}/partial-withdrawals",
            [
                'parent_order_item_id' => $parentItem->id,
                'requested_units' => 25,
                'scheduled_at' => '2026-07-20T18:00:00+01:00',
                'generate_child_order' => true,
                'notes' => 'Cliente confirmou hoje de manhã.',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.withdrawal.requested_units', 25)
            ->assertJsonPath('data.withdrawal.generated_order_id', 2)
            ->assertJsonPath('data.generated_order.parent_order_id', $order->id)
            ->assertJsonPath('data.generated_order.customer_name', $order->customer_name)
            ->assertJsonPath('data.generated_order.store.id', $store->id)
            ->assertJsonPath('data.generated_order.items.0.parent_order_item_id', $parentItem->id)
            ->assertJsonPath('data.generated_order.items.0.quantity', 25)
            ->assertJsonPath('data.parent_order.partial_withdrawals.0.requested_units', 25);

        $this->assertStringContainsString(
            'Saldo restante na encomenda mãe: 75 unidades.',
            (string) data_get($response->json(), 'data.generated_order.notes')
        );

        $this->assertDatabaseHas('orders', [
            'id' => 2,
            'parent_order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'slot' => 'noite',
        ]);

        $this->assertDatabaseHas('order_partial_withdrawals', [
            'parent_order_id' => $order->id,
            'parent_order_item_id' => $parentItem->id,
            'generated_order_id' => 2,
            'requested_units' => 25,
            'status' => 'planned',
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'partial_withdrawal_planned',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_register_partial_withdrawals_above_remaining_balance(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        [$order, $parentItem] = $this->makeParentOrder(quantity: 100);

        $order->partialWithdrawals()->create([
            'parent_order_item_id' => $parentItem->id,
            'requested_units' => 75,
            'scheduled_at' => Carbon::create(2026, 7, 19, 18, 0, 0, 'UTC'),
            'status' => 'planned',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/admin/orders/{$order->id}/partial-withdrawals",
            [
                'parent_order_item_id' => $parentItem->id,
                'requested_units' => 50,
                'scheduled_at' => '2026-07-20T18:00:00+01:00',
                'generate_child_order' => false,
            ],
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['requested_units']);
    }

    public function test_admin_can_register_partial_withdrawals_with_selected_flavors(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        [$order, $parentItem, $store, $flavors] = $this->makePackParentOrder();

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/admin/orders/{$order->id}/partial-withdrawals",
            [
                'parent_order_item_id' => $parentItem->id,
                'requested_units' => 25,
                'flavor_ids' => [$flavors['frango']->id],
                'scheduled_at' => '2026-07-20T18:00:00+01:00',
                'generate_child_order' => true,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.withdrawal.flavor_ids.0', $flavors['frango']->id)
            ->assertJsonPath('data.withdrawal.flavor_names.0', 'Frango')
            ->assertJsonPath('data.generated_order.items.0.options.flavors.0', $flavors['frango']->id)
            ->assertJsonPath('data.parent_order.partial_withdrawals.0.flavor_names.0', 'Frango');

        $this->assertDatabaseHas('order_partial_withdrawals', [
            'parent_order_id' => $order->id,
            'parent_order_item_id' => $parentItem->id,
            'generated_order_id' => 2,
        ]);

        $this->assertSame(
            [$flavors['frango']->id],
            Order::query()->findOrFail(2)->items()->firstOrFail()->options['flavors'] ?? []
        );
    }

    public function test_admin_can_register_retroactive_partial_withdrawal_with_schedule_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-20 18:30', 'Europe/Lisbon'));
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        [$order, $parentItem] = $this->makeParentOrder(quantity: 100);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/admin/orders/{$order->id}/partial-withdrawals",
            [
                'parent_order_item_id' => $parentItem->id,
                'requested_units' => 25,
                'scheduled_at' => '2026-07-20T10:00:00+01:00',
                'allow_schedule_exception' => true,
                'generate_child_order' => true,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.withdrawal.requested_units', 25)
            ->assertJsonPath('data.generated_order.slot', 'manha');
    }

    public function test_child_orders_cannot_generate_new_partial_withdrawals(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        [$parentOrder] = $this->makeParentOrder(quantity: 100);
        [$childOrder, $childItem] = $this->makeParentOrder(quantity: 25, parentOrderId: $parentOrder->id);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/admin/orders/{$childOrder->id}/partial-withdrawals",
            [
                'parent_order_item_id' => $childItem->id,
                'requested_units' => 25,
                'scheduled_at' => '2026-07-20T18:00:00+01:00',
            ],
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['order']);
    }

    public function test_customer_order_history_hides_generated_child_orders(): void
    {
        [$parentOrder] = $this->makeParentOrder(quantity: 100);
        $this->makeParentOrder(quantity: 25, parentOrderId: $parentOrder->id, user: $parentOrder->user);

        $response = $this->actingAs($parentOrder->user, 'sanctum')->getJson('/api/v1/orders');

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $parentOrder->id);
    }

    public function test_customer_cannot_open_a_generated_child_order_directly(): void
    {
        [$parentOrder] = $this->makeParentOrder(quantity: 100);
        [$childOrder] = $this->makeParentOrder(quantity: 25, parentOrderId: $parentOrder->id, user: $parentOrder->user);

        $response = $this->actingAs($parentOrder->user, 'sanctum')->getJson("/api/v1/orders/{$childOrder->id}");

        $response->assertStatus(404);
    }

    /**
     * @return array{0: Order, 1: \App\Models\OrderItem, 2: Store}
     */
    protected function makeParentOrder(int $quantity, ?int $parentOrderId = null, ?User $user = null): array
    {
        $customer = $user ?? User::factory()->create();
        $store = $this->createStore();
        $category = Category::create([
            'name' => 'Salgados',
            'description' => 'Categoria de teste',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pack Festa',
            'price' => 1.20,
            'active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'parent_order_id' => $parentOrderId,
            'customer_name' => 'Cliente Teste',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => Carbon::create(2026, 7, 25, 15, 0, 0, 'UTC'),
            'total' => round($quantity * 1.20, 2),
            'notes' => 'Teste',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'name_snapshot' => $product->name,
            'price_snapshot' => 1.20,
            'quantity' => $quantity,
            'options' => null,
            'total' => round($quantity * 1.20, 2),
        ]);

        return [$order, $item, $store];
    }

    /**
     * @return array{0: Order, 1: \App\Models\OrderItem, 2: Store, 3: array{frango: Flavor, carne: Flavor}}
     */
    protected function makePackParentOrder(): array
    {
        $customer = User::factory()->create();
        $store = $this->createStore();
        $category = Category::create([
            'name' => 'Salgados Pack',
            'description' => 'Categoria de teste',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pack Festa',
            'price' => 120,
            'active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Pack 100',
            'unit_count' => 100,
            'max_flavors' => 4,
            'price' => 120,
            'active' => true,
            'display_order' => 1,
        ]);
        $frango = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $carne = Flavor::create(['name' => 'Carne', 'active' => true, 'display_order' => 2]);
        $product->flavors()->sync([$frango->id, $carne->id]);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Cliente Teste Pack',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => Carbon::create(2026, 7, 25, 15, 0, 0, 'UTC'),
            'total' => 120,
            'notes' => 'Teste pack',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'name_snapshot' => $variant->name,
            'price_snapshot' => 120,
            'quantity' => 1,
            'options' => ['flavors' => [$frango->id, $carne->id, $frango->id, $carne->id]],
            'total' => 120,
        ]);

        return [$order, $item, $store, compact('frango', 'carne')];
    }

    protected function setEditWindow(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'order_cancel_minutes'],
            ['value' => 60, 'type' => 'integer', 'editable' => true],
        );
    }

    protected function createStore(array $overrides = []): Store
    {
        $suffix = (string) Store::query()->count();

        return Store::create(array_merge([
            'name' => "Loja Centro {$suffix}",
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
                'monday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'tuesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'thursday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'friday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'saturday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
                'sunday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '20:00'],
            ],
            'pickup_date_exceptions' => [],
        ], $overrides));
    }
}
