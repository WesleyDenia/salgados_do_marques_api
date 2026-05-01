<?php

namespace App\Services;

use App\Jobs\SendResetLinkJob;
use App\Jobs\SendWhatsAppOtpJob;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\WhatsAppQueueItem;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(
        protected WhatsAppMessageFormatter $messages,
        protected WhatsAppQueueService $whatsAppQueue,
    ) {
    }

    public function forgot(array $data): array
    {
        $method = $data['method'];
        $identifier = self::normalizeIdentifier($method, $data['identifier']);
        $identifierHash = hash('sha256', $identifier);
        $expiresAt = CarbonImmutable::now()->addMinutes(15);

        Log::info('[PasswordResetService] forgot-password request received', [
            'method' => $method,
            'identifier_hash' => $identifierHash,
            'identifier_length' => mb_strlen($identifier),
        ]);

        $user = $this->findUserByIdentifier($method, $identifier);

        if (!$user) {
            Log::warning('[PasswordResetService] forgot-password lookup missed', [
                'method' => $method,
                'identifier_hash' => $identifierHash,
            ]);

            return [
                'success' => true,
                'message' => $method === 'whatsapp'
                    ? 'Código enviado via WhatsApp'
                    : 'Link de redefinição enviado por e-mail',
            ];
        }

        Log::info('[PasswordResetService] forgot-password user matched', [
            'method' => $method,
            'user_id' => $user->id,
            'identifier_hash' => $identifierHash,
        ]);

        $this->deleteExistingResets($method, $identifier);

        $plainToken = $method === 'whatsapp'
            ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            : Str::random(64);

        $reset = PasswordReset::create([
            'email' => $method === 'email' ? $identifier : null,
            'phone' => $method === 'whatsapp' ? $identifier : null,
            'method' => $method,
            'token' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        Log::info('[PasswordResetService] password reset token stored', [
            'method' => $method,
            'user_id' => $user->id,
            'reset_id' => $reset->id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        if ($method === 'whatsapp') {
            $message = $this->messages->otp($plainToken);

            try {
                $queueItem = $this->whatsAppQueue->enqueue([
                    'type' => WhatsAppQueueItem::TYPE_OTP,
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                    'recipient_name' => $user->name,
                    'phone' => $identifier,
                    'message' => $message,
                ]);

                Log::info('[PasswordResetService] whatsapp otp queued', [
                    'method' => $method,
                    'user_id' => $user->id,
                    'reset_id' => $reset->id,
                    'queue_item_id' => $queueItem->id,
                ]);

                SendWhatsAppOtpJob::dispatch($queueItem->id)->onQueue('notifications');

                Log::info('[PasswordResetService] whatsapp otp job dispatched', [
                    'method' => $method,
                    'user_id' => $user->id,
                    'reset_id' => $reset->id,
                    'queue_item_id' => $queueItem->id,
                    'queue' => 'notifications',
                ]);
            } catch (\Throwable $exception) {
                Log::error('[PasswordResetService] failed to enqueue whatsapp otp', [
                    'method' => $method,
                    'user_id' => $user->id,
                    'reset_id' => $reset->id,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        } else {
            try {
                dispatch(new SendResetLinkJob($identifier, $plainToken));

                Log::info('[PasswordResetService] email reset job dispatched', [
                    'method' => $method,
                    'user_id' => $user->id,
                    'reset_id' => $reset->id,
                    'identifier_hash' => $identifierHash,
                ]);
            } catch (\Throwable $exception) {
                Log::error('[PasswordResetService] failed to dispatch email reset job', [
                    'method' => $method,
                    'user_id' => $user->id,
                    'reset_id' => $reset->id,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
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
        $phoneHash = hash('sha256', $phone);

        $reset = PasswordReset::query()
            ->where('method', 'whatsapp')
            ->where('phone', $phone)
            ->latest()
            ->first();

        if (!$reset || $reset->isExpired() || !hash_equals($reset->token, $incomingToken)) {
            Log::warning('[PasswordResetService] verify-otp rejected', [
                'phone_hash' => $phoneHash,
                'reason' => !$reset
                    ? 'missing_reset'
                    : ($reset->isExpired() ? 'expired' : 'token_mismatch'),
            ]);

            return $this->invalidCodeResponse();
        }

        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            Log::warning('[PasswordResetService] verify-otp user not found', [
                'phone_hash' => $phoneHash,
                'reset_id' => $reset->id,
            ]);

            $this->deleteExistingResets('whatsapp', $phone);

            return $this->invalidCodeResponse();
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->deleteExistingResets('whatsapp', $phone);

        Log::info('[PasswordResetService] verify-otp password updated', [
            'user_id' => $user->id,
            'reset_id' => $reset->id,
            'phone_hash' => $phoneHash,
        ]);

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
            Log::warning('[PasswordResetService] reset-password rejected', [
                'reason' => !$reset ? 'missing_reset' : 'expired',
            ]);

            return $this->invalidTokenResponse();
        }

        $email = $reset->email;
        $user = $email ? User::query()->where('email', $email)->first() : null;

        if (!$user) {
            Log::warning('[PasswordResetService] reset-password user not found', [
                'reset_id' => $reset->id,
                'email_hash' => $email ? hash('sha256', Str::lower($email)) : null,
            ]);

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

        Log::info('[PasswordResetService] reset-password completed', [
            'user_id' => $user->id,
            'reset_id' => $reset->id,
            'email_hash' => $email ? hash('sha256', Str::lower($email)) : null,
        ]);

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
