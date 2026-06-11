<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminPeriodPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_period_planning_endpoint_filters_orders_for_requested_lisbon_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $firstDayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Início',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 12, 23, 30, 0, 'UTC'),
            'total' => 22.50,
            'notes' => null,
        ]);
        $firstDayOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Coxinha',
            'price_snapshot' => 11.25,
            'quantity' => 2,
            'options' => null,
            'total' => 22.50,
        ]);

        $lastDayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'customer_name' => 'Cliente Fim',
            'payment_status' => 'paid',
            'slot' => 'noite',
            'scheduled_at' => Carbon::create(2026, 7, 14, 22, 30, 0, 'UTC'),
            'total' => 18.00,
            'notes' => null,
        ]);
        $lastDayOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Risole',
            'price_snapshot' => 6.00,
            'quantity' => 3,
            'options' => null,
            'total' => 18.00,
        ]);

        $unslottedOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Sem Slot',
            'payment_status' => 'pending',
            'slot' => null,
            'scheduled_at' => Carbon::create(2026, 7, 14, 10, 0, 0, 'UTC'),
            'total' => 10.00,
            'notes' => null,
        ]);
        $unslottedOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Empada',
            'price_snapshot' => 5.00,
            'quantity' => 1,
            'options' => null,
            'total' => 10.00,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Antes',
            'scheduled_at' => Carbon::create(2026, 7, 12, 22, 30, 0, 'UTC'),
            'total' => 12.50,
            'notes' => null,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Depois',
            'scheduled_at' => Carbon::create(2026, 7, 14, 23, 15, 0, 'UTC'),
            'total' => 14.50,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson(
            '/api/v1/admin/orders/period?start_date=2026-07-13&end_date=2026-07-14'
        );

        $response->assertOk();
        $response->assertJsonPath('filters.start_date', '2026-07-13');
        $response->assertJsonPath('filters.end_date', '2026-07-14');
        $response->assertJsonPath('selected_period_label', '13/07/2026 - 14/07/2026');
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('summary.orderCount', 3);
        $response->assertJsonPath('summary.itemQuantity', 6);
        $response->assertJsonPath('summary.paidCount', 1);
        $response->assertJsonPath('summary.attentionCount', 3);
        $response->assertJsonPath('summary.slotCounts.manha', 1);
        $response->assertJsonPath('summary.slotCounts.noite', 1);
        $response->assertJsonPath('summary.slotCounts.sem_slot', 1);
        $response->assertJsonPath('slot_occupancy.manha.state', null);
        $response->assertJsonPath('slot_occupancy.manha.context_status', 'insufficient_context');
        $response->assertJsonFragment(['customer_name' => 'Cliente Início']);
        $response->assertJsonFragment(['customer_name' => 'Cliente Fim']);
        $response->assertJsonFragment(['customer_name' => 'Cliente Sem Slot']);
        $response->assertJsonMissing(['customer_name' => 'Cliente Antes']);
        $response->assertJsonMissing(['customer_name' => 'Cliente Depois']);

        $daySummaries = $response->json('day_summaries');

        $this->assertIsArray($daySummaries);
        $this->assertSame(
            [
                '2026-07-13',
                '2026-07-14',
            ],
            array_keys($daySummaries),
        );
        $this->assertSame(1, $daySummaries['2026-07-13']['orderCount']);
        $this->assertSame(2, $daySummaries['2026-07-14']['orderCount']);
        $this->assertSame(1, $daySummaries['2026-07-14']['slot_counts']['sem_slot']);
        $this->assertSame('official', $daySummaries['2026-07-13']['slot_occupancy']['manha']['context_status']);
        $this->assertSame('disponível', $daySummaries['2026-07-13']['slot_occupancy']['manha']['state']);
        $this->assertSame('not_applicable', $daySummaries['2026-07-14']['slot_occupancy']['sem_slot']['context_status']);
    }

    public function test_admin_period_planning_endpoint_returns_zero_day_summaries_for_days_without_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->getJson(
            '/api/v1/admin/orders/period?start_date=2026-07-13&end_date=2026-07-15'
        );

        $response->assertOk();
        $response->assertJsonPath('day_summaries.2026-07-13.orderCount', 0);
        $response->assertJsonPath('day_summaries.2026-07-14.orderCount', 0);
        $response->assertJsonPath('day_summaries.2026-07-15.orderCount', 0);
        $response->assertJsonPath('day_summaries.2026-07-13.slot_occupancy.manha.context_status', 'insufficient_context');
    }

    public function test_admin_period_planning_endpoint_validates_date_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->getJson(
            '/api/v1/admin/orders/period?start_date=2026-07-15&end_date=2026-07-13'
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_admin_period_planning_endpoint_handles_dst_boundaries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente DST Início',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 10, 24, 23, 30, 0, 'UTC'),
            'total' => 20.00,
            'notes' => null,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'customer_name' => 'Cliente DST Fim',
            'payment_status' => 'paid',
            'slot' => 'noite',
            'scheduled_at' => Carbon::create(2026, 10, 27, 0, 15, 0, 'UTC'),
            'total' => 25.00,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson(
            '/api/v1/admin/orders/period?start_date=2026-10-25&end_date=2026-10-26'
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['customer_name' => 'Cliente DST Início']);
        $response->assertJsonMissing(['customer_name' => 'Cliente DST Fim']);
    }

    public function test_operacional_user_can_access_period_planning_endpoint(): void
    {
        $operationalUser = User::factory()->create(['role' => 'operacional']);

        $response = $this->actingAs($operationalUser, 'sanctum')->getJson(
            '/api/v1/admin/orders/period?start_date=2026-07-13&end_date=2026-07-14'
        );

        $response->assertOk();
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
