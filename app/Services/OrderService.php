<?php

namespace App\Services;

use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WhatsAppQueueItem;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    private const SLOT_WINDOWS = [
        'manha' => ['start' => 0, 'end' => 11 * 60 + 59],
        'tarde' => ['start' => 12 * 60, 'end' => 17 * 60 + 59],
        'noite' => ['start' => 18 * 60, 'end' => 23 * 60 + 59],
    ];

    public function __construct(
        protected OrderRepository $repository,
        protected ProductRepository $products,
        protected AdminFlavorService $flavors,
        protected SettingService $settings,
        protected StoreService $stores,
        protected WhatsAppMessageFormatter $messages,
        protected WhatsAppQueueService $whatsAppQueue,
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
                'ORDER_SCHEDULING_WINDOW_DAYS', 15)
            ),
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

    public function whatsappOrderRecipient(): string
    {
        return trim((string) $this->settings->get('WHATSAPP_ORDER_TO', ''));
    }

    public function createForUser(User $actor, array $data): Order
    {
        $orderSettings = $this->orderSettings();
        $scheduled = $this->parseScheduledAt($data['scheduled_at'], $orderSettings['timezone']);
        $store = $this->stores->findById((int) $data['store_id']);

        if (! $store) {
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
        $resolvedCustomer = $this->resolveCustomerContext($actor, $data);

        $order = $this->repository->createWithItems(
            $resolvedCustomer['user_id'],
            (int) $data['store_id'],
            $resolvedCustomer['customer_name'],
            $resolvedCustomer['customer_contact'],
            $data['payment_status'] ?? null,
            $data['slot'] ?? null,
            $scheduled->copy()->timezone('UTC'),
            $data['notes'] ?? null,
            $lineItems
        );

        if ($resolvedCustomer['notification_contact']) {
            try {
                $recipient = $this->whatsappOrderRecipient();
                $flavorIds = $items
                    ->flatMap(fn (array $item) => isset($item['flavors']) && is_array($item['flavors']) ? $item['flavors'] : [])
                    ->map(fn ($flavorId) => (int) $flavorId)
                    ->filter(fn (int $flavorId) => $flavorId > 0)
                    ->unique()
                    ->values()
                    ->all();
                $flavorNamesById = $this->flavors->namesByIds($flavorIds);
                $message = $this->messages->orderPlacedSnapshot(
                    (string) $resolvedCustomer['notification_name'],
                    (string) $resolvedCustomer['notification_contact'],
                    $scheduled->copy()->timezone($orderSettings['timezone']),
                    $lineItems,
                    $flavorNamesById
                );

                $queueItem = $this->whatsAppQueue->enqueue([
                    'type' => WhatsAppQueueItem::TYPE_ORDER_PLACED,
                    'entity_type' => 'order',
                    'entity_id' => $order->id,
                    'recipient_name' => $resolvedCustomer['notification_name'],
                    'phone' => $recipient,
                    'message' => $message,
                ]);

                if ($recipient === '') {
                    $this->whatsAppQueue->markFailed($queueItem, 'WHATSAPP_ORDER_TO não configurado.');
                } else {
                    SendOrderPlacedWhatsAppJob::dispatch($queueItem->id)
                        ->onQueue('notifications')
                        ->afterCommit();
                }
            } catch (\Throwable $exception) {
                Log::warning('[OrderService] Falha ao enfileirar WhatsApp do pedido', [
                    'order_id' => $order->id,
                    'user_id' => $resolvedCustomer['user_id'],
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $order;
    }

    public function availabilityDates(array $data): array
    {
        $settings = $this->orderSettings();
        $store = $this->findAvailableStore((int) $data['store_id']);

        return [
            'store_id' => $store->id,
            'timezone' => $settings['timezone'],
            'dates' => $this->stores->availablePickupDates($store, $settings),
        ];
    }

    public function availabilityHours(array $data): array
    {
        $settings = $this->orderSettings();
        $store = $this->findAvailableStore((int) $data['store_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], $settings['timezone']);

        return [
            'store_id' => $store->id,
            'date' => $date->format('Y-m-d'),
            'timezone' => $settings['timezone'],
            'hours' => $this->stores->availablePickupHours($store, $date, $settings),
        ];
    }

    public function availabilityMinutes(array $data): array
    {
        $settings = $this->orderSettings();
        $store = $this->findAvailableStore((int) $data['store_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], $settings['timezone']);

        return [
            'store_id' => $store->id,
            'date' => $date->format('Y-m-d'),
            'hour' => $data['hour'],
            'timezone' => $settings['timezone'],
            'minute_options' => $this->stores->availablePickupMinutes($store, $date, $data['hour'], $settings),
        ];
    }

    public function availabilitySlots(array $data): array
    {
        $settings = $this->orderSettings();
        $store = $this->findAvailableStore((int) $data['store_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], $settings['timezone']);
        $minuteOptions = $this->stores->availablePickupSlots($store, $date, $settings);

        return [
            'store_id' => $store->id,
            'date' => $date->format('Y-m-d'),
            'timezone' => $settings['timezone'],
            'slots' => array_map(
                fn (string $slot): array => [
                    'slot' => $slot,
                    'state' => $this->slotStateFromMinuteOptions($slot, $minuteOptions),
                ],
                array_keys(self::SLOT_WINDOWS)
            ),
        ];
    }

    public function cancelForUser(Order $order): Order
    {
        if (! in_array($order->status, ['placed', 'accepted'], true)) {
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
        return $this->repository->paginateForAdmin(
            $this->normalizeAdminFilters($filters),
            $perPage
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   orders: LengthAwarePaginator,
     *   filters: array<string, mixed>,
     *   slotLabels: array<string, string>,
     *   selectedDayLabel: string,
     *   summary: array<string, mixed>
     * }
     */
    public function dailyPlanning(array $filters, int $perPage = 20): array
    {
        $preparedFilters = $this->prepareDailyPlanningFilters($filters);
        $normalizedFilters = $this->normalizeAdminFilters($preparedFilters);
        $orders = $this->repository->paginateForAdmin($normalizedFilters, $perPage);
        $allOrders = $this->repository->listForAdmin($normalizedFilters);

        return [
            'orders' => $orders,
            'filters' => $preparedFilters,
            'slotLabels' => $this->slotLabels(),
            'selectedDayLabel' => Carbon::createFromFormat('Y-m-d', $preparedFilters['day'], $this->orderSettings()['timezone'])
                ->translatedFormat('d/m/Y'),
            'summary' => $this->buildDailyPlanningSummary($allOrders),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   orders: Collection<int, Order>,
     *   filters: array<string, mixed>,
     *   slotLabels: array<string, string>,
     *   selectedDayLabel: string,
     *   summary: array<string, mixed>
     * }
     */
    public function dailyPlanningDataset(array $filters): array
    {
        $preparedFilters = $this->prepareDailyPlanningFilters($filters);
        $normalizedFilters = $this->normalizeAdminFilters($preparedFilters);
        $orders = $this->repository->listForAdmin($normalizedFilters);

        return [
            'orders' => $orders,
            'filters' => $preparedFilters,
            'slotLabels' => $this->slotLabels(),
            'selectedDayLabel' => Carbon::createFromFormat('Y-m-d', $preparedFilters['day'], $this->orderSettings()['timezone'])
                ->translatedFormat('d/m/Y'),
            'summary' => $this->buildDailyPlanningSummary($orders),
        ];
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

        $before = [
            'status' => $order->status,
            'cancelled_at' => $this->normalizeStoredOrderDateTime($order, 'cancelled_at'),
        ];

        $payload = ['status' => $newStatus];

        if ($newStatus === 'canceled') {
            $payload['cancelled_at'] = $order->cancelled_at ?? now('UTC');
        } elseif ($order->cancelled_at !== null) {
            $payload['cancelled_at'] = null;
        }

        $after = [
            'status' => $newStatus,
            'cancelled_at' => $this->normalizeDateTimeValue($payload['cancelled_at'] ?? $order->cancelled_at),
        ];

        return $this->repository->updateStatus($order, $payload, $this->makeHistoryPayload(
            'status_changed',
            $this->calculateChanges($before, $after)
        ));
    }

    public function updateForAdmin(Order $order, array $data): Order
    {
        $this->assertOrderEditable($order);
        $order->loadMissing('items');

        $orderSettings = $this->orderSettings();
        $scheduled = $this->parseScheduledAt($data['scheduled_at'], $orderSettings['timezone']);
        $store = $this->stores->findById((int) $data['store_id']);

        if (! $store) {
            throw ValidationException::withMessages([
                'store_id' => 'A loja selecionada não existe.',
            ]);
        }

        $this->stores->validateScheduledPickup($store, $scheduled, $orderSettings);
        $this->assertSlotAvailableForSchedule($store, $scheduled, $data['slot'] ?? null, $orderSettings);

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
        $before = $this->snapshotOrderForHistory($order);
        $after = $this->snapshotOrderForHistory($order, [
            'customer_name' => $this->normalizeNullableText($data['customer_name'] ?? null),
            'customer_contact' => $this->normalizeNullableText($data['customer_contact'] ?? null),
            'store_id' => (int) $data['store_id'],
            'payment_status' => $data['payment_status'] ?? null,
            'slot' => $data['slot'] ?? null,
            'scheduled_at' => $scheduled->copy()->timezone('UTC'),
            'notes' => $this->normalizeNullableText($data['notes'] ?? null),
            'items' => $lineItems,
        ]);

        return $this->repository->updateWithItems($order, [
            'customer_name' => $this->normalizeNullableText($data['customer_name'] ?? null),
            'customer_contact' => $this->normalizeNullableText($data['customer_contact'] ?? null),
            'store_id' => (int) $data['store_id'],
            'payment_status' => $data['payment_status'] ?? null,
            'slot' => $data['slot'] ?? null,
            'scheduled_at' => $scheduled->copy()->timezone('UTC'),
            'notes' => $this->normalizeNullableText($data['notes'] ?? null),
        ], $lineItems, $this->makeHistoryPayload(
            'updated',
            $this->calculateChanges($before, $after)
        ));
    }

    protected function assertStatusTransition(Order $order, string $newStatus): void
    {
        if (! array_key_exists($newStatus, self::STATUS_LABELS)) {
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

    protected function assertOrderEditable(Order $order): void
    {
        if (! $this->canEdit($order)) {
            throw ValidationException::withMessages([
                'status' => 'Esta encomenda já não pode ser corrigida no fluxo operacional atual.',
            ]);
        }
    }

    public function canEdit(Order $order, ?Carbon $now = null): bool
    {
        if (! in_array($order->status, ['placed', 'accepted'], true)) {
            return false;
        }

        if (! $order->scheduled_at) {
            return true;
        }

        $settings = $this->orderSettings();
        $timezone = $settings['timezone'];
        $editMinutes = max(0, (int) $settings['cancel_minutes']);
        $current = ($now ?? Carbon::now($timezone))->copy()->timezone($timezone);
        $scheduled = Carbon::parse($order->scheduled_at, 'UTC')->timezone($timezone);
        $deadline = $scheduled->copy()->subMinutes($editMinutes);

        return $current->lessThanOrEqualTo($deadline);
    }

    protected function parseScheduledAt(string $value, string $timezone): Carbon
    {
        return Carbon::parse($value, $timezone);
    }

    protected function assertSlotAvailableForSchedule($store, Carbon $scheduled, ?string $slot, array $settings): void
    {
        if ($slot === null || $slot === '') {
            return;
        }

        if ($this->slotStateFromSchedule($store, $scheduled, $slot, $settings) === 'bloqueado') {
            throw ValidationException::withMessages([
                'slot' => 'O slot operacional selecionado já não está disponível para essa data.',
            ]);
        }
    }

    protected function findAvailableStore(int $storeId)
    {
        $store = $this->stores->findById($storeId);

        if (! $store) {
            throw ValidationException::withMessages([
                'store_id' => 'A loja selecionada não existe.',
            ]);
        }

        return $store;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *   user_id: int|null,
     *   customer_name: string|null,
     *   customer_contact: string|null,
     *   notification_name: string|null,
     *   notification_contact: string|null
     * }
     */
    protected function resolveCustomerContext(User $actor, array $data): array
    {
        $requestedUserId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $requestedCustomer = $requestedUserId ? User::query()->find($requestedUserId) : null;

        if ($requestedCustomer) {
            return [
                'user_id' => $requestedCustomer->id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_contact' => $data['customer_contact'] ?? null,
                'notification_name' => $data['customer_name'] ?? $requestedCustomer->name,
                'notification_contact' => $data['customer_contact'] ?? $requestedCustomer->phone,
            ];
        }

        $customerName = $this->normalizeNullableText($data['customer_name'] ?? null);
        $customerContact = $this->normalizeNullableText($data['customer_contact'] ?? null);

        if ($customerName !== null || $customerContact !== null) {
            return [
                'user_id' => null,
                'customer_name' => $customerName,
                'customer_contact' => $customerContact,
                'notification_name' => $customerName,
                'notification_contact' => $customerContact,
            ];
        }

        return [
            'user_id' => $actor->id,
            'customer_name' => null,
            'customer_contact' => null,
            'notification_name' => $actor->name,
            'notification_contact' => $actor->phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeAdminFilters(array $filters): array
    {
        $timezone = $this->orderSettings()['timezone'];

        foreach (['scheduled_from', 'scheduled_to'] as $key) {
            if (empty($filters[$key])) {
                continue;
            }

            $filters[$key] = Carbon::parse((string) $filters[$key], $timezone)->timezone('UTC');
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareDailyPlanningFilters(array $filters): array
    {
        $timezone = $this->orderSettings()['timezone'];
        $day = isset($filters['day']) && is_string($filters['day']) && $filters['day'] !== ''
            ? $filters['day']
            : Carbon::now($timezone)->format('Y-m-d');

        $dayStart = Carbon::createFromFormat('Y-m-d', $day, $timezone)->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();

        $filters['day'] = $day;
        $filters['scheduled_from'] = $dayStart->format('Y-m-d H:i:s');
        $filters['scheduled_to'] = $dayEnd->format('Y-m-d H:i:s');

        return $filters;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    protected function buildDailyPlanningSummary(Collection $orders): array
    {
        $slotCounts = [
            'manha' => 0,
            'tarde' => 0,
            'noite' => 0,
            'sem_slot' => 0,
        ];

        $itemQuantity = 0;

        foreach ($orders as $order) {
            $slot = $order->slot ?: 'sem_slot';
            $slotCounts[$slot] = ($slotCounts[$slot] ?? 0) + 1;
            $itemQuantity += (int) $order->items->sum('quantity');
        }

        return [
            'orderCount' => $orders->count(),
            'itemQuantity' => $itemQuantity,
            'paidCount' => $orders->where('payment_status', 'paid')->count(),
            'attentionCount' => $orders->filter(fn (Order $order): bool => in_array($order->status, ['placed', 'accepted'], true))->count(),
            'slotCounts' => $slotCounts,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function slotLabels(): array
    {
        return [
            'manha' => 'Manhã',
            'tarde' => 'Tarde',
            'noite' => 'Noite',
            'sem_slot' => 'Sem slot',
        ];
    }

    protected function normalizeNullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function snapshotOrderForHistory(Order $order, array $overrides = []): array
    {
        $items = array_key_exists('items', $overrides)
            ? $this->normalizeLineItemsForHistory($overrides['items'])
            : $this->normalizeExistingItemsForHistory($order->items);

        $total = array_key_exists('items', $overrides)
            ? round((float) collect($overrides['items'])->sum(fn (array $item): float => (float) ($item['total'] ?? 0)), 2)
            : round((float) $order->total, 2);

        return [
            'customer_name' => $overrides['customer_name'] ?? $order->customer_name,
            'customer_contact' => $overrides['customer_contact'] ?? $order->customer_contact,
            'store_id' => $overrides['store_id'] ?? $order->store_id,
            'payment_status' => $overrides['payment_status'] ?? $order->payment_status,
            'slot' => $overrides['slot'] ?? $order->slot,
            'scheduled_at' => array_key_exists('scheduled_at', $overrides)
                ? $this->normalizeDateTimeValue($overrides['scheduled_at'])
                : $this->normalizeStoredOrderDateTime($order, 'scheduled_at'),
            'notes' => $overrides['notes'] ?? $order->notes,
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @param  iterable<int, OrderItem>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeExistingItemsForHistory(iterable $items): array
    {
        return collect($items)
            ->map(fn (OrderItem $item): array => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'name_snapshot' => $item->name_snapshot,
                'price_snapshot' => round((float) $item->price_snapshot, 2),
                'quantity' => (int) $item->quantity,
                'options' => $item->options,
                'total' => round((float) $item->total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeLineItemsForHistory(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): array => [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'name_snapshot' => $item['name_snapshot'],
                'price_snapshot' => round((float) $item['price_snapshot'], 2),
                'quantity' => (int) $item['quantity'],
                'options' => $item['options'] ?? null,
                'total' => round((float) $item['total'], 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{from:mixed,to:mixed}>
     */
    protected function calculateChanges(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $afterValue) {
            $beforeValue = $before[$key] ?? null;

            if ($beforeValue == $afterValue) {
                continue;
            }

            $changes[$key] = [
                'from' => $beforeValue,
                'to' => $afterValue,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, array{from:mixed,to:mixed}>  $changes
     * @return array<string, mixed>|null
     */
    protected function makeHistoryPayload(string $action, array $changes): ?array
    {
        if ($changes === []) {
            return null;
        }

        return [
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
        ];
    }

    protected function normalizeDateTimeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->copy()->utc()->format('Y-m-d\TH:i:sP');
        }

        return Carbon::parse((string) $value)->utc()->format('Y-m-d\TH:i:sP');
    }

    protected function normalizeStoredOrderDateTime(Order $order, string $attribute): ?string
    {
        $raw = $order->getRawOriginal($attribute);

        if ($raw === null || $raw === '') {
            return null;
        }

        return Carbon::parse((string) $raw, 'UTC')->format('Y-m-d\TH:i:sP');
    }

    protected function slotStateFromSchedule($store, Carbon $scheduled, string $slot, array $settings): string
    {
        $date = $scheduled->copy()->timezone($settings['timezone'])->startOfDay();
        $minuteOptions = $this->stores->availablePickupSlots($store, $date, $settings);

        return $this->slotStateFromMinuteOptions($slot, $minuteOptions);
    }

    /**
     * @param  array<int, string>  $minuteOptions
     */
    protected function slotStateFromMinuteOptions(string $slot, array $minuteOptions): string
    {
        $window = self::SLOT_WINDOWS[$slot] ?? null;

        if ($window === null) {
            return 'bloqueado';
        }

        $count = collect($minuteOptions)
            ->filter(function (string $option) use ($window): bool {
                [$hour, $minute] = array_map('intval', explode(':', $option));
                $totalMinutes = ($hour * 60) + $minute;

                return $totalMinutes >= $window['start'] && $totalMinutes <= $window['end'];
            })
            ->count();

        if ($count === 0) {
            return 'bloqueado';
        }

        if ($count <= 6) {
            return 'limitado';
        }

        return 'disponível';
    }

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ProductVariant>  $variants
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
            $variantId = $item['variant_id'] ?? null;
            $variant = $variantId ? $variants->get($variantId) : null;
            $quantity = (int) $item['quantity'];

            if ($variant && $variant->product_id !== $product->id) {
                throw ValidationException::withMessages([
                    'items' => 'A opção selecionada não pertence ao produto escolhido.',
                ]);
            }

            $flavors = isset($item['flavors']) && is_array($item['flavors']) ? $item['flavors'] : [];
            $allowedFlavorIds = $product->allowedFlavors->pluck('id');

            if (! $variant && $flavors !== []) {
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
                    fn ($flavorId) => ! $allowedFlavorIds->contains((int) $flavorId)
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
