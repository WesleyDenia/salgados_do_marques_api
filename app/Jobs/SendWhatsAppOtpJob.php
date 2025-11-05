<?php

namespace App\Jobs;

use App\Contracts\Notifications\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $phone, private readonly string $token)
    {
    }

    public function handle(WhatsAppClient $whatsAppClient): void
    {
        $message = sprintf(
            'Seu código de verificação Coinxinhas - Salgados do Marquês é %s. Ele expira em 15 minutos.',
            $this->token
        );

        $sent = $whatsAppClient->sendMessage($this->phone, $message);
        $maskedToken = substr($this->token, 0, 2) . str_repeat('*', max(strlen($this->token) - 2, 0));

        if ($sent) {
            Log::info('[SendWhatsAppOtpJob] OTP enviado com sucesso', [
                'phone' => $this->phone,
                'token' => $maskedToken,
            ]);

            return;
        }

        Log::warning('[SendWhatsAppOtpJob] Falha ao enviar OTP', [
            'phone' => $this->phone,
        ]);
    }
}
