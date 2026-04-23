<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPendingCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Quantas tentativas antes de falhar definitivamente */
    public int $tries = 3;

    /** Timeout (segundos) */
    public int $timeout = 120;

    public function handle(): void
    {
        User::whereNull('external_id')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    SyncCustomerToErpJob::dispatch($user->id);
                }
            });

        Log::info('✅ [SyncPendingCustomersJob] Sincronização de clientes pendentes enfileirada');
    }
}
