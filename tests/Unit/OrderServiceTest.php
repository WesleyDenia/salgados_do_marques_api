<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    public function test_it_rejects_invalid_status_transition(): void
    {
        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldNotReceive('updateStatus');

        $service = new OrderService($repository);
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

        $service = new OrderService($repository);
        $updated = $service->updateStatus($order, 'accepted');

        $this->assertSame($order, $updated);
    }
}
