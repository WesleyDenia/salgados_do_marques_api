<?php

namespace Tests\Feature\Admin;

use App\Models\Flavor;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_detail_shows_flavor_names_instead_of_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = Store::create([
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
        $order = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 30, 0, 'UTC'),
            'total' => 14.90,
            'notes' => null,
        ]);

        $frango = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $carne = Flavor::create(['name' => 'Carne', 'active' => true, 'display_order' => 2]);

        $order->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Pack Festa',
            'price_snapshot' => 14.90,
            'quantity' => 1,
            'options' => ['flavors' => [$frango->id, $carne->id]],
            'total' => 14.90,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSeeText('15/07/2026 12:30');
        $response->assertSeeText('Sabores:');
        $response->assertSeeText('Frango');
        $response->assertSeeText('Carne');
        $response->assertDontSeeText("Sabores: {$frango->id}, {$carne->id}");
    }
}
