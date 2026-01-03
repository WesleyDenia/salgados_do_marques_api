<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Erp\Vendus\VendusLoyaltySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllUsersLoyaltyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // até 5 minutos por batch

    public function __construct(public int $days = 10) {}

    public function handle(VendusLoyaltySyncService $syncService)
    {
        // Filtra apenas usuários com vendus_client_id definido
        $users = User::whereNotNull('external_id')->get();

        if ($users->isEmpty()) {
            Log::warning('[GlobalLoyaltySync] Nenhum usuário com external_id encontrado.');
            return;
        }        

        foreach ($users as $user) {
            try {
                $externalId = $user->external_id;

                $result = $syncService->sync($externalId, $this->days);

                if (($result['status'] ?? 'error') === 'error') {
                    $message = $result['message'] ?? 'Erro desconhecido';
                    // Silencia "sem dados" para evitar spam
                    if (stripos($message, 'sem dados') !== false) {
                        continue;
                    }
                    Log::warning("[GlobalLoyaltySync] Erro ao sincronizar {$user->name}: {$message}");
                    continue;
                }

            } catch (\Throwable $e) {
                Log::error("[GlobalLoyaltySync] Exceção para {$user->name}: {$e->getMessage()}");
            }
        }        
    }
}
