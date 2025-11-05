<?php

namespace App\Contracts\Erp;

use App\DTOs\CustomerData;

interface CustomerSyncInterface
{
    /** Upsert por NIF (se houver), senão cria */
    public function upsert(CustomerData $customer): ?string;

    /** Update direto por externalId */
    public function update(string $externalId, CustomerData $customer): bool;

    /** Busca por NIF (fiscal_id) no ERP */
    public function findByFiscalId(string $fiscalId): ?array;

    public function delete(string $externalId): bool;
}
