<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncPendingCustomersJob;
use App\Contracts\Erp\CustomerSyncInterface;

class SyncCustomersCommand extends Command
{
    protected $signature = 'sync:customers {--now : Executa imediatamente sem usar fila}';
    protected $description = 'Sincroniza clientes pendentes com o ERP (ex: Vendus)';

    public function handle(CustomerSyncInterface $erp): int
    {
        if ($this->option('now')) {            
            (new SyncPendingCustomersJob())->handle($erp);
        } else {            
            SyncPendingCustomersJob::dispatch();
        }        
        return self::SUCCESS;
    }
}
