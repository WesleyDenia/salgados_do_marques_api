<?php

use App\Jobs\SyncAllUsersLoyaltyJob;
use App\Jobs\SyncPendingCustomersJob;
use App\Jobs\SyncVendusCouponsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:complete-overdue')->dailyAt('01:00')->timezone('Europe/Lisbon')->withoutOverlapping();

Schedule::job(new SyncPendingCustomersJob)->everyFiveMinutes()->withoutOverlapping();

Schedule::job(new SyncVendusCouponsJob)->everyFiveMinutes()->withoutOverlapping();

Schedule::job((new SyncAllUsersLoyaltyJob(20)))->everyMinute()->withoutOverlapping();
