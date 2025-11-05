<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Services\SettingService;

class SettingController extends Controller
{
    public function __construct(protected SettingService $service) {}

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $key)
    {
        $value = $this->service->get($key);
        return response()->json(['key' => $key, 'value' => $value]);
    }

    public function update(SettingRequest $request, string $key)
    {
        $updated = $this->service->set($key, $request->input('value'));
        return response()->json(['message' => 'Configuração atualizada', 'data' => $updated]);
    }
}
