<?php

use App\Jobs\SyncVendusCouponsJob;
use App\Jobs\SyncAllUsersLoyaltyJob;
use App\Jobs\SyncPendingCustomersJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncPendingCustomersJob())->everyFiveMinutes()->withoutOverlapping();

Schedule::job(new SyncVendusCouponsJob())->everyFiveMinutes()->withoutOverlapping();

Schedule::job((new SyncAllUsersLoyaltyJob(20)))->everyFiveMinutes()->withoutOverlapping();
