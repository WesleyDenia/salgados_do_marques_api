<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\RegisterFryingAlarmJob;
use App\Models\Category;
use App\Models\OperationalPreparationSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPreparationSetting;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FryingAlarmRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_queues_one_alarm_for_each_new_heating_cycle(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 10, 0, 0, 'Europe/Lisbon'));
        Queue::fake();
        Setting::create(['key' => 'FRYING_PACKAGING_MINUTES', 'value' => 0, 'type' => 'integer']);
        Setting::create(['key' => 'FRYING_COOLING_MINUTES', 'value' => 15, 'type' => 'integer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $store = $this->store();
        $slot = OperationalPreparationSlot::create([
            'store_id' => $store->id,
            'name' => 'Cuba 1',
            'heating_time_seconds' => 10 * 60,
            'active' => true,
            'display_order' => 1,
        ]);
        $product = $this->productFor($slot);

        foreach (['12:00', '12:00', '12:10', '12:15', '13:40'] as $time) {
            $order = $this->order($store, $customer, $time);
            $order->items()->create([
                'product_id' => $product->id,
                'name_snapshot' => $product->name,
                'price_snapshot' => 1,
                'quantity' => 1,
                'total' => 1,
            ]);
        }

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/frying-planning/alarms', [
            'date' => '2026-11-25',
            'store_id' => $store->id,
        ]);

        $response->assertOk()->assertJsonPath('data.queued_count', 2);
        $response->assertJsonPath('data.skipped_past_count', 0);
        Queue::assertPushed(RegisterFryingAlarmJob::class, 2);
        Queue::assertPushedOn('frying-alarms', RegisterFryingAlarmJob::class);
        Queue::assertPushed(RegisterFryingAlarmJob::class, fn (RegisterFryingAlarmJob $job) =>
            $job->webhook === 'https://macrodroid.example/fritura-encomenda'
            && $job->minutesUntilAlarm === 100
        );
        Queue::assertPushed(RegisterFryingAlarmJob::class, fn (RegisterFryingAlarmJob $job) =>
            $job->webhook === 'https://macrodroid.example/fritura-encomenda'
            && $job->minutesUntilAlarm === 205
        );
    }

    public function test_it_requires_a_store_webhook_before_queueing_alarms(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->store(['webhook' => null]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/frying-planning/alarms', [
            'date' => '2026-11-25',
            'store_id' => $store->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id']);
        Queue::assertNothingPushed();
    }

    protected function store(array $attributes = []): Store
    {
        return Store::create(array_replace([
            'name' => 'Loja Alarmes',
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'webhook' => 'https://macrodroid.example/fritura-encomenda',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => false,
            'pickup_weekly_schedule' => [],
            'pickup_date_exceptions' => [],
        ], $attributes));
    }

    protected function productFor(OperationalPreparationSlot $slot): Product
    {
        $category = Category::create(['name' => 'Salgados Alarmes', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Coxinha Alarmes',
            'price' => 1,
            'active' => true,
        ]);
        ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $slot->id,
            'product_id' => $product->id,
            'batch_size' => 100,
            'preparation_time_seconds' => 5 * 60,
        ]);

        return $product;
    }

    protected function order(Store $store, User $customer, string $time): Order
    {
        $pickupAt = Carbon::createFromFormat('Y-m-d H:i', "2026-11-25 {$time}", 'Europe/Lisbon');

        return Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'customer_name' => "Cliente {$time}",
            'payment_status' => 'pending',
            'slot' => 'tarde',
            'scheduled_at' => $pickupAt->copy()->utc(),
            'pickup_date' => $pickupAt->format('Y-m-d'),
            'pickup_time' => $pickupAt->format('H:i'),
            'total' => 1,
        ]);
    }
}
