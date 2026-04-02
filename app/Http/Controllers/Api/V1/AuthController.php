<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service) {}

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

        $auth = $this->service->authPayload($user);

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $auth['token'],
            'config' => $auth['config'],
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        try {
            $auth = $this->service->login($request->validated());
        } catch (AuthenticationException) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        return response()->json([
            'user'  => new UserResource($auth['user']),
            'token' => $auth['token'],
            'config' => $auth['config'],
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

    public function refresh(Request $request)
    {
        $auth = $this->service->refresh($request->user());

        return response()->json([
            'user'   => new UserResource($auth['user']),
            'token'  => $auth['token'],
            'config' => $auth['config'],
        ]);
    }
}
