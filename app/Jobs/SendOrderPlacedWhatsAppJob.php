<?php

namespace App\Jobs;

use App\Contracts\Notifications\WhatsAppClient;
use App\Models\Order;
use App\Services\SettingService;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendOrderPlacedWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public int $orderId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(
        WhatsAppClient $whatsAppClient,
        SettingService $settings,
        WhatsAppMessageFormatter $messages
    ): void
    {
        $order = Order::query()
            ->with(['items', 'user'])
            ->find($this->orderId);

        if (!$order) {
            return;
        }

        $phone = (string) ($order->user?->phone ?? '');

        if ($phone === '') {
            Log::warning('[SendOrderPlacedWhatsAppJob] Pedido sem telefone do cliente', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $message = $messages->orderPlaced($order, $this->resolveTimezone($settings));
        $sent = $whatsAppClient->sendMessage($phone, $message);

        if (!$sent) {
            throw new RuntimeException('Nao foi possivel enviar a mensagem do pedido.');
        }
    }

    protected function resolveTimezone(SettingService $settings): string
    {
        return (string) $settings->get(
            'ORDER_TIMEZONE',
            $settings->get('order_timezone', config('app.timezone', 'Europe/Lisbon'))
        );
    }
}
