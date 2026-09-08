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

class SendUrbanCampaignCouponWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public int $queueItemId) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(WhatsAppClient $whatsAppClient, WhatsAppQueueService $queue): void
    {
        $item = WhatsAppQueueItem::query()->find($this->queueItemId);

        if (! $item || $item->status === WhatsAppQueueItem::STATUS_MANUALLY_CLOSED || $item->status === WhatsAppQueueItem::STATUS_SENT) {
            return;
        }

        if (trim((string) $item->phone) === '') {
            $error = 'Telemovel do resgate nao informado.';
            $queue->markFailed($item, $error);

            throw new RuntimeException($error);
        }

        $queue->markProcessing($item);
        $sent = $whatsAppClient->sendMessage((string) $item->phone, (string) $item->message);

        if ($sent) {
            $queue->markSent($item);

            return;
        }

        $error = $whatsAppClient->lastError() ?: 'Nao foi possivel enviar o cupom da Campanha Urbana via WhatsApp.';
        $queue->markFailed($item, $error);

        throw new RuntimeException($error);
    }
}
