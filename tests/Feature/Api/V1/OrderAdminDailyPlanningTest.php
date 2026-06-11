<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminDailyPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_daily_planning_endpoint_returns_official_slot_occupancy_for_single_store_context(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $morningOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Manhã',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 14, 9, 0, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);
        $morningOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Coxinha',
            'price_snapshot' => 12.50,
            'quantity' => 1,
            'options' => null,
            'total' => 12.50,
        ]);

        $unslottedOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'customer_name' => 'Cliente Sem Slot',
            'payment_status' => 'paid',
            'slot' => null,
            'scheduled_at' => Carbon::create(2026, 7, 14, 13, 0, 0, 'UTC'),
            'total' => 18.00,
            'notes' => null,
        ]);
        $unslottedOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Empada',
            'price_snapshot' => 18.00,
            'quantity' => 2,
            'options' => null,
            'total' => 18.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/orders/daily?day=2026-07-14');

        $response->assertOk();
        $response->assertJsonPath('summary.slotCounts.manha', 1);
        $response->assertJsonPath('summary.slotCounts.sem_slot', 1);
        $response->assertJsonPath('slot_occupancy.manha.count', 1);
        $response->assertJsonPath('slot_occupancy.manha.label', 'Manhã');
        $response->assertJsonPath('slot_occupancy.manha.state', 'disponível');
        $response->assertJsonPath('slot_occupancy.manha.context_status', 'official');
        $response->assertJsonPath('slot_occupancy.tarde.state', 'disponível');
        $response->assertJsonPath('slot_occupancy.noite.state', 'limitado');
        $response->assertJsonPath('slot_occupancy.sem_slot.count', 1);
        $response->assertJsonPath('slot_occupancy.sem_slot.state', null);
        $response->assertJsonPath('slot_occupancy.sem_slot.context_status', 'not_applicable');
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
