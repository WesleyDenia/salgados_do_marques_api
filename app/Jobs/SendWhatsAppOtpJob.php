<?php

namespace App\Jobs;

use App\Contracts\Notifications\WhatsAppClient;
use App\Models\WhatsAppQueueItem;
use App\Services\WhatsAppQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendWhatsAppOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public int $queueItemId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(WhatsAppClient $whatsAppClient, WhatsAppQueueService $queue): void
    {
        $item = WhatsAppQueueItem::query()->find($this->queueItemId);

        if (!$item || $item->status === WhatsAppQueueItem::STATUS_MANUALLY_CLOSED || $item->status === WhatsAppQueueItem::STATUS_SENT) {
            Log::info('[SendWhatsAppOtpJob] skipped', [
                'queue_item_id' => $this->queueItemId,
                'reason' => !$item ? 'missing_item' : $item->status,
            ]);

            return;
        }

        Log::info('[SendWhatsAppOtpJob] processing queued otp', [
            'queue_item_id' => $item->id,
            'entity_id' => $item->entity_id,
            'phone_hash' => hash('sha256', (string) $item->phone),
            'attempts' => $item->attempts,
        ]);

        $queue->markProcessing($item);

        $sent = $whatsAppClient->sendMessage((string) $item->phone, (string) $item->message);

        if ($sent) {
            Log::info('[SendWhatsAppOtpJob] otp sent', [
                'queue_item_id' => $item->id,
                'entity_id' => $item->entity_id,
            ]);

            $queue->markSent($item);
            return;
        }

        $error = $whatsAppClient->lastError() ?: 'Nao foi possivel enviar a mensagem via WhatsApp.';
        $queue->markFailed($item, $error);

        Log::warning('[SendWhatsAppOtpJob] otp send failed', [
            'queue_item_id' => $item->id,
            'entity_id' => $item->entity_id,
            'error' => $error,
        ]);

        throw new RuntimeException($error);
    }
}
