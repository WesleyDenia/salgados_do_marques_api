<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomeComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $componentId = $this->route('home_component')?->id;

        return [
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z][A-Za-z0-9]*$/',
                Rule::unique('home_components', 'key')->ignore($componentId),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
