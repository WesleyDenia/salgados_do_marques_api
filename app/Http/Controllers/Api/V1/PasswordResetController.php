<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Jobs\SendResetLinkJob;
use App\Jobs\SendWhatsAppOtpJob;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $method = $validated['method'];
        $identifier = $this->normalizeIdentifier($method, $validated['identifier']);
        $expiresAt = CarbonImmutable::now()->addMinutes(15);

        $user = $method === 'whatsapp'
            ? User::where('phone', $identifier)->first()
            : User::where('email', $identifier)->first();

        if ($user) {
            PasswordReset::where('method', $method)
                ->when($method === 'whatsapp', fn ($query) => $query->where('phone', $identifier))
                ->when($method === 'email', fn ($query) => $query->where('email', $identifier))
                ->delete();

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

        $message = $method === 'whatsapp'
            ? 'Código enviado via WhatsApp'
            : 'Link de redefinição enviado por e-mail';

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $this->normalizeIdentifier('whatsapp', $validated['phone']);
        $incomingToken = hash('sha256', $validated['token']);

        $reset = PasswordReset::where('method', 'whatsapp')
            ->where('phone', $phone)
            ->latest()
            ->first();

        if (!$reset || $reset->isExpired() || !hash_equals($reset->token, $incomingToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido ou expirado',
            ], 422);
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            PasswordReset::where('method', 'whatsapp')->where('phone', $phone)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Código inválido ou expirado',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        PasswordReset::where('method', 'whatsapp')
            ->where('phone', $phone)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tokenHash = hash('sha256', $validated['token']);

        $reset = PasswordReset::where('method', 'email')
            ->where('token', $tokenHash)
            ->latest()
            ->first();

        if (!$reset || $reset->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ], 422);
        }

        $email = $reset->email;
        $user = $email ? User::where('email', $email)->first() : null;

        if (!$user) {
            PasswordReset::where('method', 'email')->where('token', $tokenHash)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        PasswordReset::where('method', 'email')
            ->where('email', $email)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ]);
    }

    private function normalizeIdentifier(string $method, string $value): string
    {
        $normalized = trim($value);

        if ($method === 'email') {
            return Str::lower($normalized);
        }

        return preg_replace('/\s+/', '', $normalized);
    }
}
