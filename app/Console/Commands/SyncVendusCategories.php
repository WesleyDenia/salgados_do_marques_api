<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Erp\Vendus\VendusCategorySyncService;

class SyncVendusCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendus:sync-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(VendusCategorySyncService $service)
    {
        $this->info('🔁 Sincronizando categorias do Vendus...');
        $service->sync();
        $this->info('✅ Sincronização concluída!');
    }
}
