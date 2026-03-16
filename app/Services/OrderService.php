<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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

    public function __construct(
        protected OrderRepository $repository,
        protected ProductRepository $products,
        protected SettingService $settings,
        protected StoreService $stores,
    ) {}

    public function orderSettings(): array
    {
        return [
            'start_time' => $this->settings->get(
                'ORDER_START_TIME',
                $this->settings->get('order_start_time', '12:00')
            ),
            'end_time' => $this->settings->get(
                'ORDER_END_TIME',
                $this->settings->get('order_end_time', '20:00')
            ),
            'minimum_minutes' => (int) $this->settings->get(
                'ORDER_MINIMUM_MINUTES',
                $this->settings->get('order_minimum_minutes', 30)
            ),
            'cancel_minutes' => (int) $this->settings->get(
                'ORDER_CANCEL_MINUTES',
                $this->settings->get('order_cancel_minutes', 60)
            ),
            'timezone' => $this->settings->get(
                'ORDER_TIMEZONE',
                $this->settings->get('order_timezone', 'Europe/Lisbon')
            ),
            'scheduling_window_days' => max(1, (int) $this->settings->get(
                'ORDER_SCHEDULING_WINDOW_DAYS',
                14
            )),
        ];
    }

    public function listForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($userId, $perPage);
    }

    public function findForUser(Order $order): Order
    {
        return $this->repository->findForUser($order);
    }

    public function createForUser(User $user, array $data): Order
    {
        $orderSettings = $this->orderSettings();
        $scheduled = $this->parseScheduledAt($data['scheduled_at'], $orderSettings['timezone']);
        $store = $this->stores->findById((int) $data['store_id']);

        if (!$store) {
            throw ValidationException::withMessages([
                'store_id' => 'A loja selecionada não existe.',
            ]);
        }

        $this->stores->validateScheduledPickup($store, $scheduled, $orderSettings);

        $items = collect($data['items']);
        $productIds = $items->pluck('product_id')->unique()->values()->all();
        $variantIds = $items->pluck('variant_id')->filter()->unique()->values()->all();
        $products = $this->products->findActiveForOrder($productIds);
        $variants = $this->products->findActiveVariantsForOrder($variantIds);

        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages([
                'items' => 'Um ou mais produtos não estão disponíveis.',
            ]);
        }

        if ($variants->count() !== count($variantIds)) {
            throw ValidationException::withMessages([
                'items' => 'Uma ou mais opções de pack não estão disponíveis.',
            ]);
        }

        $lineItems = $this->buildOrderLineItems($items, $products, $variants);

        return $this->repository->createWithItems(
            $user->id,
            (int) $data['store_id'],
            $scheduled->copy()->timezone('UTC'),
            $data['notes'] ?? null,
            $lineItems
        );
    }

    public function cancelForUser(Order $order): Order
    {
        if (!in_array($order->status, ['placed', 'accepted'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Este pedido não pode ser cancelado.',
            ]);
        }

        $orderSettings = $this->orderSettings();
        $timezone = $orderSettings['timezone'];
        $cancelMinutes = max(0, (int) $orderSettings['cancel_minutes']);
        $now = Carbon::now($timezone);
        $scheduled = Carbon::parse($order->scheduled_at, 'UTC')->timezone($timezone);
        $deadline = $scheduled->copy()->subMinutes($cancelMinutes);

        if ($now->greaterThan($deadline)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'O prazo para cancelamento deste pedido expirou.',
            ]);
        }

        return $this->repository->cancel($order, [
            'status' => 'canceled',
            'cancelled_at' => Carbon::now('UTC'),
        ]);
    }

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

    protected function parseScheduledAt(string $value, string $timezone): Carbon
    {
        return Carbon::parse($value, $timezone);
    }

    /**
     * @param SupportCollection<int, array<string, mixed>> $items
     * @param Collection<int, Product> $products
     * @param Collection<int, ProductVariant> $variants
     * @return array<int, array<string, mixed>>
     */
    protected function buildOrderLineItems(
        SupportCollection $items,
        Collection $products,
        Collection $variants
    ): array {
        return $items->map(function (array $item) use ($products, $variants): array {
            /** @var Product $product */
            $product = $products->get($item['product_id']);
            /** @var ProductVariant|null $variant */
            $variant = $item['variant_id'] ? $variants->get($item['variant_id']) : null;
            $quantity = (int) $item['quantity'];

            if ($variant && $variant->product_id !== $product->id) {
                throw ValidationException::withMessages([
                    'items' => 'A opção selecionada não pertence ao produto escolhido.',
                ]);
            }

            $flavors = isset($item['flavors']) && is_array($item['flavors']) ? $item['flavors'] : [];
            $allowedFlavorIds = $product->allowedFlavors->pluck('id');

            if (!$variant && $flavors !== []) {
                throw ValidationException::withMessages([
                    'items' => 'Os sabores só podem ser informados para packs.',
                ]);
            }

            if ($variant) {
                if (count($flavors) < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Selecione pelo menos 1 sabor para o pack escolhido.',
                    ]);
                }

                $maxFlavors = (int) $variant->max_flavors;
                if ($maxFlavors < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'O pack selecionado não aceita sabores no momento.',
                    ]);
                }

                if (count($flavors) > $maxFlavors) {
                    throw ValidationException::withMessages([
                        'items' => 'Você selecionou mais sabores do que o permitido para este pack.',
                    ]);
                }

                $hasInvalidFlavor = collect($flavors)->contains(
                    fn ($flavorId) => !$allowedFlavorIds->contains((int) $flavorId)
                );

                if ($hasInvalidFlavor) {
                    throw ValidationException::withMessages([
                        'items' => 'Um ou mais sabores informados não são permitidos para este artigo.',
                    ]);
                }
            }

            $price = $variant ? (float) $variant->price : (float) $product->price;
            $lineTotal = $price * $quantity;

            return [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name_snapshot' => $variant ? $variant->name : $product->name,
                'price_snapshot' => $price,
                'quantity' => $quantity,
                'options' => $flavors !== [] ? ['flavors' => $flavors] : null,
                'total' => $lineTotal,
            ];
        })->all();
    }
}
