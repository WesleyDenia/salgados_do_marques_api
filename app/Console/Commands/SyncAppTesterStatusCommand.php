<?php

namespace App\Console\Commands;

use App\Services\AppTesterService;
use Illuminate\Console\Command;

class SyncAppTesterStatusCommand extends Command
{
    protected $signature = 'app-testers:sync-status';
    protected $description = 'Sincroniza o status dos testers com base nos usuários já cadastrados no app';

    public function handle(AppTesterService $service): int
    {
        $updated = $service->syncStatusesFromUsers();

        $this->info("Testers atualizados: {$updated}");

        return self::SUCCESS;
    }
}
