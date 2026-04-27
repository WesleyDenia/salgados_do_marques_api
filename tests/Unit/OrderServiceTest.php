<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\WhatsAppQueueItem;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Services\OrderService;
use App\Services\Notifications\WhatsAppMessageFormatter;
use App\Services\SettingService;
use App\Services\StoreService;
use App\Services\WhatsAppQueueService;
use Illuminate\Support\Facades\Queue;
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
            Mockery::mock(WhatsAppMessageFormatter::class),
            Mockery::mock(WhatsAppQueueService::class),
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
        $settings->shouldReceive('get')->once()->with('ORDER_SCHEDULING_WINDOW_DAYS', 15)->andReturn('30');

        $service = new OrderService(
            $repository,
            $products,
            $settings,
            $stores,
            Mockery::mock(WhatsAppMessageFormatter::class),
            Mockery::mock(WhatsAppQueueService::class),
        );

        $this->assertSame([
            'start_time' => '12:30',
            'end_time' => '21:30',
            'minimum_minutes' => 50,
            'cancel_minutes' => 120,
            'timezone' => 'Europe/Madrid',
            'scheduling_window_days' => 30,
        ], $service->orderSettings());
    }

    public function test_it_dispatches_whatsapp_notification_after_creating_order(): void
    {
        Queue::fake();

        $repository = Mockery::mock(OrderRepository::class);
        $products = Mockery::mock(ProductRepository::class);
        $settings = Mockery::mock(SettingService::class);
        $stores = Mockery::mock(StoreService::class);
        $messages = Mockery::mock(WhatsAppMessageFormatter::class);
        $queue = Mockery::mock(WhatsAppQueueService::class);

        $service = Mockery::mock(OrderService::class, [
            $repository,
            $products,
            $settings,
            $stores,
            $messages,
            $queue,
        ])->makePartial();

        $service->shouldReceive('orderSettings')->andReturn([
            'start_time' => '12:00',
            'end_time' => '20:00',
            'minimum_minutes' => 30,
            'cancel_minutes' => 60,
            'timezone' => 'UTC',
            'scheduling_window_days' => 15,
        ]);

        $user = new \App\Models\User();
        $user->id = 10;
        $user->name = 'Joao';
        $user->email = 'joao@example.com';
        $user->phone = '351911928481';
        $store = new \App\Models\Store(['id' => 20, 'name' => 'Loja', 'accepts_orders' => true]);
        $product = new \App\Models\Product(['id' => 30, 'name' => 'Coxinha', 'price' => 2.50]);
        $product->setRelation('allowedFlavors', new \Illuminate\Database\Eloquent\Collection());
        $stores->shouldReceive('findById')->once()->with(20)->andReturn($store);
        $stores->shouldReceive('validateScheduledPickup')
            ->once()
            ->with($store, Mockery::type(\Carbon\Carbon::class), Mockery::type('array'));
        $products->shouldReceive('findActiveForOrder')
            ->once()
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([30 => $product]));
        $products->shouldReceive('findActiveVariantsForOrder')
            ->once()
            ->andReturn(new \Illuminate\Database\Eloquent\Collection());

        $createdOrder = new Order();
        $createdOrder->id = 99;

        $repository->shouldReceive('createWithItems')
            ->once()
            ->andReturn($createdOrder);

        $messages->shouldReceive('orderPlacedSnapshot')
            ->once()
            ->andReturn("Nome: Joao\nTel: 351911928481\nData/Hora: 15/01/2026 12:30\nPedido:\n2x Coxinha");

        $queueItem = new WhatsAppQueueItem(['id' => 555]);
        $queueItem->id = 555;

        $queue->shouldReceive('enqueue')
            ->once()
            ->andReturn($queueItem);

        $service->createForUser($user, [
            'store_id' => 20,
            'scheduled_at' => '2026-01-15 12:30:00',
            'items' => [
                [
                    'product_id' => 30,
                    'quantity' => 2,
                ],
            ],
        ]);

        Queue::assertPushed(SendOrderPlacedWhatsAppJob::class, fn (SendOrderPlacedWhatsAppJob $job) => $job->queueItemId === 555);
    }
}
