<?php

namespace Tests\Feature;

use App\Models\OperationalPreparationSlot;
use App\Models\Order;
use App\Models\OrderPreparationAllocation;
use App\Models\Store;
use App\Models\User;
use App\Services\FryingPlanningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FryingPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_plans_each_cuba_backwards_without_overlapping_earlier_deliveries(): void
    {
        $store = $this->makeStore('Loja Centro');
        $slot = $this->makeSlot($store, 'Cuba 1');
        $customer = User::factory()->create();

        $order1530 = $this->makeOrder($store, $customer, '2026-11-25 15:30', 'Cliente 15:30');
        $this->addBatch($order1530, $slot, 20 * 60, 20, 1);

        $order1600 = $this->makeOrder($store, $customer, '2026-11-25 16:00', 'Cliente 16:00');
        $this->addBatch($order1600, $slot, 20 * 60, 20, 1);
        $this->addBatch($order1600, $slot, 20 * 60, 20, 2);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $store);
        $tasks = collect($plan['slots'][0]['tasks']);
        $earlierTask = $tasks->firstWhere('order_id', $order1530->id);
        $laterTasks = $tasks->where('order_id', $order1600->id)->values();

        $this->assertSame(3, $plan['tasks_count']);
        $this->assertSame('15:00', $earlierTask['start_at']->format('H:i'));
        $this->assertSame('15:20', $earlierTask['finish_at']->format('H:i'));
        $this->assertSame('15:30', $earlierTask['ready_by']->format('H:i'));
        $this->assertSame('15:20', $laterTasks[0]['start_at']->format('H:i'));
        $this->assertSame('16:00', $laterTasks[1]['finish_at']->format('H:i'));
    }

    public function test_it_rounds_the_ready_time_down_to_the_previous_half_hour(): void
    {
        $store = $this->makeStore('Loja Norte');
        $slot = $this->makeSlot($store, 'Cuba Norte');
        $order = $this->makeOrder($store, User::factory()->create(), '2026-11-25 18:15', 'Cliente 18:15');
        $this->addBatch($order, $slot, 5 * 60, 10, 1);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $store);
        $task = $plan['slots'][0]['tasks'][0];

        $this->assertSame('17:55', $task['start_at']->format('H:i'));
        $this->assertSame('18:00', $task['finish_at']->format('H:i'));
        $this->assertSame('18:00', $task['ready_by']->format('H:i'));
    }

    public function test_it_never_includes_the_other_store_in_the_selected_store_plan(): void
    {
        $centre = $this->makeStore('Loja Centro');
        $north = $this->makeStore('Loja Norte');
        $customer = User::factory()->create();
        $centreSlot = $this->makeSlot($centre, 'Cuba Centro');
        $northSlot = $this->makeSlot($north, 'Cuba Norte');

        $centreOrder = $this->makeOrder($centre, $customer, '2026-11-25 14:00', 'Cliente Centro');
        $northOrder = $this->makeOrder($north, $customer, '2026-11-25 14:00', 'Cliente Norte');
        $this->addBatch($centreOrder, $centreSlot, 180, 10, 1);
        $this->addBatch($northOrder, $northSlot, 180, 10, 1);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $centre);

        $this->assertSame(1, $plan['orders_count']);
        $this->assertSame(1, $plan['tasks_count']);
        $this->assertSame($centreOrder->id, $plan['slots'][0]['tasks'][0]['order_id']);
    }

    public function test_admin_can_open_the_planning_screen_with_a_date_and_store_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore('Loja Planeamento');

        $this->actingAs($admin)
            ->get(route('admin.frying-planning.index', [
                'date' => '2026-11-25',
                'store_id' => $store->id,
            ]))
            ->assertOk()
            ->assertSee('Planeamento de fritura')
            ->assertSee('Loja Planeamento');
    }

    private function makeStore(string $name): Store
    {
        return Store::create([
            'name' => $name,
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => false,
            'pickup_weekly_schedule' => [],
            'pickup_date_exceptions' => [],
        ]);
    }

    private function makeSlot(Store $store, string $name): OperationalPreparationSlot
    {
        return OperationalPreparationSlot::create([
            'store_id' => $store->id,
            'name' => $name,
            'active' => true,
            'display_order' => 0,
        ]);
    }

    private function makeOrder(Store $store, User $customer, string $localTime, string $customerName): Order
    {
        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $localTime, 'Europe/Lisbon');

        return Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => $customerName,
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => $scheduledAt->copy()->utc(),
            'pickup_date' => $scheduledAt->format('Y-m-d'),
            'pickup_time' => $scheduledAt->format('H:i'),
            'total' => 10,
        ]);
    }

    private function addBatch(Order $order, OperationalPreparationSlot $slot, int $seconds, int $units, int $index): void
    {
        $item = $order->items()->create([
            'name_snapshot' => 'Mini coxinha',
            'price_snapshot' => 1,
            'quantity' => $units,
            'options' => [],
            'total' => $units,
        ]);

        OrderPreparationAllocation::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'operational_preparation_slot_id' => $slot->id,
            'scheduled_slot' => 'tarde',
            'scheduled_for_date' => $order->pickup_date,
            'batch_index' => $index,
            'batch_units' => $units,
            'preparation_time_seconds' => $seconds,
        ]);
    }
}
