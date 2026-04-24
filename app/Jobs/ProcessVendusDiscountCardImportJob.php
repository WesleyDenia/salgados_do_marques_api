<?php

namespace App\Jobs;

use App\Models\ErpSyncTask;
use App\Models\UserCoupon;
use App\Models\VendusDiscountCardImport;
use App\Services\ErpSyncTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessVendusDiscountCardImportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(public int $importId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->importId;
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ErpSyncTaskService $tasks): void
    {
        $import = VendusDiscountCardImport::find($this->importId);

        if (!$import || in_array($import->sync_status, [
            VendusDiscountCardImport::STATUS_PROCESSED,
            VendusDiscountCardImport::STATUS_MANUALLY_CLOSED,
        ], true)) {
            return;
        }

        $task = $tasks->createOrReuseActive(
            ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
            ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
            $import->id,
            [
                'status' => ErpSyncTask::STATUS_QUEUED,
                'external_id' => $import->external_id,
                'external_code' => $import->external_code,
                'queued_at' => $import->queued_at ?: now(),
            ]
        );
        $task = $tasks->markProcessing($task);

        $import->forceFill([
            'sync_status' => VendusDiscountCardImport::STATUS_PROCESSING,
            'sync_attempts' => $import->sync_attempts + 1,
            'sync_error' => null,
        ])->save();

        try {
            $this->process($import);
            $tasks->markSynced($task, [
                'external_id' => $import->external_id,
                'external_code' => $import->external_code,
            ]);
        } catch (Throwable $e) {
            $import->forceFill([
                'sync_status' => VendusDiscountCardImport::STATUS_FAILED,
                'sync_error' => $e->getMessage(),
            ])->save();

            $tasks->markFailed($task, $e->getMessage());

            throw $e;
        }
    }

    protected function process(VendusDiscountCardImport $import): void
    {
        $userCoupon = UserCoupon::query()
            ->when($import->external_code, fn ($query) => $query->where('external_code', $import->external_code))
            ->when($import->external_code && $import->external_id, fn ($query) => $query->orWhere('external_id', $import->external_id))
            ->when(!$import->external_code && $import->external_id, fn ($query) => $query->where('external_id', $import->external_id))
            ->first();

        if (!$userCoupon) {
            throw new RuntimeException(sprintf(
                'Cupom Vendus não encontrado localmente. external_id=%s external_code=%s',
                $import->external_id ?: '—',
                $import->external_code ?: '—'
            ));
        }

        $userCoupon->update([
            'status' => 'done',
            'active' => false,
        ]);

        if ($userCoupon->coupon && ($userCoupon->coupon->is_loyalty_reward || $userCoupon->partner_campaign_id)) {
            $userCoupon->coupon->update(['active' => false]);
        }

        $import->forceFill([
            'sync_status' => VendusDiscountCardImport::STATUS_PROCESSED,
            'sync_error' => null,
            'user_coupon_id' => $userCoupon->id,
            'processed_at' => now(),
        ])->save();
    }
}
