<?php

namespace App\Repositories;

use App\Models\ErpSyncTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ErpSyncTaskRepository extends BaseRepository
{
    public function __construct(ErpSyncTask $model)
    {
        parent::__construct($model);
    }

    public function activeQuery(string $operation, string $entityType, int $entityId): Builder
    {
        return $this->model
            ->newQuery()
            ->where('active_key', ErpSyncTask::makeActiveKey($operation, $entityType, $entityId));
    }

    public function latestForEntity(string $operation, string $entityType, int $entityId): ?ErpSyncTask
    {
        return $this->model
            ->newQuery()
            ->where('operation', $operation)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->latest('id')
            ->first();
    }

    public function latestByStatus(string $operation, string $entityType, int $entityId, string $status): ?ErpSyncTask
    {
        return $this->model
            ->newQuery()
            ->where('operation', $operation)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', $status)
            ->latest('id')
            ->first();
    }

    public function queryForAdmin(?array $statuses = null): Builder
    {
        return $this->model
            ->newQuery()
            ->when($statuses, fn (Builder $query) => $query->whereIn('status', $statuses))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function paginateForAdmin(?array $statuses = null, int $perPage = 15, string $pageName = 'tasks_page'): LengthAwarePaginator
    {
        return $this->queryForAdmin($statuses)->paginate($perPage, ['*'], $pageName);
    }
}
