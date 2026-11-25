<?php

namespace App\Services;

use App\Jobs\RegisterFryingAlarmJob;
use App\Models\Store;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FryingAlarmRegistrationService
{
    public function __construct(protected FryingPlanningService $planning)
    {
    }

    /**
     * Queue one MacroDroid registration for each heating cycle that is still
     * ahead. Consecutive batches share the first cycle because the oil remains
     * hot; a later alarm is created only when the planning inserts heating again.
     *
     * @return array{queued_count: int, skipped_past_count: int}
     */
    public function register(string $date, Store $store): array
    {
        $webhook = trim((string) $store->webhook);

        if ($webhook === '') {
            throw ValidationException::withMessages([
                'store_id' => 'Configure o WebHook do MacroDroid antes de registar alarmes para esta loja.',
            ]);
        }

        $plan = $this->planning->forDateAndStore($date, $store);
        $now = Carbon::now((string) $plan['timezone']);
        $queuedCount = 0;
        $skippedPastCount = 0;

        foreach ($this->alarmStarts($plan) as $alarmStart) {
            if ($alarmStart->lessThanOrEqualTo($now)) {
                $skippedPastCount++;

                continue;
            }

            $minutesUntilAlarm = max(1, (int) ceil($now->diffInSeconds($alarmStart) / 60));

            RegisterFryingAlarmJob::dispatch($webhook, $minutesUntilAlarm)
                ->onQueue('frying-alarms');
            $queuedCount++;
        }

        return [
            'queued_count' => $queuedCount,
            'skipped_past_count' => $skippedPastCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return Collection<int, CarbonInterface>
     */
    private function alarmStarts(array $plan): Collection
    {
        return collect($plan['slots'])
            ->flatMap(function (array $slot): Collection {
                $tasks = collect($slot['tasks'])
                    ->sortBy(fn (array $task) => $task['start_at']->getTimestamp())
                    ->values();
                $heatingStarts = $tasks
                    ->filter(fn (array $task) => $task['heating_start_at'] !== null)
                    ->map(fn (array $task) => $task['heating_start_at']);

                // A cuba without configured heating still needs one alarm for
                // its first frying, but never one per consecutive batch.
                return $heatingStarts->isNotEmpty()
                    ? $heatingStarts
                    : $tasks->take(1)->map(fn (array $task) => $task['start_at']);
            })
            ->filter(fn ($start) => $start instanceof CarbonInterface)
            ->groupBy(fn (CarbonInterface $start) => $start->getTimestamp())
            ->map(fn (Collection $starts) => $starts->first())
            ->sortBy(fn (CarbonInterface $start) => $start->getTimestamp())
            ->values();
    }
}
