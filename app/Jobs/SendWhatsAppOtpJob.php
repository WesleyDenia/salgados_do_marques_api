<?php

namespace App\Jobs;

use App\Contracts\Notifications\WhatsAppClient;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $phone, private readonly string $token)
    {
    }

    public function handle(WhatsAppClient $whatsAppClient, WhatsAppMessageFormatter $messages): void
    {
        $message = $messages->otp($this->token);

        $sent = $whatsAppClient->sendMessage($this->phone, $message);

        if ($sent) {
            return;
        }
    }
}
