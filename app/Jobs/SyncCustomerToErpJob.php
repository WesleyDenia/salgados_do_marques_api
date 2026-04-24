<?php

namespace App\Jobs;

use App\Contracts\Erp\CustomerSyncInterface;
use App\Mappers\CustomerMapper;
use App\Models\ErpSyncTask;
use App\Models\User;
use App\Services\ErpSyncTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

    public function handle(CustomerSyncInterface $erp, ErpSyncTaskService $tasks): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $task = $tasks->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id,
            [
                'status' => ErpSyncTask::STATUS_QUEUED,
                'external_id' => $user->external_id,
                'queued_at' => now(),
            ]
        );
        $task = $tasks->markProcessing($task);

        $user->forceFill([
            'erp_sync_status' => 'syncing',
            'erp_sync_attempts' => ((int) $user->erp_sync_attempts) + 1,
            'erp_sync_attempted_at' => now(),
        ])->save();

        try {
            $this->syncUser($user, $erp);
            $tasks->markSynced($task, [
                'external_id' => $user->refresh()->external_id,
            ]);
        } catch (Throwable $e) {
            $user->forceFill([
                'erp_sync_status' => 'failed',
                'erp_sync_error' => $e->getMessage(),
                'erp_sync_attempted_at' => now(),
            ])->save();

            $tasks->markFailed($task, $e->getMessage());

            throw $e;
        }
    }

    protected function syncUser(User $user, CustomerSyncInterface $erp): void
    {
        $customer = CustomerMapper::fromUser($user);

        if ($user->external_id) {
            if (!$erp->update((string) $user->external_id, $customer)) {
                throw new RuntimeException($this->syncError($erp, "Falha ao atualizar cliente {$user->id} no ERP."));
            }

            $user->forceFill([
                'erp_sync_status' => 'synced',
                'erp_sync_error' => null,
                'erp_synced_at' => now(),
            ])->save();

            Log::info('✅ [SyncCustomerToErpJob] Cliente atualizado no ERP', [
                'user_id' => $user->id,
                'external_id' => $user->external_id,
            ]);

            return;
        }

        $externalId = $erp->upsert($customer);

        if (!$externalId) {
            throw new RuntimeException($this->syncError($erp, "ERP não retornou external_id para o usuário {$user->id}."));
        }

        $user->forceFill([
            'external_id' => $externalId,
            'erp_sync_status' => 'synced',
            'erp_sync_error' => null,
            'erp_synced_at' => now(),
        ])->save();

        Log::info('✅ [SyncCustomerToErpJob] Cliente sincronizado no ERP', [
            'user_id' => $user->id,
            'external_id' => $externalId,
        ]);
    }

    protected function syncError(CustomerSyncInterface $erp, string $fallback): string
    {
        if (method_exists($erp, 'lastError') && $erp->lastError()) {
            return $erp->lastError();
        }

        return $fallback;
    }
}
