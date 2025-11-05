<?php
// app/Services/Erp/Vendus/VendusHttpClient.php

namespace App\Services\Erp\Vendus;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class VendusHttpClient
{
    public function __construct(
        protected string $baseUrl = '',
        protected string $token = '',
    ) {
        $this->baseUrl = $this->baseUrl ?: config('services.vendus.base_url');
        $this->token   = $this->token   ?: config('services.vendus.token');
    }

    public function client()
    {
        return Http::withBasicAuth($this->token, '')
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 200); // resiliente a falhas momentâneas
    }

    public function logRequest(string $endpoint, array $params = []): void
    {
        $query = http_build_query($params);
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        if (!empty($query)) {
            $url .= '?' . $query;
        }

        Log::debug('[VendusHttpClient] URL gerada: ' . $url);
    }
}
