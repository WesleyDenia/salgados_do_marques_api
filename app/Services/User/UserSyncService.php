<?php

namespace App\Services\User;

use App\Models\User;
use App\DTOs\CustomerData;
use App\Contracts\Erp\CustomerSyncInterface;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    public function __construct(protected CustomerSyncInterface $erpSync) {}

    public function sync(User $user): ?string
    {
        try {
            $dto = new CustomerData(
                id: (string) $user->id,
                name: $user->name,
                email: $user->email,
                taxNumber: $user->nif,
                phone: $user->phone,
                mobile: $user->phone,
                street: $user->street,
                city: $user->city,
                postalCode: $user->postal_code,
                countryCode: 'PT',
                notes: 'Sincronizado automaticamente a partir do app.'
            );

            $vendusId = $user->external_id;

            if ($vendusId) {
                Log::info("♻️ [UserSync] Atualizando cliente existente no Vendus", [
                    'user_id' => $user->id,
                    'external_id' => $vendusId
                ]);
                $this->erpSync->update($vendusId, $dto);
                return $vendusId;
            }

            // 🚀 Caso ainda não tenha external_id, tenta localizar por NIF
            if ($user->nif) {
                $existing = $this->erpSync->findByFiscalId($user->nif);
                if ($existing && isset($existing['id'])) {
                    $vendusId = (string) $existing['id'];
                    $this->erpSync->update($vendusId, $dto);
                    $user->update(['external_id' => $vendusId]);
                    Log::info("🔗 [UserSync] Cliente vinculado ao Vendus via NIF", [
                        'user_id' => $user->id,
                        'external_id' => $vendusId
                    ]);
                    return $vendusId;
                }
            }

            // 🆕 Caso contrário, cria um novo cliente
            $newId = $this->erpSync->upsert($dto);
            if ($newId) {
                $user->update(['external_id' => $newId]);
                Log::info("✅ [UserSync] Cliente criado no Vendus", [
                    'user_id' => $user->id,
                    'external_id' => $newId
                ]);
            } else {
                Log::warning("⚠️ [UserSync] Criação no Vendus falhou", [
                    'user_id' => $user->id,
                ]);
            }

            return $newId;
        } catch (\Throwable $e) {
            Log::error("❌ [UserSync] Falha ao sincronizar com o Vendus", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
