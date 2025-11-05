<?php
// app/Services/Erp/Vendus/VendusCustomerSyncService.php

namespace App\Services\Erp\Vendus;

use App\Contracts\Erp\CustomerSyncInterface;
use App\DTOs\CustomerData;
use Illuminate\Support\Facades\Log;

class VendusCustomerSyncService implements CustomerSyncInterface
{
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
        $payload = $this->toVendusPayload($customer);
        Log::info('📤 [Vendus] POST /clients payload', $payload);

        $resp = $this->http->client()->post('/clients/', $payload);
        Log::info('📥 [Vendus] POST /clients resp', ['status' => $resp->status(), 'body' => $resp->body()]);

        if ($resp->successful()) {
            $json = $resp->json();
            return $json['id'] ?? ($json['client']['id'] ?? null);
        }

        // Se falhou e existe NIF → tentar localizar e atualizar (idempotência)
        if (!empty($payload['fiscal_id'])) {
            $existing = $this->findByFiscalId($payload['fiscal_id']);
            if ($existing && isset($existing['id'])) {
                $id = (string)$existing['id'];
                if ($this->update($id, $customer)) {
                    return $id;
                }
            }
        }

        Log::error('❌ [Vendus] upsert falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
        return null;
    }

    public function update(string $externalId, CustomerData $customer): bool
    {
        $payload = $this->toVendusPayload($customer);
        Log::info('♻️ [Vendus] PATCH /clients/{id}', ['id' => $externalId, 'payload' => $payload]);

        $resp = $this->http->client()->send('PATCH', "/clients/{$externalId}", ['json' => $payload]);

        Log::info('📥 [Vendus] PATCH resp', ['status' => $resp->status(), 'body' => $resp->body()]);
        if ($resp->successful()) return true;

        Log::error('❌ [Vendus] update falhou', ['id' => $externalId, 'status' => $resp->status(), 'body' => $resp->body()]);
        return false;
    }

    public function findByFiscalId(string $fiscalId): ?array
    {
        if ($fiscalId === '') return null;

        $resp = $this->http->client()->get('/clients/', [
            'fiscal_id' => $fiscalId,
            'status'    => 'active',
        ]);

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
}
