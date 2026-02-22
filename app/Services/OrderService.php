<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const STATUS_LABELS = [
        'placed' => 'Realizado',
        'accepted' => 'Aceito',
        'rejected' => 'Rejeitado',
        'ready' => 'Pronto',
        'done' => 'Concluído',
        'canceled' => 'Cancelado',
    ];

    private const STATUS_TRANSITIONS = [
        'placed' => ['accepted', 'rejected', 'canceled'],
        'accepted' => ['ready', 'canceled'],
        'rejected' => [],
        'ready' => ['done'],
        'done' => [],
        'canceled' => [],
    ];

    public function __construct(protected OrderRepository $repository) {}

    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateForAdmin($filters, $perPage);
    }

    public function findForAdmin(Order $order): Order
    {
        return $this->repository->findForAdmin($order);
    }

    public function listStoresForFilter(): Collection
    {
        return $this->repository->listStoresForFilter();
    }

    public function statusLabels(): array
    {
        return self::STATUS_LABELS;
    }

    public function allowedTransitions(Order $order): array
    {
        return self::STATUS_TRANSITIONS[$order->status] ?? [];
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        $this->assertStatusTransition($order, $newStatus);

        if ($newStatus === $order->status) {
            return $this->repository->findForAdmin($order);
        }

        $payload = ['status' => $newStatus];

        if ($newStatus === 'canceled') {
            $payload['cancelled_at'] = $order->cancelled_at ?? now('UTC');
        } elseif ($order->cancelled_at !== null) {
            $payload['cancelled_at'] = null;
        }

        return $this->repository->updateStatus($order, $payload);
    }

    protected function assertStatusTransition(Order $order, string $newStatus): void
    {
        if (!array_key_exists($newStatus, self::STATUS_LABELS)) {
            throw ValidationException::withMessages([
                'status' => 'Status inválido para o pedido.',
            ]);
        }

        if ($newStatus === $order->status) {
            return;
        }

        $allowed = $this->allowedTransitions($order);
        if (in_array($newStatus, $allowed, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => sprintf(
                'Transição inválida de "%s" para "%s".',
                self::STATUS_LABELS[$order->status] ?? $order->status,
                self::STATUS_LABELS[$newStatus] ?? $newStatus
            ),
        ]);
    }
}
