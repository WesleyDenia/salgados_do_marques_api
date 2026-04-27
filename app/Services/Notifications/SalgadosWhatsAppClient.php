<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalgadosWhatsAppClient implements WhatsAppClient
{
    public function sendMessage(string $phone, string $message): bool
    {
        $config = config('services.whatsapp', []);

        $baseUrl = (string) ($config['base_url'] ?? '');
        $token = (string) ($config['internal_token'] ?? '');
        $verifySsl = filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $verifySsl = $verifySsl ?? true;
        $timeout = max(1, (int) ($config['timeout'] ?? 10));

        if ($baseUrl === '') {
            Log::warning('[SalgadosWhatsAppClient] URL do servico nao configurada');
            return false;
        }

        try {
            $request = Http::asJson()
                ->timeout($timeout)
                ->withOptions(['verify' => $verifySsl]);

            if ($token !== '') {
                $request = $request->withHeaders([
                    'X-Internal-Token' => $token,
                ]);
            }

            $response = $request->post(rtrim($baseUrl, '/') . '/send', [
                'to' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('[SalgadosWhatsAppClient] Mensagem enviada', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::warning('[SalgadosWhatsAppClient] Falha ao enviar mensagem', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('[SalgadosWhatsAppClient] Erro inesperado ao enviar mensagem', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
