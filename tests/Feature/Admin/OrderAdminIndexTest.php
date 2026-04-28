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
