<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_index_shows_pickup_time_in_lisbon_timezone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSeeText('15/07/2026 12:30');
    }

    public function test_admin_order_index_filters_pickup_time_using_lisbon_timezone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $matchingOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        $nonMatchingOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 10, 30, 0, 'UTC'),
            'total' => 9.50,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'scheduled_from' => '2026-07-15T12:00',
            'scheduled_to' => '2026-07-15T12:45',
        ]));

        $response->assertOk();
        $response->assertSeeText("#{$matchingOrder->id}");
        $response->assertDontSeeText("#{$nonMatchingOrder->id}");
    }

    public function test_admin_order_index_filters_orders_by_operational_search_terms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'name' => 'Carla Atendimento',
            'phone' => '912345678',
        ]);
        $otherCustomer = User::factory()->create([
            'name' => 'Bruno Operacao',
            'phone' => '934567890',
        ]);
        $store = $this->createStore();

        $matchingOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Carla Atendimento',
            'customer_contact' => '912345678',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        $nonMatchingOrder = Order::create([
            'user_id' => $otherCustomer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Bruno Operacao',
            'customer_contact' => '934567890',
            'scheduled_at' => Carbon::create(2026, 7, 15, 10, 30, 0, 'UTC'),
            'total' => 9.50,
            'notes' => null,
        ]);

        $responseByName = $this->actingAs($admin)->get(route('admin.orders.index', [
            'search' => 'Carla',
        ]));

        $responseByName->assertOk();
        $responseByName->assertSeeText("#{$matchingOrder->id}");
        $responseByName->assertDontSeeText("#{$nonMatchingOrder->id}");

        $responseByPhone = $this->actingAs($admin)->get(route('admin.orders.index', [
            'search' => '912345678',
        ]));

        $responseByPhone->assertOk();
        $responseByPhone->assertSeeText("#{$matchingOrder->id}");
        $responseByPhone->assertDontSeeText("#{$nonMatchingOrder->id}");

        $responseById = $this->actingAs($admin)->get(route('admin.orders.index', [
            'search' => (string) $matchingOrder->id,
        ]));

        $responseById->assertOk();
        $responseById->assertSeeText("#{$matchingOrder->id}");
        $responseById->assertDontSeeText("#{$nonMatchingOrder->id}");
    }

    public function test_admin_order_index_hides_canceled_orders_by_default_but_allows_explicit_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $visibleOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Pedido ativo',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        $hiddenCanceledOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'canceled',
            'customer_name' => 'Pedido cancelado',
            'scheduled_at' => Carbon::create(2026, 7, 15, 12, 30, 0, 'UTC'),
            'total' => 9.50,
            'notes' => null,
        ]);

        $defaultResponse = $this->actingAs($admin)->get(route('admin.orders.index'));

        $defaultResponse->assertOk();
        $defaultResponse->assertSeeText("#{$visibleOrder->id}");
        $defaultResponse->assertDontSeeText("#{$hiddenCanceledOrder->id}");
        $defaultResponse->assertSeeText('€ 12,50');
        $defaultResponse->assertDontSeeText('€ 9,50');

        $canceledResponse = $this->actingAs($admin)->get(route('admin.orders.index', [
            'status' => 'canceled',
        ]));

        $canceledResponse->assertOk();
        $canceledResponse->assertSeeText("#{$hiddenCanceledOrder->id}");
        $canceledResponse->assertSeeText('€ 9,50');
        $canceledResponse->assertDontSeeText('€ 12,50');
    }

    public function test_admin_daily_planning_view_filters_orders_for_requested_lisbon_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $startBoundaryOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Madrugada',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 15, 23, 30, 0, 'UTC'),
            'total' => 22.50,
            'notes' => null,
        ]);
        $startBoundaryOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Coxinha',
            'price_snapshot' => 11.25,
            'quantity' => 2,
            'options' => null,
            'total' => 22.50,
        ]);

        $endBoundaryOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'customer_name' => 'Cliente Fecho',
            'payment_status' => 'paid',
            'slot' => 'noite',
            'scheduled_at' => Carbon::create(2026, 7, 16, 22, 30, 0, 'UTC'),
            'total' => 18.00,
            'notes' => null,
        ]);
        $endBoundaryOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Risole',
            'price_snapshot' => 6.00,
            'quantity' => 3,
            'options' => null,
            'total' => 18.00,
        ]);

        $previousDayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Ontem',
            'scheduled_at' => Carbon::create(2026, 7, 15, 22, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        $nextDayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Amanhã',
            'scheduled_at' => Carbon::create(2026, 7, 16, 23, 15, 0, 'UTC'),
            'total' => 14.50,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.daily', [
            'day' => '2026-07-16',
        ]));

        $response->assertOk();
        $response->assertSeeText('Planeamento diário');
        $response->assertSeeText('16/07/2026');
        $response->assertSeeText('Cliente Madrugada');
        $response->assertSeeText('Cliente Fecho');
        $response->assertDontSeeText('Cliente Ontem');
        $response->assertDontSeeText('Cliente Amanhã');
        $response->assertSeeText('2 encomendas');
        $response->assertSeeText('5 itens');
        $response->assertSee(route('admin.orders.show', $startBoundaryOrder), false);
    }

    public function test_admin_daily_planning_view_shows_explicit_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.orders.daily', [
            'day' => '2026-07-16',
        ]));

        $response->assertOk();
        $response->assertSeeText('Nenhuma encomenda planeada para o dia selecionado.');
        $response->assertSeeText('Ajuste a data ou remova filtros para verificar outras encomendas.');
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
        ]);
    }
}
