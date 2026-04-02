<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LgpdService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LgpdController extends Controller
{
    public function __construct(protected LgpdService $service) {}

    public function terms(): JsonResponse
    {
        try {
            return response()->json($this->service->terms());
        } catch (NotFoundHttpException) {
            return response()->json(['message' => 'Termo LGPD não configurado'], 404);
        }
    }
}
