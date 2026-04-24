<?php

namespace App\Console\Commands;

use App\Models\ErpSyncTask;
use App\Models\VendusDiscountCardImport;
use App\Services\ErpSyncTaskService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillErpSyncTasks extends Command
{
    protected $signature = 'erp-sync-tasks:backfill {--dry-run : Count records without writing tasks}';

    protected $description = 'Backfill explicit ERP sync tasks from legacy queue/import state.';

    public function handle(ErpSyncTaskService $tasks): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $skipped = 0;

        foreach (VendusDiscountCardImport::query()->cursor() as $import) {
            if (!$dryRun) {
                $tasks->syncHistorical(
                    ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
                    ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
                    $import->id,
                    $this->mapImportStatus($import->sync_status),
                    [
                        'attempts' => (int) $import->sync_attempts,
                        'external_id' => $import->external_id,
                        'external_code' => $import->external_code,
                        'last_error' => $import->sync_error,
                        'queued_at' => $import->queued_at,
                        'started_at' => $import->sync_status === VendusDiscountCardImport::STATUS_PROCESSING ? $import->updated_at : null,
                        'finished_at' => in_array($import->sync_status, [
                            VendusDiscountCardImport::STATUS_PROCESSED,
                            VendusDiscountCardImport::STATUS_FAILED,
                            VendusDiscountCardImport::STATUS_MANUALLY_CLOSED,
                        ], true) ? $import->updated_at : null,
                    ]
                );
            }

            $created++;
        }

        if ($this->tableExists('jobs')) {
            foreach (DB::table('jobs')->cursor() as $job) {
                $mapped = $this->mapQueuePayload((string) $job->payload, ErpSyncTask::STATUS_QUEUED);

                if (!$mapped) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $tasks->createOrReuseActive($mapped['operation'], $mapped['entity_type'], $mapped['entity_id'], [
                        'status' => $mapped['status'],
                        'queued_at' => isset($job->created_at) ? Carbon::createFromTimestamp((int) $job->created_at) : now(),
                    ]);
                }

                $created++;
            }
        }

        if ($this->tableExists('failed_jobs')) {
            foreach (DB::table('failed_jobs')->cursor() as $job) {
                $mapped = $this->mapQueuePayload((string) $job->payload, ErpSyncTask::STATUS_FAILED);

                if (!$mapped) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $tasks->syncHistorical($mapped['operation'], $mapped['entity_type'], $mapped['entity_id'], ErpSyncTask::STATUS_FAILED, [
                        'last_error' => $this->exceptionSummary((string) $job->exception),
                        'finished_at' => isset($job->failed_at) ? Carbon::parse((string) $job->failed_at) : now(),
                    ]);
                }

                $created++;
            }
        }

        $this->info("ERP task backfill complete. mapped={$created} skipped={$skipped} dry_run=" . ($dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }

    protected function mapImportStatus(string $status): string
    {
        return match ($status) {
            VendusDiscountCardImport::STATUS_DOWNLOADED => ErpSyncTask::STATUS_PENDING,
            VendusDiscountCardImport::STATUS_QUEUED => ErpSyncTask::STATUS_QUEUED,
            VendusDiscountCardImport::STATUS_PROCESSING => ErpSyncTask::STATUS_PROCESSING,
            VendusDiscountCardImport::STATUS_PROCESSED => ErpSyncTask::STATUS_SYNCED,
            VendusDiscountCardImport::STATUS_MANUALLY_CLOSED => ErpSyncTask::STATUS_MANUAL_REVIEW,
            VendusDiscountCardImport::STATUS_FAILED => ErpSyncTask::STATUS_FAILED,
            default => ErpSyncTask::STATUS_PENDING,
        };
    }

    protected function mapQueuePayload(string $payload, string $status): ?array
    {
        if (str_contains($payload, 'SyncCustomerToErpJob') && preg_match('/"userId";i:(\d+)/', $payload, $matches)) {
            return [
                'operation' => ErpSyncTask::OPERATION_SYNC_CUSTOMER,
                'entity_type' => ErpSyncTask::ENTITY_USER,
                'entity_id' => (int) $matches[1],
                'status' => $status,
            ];
        }

        if (str_contains($payload, 'ProcessVendusDiscountCardImportJob') && preg_match('/"importId";i:(\d+)/', $payload, $matches)) {
            return [
                'operation' => ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
                'entity_type' => ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
                'entity_id' => (int) $matches[1],
                'status' => $status,
            ];
        }

        return null;
    }

    protected function exceptionSummary(string $exception): string
    {
        $lines = preg_split('/\R/', trim($exception));

        return (string) ($lines[0] ?? 'Erro sem detalhe.');
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
