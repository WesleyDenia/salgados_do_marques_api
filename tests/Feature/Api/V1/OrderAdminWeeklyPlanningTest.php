<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminWeeklyPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_weekly_planning_endpoint_filters_orders_for_requested_lisbon_week(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->createStore();

        $mondayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => 'Cliente Segunda',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::create(2026, 7, 12, 23, 30, 0, 'UTC'),
            'total' => 22.50,
            'notes' => null,
        ]);
        $mondayOrder->items()->create([
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Coxinha',
            'price_snapshot' => 11.25,
            'quantity' => 2,
            'options' => null,
            'total' => 22.50,
        ]);

        $sundayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'customer_name' => 'Cliente Domingo',
            'payment_status' => 'paid',
            'slot' => 'noite',
            'scheduled_at' => Carbon::create(2026, 7, 19, 22, 30, 0, 'UTC'),
            'total' => 18.00,
            'notes' => null,
        ]);
        $sundayOrder->items()->create([
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
            'scheduled_at' => Carbon::create(2026, 7, 15, 11, 0, 0, 'UTC'),
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
            'scheduled_at' => Carbon::create(2026, 7, 19, 23, 15, 0, 'UTC'),
            'total' => 14.50,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/orders/weekly?week_start=2026-07-13');

        $response->assertOk();
        $response->assertJsonPath('filters.week_start', '2026-07-13');
        $response->assertJsonPath('selected_week_label', '13/07/2026 - 19/07/2026');
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
        $response->assertJsonFragment(['customer_name' => 'Cliente Segunda']);
        $response->assertJsonFragment(['customer_name' => 'Cliente Domingo']);
        $response->assertJsonFragment(['customer_name' => 'Cliente Sem Slot']);
        $response->assertJsonMissing(['customer_name' => 'Cliente Antes']);
        $response->assertJsonMissing(['customer_name' => 'Cliente Depois']);

        $daySummaries = $response->json('day_summaries');

        $this->assertIsArray($daySummaries);
        $this->assertCount(7, $daySummaries);
        $this->assertSame(
            [
                '2026-07-13',
                '2026-07-14',
                '2026-07-15',
                '2026-07-16',
                '2026-07-17',
                '2026-07-18',
                '2026-07-19',
            ],
            array_keys($daySummaries),
        );
        $this->assertSame(0, $daySummaries['2026-07-14']['orderCount']);
        $this->assertSame(1, $daySummaries['2026-07-15']['slot_counts']['sem_slot']);
        $this->assertSame('official', $daySummaries['2026-07-13']['slot_occupancy']['manha']['context_status']);
        $this->assertSame('disponível', $daySummaries['2026-07-13']['slot_occupancy']['manha']['state']);
        $this->assertSame('not_applicable', $daySummaries['2026-07-15']['slot_occupancy']['sem_slot']['context_status']);
        $this->assertSame('official', $daySummaries['2026-07-14']['slot_occupancy']['manha']['context_status']);
        $this->assertContains(
            1,
            array_map(
                static fn (array $summary): int => (int) ($summary['slot_counts']['manha'] ?? 0),
                $daySummaries,
            ),
        );
        $this->assertContains(
            1,
            array_map(
                static fn (array $summary): int => (int) ($summary['slot_counts']['noite'] ?? 0),
                $daySummaries,
            ),
        );
        $this->assertContains(
            1,
            array_map(
                static fn (array $summary): int => (int) ($summary['slot_counts']['sem_slot'] ?? 0),
                $daySummaries,
            ),
        );
    }

    public function test_admin_weekly_planning_endpoint_handles_dst_week_boundaries(): void
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
            'scheduled_at' => Carbon::create(2026, 10, 18, 23, 30, 0, 'UTC'),
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
            'scheduled_at' => Carbon::create(2026, 10, 26, 0, 15, 0, 'UTC'),
            'total' => 25.00,
            'notes' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/orders/weekly?week_start=2026-10-19');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['customer_name' => 'Cliente DST Início']);
        $response->assertJsonMissing(['customer_name' => 'Cliente DST Fim']);
    }

    public function test_operacional_user_can_access_weekly_planning_endpoint(): void
    {
        $operationalUser = User::factory()->create(['role' => 'operacional']);

        $response = $this->actingAs($operationalUser, 'sanctum')->getJson('/api/v1/admin/orders/weekly?week_start=2026-07-13');

        $response->assertOk();
    }

    public function test_weekly_planning_endpoint_defaults_week_start_for_weekly_action(): void
    {
        Carbon::setTestNow('2026-07-15 10:00:00');

        try {
            $admin = User::factory()->create(['role' => 'admin']);

            $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/orders/weekly');

            $response->assertOk();
            $response->assertJsonPath('filters.week_start', '2026-07-13');
        } finally {
            Carbon::setTestNow();
        }
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
