<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WapifyWhatsAppClient implements WhatsAppClient
{
    protected ?string $lastError = null;

    public function sendMessage(string $phone, string $message): bool
    {
        $this->lastError = null;
        $config = config('services.wapify', []);

        $instance = (string) ($config['instance'] ?? '');
        $apiKey   = (string) ($config['api_key'] ?? '');
        $endpoint = (string) ($config['base_url'] ?? 'https://app.wapify.net/api/text-message.php');
        $verifySsl = filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $verifySsl = $verifySsl ?? true;

        if ($instance === '' || $apiKey === '') {
            $this->lastError = 'Credenciais da Wapify não configuradas.';
            Log::warning('[WapifyWhatsAppClient] Credenciais não configuradas');
            return false;
        }

        try {
            $response = Http::asForm()
                ->withOptions(['verify' => $verifySsl])
                ->post($endpoint, [
                    'number'   => $phone,
                    'msg'      => $message,
                    'instance' => $instance,
                    'apikey'   => $apiKey,
                ]);

            if ($response->successful()) {
                Log::info('[WapifyWhatsAppClient] Mensagem enviada', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return true;
            }

            $this->lastError = $this->responseError($response);
            Log::warning('[WapifyWhatsAppClient] Falha ao enviar mensagem', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::error('[WapifyWhatsAppClient] Erro inesperado ao enviar mensagem', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    protected function responseError($response): ?string
    {
        if (!$response) {
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            if (isset($json['message']) && is_string($json['message'])) {
                return $json['message'];
            }

            if (isset($json['error']) && is_string($json['error'])) {
                return $json['error'];
            }
        }

        return sprintf('HTTP %s: %s', $response->status(), trim((string) $response->body()));
    }
}
