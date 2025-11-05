<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Erp\Vendus\VendusCouponSyncService;


class SyncVendusCouponsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(VendusCouponSyncService $syncService): void
    {
        $syncService->syncUsedCoupons();
    }
}
