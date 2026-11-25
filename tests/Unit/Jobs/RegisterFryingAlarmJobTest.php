<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RegisterFryingAlarmJob;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterFryingAlarmJobTest extends TestCase
{
    public function test_it_registers_the_minutes_remaining_with_macrodroid(): void
    {
        Http::fake([
            'https://macrodroid.example/fritura-encomenda*' => Http::response([], 200),
        ]);

        (new RegisterFryingAlarmJob('https://macrodroid.example/fritura-encomenda?minutos-alarme=', 60))->handle();

        Http::assertSent(fn ($request) =>
            $request->method() === 'GET'
            && $request->url() === 'https://macrodroid.example/fritura-encomenda?minutos-alarme=60'
        );
    }
}
