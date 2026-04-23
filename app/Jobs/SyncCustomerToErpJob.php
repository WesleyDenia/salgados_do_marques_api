<?php

namespace App\Jobs;

use App\Contracts\Erp\CustomerSyncInterface;
use App\Mappers\CustomerMapper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncCustomerToErpJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public int $userId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(CustomerSyncInterface $erp): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $customer = CustomerMapper::fromUser($user);

        if ($user->external_id) {
            if (!$erp->update((string) $user->external_id, $customer)) {
                throw new RuntimeException("Falha ao atualizar cliente {$user->id} no ERP.");
            }

            Log::info('✅ [SyncCustomerToErpJob] Cliente atualizado no ERP', [
                'user_id' => $user->id,
                'external_id' => $user->external_id,
            ]);

            return;
        }

        $externalId = $erp->upsert($customer);

        if (!$externalId) {
            throw new RuntimeException("ERP não retornou external_id para o usuário {$user->id}.");
        }

        $user->forceFill(['external_id' => $externalId])->save();

        Log::info('✅ [SyncCustomerToErpJob] Cliente sincronizado no ERP', [
            'user_id' => $user->id,
            'external_id' => $externalId,
        ]);
    }
}
