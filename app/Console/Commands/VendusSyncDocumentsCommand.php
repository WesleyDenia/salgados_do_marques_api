<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Erp\Vendus\VendusLoyaltyDocumentsService;
use Illuminate\Support\Facades\Log;

class VendusSyncDocumentsCommand extends Command
{
    /**
     * Nome e assinatura do comando Artisan.
     */
    protected $signature = 'vendus:sync-documents';

    /**
     * Descrição que aparece no php artisan list.
     */
    protected $description = 'Sincroniza pontos de fidelidade via /documents do Vendus.';

    public function __construct(protected VendusLoyaltyDocumentsService $service)
    {
        parent::__construct();
    }

    /**
     * Execução do comando.
     */
    public function handle(): int
    {
        try {
            $result = $this->service->sync();

            if ($result['status'] === 'error') {
                return self::FAILURE;
            }

            $processed = $result['processed'] ?? [];
            $count = count($processed);

            if ($count === 0) {
                
            } else {
                
                foreach ($processed as $item) {
                    $this->line("   • #{$item['invoice_id']} - {$item['client']} (+{$item['points']} pontos)");
                }
            }            
            
            return self::SUCCESS;

        } catch (\Throwable $e) {            
            return self::FAILURE;
        }
    }
}
