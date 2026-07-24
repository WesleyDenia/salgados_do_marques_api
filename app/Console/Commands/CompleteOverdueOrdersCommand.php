<?php

namespace App\Console\Commands;

use App\Services\OverdueOrderCompletionService;
use Illuminate\Console\Command;

class CompleteOverdueOrdersCommand extends Command
{
    protected $signature = 'orders:complete-overdue';

    protected $description = 'Marca como concluídas as encomendas agendadas antes do dia operacional atual.';

    public function handle(OverdueOrderCompletionService $service): int
    {
        $result = $service->completeOverdueOrders();

        $this->info(sprintf(
            'Encomendas concluídas automaticamente: %d (corte UTC: %s, timezone: %s).',
            $result['completed'],
            $result['cutoff_utc'],
            $result['timezone'],
        ));

        return self::SUCCESS;
    }
}
