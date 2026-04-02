<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settingId = $this->route('setting')?->id;

        return [
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9_]+$/', Rule::unique('settings', 'key')->ignore($settingId)],
            'type' => ['required', 'in:string,integer,boolean,json'],
            'value' => ['nullable'],
            'editable' => ['nullable', 'boolean'],
        ];
    }
}
