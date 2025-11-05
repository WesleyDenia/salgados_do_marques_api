<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncAllUsersLoyaltyJob;

class VendusSyncLoyaltyCommand extends Command
{
    protected $signature = 'vendus:sync-loyalty {days=10}';
    protected $description = 'Roda o job de sincronização de fidelidade manualmente';

    public function handle()
    {
        $days = (int) $this->argument('days');
        dispatch((new SyncAllUsersLoyaltyJob($days))->onQueue('sync-loyalty'));
        $this->info("✅ Job SyncAllUsersLoyaltyJob({$days}) enviado para a fila loyalty.");
    }
}
