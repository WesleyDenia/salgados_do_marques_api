<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalgadosWhatsAppClient implements WhatsAppClient
{
    protected ?string $lastError = null;

    public function sendMessage(string $phone, string $message): bool
    {
        $this->lastError = null;
        $config = config('services.whatsapp', []);

        $baseUrl = (string) ($config['base_url'] ?? '');
        $token = (string) ($config['internal_token'] ?? '');
        $verifySsl = filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $verifySsl = $verifySsl ?? true;
        $timeout = max(1, (int) ($config['timeout'] ?? 10));

        if ($baseUrl === '') {
            $this->lastError = 'URL do serviço WhatsApp não configurada.';
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

            $this->lastError = $this->responseError($response);
            Log::warning('[SalgadosWhatsAppClient] Falha ao enviar mensagem', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::error('[SalgadosWhatsAppClient] Erro inesperado ao enviar mensagem', [
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
