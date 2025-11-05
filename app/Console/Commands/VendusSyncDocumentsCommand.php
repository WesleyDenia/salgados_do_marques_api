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
        $this->info('🔄 Iniciando sincronização de documentos do Vendus...');
        Log::info('[VendusSyncDocuments] Execução manual iniciada');

        try {
            $result = $this->service->sync();

            if ($result['status'] === 'error') {
                $this->error('❌ ' . $result['message']);
                Log::error('[VendusSyncDocuments] Falha: ' . $result['message']);
                return self::FAILURE;
            }

            $processed = $result['processed'] ?? [];
            $count = count($processed);

            if ($count === 0) {
                $this->info('⚠️ Nenhum novo documento processado.');
            } else {
                $this->info("✅ {$count} documento(s) processado(s):");
                foreach ($processed as $item) {
                    $this->line("   • #{$item['invoice_id']} - {$item['client']} (+{$item['points']} pontos)");
                }
            }

            $this->info('✨ Sincronização concluída com sucesso!');
            Log::info("[VendusSyncDocuments] Concluído: {$count} documentos processados");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('💥 Erro durante a sincronização: ' . $e->getMessage());
            Log::error('[VendusSyncDocuments] Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
