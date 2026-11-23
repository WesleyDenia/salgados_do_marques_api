<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OperationalPreparationSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPreparationSetting;
use App\Models\Store;
use App\Models\User;
use App\Services\FryingPlanningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FryingPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_current_cubas_and_plans_each_one_backwards_without_overlaps(): void
    {
        $store = $this->makeStore('Loja Centro');
        $slot = $this->makeSlot($store, 'Cuba 1');
        $product = $this->makeProductForSlot($slot, 20, 20 * 60);
        $customer = User::factory()->create();

        $order1530 = $this->makeOrder($store, $customer, '2026-11-25 15:30', 'Cliente 15:30');
        $this->addItem($order1530, $product, 20);
        $order1600 = $this->makeOrder($store, $customer, '2026-11-25 16:00', 'Cliente 16:00');
        $this->addItem($order1600, $product, 40);

        // No order_preparation_allocations records are created: the plan must
        // be a projection of the configuration that is registered today.
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

    public function test_it_recalculates_existing_orders_when_the_cuba_configuration_changes(): void
    {
        $store = $this->makeStore('Loja Configurável');
        $firstSlot = $this->makeSlot($store, 'Cuba antiga');
        $product = $this->makeProductForSlot($firstSlot, 25, 20 * 60);
        $order = $this->makeOrder($store, User::factory()->create(), '2026-11-25 16:00', 'Cliente');
        $this->addItem($order, $product, 25);

        $newSlot = $this->makeSlot($store, 'Cuba registada agora', 1);
        ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $newSlot->id,
            'product_id' => $product->id,
            'batch_size' => 25,
            'preparation_time_seconds' => 5 * 60,
        ]);
        $firstSlot->update(['active' => false]);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $store);

        $this->assertSame('Cuba registada agora', $plan['slots'][0]['name']);
        $this->assertSame('15:55', $plan['slots'][0]['tasks'][0]['start_at']->format('H:i'));
    }

    public function test_it_rounds_the_ready_time_down_to_the_previous_half_hour(): void
    {
        $store = $this->makeStore('Loja Norte');
        $slot = $this->makeSlot($store, 'Cuba Norte');
        $product = $this->makeProductForSlot($slot, 10, 5 * 60);
        $order = $this->makeOrder($store, User::factory()->create(), '2026-11-25 18:15', 'Cliente 18:15');
        $this->addItem($order, $product, 10);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $store);
        $task = $plan['slots'][0]['tasks'][0];

        $this->assertSame('17:55', $task['start_at']->format('H:i'));
        $this->assertSame('18:00', $task['finish_at']->format('H:i'));
        $this->assertSame('18:00', $task['ready_by']->format('H:i'));
    }

    public function test_it_never_uses_cubas_from_another_store(): void
    {
        $centre = $this->makeStore('Loja Centro');
        $north = $this->makeStore('Loja Norte');
        $customer = User::factory()->create();
        $centreSlot = $this->makeSlot($centre, 'Cuba Centro');
        $northSlot = $this->makeSlot($north, 'Cuba Norte');
        $centreProduct = $this->makeProductForSlot($centreSlot, 10, 180);
        $northProduct = $this->makeProductForSlot($northSlot, 10, 180);
        $centreOrder = $this->makeOrder($centre, $customer, '2026-11-25 14:00', 'Cliente Centro');
        $northOrder = $this->makeOrder($north, $customer, '2026-11-25 14:00', 'Cliente Norte');
        $this->addItem($centreOrder, $centreProduct, 10);
        $this->addItem($northOrder, $northProduct, 10);

        $plan = app(FryingPlanningService::class)->forDateAndStore('2026-11-25', $centre);

        $this->assertSame(1, $plan['orders_count']);
        $this->assertSame(1, $plan['tasks_count']);
        $this->assertSame($centreOrder->id, $plan['slots'][0]['tasks'][0]['order_id']);
        $this->assertSame('Cuba Centro', $plan['slots'][0]['name']);
    }

    public function test_admin_can_open_the_planning_screen_with_a_date_and_store_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore('Loja Planeamento');

        $this->actingAs($admin)
            ->get(route('admin.frying-planning.index', ['date' => '2026-11-25', 'store_id' => $store->id]))
            ->assertOk()
            ->assertSee('Planeamento de fritura')
            ->assertSee('Loja Planeamento');
    }

    private function makeStore(string $name): Store
    {
        return Store::create(['name' => $name, 'address' => 'Rua 1', 'city' => 'Lisboa', 'latitude' => 38.7169, 'longitude' => -9.1399, 'phone' => '123456789', 'type' => 'principal', 'is_active' => true, 'accepts_orders' => true, 'default_store' => false, 'pickup_weekly_schedule' => [], 'pickup_date_exceptions' => []]);
    }

    private function makeSlot(Store $store, string $name, int $displayOrder = 0): OperationalPreparationSlot
    {
        return OperationalPreparationSlot::create(['store_id' => $store->id, 'name' => $name, 'active' => true, 'display_order' => $displayOrder]);
    }

    private function makeProductForSlot(OperationalPreparationSlot $slot, int $batchSize, int $seconds): Product
    {
        $category = Category::create(['name' => 'Salgados '.uniqid(), 'active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Mini coxinha '.uniqid(), 'price' => 1, 'active' => true]);
        ProductPreparationSetting::create(['operational_preparation_slot_id' => $slot->id, 'product_id' => $product->id, 'batch_size' => $batchSize, 'preparation_time_seconds' => $seconds]);

        return $product;
    }

    private function makeOrder(Store $store, User $customer, string $localTime, string $customerName): Order
    {
        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $localTime, 'Europe/Lisbon');

        return Order::create(['user_id' => $customer->id, 'store_id' => $store->id, 'status' => 'placed', 'customer_name' => $customerName, 'payment_status' => 'pending', 'slot' => 'tarde', 'scheduled_at' => $scheduledAt->copy()->utc(), 'pickup_date' => $scheduledAt->format('Y-m-d'), 'pickup_time' => $scheduledAt->format('H:i'), 'total' => 10]);
    }

    private function addItem(Order $order, Product $product, int $units): void
    {
        $order->items()->create(['product_id' => $product->id, 'name_snapshot' => $product->name, 'price_snapshot' => 1, 'quantity' => $units, 'options' => [], 'total' => $units]);
    }
}
