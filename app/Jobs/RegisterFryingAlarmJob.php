<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RegisterFryingAlarmJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public string $webhook,
        public int $minutesUntilAlarm,
    ) {
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        Http::timeout(10)
            ->get($this->urlWithAlarmMinutes())
            ->throw();
    }

    private function urlWithAlarmMinutes(): string
    {
        $minutes = rawurlencode((string) $this->minutesUntilAlarm);
        [$url, $fragment] = array_pad(explode('#', $this->webhook, 2), 2, null);

        if (preg_match('/([?&]minutos-alarme=)[^&#]*/', $url) === 1) {
            $url = preg_replace(
                '/([?&]minutos-alarme=)[^&#]*/',
                '${1}' . $minutes,
                $url,
                1,
            ) ?? $url;
        } else {
            $url .= str_contains($url, '?') ? '&' : '?';
            $url .= 'minutos-alarme=' . $minutes;
        }

        return $fragment === null ? $url : "{$url}#{$fragment}";
    }
}
