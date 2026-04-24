<?php

namespace App\Services;

use App\Jobs\SendResetLinkJob;
use App\Jobs\SendWhatsAppOtpJob;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function forgot(array $data): array
    {
        $method = $data['method'];
        $identifier = self::normalizeIdentifier($method, $data['identifier']);
        $expiresAt = CarbonImmutable::now()->addMinutes(15);

        $user = $this->findUserByIdentifier($method, $identifier);

        if ($user) {
            $this->deleteExistingResets($method, $identifier);

            $plainToken = $method === 'whatsapp'
                ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
                : Str::random(64);

            PasswordReset::create([
                'email' => $method === 'email' ? $identifier : null,
                'phone' => $method === 'whatsapp' ? $identifier : null,
                'method' => $method,
                'token' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
            ]);

            if ($method === 'whatsapp') {
                dispatch(new SendWhatsAppOtpJob($identifier, $plainToken));
            } else {
                dispatch(new SendResetLinkJob($identifier, $plainToken));
            }
        }

        return [
            'success' => true,
            'message' => $method === 'whatsapp'
                ? 'Código enviado via WhatsApp'
                : 'Link de redefinição enviado por e-mail',
        ];
    }

    public function verifyOtp(array $data): array
    {
        $phone = self::normalizeIdentifier('whatsapp', $data['phone']);
        $incomingToken = hash('sha256', $data['token']);

        $reset = PasswordReset::query()
            ->where('method', 'whatsapp')
            ->where('phone', $phone)
            ->latest()
            ->first();

        if (!$reset || $reset->isExpired() || !hash_equals($reset->token, $incomingToken)) {
            return $this->invalidCodeResponse();
        }

        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            $this->deleteExistingResets('whatsapp', $phone);

            return $this->invalidCodeResponse();
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->deleteExistingResets('whatsapp', $phone);

        return [
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ];
    }

    public function reset(array $data): array
    {
        $tokenHash = hash('sha256', $data['token']);

        $reset = PasswordReset::query()
            ->where('method', 'email')
            ->where('token', $tokenHash)
            ->latest()
            ->first();

        if (!$reset || $reset->isExpired()) {
            return $this->invalidTokenResponse();
        }

        $email = $reset->email;
        $user = $email ? User::query()->where('email', $email)->first() : null;

        if (!$user) {
            PasswordReset::query()
                ->where('method', 'email')
                ->where('token', $tokenHash)
                ->delete();

            return $this->invalidTokenResponse();
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        PasswordReset::query()
            ->where('method', 'email')
            ->where('email', $email)
            ->delete();

        return [
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ];
    }

    protected function findUserByIdentifier(string $method, string $identifier): ?User
    {
        return $method === 'whatsapp'
            ? User::query()->where('phone', $identifier)->first()
            : User::query()->where('email', $identifier)->first();
    }

    protected function deleteExistingResets(string $method, string $identifier): void
    {
        PasswordReset::query()
            ->where('method', $method)
            ->when($method === 'whatsapp', fn ($query) => $query->where('phone', $identifier))
            ->when($method === 'email', fn ($query) => $query->where('email', $identifier))
            ->delete();
    }

    public static function normalizeIdentifier(string $method, string $value): string
    {
        $normalized = trim($value);

        if ($method === 'email') {
            return Str::lower($normalized);
        }

        return preg_replace('/\s+/', '', $normalized);
    }

    protected function invalidCodeResponse(): array
    {
        return [
            'success' => false,
            'message' => 'Código inválido ou expirado',
        ];
    }

    protected function invalidTokenResponse(): array
    {
        return [
            'success' => false,
            'message' => 'Token inválido ou expirado',
        ];
    }
}
