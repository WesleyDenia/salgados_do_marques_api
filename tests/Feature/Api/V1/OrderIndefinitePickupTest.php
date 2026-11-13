<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Models\WhatsAppQueueItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderIndefinitePickupTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_an_order_with_a_date_but_no_pickup_time(): void
    {
        $staff = User::factory()->create(['role' => 'atendimento']);
        $store = $this->createStore();
        $product = $this->createProduct();
        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'customer_name' => 'Cliente sem hora',
            'pickup_date' => '2026-12-20',
            'pickup_time' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.pickup_date', '2026-12-20')
            ->assertJsonPath('data.pickup_time', null)
            ->assertJsonPath('data.scheduled_at', null)
            ->assertJsonPath('data.slot', null);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Cliente sem hora',
            'pickup_date' => '2026-12-20',
            'pickup_time' => null,
            'scheduled_at' => null,
            'slot' => null,
        ]);
    }

    public function test_staff_can_create_orders_with_only_a_time_or_no_pickup_parts(): void
    {
        $staff = User::factory()->create(['role' => 'atendimento']);
        $store = $this->createStore();
        $product = $this->createProduct();
        Sanctum::actingAs($staff);

        $onlyTime = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'pickup_date' => null,
            'pickup_time' => '10:30',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $noParts = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'pickup_date' => null,
            'pickup_time' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $onlyTime->assertOk()
            ->assertJsonPath('data.pickup_date', null)
            ->assertJsonPath('data.pickup_time', '10:30')
            ->assertJsonPath('data.scheduled_at', null);
        $noParts->assertOk()
            ->assertJsonPath('data.pickup_date', null)
            ->assertJsonPath('data.pickup_time', null)
            ->assertJsonPath('data.scheduled_at', null);
    }

    public function test_staff_can_register_a_paid_amount_for_a_pending_order(): void
    {
        $staff = User::factory()->create(['role' => 'atendimento']);
        $store = $this->createStore();
        $product = $this->createProduct();
        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'payment_status' => 'pending',
            'paid_amount' => 2,
            'pickup_date' => null,
            'pickup_time' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.paid_amount', 2);

        $this->assertDatabaseHas('orders', [
            'payment_status' => 'pending',
            'paid_amount' => 2,
            'total' => 5,
        ]);
    }

    public function test_staff_cannot_register_a_paid_amount_above_the_order_total(): void
    {
        $staff = User::factory()->create(['role' => 'atendimento']);
        $store = $this->createStore();
        $product = $this->createProduct();
        Sanctum::actingAs($staff);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'payment_status' => 'pending',
            'paid_amount' => 5.01,
            'pickup_date' => null,
            'pickup_time' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('paid_amount');
    }

    public function test_it_queues_a_whatsapp_message_for_an_order_without_a_pickup_schedule(): void
    {
        Queue::fake();

        $staff = User::factory()->create(['role' => 'atendimento']);
        $store = $this->createStore();
        $product = $this->createProduct();
        Setting::updateOrCreate(
            ['key' => 'WHATSAPP_ORDER_TO'],
            ['value' => '+351912345678', 'type' => 'string', 'editable' => true],
        );
        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'customer_name' => 'Cliente sem agendamento',
            'customer_contact' => '912345678',
            'pickup_date' => null,
            'pickup_time' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertOk()->assertJsonPath('data.scheduled_at', null);

        $queueItem = WhatsAppQueueItem::query()
            ->where('type', WhatsAppQueueItem::TYPE_ORDER_PLACED)
            ->firstOrFail();

        $this->assertSame('+351912345678', $queueItem->phone);
        $this->assertSame('outbound', $queueItem->direction);
        $this->assertStringContainsString('Data/Hora: indefinido', (string) $queueItem->message);
        $this->assertStringContainsString('RETIRADA NA LOJA "Loja Centro"', (string) $queueItem->message);
        $this->assertStringContainsString('Valor total: €5,00', (string) $queueItem->message);
        $this->assertStringContainsString('Estado de pagamento: Pendente', (string) $queueItem->message);
        Queue::assertPushed(
            SendOrderPlacedWhatsAppJob::class,
            fn (SendOrderPlacedWhatsAppJob $job) => $job->queueItemId === $queueItem->id,
        );
    }

    public function test_daily_planning_reports_global_date_and_day_scoped_time_attention_without_slot_load(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->createStore();
        $withoutDate = Order::create([
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Sem data',
            'total' => 10,
        ]);
        $withoutTime = Order::create([
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Sem hora',
            'pickup_date' => '2026-12-20',
            'total' => 10,
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/admin/orders/daily?day=2026-12-20');

        $response->assertOk()
            ->assertJsonPath('summary.orderCount', 1)
            ->assertJsonPath('summary.slotCounts.sem_slot', 0)
            ->assertJsonPath('operational_attention.undefined_pickup_date.count', 1)
            ->assertJsonPath('operational_attention.undefined_pickup_date.orders.0.id', $withoutDate->id)
            ->assertJsonPath('operational_attention.undefined_pickup_time.count', 1)
            ->assertJsonPath('operational_attention.undefined_pickup_time.orders.0.id', $withoutTime->id);
    }

    public function test_resource_derives_pickup_parts_for_a_legacy_scheduled_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->createStore();
        $legacyOrder = Order::create([
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 12, 20, 10, 30, 0, 'Europe/Lisbon')->utc(),
            'total' => 10,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/admin/orders/{$legacyOrder->id}")
            ->assertOk()
            ->assertJsonPath('data.pickup_date', '2026-12-20')
            ->assertJsonPath('data.pickup_time', '10:30');
    }

    protected function createStore(): Store
    {
        return Store::create([
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
            'pickup_weekly_schedule' => [],
            'pickup_date_exceptions' => [],
        ]);
    }

    protected function createProduct(): Product
    {
        $category = Category::create(['name' => 'Salgados', 'active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Coxinha',
            'price' => 2.50,
            'active' => true,
        ]);
    }
}
