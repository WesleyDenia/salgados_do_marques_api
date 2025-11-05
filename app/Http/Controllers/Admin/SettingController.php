<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('key')->paginate(20);

        return view('admin.settings.index', compact('settings'));
    }

    public function create()
    {
        return view('admin.settings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'type' => ['required', 'in:string,integer,boolean,json'],
            'value' => ['nullable'],
            'editable' => ['nullable', 'boolean'],
        ]);

        $value = $data['value'] ?? null;

        if ($value !== null) {
            $value = $this->castValue($value, $data['type']);
        }

        Setting::create([
            'key' => $data['key'],
            'type' => $data['type'],
            'value' => $value,
            'editable' => $request->boolean('editable', true),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Configuração criada com sucesso.');
    }

    public function edit(Setting $setting)
    {
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request, Setting $setting)
    {
        if (!$setting->editable) {
            return redirect()
                ->route('admin.settings.index')
                ->with('status', 'Esta configuração não pode ser alterada.');
        }

        $request->validate([
            'value' => ['required'],
        ]);

        $value = $this->castValue($request->input('value'), $setting->type);

        $setting->value = $value;
        $setting->save();

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Configuração atualizada com sucesso.');
    }

    protected function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOL);
            case 'integer':
                return (int) $value;
            case 'json':
                return $this->sanitizeJson($value);
            default:
                return $value;
        }
    }

    protected function sanitizeJson($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        throw ValidationException::withMessages([
            'value' => 'JSON inválido. Verifique o formato.',
        ]);
    }
}
