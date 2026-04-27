<?php

namespace App\Services;

use App\Models\WhatsAppQueueItem;
use App\Repositories\WhatsAppQueueItemRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsAppQueueService
{
    public function __construct(protected WhatsAppQueueItemRepository $items)
    {
    }

    public function enqueue(array $attributes): WhatsAppQueueItem
    {
        return WhatsAppQueueItem::create(array_merge([
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ], $this->filterNulls($attributes)));
    }

    public function markQueued(WhatsAppQueueItem $item): WhatsAppQueueItem
    {
        $item->forceFill([
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'last_error' => null,
            'last_error_code' => null,
            'queued_at' => now(),
            'started_at' => null,
            'finished_at' => null,
            'sent_at' => null,
            'manual_note' => null,
            'manually_closed_at' => null,
            'resolved_by' => null,
        ])->save();

        return $item->refresh();
    }

    public function markProcessing(WhatsAppQueueItem $item): WhatsAppQueueItem
    {
        $item->forceFill([
            'status' => WhatsAppQueueItem::STATUS_PROCESSING,
            'attempts' => $item->attempts + 1,
            'started_at' => now(),
            'finished_at' => null,
        ])->save();

        return $item->refresh();
    }

    public function markSent(WhatsAppQueueItem $item): WhatsAppQueueItem
    {
        $item->forceFill([
            'status' => WhatsAppQueueItem::STATUS_SENT,
            'last_error' => null,
            'last_error_code' => null,
            'finished_at' => now(),
            'sent_at' => now(),
        ])->save();

        return $item->refresh();
    }

    public function markFailed(WhatsAppQueueItem $item, string $error, ?string $code = null): WhatsAppQueueItem
    {
        $item->forceFill([
            'status' => WhatsAppQueueItem::STATUS_FAILED,
            'last_error' => mb_strimwidth($error, 0, 1000, '...'),
            'last_error_code' => $code,
            'finished_at' => now(),
        ])->save();

        return $item->refresh();
    }

    public function markManuallyClosed(WhatsAppQueueItem $item, ?string $note = null, ?int $resolvedBy = null): WhatsAppQueueItem
    {
        $item->forceFill([
            'status' => WhatsAppQueueItem::STATUS_MANUALLY_CLOSED,
            'last_error' => $note ? mb_strimwidth($note, 0, 1000, '...') : null,
            'finished_at' => now(),
            'manual_note' => $note ? mb_strimwidth($note, 0, 1000, '...') : null,
            'manually_closed_at' => now(),
            'resolved_by' => $resolvedBy,
        ])->save();

        return $item->refresh();
    }

    public function paginateForAdmin(?array $statuses = null, ?array $types = null, int $perPage = 15, string $pageName = 'whatsapp_page'): LengthAwarePaginator
    {
        return $this->items->paginateForAdmin($statuses, $types, $perPage, $pageName);
    }

    protected function filterNulls(array $attributes): array
    {
        return array_filter($attributes, fn ($value) => $value !== null);
    }
}
