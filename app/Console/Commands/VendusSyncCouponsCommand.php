<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Erp\Vendus\VendusCouponSyncService;

class VendusSyncCouponsCommand extends Command
{
    /**
     * Nome e assinatura do comando.
     *
     * Exemplo de execução:
     * php artisan vendus:sync-coupons
     */
    protected $signature = 'vendus:sync-coupons';

    /**
     * Descrição do comando.
     */
    protected $description = 'Sincroniza os cupons utilizados no Vendus com o banco local';

    /**
     * Executa o comando.
     */
    public function handle(VendusCouponSyncService $syncService): int
    {        

        try {
            $syncService->syncUsedCoupons();
     
            return Command::SUCCESS;
        } catch (\Throwable $e) {            
            return Command::FAILURE;
        }
    }
}
