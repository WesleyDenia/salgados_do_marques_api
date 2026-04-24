<?php

namespace App\Services;

use App\Models\ErpSyncTask;
use App\Repositories\ErpSyncTaskRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ErpSyncTaskService
{
    public function __construct(protected ErpSyncTaskRepository $tasks)
    {
    }

    public function createOrReuseActive(string $operation, string $entityType, int $entityId, array $attributes = []): ErpSyncTask
    {
        $activeKey = ErpSyncTask::makeActiveKey($operation, $entityType, $entityId);

        try {
            return DB::transaction(function () use ($operation, $entityType, $entityId, $attributes, $activeKey) {
                $task = $this->tasks
                    ->activeQuery($operation, $entityType, $entityId)
                    ->lockForUpdate()
                    ->first();

                if ($task) {
                    return $this->fillAndSave($task, $attributes);
                }

                return ErpSyncTask::create(array_merge([
                    'operation' => $operation,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'active_key' => $activeKey,
                    'status' => ErpSyncTask::STATUS_QUEUED,
                    'queued_at' => now(),
                ], $this->filterNulls($attributes)));
            });
        } catch (QueryException $exception) {
            $task = $this->tasks->activeQuery($operation, $entityType, $entityId)->first();

            if ($task) {
                return $this->fillAndSave($task, $attributes);
            }

            throw $exception;
        }
    }

    public function syncHistorical(string $operation, string $entityType, int $entityId, string $status, array $attributes = []): ErpSyncTask
    {
        return DB::transaction(function () use ($operation, $entityType, $entityId, $status, $attributes) {
            $task = $this->tasks->latestByStatus($operation, $entityType, $entityId, $status)
                ?? new ErpSyncTask([
                    'operation' => $operation,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'status' => $status,
                ]);

            $task->fill($this->filterNulls(array_merge($attributes, [
                'status' => $status,
                'active_key' => in_array($status, ErpSyncTask::TERMINAL_STATUSES, true)
                    ? null
                    : ErpSyncTask::makeActiveKey($operation, $entityType, $entityId),
            ])));
            $task->save();

            return $task->refresh();
        });
    }

    public function markQueued(ErpSyncTask $task): ErpSyncTask
    {
        $task->forceFill([
            'active_key' => ErpSyncTask::makeActiveKey($task->operation, $task->entity_type, $task->entity_id),
            'status' => ErpSyncTask::STATUS_QUEUED,
            'last_error' => null,
            'last_error_code' => null,
            'queued_at' => now(),
            'started_at' => null,
            'finished_at' => null,
        ])->save();

        return $task->refresh();
    }

    public function markProcessing(ErpSyncTask $task): ErpSyncTask
    {
        $task->forceFill([
            'active_key' => ErpSyncTask::makeActiveKey($task->operation, $task->entity_type, $task->entity_id),
            'status' => ErpSyncTask::STATUS_PROCESSING,
            'attempts' => $task->attempts + 1,
            'started_at' => now(),
            'finished_at' => null,
        ])->save();

        return $task->refresh();
    }

    public function markSynced(ErpSyncTask $task, array $attributes = []): ErpSyncTask
    {
        $task->forceFill(array_merge([
            'active_key' => null,
            'status' => ErpSyncTask::STATUS_SYNCED,
            'last_error' => null,
            'last_error_code' => null,
            'finished_at' => now(),
        ], $attributes))->save();

        return $task->refresh();
    }

    public function markFailed(ErpSyncTask $task, string $error, ?string $code = null): ErpSyncTask
    {
        $task->forceFill([
            'active_key' => null,
            'status' => ErpSyncTask::STATUS_FAILED,
            'last_error' => mb_strimwidth($error, 0, 1000, '...'),
            'last_error_code' => $code,
            'finished_at' => now(),
        ])->save();

        return $task->refresh();
    }

    public function markManualReview(ErpSyncTask $task, ?string $error = null, ?int $resolvedBy = null): ErpSyncTask
    {
        $task->forceFill([
            'active_key' => null,
            'status' => ErpSyncTask::STATUS_MANUAL_REVIEW,
            'last_error' => $error ? mb_strimwidth($error, 0, 1000, '...') : null,
            'finished_at' => now(),
            'resolved_by' => $resolvedBy,
        ])->save();

        return $task->refresh();
    }

    public function markCancelled(ErpSyncTask $task, ?string $error = null, ?int $resolvedBy = null): ErpSyncTask
    {
        $task->forceFill([
            'active_key' => null,
            'status' => ErpSyncTask::STATUS_CANCELLED,
            'last_error' => $error ? mb_strimwidth($error, 0, 1000, '...') : null,
            'finished_at' => now(),
            'resolved_by' => $resolvedBy,
        ])->save();

        return $task->refresh();
    }

    protected function fillAndSave(ErpSyncTask $task, array $attributes): ErpSyncTask
    {
        $task->fill($this->filterNulls($attributes));
        $task->save();

        return $task->refresh();
    }

    protected function filterNulls(array $attributes): array
    {
        return array_filter($attributes, fn ($value) => $value !== null);
    }
}
