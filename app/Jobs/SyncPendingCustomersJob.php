<?php

namespace App\Jobs;

use App\Contracts\Erp\CustomerSyncInterface;
use App\DTOs\CustomerData;
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

    public function handle(CustomerSyncInterface $erp): void
    {
        // Processa em lotes para não estourar memória
        User::whereNull('external_id')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($erp) {
                foreach ($users as $user) {
                    try {
                        // ✅ Usa DTO (CustomerData)
                        $customer = new CustomerData(
                            id:         (string) $user->id,
                            name:       $user->name,
                            email:      $user->email,
                            taxNumber:  $user->nif,
                            phone:      $user->phone,
                            mobile:     $user->mobile ?? null,
                            street:     $user->street,
                            city:       $user->city,
                            postalCode: $user->postal_code,
                            countryCode:'PT',
                            notes:      'Sincronizado via Job (fila ERP)',
                        );

                        // ✅ Usa método upsert (cria ou atualiza conforme NIF)
                        $externalId = $erp->upsert($customer);

                        if ($externalId) {
                            $user->forceFill(['external_id' => $externalId])->save();

                            Log::info('✅ [SyncJob] Cliente sincronizado com sucesso', [
                                'user_id'     => $user->id,
                                'external_id' => $externalId,
                            ]);
                        } else {
                            Log::warning('⚠️ [SyncJob] ERP não retornou external_id', [
                                'user_id' => $user->id,
                            ]);
                        }

                    } catch (\Throwable $e) {
                        Log::error('💥 [SyncJob] Falha ao sincronizar cliente', [
                            'user_id' => $user->id,
                            'error'   => $e->getMessage(),
                            'trace'   => $e->getTraceAsString(),
                        ]);
                    }
                }
            });
    }
}
