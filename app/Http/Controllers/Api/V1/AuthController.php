<?php

namespace App\Http\Controllers\Api\V1;


use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
    protected AuthService $service;
    protected SettingService $settings;

    public function __construct(AuthService $service, SettingService $settings)
    {
        $this->service = $service;
        $this->settings = $settings;
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = $this->service->register(
            $validated,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'channel' => data_get($validated, 'lgpd.channel', 'mobile-app'),
            ]
        );

        // Gera token após criar o usuário
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
            'config' => $this->getAppConfig(),
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        Log::info('Tentativa de login', [
            'payload' => $credentials
        ]);

        if (!Auth::guard('web')->attempt($credentials)) {
            Log::warning('Falha no login', [
                'payload' => $credentials
            ]);
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        /** @var \App\Models\User|\Laravel\Sanctum\HasApiTokens */
        $user = Auth::guard('web')->user();

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Login bem sucedido', [
            'user_id' => $user->id,
            'email'   => $user->email
        ]);

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
            'config' => $this->getAppConfig(),
        ]);
    }



    public function logout(Request $request)
    {
        $this->service->logout($request->user());
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    protected function getAppConfig(): array
    {
        $baseUrl = Setting::where('key', 'ASSET_BASE_URL')->value('value');
        if (!$baseUrl) {
            $baseUrl = $this->settings->get('ASSET_BASE_URL', config('app.url'));
        }

        return [
            'assets_base_url' => rtrim($baseUrl ?? '', '/'),
        ];
    }
}
