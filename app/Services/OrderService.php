<?php

namespace App\Services;

use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
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
        protected PlanningSlotCapacityService $slotCapacities,
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
            'settings_version' => (int) $this->settings->get('SETTINGS_VERSION', 1),
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

        return \Illuminate\Support\Facades\DB::transaction(function () use ($actor, $data, $orderSettings, $scheduled, $store) {
            // Apply slot-specific lead time precedence: Slot Rule > Global Rule (whichever is more restrictive/larger)
            $slot = $data['slot'] ?? null;
            if ($slot) {
                $slotRules = $this->slotCapacities->getOperationalRules();
                $slotLeadTime = (int) ($slotRules['lead_times'][$slot] ?? 0);
                if ($slotLeadTime > (int) $orderSettings['minimum_minutes']) {
                    $orderSettings['minimum_minutes'] = $slotLeadTime;
                }
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
        });
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
        $consumedCapacity = $this->consumedCapacityForStoreDay($store->id, $date);
        $baseCapacities = $this->slotCapacities->getBaseCapacities();

        return [
            'store_id' => $store->id,
            'date' => $date->format('Y-m-d'),
            'timezone' => $settings['timezone'],
            'slot_capacities' => $baseCapacities,
            'slots' => array_map(
                fn (string $slot): array => [
                    'slot' => $slot,
                    'state' => $this->slotStateFromMinuteOptions(
                        $slot,
                        $minuteOptions,
                        (int) ($consumedCapacity[$slot] ?? 0),
                        $date
                    ),
                    'capacity' => $baseCapacities[$slot] ?? 0,
                    'consumed' => (int) ($consumedCapacity[$slot] ?? 0),
                    'remaining' => max(0, (int) (($baseCapacities[$slot] ?? 0) - ($consumedCapacity[$slot] ?? 0))),
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
     *   summary: array<string, mixed>,
     *   slotOccupancy: array<string, array<string, mixed>>
     * }
     */
    public function dailyPlanningDataset(array $filters): array
    {
        $preparedFilters = $this->prepareDailyPlanningFilters($filters);
        $normalizedFilters = $this->normalizeAdminFilters($preparedFilters);
        $orders = $this->repository->listForAdmin($normalizedFilters);
        $summary = $this->buildPlanningSummary($orders);
        $day = Carbon::createFromFormat('Y-m-d', $preparedFilters['day'], $this->orderSettings()['timezone'])->startOfDay();
        $storeContext = $this->resolvePlanningSlotOccupancyContext(
            $orders,
            isset($preparedFilters['store_id']) ? (int) $preparedFilters['store_id'] : null
        );

        return [
            'orders' => $orders,
            'filters' => $preparedFilters,
            'slotLabels' => $this->slotLabels(),
            'selectedDayLabel' => Carbon::createFromFormat('Y-m-d', $preparedFilters['day'], $this->orderSettings()['timezone'])
                ->translatedFormat('d/m/Y'),
            'summary' => $summary,
            'slotOccupancy' => $this->buildPlanningSlotOccupancyForDay(
                $orders,
                $day,
                $summary['slotCounts'],
                $storeContext['store'] ?? null
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   orders: Collection<int, Order>,
     *   filters: array<string, mixed>,
     *   slotLabels: array<string, string>,
     *   selectedWeekLabel: string,
     *   summary: array<string, mixed>,
     *   slotOccupancy: array<string, array<string, mixed>>,
     *   daySummaries: array<string, array<string, mixed>>
     * }
     */
    public function weeklyPlanningDataset(array $filters): array
    {
        $preparedFilters = $this->prepareWeeklyPlanningFilters($filters);
        $normalizedFilters = $this->normalizeAdminFilters($preparedFilters);
        $orders = $this->repository->listForAdmin($normalizedFilters);
        $summary = $this->buildPlanningSummary($orders);
        $consumedCounts = $this->slotCapacities->countConsumedCapacity($orders);
        $storeContext = $this->resolvePlanningSlotOccupancyContext(
            $orders,
            isset($preparedFilters['store_id']) ? (int) $preparedFilters['store_id'] : null
        );

        return [
            'orders' => $orders,
            'filters' => $preparedFilters,
            'slotLabels' => $this->slotLabels(),
            'selectedWeekLabel' => $this->buildPlanningPeriodLabel(
                $preparedFilters['week_start'],
                $preparedFilters['week_end']
            ),
            'summary' => $summary,
            'slotOccupancy' => $this->buildAggregatePlanningSlotOccupancy(
                array_merge(['sem_slot' => (int) ($summary['slotCounts']['sem_slot'] ?? 0)], $consumedCounts),
                'O agregado semanal combina vários dias operacionais; consulte a ocupação oficial por dia para validar disponibilidade.'
            ),
            'daySummaries' => $this->buildPlanningDaySummaries(
                $orders,
                $preparedFilters['week_start'],
                $preparedFilters['week_end'],
                $storeContext['store'] ?? null
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   orders: Collection<int, Order>,
     *   filters: array<string, mixed>,
     *   slotLabels: array<string, string>,
     *   selectedPeriodLabel: string,
     *   summary: array<string, mixed>,
     *   slotOccupancy: array<string, array<string, mixed>>,
     *   daySummaries: array<string, array<string, mixed>>
     * }
     */
    public function periodPlanningDataset(array $filters): array
    {
        $preparedFilters = $this->preparePeriodPlanningFilters($filters);
        $normalizedFilters = $this->normalizeAdminFilters($preparedFilters);
        $orders = $this->repository->listForAdmin($normalizedFilters);
        $summary = $this->buildPlanningSummary($orders);
        $consumedCounts = $this->slotCapacities->countConsumedCapacity($orders);
        $storeContext = $this->resolvePlanningSlotOccupancyContext(
            $orders,
            isset($preparedFilters['store_id']) ? (int) $preparedFilters['store_id'] : null
        );
        $periodStartDay = Carbon::createFromFormat('Y-m-d', $preparedFilters['start_date'], $this->orderSettings()['timezone'])->startOfDay();
        $isSingleDayPeriod = $preparedFilters['start_date'] === $preparedFilters['end_date'];

        return [
            'orders' => $orders,
            'filters' => [
                'start_date' => $preparedFilters['start_date'],
                'end_date' => $preparedFilters['end_date'],
            ],
            'slotLabels' => $this->slotLabels(),
            'selectedPeriodLabel' => $this->buildPlanningPeriodLabel(
                $preparedFilters['start_date'],
                $preparedFilters['end_date']
            ),
            'summary' => $summary,
            'slotOccupancy' => $isSingleDayPeriod
                ? $this->buildPlanningSlotOccupancyForDay(
                    $orders,
                    $periodStartDay,
                    $summary['slotCounts'],
                    $storeContext['store'] ?? null
                )
                : $this->buildAggregatePlanningSlotOccupancy(
                    array_merge(['sem_slot' => (int) ($summary['slotCounts']['sem_slot'] ?? 0)], $consumedCounts),
                    'O agregado do período combina vários dias operacionais; consulte a ocupação oficial por dia para validar disponibilidade.'
                ),
            'daySummaries' => $this->buildPlanningDaySummaries(
                $orders,
                $preparedFilters['start_date'],
                $preparedFilters['end_date'],
                $storeContext['store'] ?? null
            ),
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

        return \Illuminate\Support\Facades\DB::transaction(function () use ($order, $newStatus) {
            // If the new status consumes capacity and the old one didn't, we must check capacity.
            $consumingStatuses = PlanningSlotCapacityService::CAPACITY_CONSUMING_STATUSES;
            $willConsume = in_array($newStatus, $consumingStatuses, true);
            $didConsume = in_array($order->status, $consumingStatuses, true);

            if ($willConsume && ! $didConsume) {
                $this->assertSlotAvailableForSchedule(
                    $order->store,
                    Carbon::parse($order->scheduled_at),
                    $order->slot,
                    $this->orderSettings(),
                    $order->id
                );
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
        });
    }

    public function updateForAdmin(Order $order, array $data): Order
    {
        $this->assertOrderEditable($order);
        $order->loadMissing(['items', 'store']);

        $orderSettings = $this->orderSettings();
        $scheduled = $this->parseScheduledAt($data['scheduled_at'], $orderSettings['timezone']);
        $store = $this->stores->findById((int) $data['store_id']);

        if (! $store) {
            throw ValidationException::withMessages([
                'store_id' => 'A loja selecionada não existe.',
            ]);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($order, $data, $orderSettings, $scheduled, $store) {
            $this->stores->validateScheduledPickup($store, $scheduled, $orderSettings);
            $this->assertSlotAvailableForSchedule($store, $scheduled, $data['slot'] ?? null, $orderSettings, $order->id);

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
                'store_id' => $this->storeSnapshotForHistory((int) $data['store_id'], $store->name),
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
        });
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

    protected function assertSlotAvailableForSchedule($store, Carbon $scheduled, ?string $slot, array $settings, ?int $ignoreOrderId = null): void
    {
        if ($slot === null || $slot === '') {
            return;
        }

        $reason = $this->slotBlockReasonFromSchedule($store, $scheduled, $slot, $settings, $ignoreOrderId);

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'slot' => [$reason],
            ]);
        }
    }

    protected function slotBlockReasonFromSchedule($store, Carbon $scheduled, string $slot, array $settings, ?int $ignoreOrderId = null): ?string
    {
        $date = $scheduled->copy()->timezone($settings['timezone'])->startOfDay();
        $minuteOptions = $this->stores->availablePickupSlots($store, $date, $settings);
        $consumedCapacity = $this->consumedCapacityForStoreDay($store->id, $date, $ignoreOrderId);

        return $this->slotCapacities->getSlotBlockReason(
            $slot,
            $minuteOptions,
            self::SLOT_WINDOWS[$slot] ?? [],
            (int) ($consumedCapacity[$slot] ?? 0),
            $date
        );
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
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareWeeklyPlanningFilters(array $filters): array
    {
        $timezone = $this->orderSettings()['timezone'];
        $weekStart = isset($filters['week_start']) && is_string($filters['week_start']) && $filters['week_start'] !== ''
            ? Carbon::createFromFormat('Y-m-d', $filters['week_start'], $timezone)->startOfWeek(Carbon::MONDAY)
            : Carbon::now($timezone)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $filters['week_start'] = $weekStart->format('Y-m-d');
        $filters['week_end'] = $weekEnd->format('Y-m-d');
        $filters['scheduled_from'] = $weekStart->format('Y-m-d H:i:s');
        $filters['scheduled_to'] = $weekEnd->format('Y-m-d H:i:s');

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function preparePeriodPlanningFilters(array $filters): array
    {
        $timezone = $this->orderSettings()['timezone'];
        $startDate = isset($filters['start_date']) && is_string($filters['start_date']) && $filters['start_date'] !== ''
            ? Carbon::createFromFormat('Y-m-d', $filters['start_date'], $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();
        $endDate = isset($filters['end_date']) && is_string($filters['end_date']) && $filters['end_date'] !== ''
            ? Carbon::createFromFormat('Y-m-d', $filters['end_date'], $timezone)->endOfDay()
            : $startDate->copy()->endOfDay();

        $filters['start_date'] = $startDate->format('Y-m-d');
        $filters['end_date'] = $endDate->format('Y-m-d');
        $filters['scheduled_from'] = $startDate->format('Y-m-d H:i:s');
        $filters['scheduled_to'] = $endDate->format('Y-m-d H:i:s');

        return $filters;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    protected function buildDailyPlanningSummary(Collection $orders): array
    {
        return $this->buildPlanningSummary($orders);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    protected function buildPlanningSummary(Collection $orders): array
    {
        $slotCounts = $this->emptyPlanningSlotCounts();

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
     * @param  Collection<int, Order>  $orders
     * @return array<string, array<string, mixed>>
     */
    protected function buildPlanningDaySummaries(
        Collection $orders,
        string $startDate,
        string $endDate,
        ?Store $contextStore = null
    ): array
    {
        $timezone = $this->orderSettings()['timezone'];
        $periodStart = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay();
        $periodEnd = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->startOfDay();
        $daySummaries = [];
        $ordersByDay = [];

        for ($day = $periodStart->copy(); $day->lessThanOrEqualTo($periodEnd); $day->addDay()) {
            $daySummaries[$day->format('Y-m-d')] = [
                'label' => $day->translatedFormat('l · d/m'),
                'orderCount' => 0,
                'itemQuantity' => 0,
                'paidCount' => 0,
                'attentionCount' => 0,
                'slotCounts' => $this->emptyPlanningSlotCounts(),
            ];
        }

        foreach ($orders as $order) {
            $scheduledAt = $order->scheduledAtForDisplay($timezone);

            if ($scheduledAt === null) {
                continue;
            }

            $dayKey = $scheduledAt->format('Y-m-d');

            if (! isset($daySummaries[$dayKey])) {
                continue;
            }

            $ordersByDay[$dayKey] ??= collect();
            $ordersByDay[$dayKey]->push($order);

            $slot = $order->slot ?: 'sem_slot';
            $daySummaries[$dayKey]['orderCount']++;
            $daySummaries[$dayKey]['itemQuantity'] += (int) $order->items->sum('quantity');
            $daySummaries[$dayKey]['slotCounts'][$slot] = ($daySummaries[$dayKey]['slotCounts'][$slot] ?? 0) + 1;

            if ($order->payment_status === 'paid') {
                $daySummaries[$dayKey]['paidCount']++;
            }

            if (in_array($order->status, ['placed', 'accepted'], true)) {
                $daySummaries[$dayKey]['attentionCount']++;
            }
        }

        foreach ($daySummaries as $dayKey => $summary) {
            $dayOrders = $ordersByDay[$dayKey] ?? collect();
            $daySummaries[$dayKey]['slotOccupancy'] = $this->buildPlanningSlotOccupancyForDay(
                $dayOrders,
                Carbon::createFromFormat('Y-m-d', $dayKey, $timezone)->startOfDay(),
                $summary['slotCounts'],
                $contextStore
            );

            $daySummaries[$dayKey]['slot_counts'] = $daySummaries[$dayKey]['slotCounts'];
            $daySummaries[$dayKey]['slot_occupancy'] = $daySummaries[$dayKey]['slotOccupancy'];
            unset($daySummaries[$dayKey]['slotCounts'], $daySummaries[$dayKey]['slotOccupancy']);
        }

        ksort($daySummaries);

        return $daySummaries;
    }

    /**
     * @return array<string, int>
     */
    protected function emptyPlanningSlotCounts(): array
    {
        return [
            'manha' => 0,
            'tarde' => 0,
            'noite' => 0,
            'sem_slot' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $slotCounts
     * @return array<string, array<string, mixed>>
     */
    protected function buildPlanningSlotOccupancyForDay(
        iterable $orders,
        CarbonInterface $day,
        array $slotCounts,
        ?Store $contextStore = null
    ): array {
        $orders = collect($orders);
        $consumedCounts = $this->slotCapacities->countConsumedCapacity($orders);
        $context = $this->resolvePlanningSlotOccupancyContext(
            $orders,
            $contextStore?->id
        );

        if (($context['status'] ?? null) !== 'ready') {
            return $this->buildPlanningSlotOccupancyEntries(
                array_merge(['sem_slot' => (int) ($slotCounts['sem_slot'] ?? 0)], $consumedCounts),
                null,
                (string) ($context['reason'] ?? 'Contexto oficial insuficiente para determinar disponibilidade.')
            );
        }

        return $this->buildPlanningSlotOccupancyEntries(
            array_merge(['sem_slot' => (int) ($slotCounts['sem_slot'] ?? 0)], $consumedCounts),
            $this->buildOfficialSlotStatesForDay($context['store'], $day, $consumedCounts)
        );
    }

    /**
     * @param  array<string, int>  $slotCounts
     * @return array<string, array<string, mixed>>
     */
    protected function buildAggregatePlanningSlotOccupancy(array $slotCounts, string $reason): array
    {
        return $this->buildPlanningSlotOccupancyEntries($slotCounts, null, $reason);
    }

    /**
     * @return array{status: 'ready', store: Store}|array{status: 'insufficient_context', reason: string}
     */
    protected function resolvePlanningSlotOccupancyContext(iterable $orders, ?int $preferredStoreId = null): array
    {
        $orders = collect($orders);

        if ($preferredStoreId !== null && $preferredStoreId > 0) {
            $store = $this->stores->findById($preferredStoreId);

            if ($store instanceof Store) {
                return [
                    'status' => 'ready',
                    'store' => $store,
                ];
            }
        }

        if ($orders->isEmpty()) {
            return [
                'status' => 'insufficient_context',
                'reason' => 'Sem encomendas suficientes neste conjunto para determinar um contexto oficial único de disponibilidade.',
            ];
        }

        $storeIds = $orders
            ->pluck('store_id')
            ->filter(fn ($storeId): bool => is_numeric($storeId) && (int) $storeId > 0)
            ->map(fn ($storeId): int => (int) $storeId)
            ->unique()
            ->values();

        if ($storeIds->count() !== 1) {
            return [
                'status' => 'insufficient_context',
                'reason' => 'O conjunto atual inclui várias lojas; o backend não afirma um estado oficial agregado de disponibilidade sem contexto único.',
            ];
        }

        $store = $this->stores->findById($storeIds->first());

        if (! $store instanceof Store) {
            return [
                'status' => 'insufficient_context',
                'reason' => 'As encomendas atuais não trazem contexto oficial de loja suficiente para afirmar disponibilidade.',
            ];
        }

        return [
            'status' => 'ready',
            'store' => $store,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function buildOfficialSlotStatesForDay(Store $store, CarbonInterface $day, array $consumedCounts): array
    {
        $settings = $this->orderSettings();
        $date = Carbon::createFromFormat('Y-m-d', $day->format('Y-m-d'), $settings['timezone']);
        $minuteOptions = $this->stores->availablePickupSlots($store, $date, $settings);

        return collect(array_keys(self::SLOT_WINDOWS))
            ->mapWithKeys(fn (string $slot): array => [
                $slot => $this->slotStateFromMinuteOptions(
                    $slot,
                    $minuteOptions,
                    (int) ($consumedCounts[$slot] ?? 0),
                    $date
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, int>  $slotCounts
     * @param  array<string, string>|null  $slotStates
     * @return array<string, array<string, mixed>>
     */
    protected function buildPlanningSlotOccupancyEntries(
        array $slotCounts,
        ?array $slotStates,
        ?string $insufficientContextReason = null
    ): array {
        $occupancy = [];
        $slotKeys = array_values(array_unique(array_merge(
            array_keys($this->emptyPlanningSlotCounts()),
            array_keys($slotCounts)
        )));

        foreach ($slotKeys as $slot) {
            $count = (int) ($slotCounts[$slot] ?? 0);

            if ($slot === 'sem_slot') {
                $occupancy[$slot] = [
                    'count' => $count,
                    'label' => $this->slotLabels()[$slot] ?? $slot,
                    'state' => null,
                    'context_status' => 'not_applicable',
                    'context_reason' => 'Sem slot atribuído não representa uma janela oficial de capacidade.',
                ];

                continue;
            }

            if ($slotStates !== null && array_key_exists($slot, $slotStates)) {
                $occupancy[$slot] = [
                    'count' => $count,
                    'label' => $this->slotLabels()[$slot] ?? $slot,
                    'state' => $slotStates[$slot],
                    'context_status' => 'official',
                    'context_reason' => null,
                ];

                continue;
            }

            $occupancy[$slot] = [
                'count' => $count,
                'label' => $this->slotLabels()[$slot] ?? $slot,
                'state' => null,
                'context_status' => 'insufficient_context',
                'context_reason' => $insufficientContextReason
                    ?? 'Contexto oficial insuficiente para afirmar disponibilidade neste conjunto.',
            ];
        }

        return $occupancy;
    }

    protected function buildPlanningPeriodLabel(string $startDate, string $endDate): string
    {
        $timezone = $this->orderSettings()['timezone'];

        return sprintf(
            '%s - %s',
            Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->translatedFormat('d/m/Y'),
            Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->translatedFormat('d/m/Y')
        );
    }

    /**
     * @return array<string, string>
     */
    protected function slotLabels(): array
    {
        return [
            'manha' => $this->slotCapacities->slotLabel('manha'),
            'tarde' => $this->slotCapacities->slotLabel('tarde'),
            'noite' => $this->slotCapacities->slotLabel('noite'),
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
            'store_id' => array_key_exists('store_id', $overrides)
                ? $this->normalizeStoreSnapshotForHistory($overrides['store_id'])
                : $this->storeSnapshotForHistory((int) $order->store_id, $order->store?->name),
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
                'variant_name_snapshot' => $item->variant_id !== null ? $item->name_snapshot : null,
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
                'variant_name_snapshot' => $item['variant_name_snapshot'] ?? null,
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
     * @return array{id:int,name:string|null}
     */
    protected function storeSnapshotForHistory(int $id, ?string $name): array
    {
        return [
            'id' => $id,
            'name' => $this->normalizeNullableText($name),
        ];
    }

    /**
     * @return array{id:int,name:string|null}|mixed
     */
    protected function normalizeStoreSnapshotForHistory(mixed $value): mixed
    {
        if (is_array($value)) {
            return [
                'id' => (int) ($value['id'] ?? 0),
                'name' => $this->normalizeNullableText($value['name'] ?? null),
            ];
        }

        return $this->storeSnapshotForHistory((int) $value, null);
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

    /**
     * @param  array<int, string>  $minuteOptions
     */
    protected function slotStateFromMinuteOptions(string $slot, array $minuteOptions, int $consumedCount = 0, ?Carbon $date = null): string
    {
        return $this->slotCapacities->resolveSlotState(
            $slot,
            $minuteOptions,
            self::SLOT_WINDOWS[$slot] ?? null,
            $consumedCount,
            $date
        );
    }

    /**
     * @return array<string, int>
     */
    protected function consumedCapacityForStoreDay(int $storeId, CarbonInterface $day, ?int $ignoreOrderId = null): array
    {
        $settings = $this->orderSettings();
        $dayStartUtc = $day->copy()->timezone($settings['timezone'])->startOfDay()->utc();
        $dayEndUtc = $day->copy()->timezone($settings['timezone'])->endOfDay()->utc();

        return $this->repository->countScheduledBySlotForStoreDay(
            $storeId,
            $dayStartUtc,
            $dayEndUtc,
            PlanningSlotCapacityService::CAPACITY_CONSUMING_STATUSES,
            $ignoreOrderId
        );
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
                'variant_name_snapshot' => $variant?->name,
                'name_snapshot' => $variant ? $variant->name : $product->name,
                'price_snapshot' => $price,
                'quantity' => $quantity,
                'options' => $flavors !== [] ? ['flavors' => $flavors] : null,
                'total' => $lineTotal,
            ];
        })->all();
    }
}
