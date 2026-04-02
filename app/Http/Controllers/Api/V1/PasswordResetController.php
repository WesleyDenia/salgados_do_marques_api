<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(protected PasswordResetService $service) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        return response()->json($this->service->forgot($request->validated()));
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $response = $this->service->verifyOtp($request->validated());

        return response()->json($response, $response['success'] ? 200 : 422);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $response = $this->service->reset($request->validated());

        return response()->json($response, $response['success'] ? 200 : 422);
    }
}
