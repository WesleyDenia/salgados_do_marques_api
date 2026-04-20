<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppTesterStoreRequest;
use App\Services\AppTesterService;

class AppTesterController extends Controller
{
    public function __construct(
        protected AppTesterService $service,
    ) {}

    public function store(AppTesterStoreRequest $request)
    {
        $validated = $request->validated();
        $isAndroid = $validated['operating_system'] === 'android';

        $tester = $this->service->register($validated, $request->path());

        $message = $isAndroid
            ? 'Registo confirmado. Vamos enviar o convite oficial do Google para o seu email assim que a sua vaga for processada.'
            : 'Registo recebido. Nesta primeira abertura estamos a dar prioridade a Android, mas vamos guardar o seu contacto para as próximas vagas.';

        return response()->json([
            'message' => $message,
            'data' => [
                'id' => $tester->id,
                'eligible_for_current_phase' => $tester->is_android_eligible,
                'status' => $tester->status,
            ],
        ], $tester->wasRecentlyCreated ? 201 : 200);
    }
}
