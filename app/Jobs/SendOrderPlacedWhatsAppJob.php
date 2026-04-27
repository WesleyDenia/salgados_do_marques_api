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
use RuntimeException;

class SendOrderPlacedWhatsAppJob implements ShouldQueue
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
            return;
        }

        $queue->markProcessing($item);
        $sent = $whatsAppClient->sendMessage((string) $item->phone, (string) $item->message);

        if ($sent) {
            $queue->markSent($item);
            return;
        }

        $error = $whatsAppClient->lastError() ?: 'Nao foi possivel enviar a mensagem do pedido.';
        $queue->markFailed($item, $error);

        throw new RuntimeException($error);
    }
}
