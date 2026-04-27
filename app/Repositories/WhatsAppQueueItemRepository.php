<?php

namespace App\Repositories;

use App\Models\WhatsAppQueueItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppQueueItemRepository extends BaseRepository
{
    public function __construct(WhatsAppQueueItem $model)
    {
        parent::__construct($model);
    }

    public function queryForAdmin(?array $statuses = null, ?array $types = null): Builder
    {
        return $this->model
            ->newQuery()
            ->when($statuses, fn (Builder $query) => $query->whereIn('status', $statuses))
            ->when($types, fn (Builder $query) => $query->whereIn('type', $types))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function paginateForAdmin(?array $statuses = null, ?array $types = null, int $perPage = 15, string $pageName = 'whatsapp_page'): LengthAwarePaginator
    {
        return $this->queryForAdmin($statuses, $types)->paginate($perPage, ['*'], $pageName);
    }
}
