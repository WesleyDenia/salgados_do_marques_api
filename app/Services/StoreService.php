<?php

namespace App\Services;

use App\Models\Store;
use App\Repositories\StoreRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public const DAY_KEYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function __construct(protected StoreRepository $repository) {}

    public function defaultWeeklySchedule(): array
    {
        $schedule = [];

        foreach (self::DAY_KEYS as $day) {
            $schedule[$day] = [
                'is_open' => false,
                'start_time' => null,
                'end_time' => null,
            ];
        }

        return $schedule;
    }

    public function normalizeWeeklySchedule(?array $schedule): array
    {
        $normalized = $this->defaultWeeklySchedule();

        foreach (self::DAY_KEYS as $day) {
            $dayData = is_array($schedule[$day] ?? null) ? $schedule[$day] : [];
            $isOpen = filter_var($dayData['is_open'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            $normalized[$day] = [
                'is_open' => (bool) $isOpen,
                'start_time' => $isOpen ? $this->normalizeTime($dayData['start_time'] ?? null) : null,
                'end_time' => $isOpen ? $this->normalizeTime($dayData['end_time'] ?? null) : null,
            ];
        }

        return $normalized;
    }

    public function normalizeDateExceptions(?array $exceptions): array
    {
        return collect($exceptions ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                $isOpen = filter_var($item['is_open'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

                return [
                    'date' => isset($item['date']) && $item['date'] !== '' ? Carbon::parse($item['date'])->format('Y-m-d') : '',
                    'is_open' => (bool) $isOpen,
                    'start_time' => $isOpen ? $this->normalizeTime($item['start_time'] ?? null) : null,
                    'end_time' => $isOpen ? $this->normalizeTime($item['end_time'] ?? null) : null,
                ];
            })
            ->filter(fn (array $item) => $item['date'] !== '')
            ->sortBy('date')
            ->values()
            ->all();
    }

    public function preparePayload(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['accepts_orders'] = (bool) ($data['accepts_orders'] ?? false);
        $data['default_store'] = (bool) ($data['default_store'] ?? false);
        $data['latitude'] = (float) $data['latitude'];
        $data['longitude'] = (float) $data['longitude'];
        $data['pickup_weekly_schedule'] = $this->normalizeWeeklySchedule($data['pickup_weekly_schedule'] ?? null);
        $data['pickup_date_exceptions'] = $this->normalizeDateExceptions($data['pickup_date_exceptions'] ?? null);

        return $data;
    }

    public function create(array $data): Store
    {
        return $this->repository->create($this->preparePayload($data));
    }

    public function update(Store $store, array $data): Store
    {
        return $this->repository->update($store, $this->preparePayload($data));
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->adminPaginate($filters, $perPage);
    }

    public function listForApi(array $filters): Collection
    {
        $stores = $this->repository->apiIndex($filters);

        if (($filters['accepts_orders'] ?? null) === true) {
            return $stores
                ->filter(fn (Store $store) => $this->isEligibleForPickup($store))
                ->values();
        }

        return $stores;
    }

    public function findById(int $id): ?Store
    {
        return $this->repository->findById($id);
    }

    public function isEligibleForPickup(Store $store): bool
    {
        return (bool) $store->is_active
            && (bool) $store->accepts_orders
            && $this->hasValidWeeklySchedule($store);
    }

    public function weeklyScheduleSummary(Store $store): string
    {
        $schedule = $this->normalizeWeeklySchedule($store->pickup_weekly_schedule);
        $openDays = collect(self::DAY_KEYS)
            ->filter(fn (string $day) => ($schedule[$day]['is_open'] ?? false) === true)
            ->values();

        if ($openDays->isEmpty()) {
            return 'Agenda de retirada não configurada';
        }

        return $openDays
            ->map(function (string $day) use ($schedule): string {
                $window = $schedule[$day];

                return sprintf('%s %s-%s', $this->labelForDay($day), $window['start_time'], $window['end_time']);
            })
            ->implode(' | ');
    }

    public function hasFutureExceptions(Store $store, ?Carbon $today = null): bool
    {
        $today = ($today ?? now())->copy()->startOfDay()->format('Y-m-d');

        return collect($this->normalizeDateExceptions($store->pickup_date_exceptions))
            ->contains(fn (array $exception) => $exception['date'] >= $today);
    }

    public function pickupWindowForDate(Store $store, Carbon $date): ?array
    {
        $exception = collect($this->normalizeDateExceptions($store->pickup_date_exceptions))
            ->firstWhere('date', $date->format('Y-m-d'));

        if (is_array($exception)) {
            return ($exception['is_open'] ?? false) ? $exception : null;
        }

        $schedule = $this->normalizeWeeklySchedule($store->pickup_weekly_schedule);
        $dayKey = strtolower($date->englishDayOfWeek);
        $window = $schedule[$dayKey] ?? null;

        if (!is_array($window) || ($window['is_open'] ?? false) !== true) {
            return null;
        }

        return $this->isValidWindow($window['start_time'] ?? null, $window['end_time'] ?? null)
            ? $window
            : null;
    }

    public function validateScheduledPickup(Store $store, Carbon $scheduled, array $settings): void
    {
        $timezone = $settings['timezone'] ?? 'Europe/Lisbon';
        $scheduled = $scheduled->copy()->timezone($timezone);
        $now = Carbon::now($timezone);
        $minimumAllowed = $now->copy()->addMinutes(max(0, (int) ($settings['minimum_minutes'] ?? 0)));

        if ($scheduled->lessThan($minimumAllowed)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'O horário escolhido precisa respeitar o tempo mínimo de preparação.',
            ]);
        }

        if (!$this->isEligibleForPickup($store)) {
            throw ValidationException::withMessages([
                'store_id' => 'Esta loja não está disponível para retirada no momento.',
            ]);
        }

        $window = $this->pickupWindowForDate($store, $scheduled);

        if ($window === null) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Essa loja não estará aberta nessa data.',
            ]);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $scheduled->format('Y-m-d') . ' ' . $window['start_time'], $timezone);
        $end = Carbon::createFromFormat('Y-m-d H:i', $scheduled->format('Y-m-d') . ' ' . $window['end_time'], $timezone);

        if ($scheduled->lessThan($start) || $scheduled->greaterThan($end)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'O horário escolhido está fora do funcionamento dessa loja.',
            ]);
        }
    }

    protected function hasValidWeeklySchedule(Store $store): bool
    {
        $schedule = $this->normalizeWeeklySchedule($store->pickup_weekly_schedule);
        $hasOpenDay = false;

        foreach (self::DAY_KEYS as $day) {
            $window = $schedule[$day] ?? null;

            if (!is_array($window)) {
                return false;
            }

            if (($window['is_open'] ?? false) === true) {
                $hasOpenDay = true;

                if (!$this->isValidWindow($window['start_time'] ?? null, $window['end_time'] ?? null)) {
                    return false;
                }
            }
        }

        return $hasOpenDay;
    }

    protected function isValidWindow(?string $startTime, ?string $endTime): bool
    {
        return $startTime !== null && $endTime !== null && $startTime <= $endTime;
    }

    protected function normalizeTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        [$hour, $minute] = array_pad(explode(':', $value), 2, '00');

        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }

    protected function labelForDay(string $day): string
    {
        return match ($day) {
            'monday' => 'Seg',
            'tuesday' => 'Ter',
            'wednesday' => 'Qua',
            'thursday' => 'Qui',
            'friday' => 'Sex',
            'saturday' => 'Sab',
            'sunday' => 'Dom',
            default => $day,
        };
    }
}
