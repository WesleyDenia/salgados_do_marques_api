<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_an_order_creates_one_history_record_with_the_changed_fields_only(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();
        $replacementStore = $this->createStore([
            'name' => 'Loja Norte',
            'default_store' => false,
        ]);
        $product = $this->createProduct('Coxinha', 2.50);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 10.00,
            'notes' => 'Sem picante',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'name_snapshot' => $product->name,
            'price_snapshot' => 2.50,
            'quantity' => 4,
            'options' => null,
            'total' => 10.00,
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
                    'quantity' => 6,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('order_histories', 1);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'action' => 'updated',
        ]);

        $history = \DB::table('order_histories')->first();
        $changes = json_decode($history->changes, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['from' => 'Maria Silva', 'to' => 'Maria Santos'], $changes['customer_name']);
        $this->assertSame(
            ['id' => $store->id, 'name' => $store->name],
            $changes['store_id']['from']
        );
        $this->assertSame(
            ['id' => $replacementStore->id, 'name' => $replacementStore->name],
            $changes['store_id']['to']
        );
        $this->assertSame(['from' => 'pending', 'to' => 'paid'], $changes['payment_status']);
        $this->assertSame(['from' => 'manha', 'to' => 'tarde'], $changes['slot']);
        $this->assertSame(['from' => 1, 'to' => 1], [
            'from' => count($changes['items']['from']),
            'to' => count($changes['items']['to']),
        ]);
        $this->assertSame(4, $changes['items']['from'][0]['quantity']);
        $this->assertSame(6, $changes['items']['to'][0]['quantity']);
    }

    public function test_status_transition_is_logged_with_actor_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->createStore();
        $order = Order::create([
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'accepted',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_update_with_no_effective_changes_does_not_create_history(): void
    {
        $this->setEditWindow();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();
        $product = $this->createProduct('Coxinha', 2.50);

        $scheduledAt = Carbon::create(2026, 7, 16, 10, 0, 0, 'UTC');

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => $scheduledAt,
            'total' => 10.00,
            'notes' => 'Sem picante',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'name_snapshot' => $product->name,
            'price_snapshot' => 2.50,
            'quantity' => 4,
            'options' => null,
            'total' => 10.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}", [
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'scheduled_at' => '2026-07-16T11:00:00+01:00',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'notes' => 'Sem picante',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('order_histories', 0);
    }

    public function test_order_detail_returns_history_in_reverse_chronological_order(): void
    {
        $this->setEditWindow();
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00', 'UTC'));

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();
        $product = $this->createProduct('Coxinha', 2.50);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'status' => 'placed',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 16, 11, 0, 0, 'UTC'),
            'total' => 10.00,
            'notes' => 'Sem picante',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'name_snapshot' => $product->name,
            'price_snapshot' => 2.50,
            'quantity' => 4,
            'options' => null,
            'total' => 10.00,
        ]);

        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}", [
            'customer_name' => 'Maria Santos',
            'customer_contact' => '912345678',
            'store_id' => $store->id,
            'scheduled_at' => '2026-07-16T12:00:00+01:00',
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'notes' => 'Sem picante',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-07-15 10:05:00', 'UTC'));

        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'accepted',
        ])->assertOk();

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.history')
            ->assertJsonPath('data.history.0.action', 'status_changed')
            ->assertJsonPath('data.history.0.user_id', $admin->id)
            ->assertJsonPath('data.history.0.user.id', $admin->id)
            ->assertJsonPath('data.history.0.user.name', $admin->name)
            ->assertJsonPath('data.history.1.action', 'updated');
    }

    public function test_order_history_table_does_not_expose_an_updated_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('order_histories', 'created_at'));
        $this->assertFalse(Schema::hasColumn('order_histories', 'updated_at'));
    }

    public function test_order_history_records_are_immutable(): void
    {
        $store = $this->createStore();
        $order = Order::create([
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
        ]);

        $history = OrderHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => User::factory()->create()->id,
            'action' => 'updated',
            'changes' => [
                'status' => ['from' => 'placed', 'to' => 'accepted'],
            ],
        ]);

        $this->expectException(LogicException::class);

        $history->update(['action' => 'status_changed']);
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

    protected function createProduct(string $name, float $price): Product
    {
        $category = Category::create([
            'name' => "Categoria {$name}",
            'description' => 'Categoria de teste',
            'active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'active' => true,
        ]);
    }
}
