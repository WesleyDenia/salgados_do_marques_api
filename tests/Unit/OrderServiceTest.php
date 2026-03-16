<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use App\Services\SettingService;
use App\Services\StoreService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    private function makeService(OrderRepository $repository): OrderService
    {
        return new OrderService(
            $repository,
            Mockery::mock(ProductRepository::class),
            Mockery::mock(SettingService::class),
            Mockery::mock(StoreService::class),
        );
    }

    public function test_it_rejects_invalid_status_transition(): void
    {
        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldNotReceive('updateStatus');

        $service = $this->makeService($repository);
        $order = new Order(['status' => 'done']);

        $this->expectException(ValidationException::class);

        $service->updateStatus($order, 'accepted');
    }

    public function test_it_allows_valid_status_transition(): void
    {
        $repository = Mockery::mock(OrderRepository::class);
        $order = new Order(['status' => 'placed']);

        $repository->shouldReceive('updateStatus')
            ->once()
            ->with($order, Mockery::on(function (array $payload): bool {
                return ($payload['status'] ?? null) === 'accepted'
                    && !array_key_exists('cancelled_at', $payload);
            }))
            ->andReturn($order);

        $service = $this->makeService($repository);
        $updated = $service->updateStatus($order, 'accepted');

        $this->assertSame($order, $updated);
    }

    public function test_it_normalizes_order_settings_contract(): void
    {
        $repository = Mockery::mock(OrderRepository::class);
        $products = Mockery::mock(ProductRepository::class);
        $settings = Mockery::mock(SettingService::class);
        $stores = Mockery::mock(StoreService::class);

        $settings->shouldReceive('get')->once()->with('order_start_time', '12:00')->andReturn('11:00');
        $settings->shouldReceive('get')->once()->with('ORDER_START_TIME', '11:00')->andReturn('12:30');
        $settings->shouldReceive('get')->once()->with('order_end_time', '20:00')->andReturn('19:00');
        $settings->shouldReceive('get')->once()->with('ORDER_END_TIME', '19:00')->andReturn('21:30');
        $settings->shouldReceive('get')->once()->with('order_minimum_minutes', 30)->andReturn('45');
        $settings->shouldReceive('get')->once()->with('ORDER_MINIMUM_MINUTES', '45')->andReturn('50');
        $settings->shouldReceive('get')->once()->with('order_cancel_minutes', 60)->andReturn('90');
        $settings->shouldReceive('get')->once()->with('ORDER_CANCEL_MINUTES', '90')->andReturn('120');
        $settings->shouldReceive('get')->once()->with('order_timezone', 'Europe/Lisbon')->andReturn('UTC');
        $settings->shouldReceive('get')->once()->with('ORDER_TIMEZONE', 'UTC')->andReturn('Europe/Madrid');
        $settings->shouldReceive('get')->once()->with('ORDER_SCHEDULING_WINDOW_DAYS', 14)->andReturn('30');

        $service = new OrderService($repository, $products, $settings, $stores);

        $this->assertSame([
            'start_time' => '12:30',
            'end_time' => '21:30',
            'minimum_minutes' => 50,
            'cancel_minutes' => 120,
            'timezone' => 'Europe/Madrid',
            'scheduling_window_days' => 30,
        ], $service->orderSettings());
    }
}
