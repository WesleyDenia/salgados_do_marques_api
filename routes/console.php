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
    ->everyTenMinutes()
    ->name('sync-pending-customers')
    ->withoutOverlapping();

Schedule::job(new SyncVendusCouponsJob())->everyTenMinutes();

Schedule::job((new \App\Jobs\SyncAllUsersLoyaltyJob(20))->onQueue('sync-loyalty'))
    ->name('sync-loyalty')
    ->everyTenMinutes()
    ->withoutOverlapping();
