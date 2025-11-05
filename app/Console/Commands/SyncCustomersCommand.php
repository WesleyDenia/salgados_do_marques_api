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
            $this->info('🔄 Executando sincronização imediata...');
            (new SyncPendingCustomersJob())->handle($erp);
        } else {
            $this->info('📦 Job enfileirado para execução...');
            SyncPendingCustomersJob::dispatch();
        }

        $this->info('✅ Sincronização concluída ou agendada com sucesso.');
        return self::SUCCESS;
    }
}
