<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlanningSlotCapacityService
{
    public const SETTING_KEY = 'ORDER_SLOT_BASE_CAPACITY';

    public const OPERATIONAL_RULES_KEY = 'ORDER_SLOT_OPERATIONAL_RULES';

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

    /**
     * @return array<string, mixed>
     */
    public function getAdminPayload(): array
    {
        $capacities = $this->getBaseCapacities();

        return [
            'scope' => 'global',
            'setting_key' => self::SETTING_KEY,
            'slot_capacities' => collect(self::CANONICAL_SLOTS)
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
        $input['blocked_dates'] = collect($input['blocked_dates'] ?? [])
            ->filter(function (array $item) use ($today): bool {
                try {
                    $date = \Carbon\Carbon::createFromFormat('Y-m-d', $item['date'], 'Europe/Lisbon')->startOfDay();
                    return $date->greaterThanOrEqualTo($today);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->values()
            ->all();

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

        if (! is_array($stored)) {
            return self::DEFAULT_OPERATIONAL_RULES;
        }

        return [
            'lead_times' => [
                'manha' => (int) ($stored['lead_times']['manha'] ?? self::DEFAULT_OPERATIONAL_RULES['lead_times']['manha']),
                'tarde' => (int) ($stored['lead_times']['tarde'] ?? self::DEFAULT_OPERATIONAL_RULES['lead_times']['tarde']),
                'noite' => (int) ($stored['lead_times']['noite'] ?? self::DEFAULT_OPERATIONAL_RULES['lead_times']['noite']),
            ],
            'blocked_dates' => is_array($stored['blocked_dates'] ?? null) ? $stored['blocked_dates'] : [],
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

        if (! is_array($stored)) {
            return self::DEFAULT_CAPACITIES;
        }

        $normalized = [];

        foreach (self::CANONICAL_SLOTS as $slot) {
            $value = $stored[$slot] ?? null;
            $normalized[$slot] = is_numeric($value) ? max(0, (int) $value) : self::DEFAULT_CAPACITIES[$slot];
        }

        return $normalized;
    }

    /**
     * @param  iterable<int, mixed>  $orders
     * @return array<string, int>
     */
    public function countConsumedCapacity(iterable $orders): array
    {
        $counts = array_fill_keys(self::CANONICAL_SLOTS, 0);

        foreach ($orders as $order) {
            $slot = $order->slot ?? null;
            $status = $order->status ?? null;

            if (! is_string($slot) || ! in_array($slot, self::CANONICAL_SLOTS, true)) {
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
        return match ($slot) {
            'manha' => 'Manhã',
            'tarde' => 'Tarde',
            'noite' => 'Noite',
            default => $slot,
        };
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
}
