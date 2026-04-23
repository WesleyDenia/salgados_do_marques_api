<?php
// app/Services/Erp/Vendus/VendusCustomerSyncService.php

namespace App\Services\Erp\Vendus;

use App\Contracts\Erp\CustomerSyncInterface;
use App\DTOs\CustomerData;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class VendusCustomerSyncService implements CustomerSyncInterface
{
    protected ?string $lastError = null;

    public function __construct(protected VendusHttpClient $http) {}

    /** Normaliza o payload exatamente no shape do Vendus */
    protected function toVendusPayload(CustomerData $c): array
    {
        return [
            'fiscal_id'          => (string) ($c->taxNumber ?? ''),       // NIF
            'name'               => $c->name ?: 'Cliente sem nome',
            'address'            => (string) ($c->street ?? ''),
            'postalcode'         => (string) ($c->postalCode ?? ''),
            'city'               => (string) ($c->city ?? ''),
            'phone'              => (string) ($c->phone ?? ''),
            'mobile'             => (string) ($c->mobile ?? ''),
            'email'              => (string) ($c->email ?? ''),
            'country'            => $c->countryCode ?: 'PT',
            'external_reference' => 'USR-' . ($c->id ?? uniqid()),
            'send_email'         => 'no',
            'notes'              => $c->notes ?: 'Sincronizado via API Salgados do Marquês',
        ];
    }

    public function upsert(CustomerData $customer): ?string
    {
        $this->lastError = null;
        $payload = $this->toVendusPayload($customer);
        $resp = null;

        if (!empty($payload['fiscal_id'])) {
            $existing = $this->findByFiscalId($payload['fiscal_id']);

            if ($existing && isset($existing['id'])) {
                $id = (string) $existing['id'];

                if ($this->update($id, $customer)) {
                    return $id;
                }

                return null;
            }
        }

        try {
            $resp = $this->http->client()->post('/clients/', $payload);
        } catch (RequestException $e) {
            $resp = $e->response;
            $this->lastError = $this->responseError($resp) ?: $e->getMessage();

            Log::warning('⚠️ [Vendus] POST /clients lançou exceção', [
                'status' => $resp?->status(),
                'body' => $resp?->body(),
                'error' => $e->getMessage(),
            ]);
        }

        if ($resp) {
            Log::info('📥 [Vendus] POST /clients resp', ['status' => $resp->status(), 'body' => $resp->body()]);
        }

        if ($resp && $resp->successful()) {
            $json = $resp->json();
            return $json['id'] ?? ($json['client']['id'] ?? null);
        }

        $this->lastError = $this->responseError($resp) ?: 'Vendus não retornou sucesso ao criar cliente.';

        Log::error('❌ [Vendus] upsert falhou', [
            'status' => $resp?->status(),
            'body' => $resp?->body(),
        ]);
        return null;
    }

    public function update(string $externalId, CustomerData $customer): bool
    {
        $this->lastError = null;
        $payload = $this->toVendusPayload($customer);
        Log::info('♻️ [Vendus] PATCH /clients/{id}', ['id' => $externalId, 'payload' => $payload]);

        try {
            $resp = $this->http->client()->send('PATCH', "/clients/{$externalId}", ['json' => $payload]);
        } catch (RequestException $e) {
            $resp = $e->response;
            $this->lastError = $this->responseError($resp) ?: $e->getMessage();

            Log::error('❌ [Vendus] update lançou exceção', [
                'id' => $externalId,
                'status' => $resp?->status(),
                'body' => $resp?->body(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        Log::info('📥 [Vendus] PATCH resp', ['status' => $resp->status(), 'body' => $resp->body()]);
        if ($resp->successful()) return true;

        $this->lastError = $this->responseError($resp) ?: 'Vendus não retornou sucesso ao atualizar cliente.';

        Log::error('❌ [Vendus] update falhou', ['id' => $externalId, 'status' => $resp->status(), 'body' => $resp->body()]);
        return false;
    }

    public function findByFiscalId(string $fiscalId): ?array
    {
        if ($fiscalId === '') return null;

        try {
            $resp = $this->http->client()->get('/clients/', [
                'fiscal_id' => $fiscalId,
                'status'    => 'active',
            ]);
        } catch (RequestException $e) {
            $resp = $e->response;

            if ($resp?->status() === 404) {
                Log::info('🔎 [Vendus] Cliente não encontrado por NIF', [
                    'fiscal_id' => $fiscalId,
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);

                return null;
            }

            throw $e;
        }

        Log::info('🔎 [Vendus] GET /clients by NIF', ['status' => $resp->status(), 'body' => $resp->body()]);

        if ($resp->successful()) {
            $data = $resp->json();
            return (is_array($data) && count($data) > 0) ? $data[0] : null;
        }

        return null;
    }

    public function delete(string $externalId): bool
    {
        $resp = $this->http->client()->delete("/clients/{$externalId}");
        return $resp->successful();
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    protected function responseError($resp): ?string
    {
        if (!$resp) {
            return null;
        }

        $json = $resp->json();

        if (is_array($json) && isset($json['errors'][0]['message'])) {
            $code = $json['errors'][0]['code'] ?? null;
            $message = $json['errors'][0]['message'];

            return trim(($code ? "{$code}: " : '') . $message);
        }

        return trim("HTTP {$resp->status()}: " . $resp->body());
    }
}
