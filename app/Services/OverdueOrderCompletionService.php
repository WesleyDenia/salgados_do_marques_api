<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class OverdueOrderCompletionService
{
    private const COMPLETABLE_STATUSES = ['placed', 'accepted', 'ready'];

    public function __construct(
        protected OrderRepository $orders,
        protected SettingService $settings,
    ) {}

    /**
     * Marks orders scheduled before the current operational day as done.
     *
     * @return array{completed:int, cutoff_utc:string, timezone:string}
     */
    public function completeOverdueOrders(?CarbonInterface $now = null): array
    {
        $timezone = $this->resolveOperationalTimezone();
        $reference = $now
            ? Carbon::instance($now)->copy()->timezone($timezone)
            : Carbon::now($timezone);
        $cutoffUtc = $reference->copy()->startOfDay()->utc();
        $completed = 0;

        foreach ($this->orders->cursorOverdueOrdersForCompletion(
            $cutoffUtc,
            self::COMPLETABLE_STATUSES,
        ) as $order) {
            $this->orders->updateStatus(
                $order,
                ['status' => 'done'],
                $this->buildHistoryPayload($order, $cutoffUtc, $timezone),
            );

            $completed++;
        }

        Log::info('Overdue orders auto-completion finished.', [
            'completed' => $completed,
            'cutoff_utc' => $cutoffUtc->toIso8601String(),
            'timezone' => $timezone,
        ]);

        return [
            'completed' => $completed,
            'cutoff_utc' => $cutoffUtc->toIso8601String(),
            'timezone' => $timezone,
        ];
    }

    protected function resolveOperationalTimezone(): string
    {
        return (string) $this->settings->get(
            'ORDER_TIMEZONE',
            $this->settings->get('order_timezone', 'Europe/Lisbon'),
        );
    }

    /**
     * @return array{user_id:null,action:string,changes:array<string,mixed>}
     */
    protected function buildHistoryPayload(
        Order $order,
        CarbonInterface $cutoffUtc,
        string $timezone,
    ): array {
        return [
            'user_id' => null,
            'action' => 'status_changed',
            'changes' => [
                'status' => [
                    'from' => $order->status,
                    'to' => 'done',
                ],
                'auto_completion' => [
                    'from' => null,
                    'to' => [
                        'reason' => 'scheduled_before_current_operational_day',
                        'cutoff_utc' => Carbon::instance($cutoffUtc)->toIso8601String(),
                        'timezone' => $timezone,
                    ],
                ],
            ],
        ];
    }
}
