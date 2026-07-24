<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\OverdueOrderCompletionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueOrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_orders_from_previous_operational_days_as_done(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'ORDER_TIMEZONE'],
            ['value' => 'Europe/Lisbon', 'type' => 'string']
        );

        $customer = User::factory()->create();
        $store = $this->createStore();

        $overdueOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'scheduled_at' => Carbon::create(2026, 7, 23, 18, 0, 0, 'Europe/Lisbon')->utc(),
            'total' => 12.50,
        ]);

        $todayOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'ready',
            'scheduled_at' => Carbon::create(2026, 7, 24, 0, 30, 0, 'Europe/Lisbon')->utc(),
            'total' => 9.50,
        ]);

        $result = app(OverdueOrderCompletionService::class)->completeOverdueOrders(
            Carbon::parse('2026-07-24 01:00:00', 'Europe/Lisbon')
        );

        $this->assertSame(1, $result['completed']);
        $this->assertSame('done', $overdueOrder->fresh()->status);
        $this->assertSame('ready', $todayOrder->fresh()->status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $overdueOrder->id,
            'user_id' => null,
            'action' => 'status_changed',
        ]);
    }

    public function test_it_does_not_overwrite_terminal_orders(): void
    {
        $customer = User::factory()->create();
        $store = $this->createStore();

        $canceledOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'canceled',
            'scheduled_at' => Carbon::create(2026, 7, 23, 18, 0, 0, 'Europe/Lisbon')->utc(),
            'total' => 12.50,
        ]);

        $rejectedOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'rejected',
            'scheduled_at' => Carbon::create(2026, 7, 23, 19, 0, 0, 'Europe/Lisbon')->utc(),
            'total' => 9.50,
        ]);

        $result = app(OverdueOrderCompletionService::class)->completeOverdueOrders(
            Carbon::parse('2026-07-24 01:00:00', 'Europe/Lisbon')
        );

        $this->assertSame(0, $result['completed']);
        $this->assertSame('canceled', $canceledOrder->fresh()->status);
        $this->assertSame('rejected', $rejectedOrder->fresh()->status);
        $this->assertDatabaseCount('order_histories', 0);
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
