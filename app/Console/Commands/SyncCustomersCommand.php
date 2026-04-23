<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncPendingCustomersJob;

class SyncCustomersCommand extends Command
{
    protected $signature = 'sync:customers {--now : Executa imediatamente sem usar fila}';
    protected $description = 'Sincroniza clientes pendentes com o ERP (ex: Vendus)';

    public function handle(): int
    {
        if ($this->option('now')) {            
            (new SyncPendingCustomersJob())->handle();
        } else {            
            SyncPendingCustomersJob::dispatch();
        }        
        return self::SUCCESS;
    }
}
