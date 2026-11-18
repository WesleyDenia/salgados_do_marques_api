<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDirectoryService
{
    /** @return Collection<int, array{id: int, name: string, phone: string|null}> */
    public function search(string $term): Collection
    {
        $phoneDigits = preg_replace('/\D+/', '', $term) ?? '';

        if (str_starts_with($phoneDigits, '351') && strlen($phoneDigits) > 9) {
            $phoneDigits = substr($phoneDigits, 3);
        }

        return User::query()
            ->where('role', User::ROLE_CLIENTE)
            ->where(function ($query) use ($term, $phoneDigits) {
                $query->where('name', 'like', "%{$term}%");

                if (strlen($phoneDigits) >= 2) {
                    $query
                        ->orWhere('phone_normalized', 'like', "%{$phoneDigits}%")
                        ->orWhereRaw("replace(replace(replace(replace(replace(replace(replace(phone, '+', ''), ' ', ''), '-', ''), '/', ''), '(', ''), ')', ''), '.', '') like ?", [
                            "%{$phoneDigits}%",
                        ]);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'phone_normalized'])
            ->map(fn (User $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone_normalized ?? $customer->phone,
            ])
            ->values();
    }

    public function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '351') && strlen($digits) > 9) {
            $digits = substr($digits, 3);
        }

        return preg_match('/^\d{9}$/', $digits) === 1 ? $digits : null;
    }

    public function findByNormalizedPhone(string $phone): ?User
    {
        $customer = User::query()
            ->where('phone_normalized', $phone)
            ->first();

        if ($customer) {
            return $customer;
        }

        // The one-off command populates the indexed key. This fallback keeps
        // the write path safe during the deployment interval before it runs.
        $customer = User::query()
            ->where('role', User::ROLE_CLIENTE)
            ->whereNotNull('phone')
            ->cursor()
            ->first(fn (User $user) => $this->normalizePhone($user->phone) === $phone);

        if ($customer && $customer->phone_normalized !== $phone) {
            $customer->forceFill(['phone_normalized' => $phone])->save();
        }

        return $customer;
    }

    public function findOrCreateOperationalCustomer(
        string $name,
        string $phone,
        string $erpSyncStatus = 'pending',
    ): User {
        $normalizedPhone = $this->normalizePhone($phone);

        if ($normalizedPhone === null) {
            throw ValidationException::withMessages([
                'customer_contact' => 'Indique um telemóvel português válido com 9 dígitos.',
            ]);
        }

        $existing = $this->findByNormalizedPhone($normalizedPhone);

        if ($existing) {
            return $existing;
        }

        try {
            return User::query()->create([
                'name' => trim($name),
                'email' => "operacional-cliente-{$normalizedPhone}@salgados.invalid",
                'password' => Hash::make(Str::random(64)),
                'phone' => $normalizedPhone,
                'phone_normalized' => $normalizedPhone,
                'role' => User::ROLE_CLIENTE,
                'active' => true,
                'theme' => 'light',
                'erp_sync_status' => $erpSyncStatus,
            ]);
        } catch (QueryException $exception) {
            // The unique key protects concurrent order submissions. If another
            // transaction won the race, reuse that customer rather than failing.
            $existing = $this->findByNormalizedPhone($normalizedPhone);

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }
}
