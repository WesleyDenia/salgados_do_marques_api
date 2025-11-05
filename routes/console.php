<?php

use App\Jobs\SyncVendusCouponsJob;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncAllUsersLoyaltyJob;
use Illuminate\Foundation\Inspiring;
use App\Jobs\SyncPendingCustomersJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncPendingCustomersJob())
    ->everyFifteenMinutes()
    ->name('sync-pending-customers')
    ->withoutOverlapping();

Schedule::job(new SyncVendusCouponsJob())->everyFifteenMinutes();

Schedule::call(function () {
    dispatch((new \App\Jobs\SyncAllUsersLoyaltyJob(20))->onQueue('sync-loyalty'));
    Log::info('[Scheduler] Job SyncAllUsersLoyaltyJob(20) despachado para fila sync-loyalty');
})
->name('sync-loyalty') 
->everyTenMinutes()
->withoutOverlapping()
->appendOutputTo(storage_path('logs/vendus_sync.log'));
