<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sessionSnapshot(): array
    {
        $config = config('services.whatsapp', []);

        $baseUrl = (string) ($config['base_url'] ?? '');
        $token = (string) ($config['internal_token'] ?? '');
        $verifySsl = filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $verifySsl = $verifySsl ?? true;
        $timeout = max(1, (int) ($config['timeout'] ?? 10));

        if ($baseUrl === '') {
            return [
                'ok' => false,
                'error' => 'URL do serviço WhatsApp não configurada.',
            ];
        }

        try {
            $request = Http::timeout($timeout)
                ->withOptions(['verify' => $verifySsl]);

            if ($token !== '') {
                $request = $request->withHeaders([
                    'X-Internal-Token' => $token,
                ]);
            }

            $response = $request->get(rtrim($baseUrl, '/') . '/session');

            if ($response->successful()) {
                $json = $response->json();

                return is_array($json) ? $json : [
                    'ok' => false,
                    'error' => 'Resposta inválida do serviço WhatsApp.',
                ];
            }

            return [
                'ok' => false,
                'error' => sprintf('HTTP %s: %s', $response->status(), trim((string) $response->body())),
            ];
        } catch (\Throwable $exception) {
            Log::warning('[WhatsAppService] Falha ao consultar estado da sessão', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
