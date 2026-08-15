<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PlanningSlotCapacityService
{
    public const SETTING_KEY = 'ORDER_SLOT_BASE_CAPACITY';

    public const OPERATIONAL_RULES_KEY = 'ORDER_SLOT_OPERATIONAL_RULES';

    public const SLOT_MODE_SETTING_KEY = 'ORDER_SLOT_MODE';

    public const SLOT_MODE_PERIOD = 'periodo';

    public const SLOT_MODE_HOURLY = 'horario';

    /**
     * Global MVP scope only. Store overrides are explicitly out of scope for story 4.6.
     *
     * @var array<string, int>
     */
    public const DEFAULT_CAPACITIES = [
        'manha' => 12,
        'tarde' => 10,
        'noite' => 8,
    ];

    private const DEFAULT_HOURLY_CAPACITY = 10;

    /**
     * @var array<string, mixed>
     */
    public const DEFAULT_OPERATIONAL_RULES = [
        'lead_times' => [
            'manha' => 120,
            'tarde' => 60,
            'noite' => 60,
        ],
        'blocked_dates' => [],
    ];

    /**
     * @var array<int, string>
     */
    public const CANONICAL_SLOTS = ['manha', 'tarde', 'noite'];

    private const PERIOD_SLOT_DEFINITIONS = [
        'manha' => ['label' => 'Manhã', 'start' => 8 * 60, 'end' => 11 * 60 + 59],
        'tarde' => ['label' => 'Tarde', 'start' => 12 * 60, 'end' => 17 * 60 + 59],
        'noite' => ['label' => 'Noite', 'start' => 18 * 60, 'end' => 22 * 60 + 59],
    ];

    private const HOURLY_START_HOUR = 8;

    private const HOURLY_END_HOUR = 22;

    /**
     * Only operationally active orders continue to reserve slot capacity.
     *
     * @var array<int, string>
     */
    public const CAPACITY_CONSUMING_STATUSES = ['placed', 'accepted', 'ready'];

    /**
     * Window threshold to return 'limitado' state.
     * Currently set to 30 minutes (6 slots of 5 mins).
     */
    public const MIN_SLOT_OPTIONS_FOR_DISPONIVEL = 6;

    public function __construct(protected SettingService $settings) {}

    public function slotMode(): string
    {
        $stored = strtolower(trim((string) $this->settings->get(self::SLOT_MODE_SETTING_KEY, self::SLOT_MODE_PERIOD)));

        return match ($stored) {
            self::SLOT_MODE_HOURLY, 'hourly', 'hora' => self::SLOT_MODE_HOURLY,
            default => self::SLOT_MODE_PERIOD,
        };
    }

    /**
     * @return array<string, array{slot:string,label:string,start:int,end:int}>
     */
    public function slotDefinitions(): array
    {
        if ($this->slotMode() === self::SLOT_MODE_HOURLY) {
            $definitions = [];

            for ($hour = self::HOURLY_START_HOUR; $hour <= self::HOURLY_END_HOUR; $hour++) {
                $slot = sprintf('%02dh', $hour);
                $definitions[$slot] = [
                    'slot' => $slot,
                    'label' => $slot,
                    'start' => $hour * 60,
                    'end' => $hour * 60 + 59,
                ];
            }

            return $definitions;
        }

        return collect(self::PERIOD_SLOT_DEFINITIONS)
            ->map(fn (array $definition, string $slot): array => [
                'slot' => $slot,
                'label' => $definition['label'],
                'start' => $definition['start'],
                'end' => $definition['end'],
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function slotKeys(): array
    {
        return array_keys($this->slotDefinitions());
    }

    /**
     * @return array<string, string>
     */
    public function slotLabels(): array
    {
        return collect($this->slotDefinitions())
            ->mapWithKeys(fn (array $definition, string $slot): array => [$slot => $definition['label']])
            ->all();
    }

    /**
     * @return array{start:int,end:int}|null
     */
    public function slotWindow(string $slot): ?array
    {
        $definition = $this->slotDefinitions()[$slot] ?? null;

        if ($definition === null) {
            return null;
        }

        return [
            'start' => $definition['start'],
            'end' => $definition['end'],
        ];
    }

    public function resolveSlotFromSchedule(\Carbon\CarbonInterface $scheduled): string
    {
        $minutes = ((int) $scheduled->format('H') * 60) + (int) $scheduled->format('i');

        foreach ($this->slotDefinitions() as $slot => $definition) {
            if ($minutes >= $definition['start'] && $minutes <= $definition['end']) {
                return $slot;
            }
        }

        throw ValidationException::withMessages([
            'scheduled_at' => 'O horário escolhido não pertence a nenhum slot operacional configurado.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminPayload(): array
    {
        $capacities = $this->getBaseCapacities();

        return [
            'scope' => 'global',
            'setting_key' => self::SETTING_KEY,
            'slot_mode' => $this->slotMode(),
            'slot_capacities' => collect($this->slotKeys())
                ->map(fn (string $slot): array => [
                    'slot' => $slot,
                    'label' => $this->slotLabel($slot),
                    'value' => $capacities[$slot],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationalRulesPayload(): array
    {
        return [
            'scope' => 'global',
            'setting_key' => self::OPERATIONAL_RULES_KEY,
            'slot_mode' => $this->slotMode(),
            'slots' => collect($this->slotDefinitions())->values()->all(),
            'rules' => $this->getOperationalRules(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateOperationalRulesPayload(array $input): array
    {
        $before = $this->getOperationalRules();

        // Automatic cleanup of past blocked dates
        $today = \Carbon\Carbon::now('Europe/Lisbon')->startOfDay();
        $allowedSlots = $this->slotKeys();
        $input = [
            'lead_times' => collect($allowedSlots)
                ->mapWithKeys(fn (string $slot): array => [$slot => max(0, (int) ($input['lead_times'][$slot] ?? 0))])
                ->all(),
            'blocked_dates' => collect($input['blocked_dates'] ?? [])
                ->filter(function (array $item) use ($today): bool {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $item['date'], 'Europe/Lisbon')->startOfDay();

                        return $date->greaterThanOrEqualTo($today);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })
                ->map(fn (array $item): array => [
                    'date' => $item['date'],
                    'slots' => collect($item['slots'] ?? [])
                        ->filter(fn (mixed $slot): bool => is_string($slot) && in_array($slot, $allowedSlots, true))
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $item): bool => $item['slots'] !== [])
                ->values()
                ->all(),
        ];

        /** @var Setting $setting */
        $setting = Setting::query()->updateOrCreate(
            ['key' => self::OPERATIONAL_RULES_KEY],
            [
                'value' => json_encode($input, JSON_THROW_ON_ERROR),
                'type' => 'json',
                'editable' => true,
            ],
        );

        $this->settings->set(self::OPERATIONAL_RULES_KEY, $setting->value);

        Log::info('[PlanningSlotCapacityService] Operational rules updated', [
            'actor_id' => Auth::id(),
            'setting_key' => self::OPERATIONAL_RULES_KEY,
            'scope' => 'global',
            'before' => $before,
            'after' => $input,
        ]);

        return $this->getOperationalRulesPayload();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationalRules(): array
    {
        $stored = $this->settings->get(self::OPERATIONAL_RULES_KEY);

        $allowedSlots = $this->slotKeys();
        $storedLeadTimes = is_array($stored) && is_array($stored['lead_times'] ?? null)
            ? $stored['lead_times']
            : [];
        $leadTimes = [];

        foreach ($allowedSlots as $slot) {
            $leadTimes[$slot] = (int) ($storedLeadTimes[$slot] ?? self::DEFAULT_OPERATIONAL_RULES['lead_times'][$slot] ?? 60);
        }

        return [
            'lead_times' => $leadTimes,
            'blocked_dates' => collect(is_array($stored) && is_array($stored['blocked_dates'] ?? null) ? $stored['blocked_dates'] : [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): array => [
                    'date' => (string) ($item['date'] ?? ''),
                    'slots' => collect($item['slots'] ?? [])
                        ->filter(fn (mixed $slot): bool => is_string($slot) && in_array($slot, $allowedSlots, true))
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $item): bool => $item['date'] !== '' && $item['slots'] !== [])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateAdminPayload(array $input): array
    {
        $before = $this->getBaseCapacities();

        /** @var Setting $setting */
        $setting = Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => json_encode($input, JSON_THROW_ON_ERROR),
                'type' => 'json',
                'editable' => true,
            ],
        );

        $this->settings->set(self::SETTING_KEY, $setting->value);

        Log::info('[PlanningSlotCapacityService] Slot base capacity updated', [
            'actor_id' => Auth::id(),
            'setting_key' => self::SETTING_KEY,
            'scope' => 'global',
            'before' => $before,
            'after' => $input,
        ]);

        return $this->getAdminPayload();
    }

    /**
     * @return array<string, int>
     */
    public function getBaseCapacities(): array
    {
        $stored = $this->settings->get(self::SETTING_KEY);

        $normalized = [];

        foreach ($this->slotKeys() as $slot) {
            $value = is_array($stored) ? ($stored[$slot] ?? null) : null;
            $normalized[$slot] = is_numeric($value) ? max(0, (int) $value) : $this->defaultCapacityForSlot($slot);
        }

        return $normalized;
    }

    /**
     * @param  iterable<int, mixed>  $orders
     * @return array<string, int>
     */
    public function countConsumedCapacity(iterable $orders): array
    {
        $slots = $this->slotKeys();
        $counts = array_fill_keys($slots, 0);

        foreach ($orders as $order) {
            $slot = $order->slot ?? null;
            $status = $order->status ?? null;

            if (! is_string($slot) || ! in_array($slot, $slots, true)) {
                continue;
            }

            if (! is_string($status) || ! in_array($status, self::CAPACITY_CONSUMING_STATUSES, true)) {
                continue;
            }

            $counts[$slot]++;
        }

        return $counts;
    }

    public function slotLabel(string $slot): string
    {
        return $this->slotLabels()[$slot] ?? $slot;
    }

    /**
     * Base capacity only adds binary blocking. "Limitado" remains tied to real pickup-window scarcity.
     *
     * @param  array<int, string>  $minuteOptions
     * @param  array{start:int,end:int}|null  $window
     */
    public function resolveSlotState(string $slot, array $minuteOptions, ?array $window, int $consumedCount, ?\Carbon\Carbon $date = null): string
    {
        if ($window === null) {
            return 'bloqueado';
        }

        if ($this->getSlotBlockReason($slot, $minuteOptions, $window, $consumedCount, $date) !== null) {
            return 'bloqueado';
        }

        $availableMinuteCount = $this->countAvailableMinutes($minuteOptions, $window);

        if ($availableMinuteCount === 0) {
            return 'bloqueado';
        }

        if ($availableMinuteCount <= self::MIN_SLOT_OPTIONS_FOR_DISPONIVEL) {
            return 'limitado';
        }

        return 'disponível';
    }

    /**
     * @param  array<int, string>  $minuteOptions
     * @param  array{start:int,end:int}  $window
     */
    public function getSlotBlockReason(string $slot, array $minuteOptions, array $window, int $consumedCount, ?\Carbon\Carbon $date = null): ?string
    {
        $rules = $this->getOperationalRules();

        // 1. Data explicitamente bloqueada pelo admin
        if ($date !== null) {
            $dateString = $date->format('Y-m-d');
            foreach ($rules['blocked_dates'] as $blockedDate) {
                if ($blockedDate['date'] === $dateString && in_array($slot, $blockedDate['slots'] ?? [], true)) {
                    return 'SLOT_DATE_BLOCKED';
                }
            }
        }

        // 2. Violação do Lead Time do slot em relação à hora atual
        if ($date !== null) {
            $slotLeadTime = (int) ($rules['lead_times'][$slot] ?? 0);

            // Story 4.8 Requirement: Global Minimum Minutes applies if no slot rule exists,
            // or if it is more restrictive (larger value) than the slot rule.
            $globalLeadTime = (int) $this->settings->get('ORDER_MINIMUM_MINUTES', 30);
            $effectiveLeadTime = max($slotLeadTime, $globalLeadTime);

            if ($effectiveLeadTime > 0) {
                $now = \Carbon\Carbon::now('Europe/Lisbon');

                // Planned start of the slot
                $slotStart = $date->copy()->startOfDay()->addMinutes($window['start']);

                if ($now->copy()->addMinutes($effectiveLeadTime)->greaterThan($slotStart)) {
                    return 'SLOT_LEAD_TIME_VIOLATION';
                }
            }
        }

        // 3. Capacidade base atingida
        if ($consumedCount >= $this->capacityForSlot($slot)) {
            return 'SLOT_CAPACITY_FULL';
        }

        // 4. Sem janelas de pickup disponíveis (configuração de loja)
        if ($this->countAvailableMinutes($minuteOptions, $window) === 0) {
            return 'SLOT_NO_WINDOW';
        }

        return null;
    }

    /**
     * @param  array<int, string>  $minuteOptions
     * @param  array{start:int,end:int}  $window
     */
    protected function countAvailableMinutes(array $minuteOptions, array $window): int
    {
        return collect($minuteOptions)
            ->filter(function (string $option) use ($window): bool {
                try {
                    $time = \Carbon\Carbon::createFromFormat('H:i', $option);
                    $totalMinutes = ($time->hour * 60) + $time->minute;

                    return $totalMinutes >= $window['start'] && $totalMinutes <= $window['end'];
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->count();
    }

    public function capacityForSlot(string $slot): int
    {
        return $this->getBaseCapacities()[$slot] ?? 0;
    }

    protected function defaultCapacityForSlot(string $slot): int
    {
        return self::DEFAULT_CAPACITIES[$slot] ?? self::DEFAULT_HOURLY_CAPACITY;
    }
}
