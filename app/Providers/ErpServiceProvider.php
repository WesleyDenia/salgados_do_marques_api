<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Erp\CustomerSyncInterface;
use App\Services\Erp\Vendus\VendusCustomerSyncService;

class ErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerSyncInterface::class, VendusCustomerSyncService::class);
    }
}
