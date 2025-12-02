<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\UserConsent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Contracts\Erp\CustomerSyncInterface;
use App\Mappers\CustomerMapper;

class AuthService
{
    public function __construct(protected CustomerSyncInterface $erp) {}

    public function register(array $data, array $metadata = []): User
    {
        $lgpdData = $data['lgpd'] ?? null;
        unset($data['lgpd']);

        // Resposta amigável se e-mail já estiver em uso (evita SQL 500)
        if (User::where('email', $data['email'])->exists()) {
            abort(409, 'Este e-mail já está em uso.');
        }

        if (!$lgpdData) {
            abort(422, 'Aceite do termo LGPD é obrigatório.');
        }

        $lgpdSetting = Setting::where('key', 'LGPD_TERMS')->first();

        if (!$lgpdSetting) {
            abort(422, 'Termo LGPD não configurado. Tente novamente mais tarde.');
        }

        if (empty($lgpdData['accepted']) || $lgpdData['accepted'] !== true) {
            abort(422, 'É necessário aceitar o termo LGPD para prosseguir.');
        }

        $termsContent = (string) $lgpdSetting->value;
        $serverHash   = hash('sha256', $termsContent);
        $providedHash = (string) ($lgpdData['hash'] ?? '');

        if (!hash_equals($serverHash, $providedHash)) {
            abort(422, 'O termo LGPD foi atualizado. Atualize a tela e confirme novamente.');
        }

        $consentAt       = now();
        $consentVersion  = $lgpdData['version']
            ?? optional($lgpdSetting->updated_at)->toISOString()
            ?? optional($lgpdSetting->created_at)->toISOString()
            ?? $consentAt->toISOString();
        $consentChannel  = $lgpdData['channel'] ?? ($metadata['channel'] ?? null);
        $consentIp       = $metadata['ip'] ?? null;
        $consentUserAgent = $metadata['user_agent'] ?? null;

        $data['password'] = Hash::make($data['password']);
        $data['theme'] = $data['theme'] ?? 'light';
        $data['lgpd_consent_at'] = $consentAt;
        $data['lgpd_consent_version'] = $consentVersion;
        $data['lgpd_consent_hash'] = $serverHash;
        $data['lgpd_consent_channel'] = $consentChannel;

        try {
            /** @var User $user */
            $user = User::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Proteção dupla para erros de integridade
            if ($e->getCode() === '23000') {
                abort(409, 'Este e-mail já está em uso.');
            }
            Log::error('💥 [AuthService] Erro ao criar usuário', ['error' => $e->getMessage()]);
            abort(500, 'Não foi possível concluir o cadastro. Tente novamente em instantes.');
        }

        UserConsent::create([
            'user_id'     => $user->id,
            'type'        => 'lgpd',
            'version'     => $consentVersion,
            'hash'        => $serverHash,
            'content'     => $termsContent,
            'consented_at'=> $consentAt,
            'channel'     => $consentChannel,
            'ip_address'  => $consentIp,
            'user_agent'  => $consentUserAgent,
        ]);

        try {
            $dto = CustomerMapper::fromUser($user);

            // Estratégia: se já houver external_id por algum motivo, garante update; senão faz upsert
            if (!empty($user->external_id)) {
                $ok = $this->erp->update((string)$user->external_id, $dto);
                if (!$ok) Log::warning('⚠️ [AuthService] Update ERP falhou; tentando upsert');
            }

            if (empty($user->external_id)) {
                $externalId = $this->erp->upsert($dto);
                if ($externalId) {
                    $user->update(['external_id' => $externalId]);
                } else {
                    Log::error('❌ [AuthService] ERP upsert falhou; user ficará sem external_id por enquanto', ['user_id' => $user->id]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('💥 [AuthService] Falha ao sincronizar cliente com ERP', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }

        return $user;
    }

    public function login(array $data): array
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            abort(401, 'Credenciais inválidas');
        }

        /** @var \App\Models\User|\Laravel\Sanctum\HasApiTokens $user */
        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    public function logout(User $user)
    {
        $user->tokens()->delete();
    }
}
