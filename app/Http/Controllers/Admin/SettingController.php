<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Services\AdminSettingService;

class SettingController extends Controller
{
    public function __construct(protected AdminSettingService $settings) {}

    public function index()
    {
        return view('admin.settings.index', [
            'settings' => $this->settings->list(),
        ]);
    }

    public function create()
    {
        return view('admin.settings.create');
    }

    public function store(SettingRequest $request)
    {
        $this->settings->create($request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Configuração criada com sucesso.');
    }

    public function edit(Setting $setting)
    {
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(SettingRequest $request, Setting $setting)
    {
        if (!$this->settings->update($setting, $request->validated())) {
            return redirect()
                ->route('admin.settings.index')
                ->with('status', 'Esta configuração não pode ser alterada.');
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Configuração atualizada com sucesso.');
    }
}
