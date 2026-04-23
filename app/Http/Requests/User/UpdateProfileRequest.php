<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['nif', 'phone', 'postal_code'] as $field) {
            if (!$this->has($field)) {
                continue;
            }

            $value = $this->input($field);
            $normalized[$field] = $value === null || $value === ''
                ? null
                : trim((string) $value);
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes','string','max:255'],
            'nif'         => ['sometimes','nullable','string','max:20'],
            'phone'       => ['sometimes','nullable','string','max:30'],
            'birth_date'  => ['sometimes','date'],
            'street'      => ['sometimes','string','max:255'],
            'city'        => ['sometimes','string','max:100'],
            'postal_code' => ['sometimes','nullable','string','max:20'],
            'theme'       => ['sometimes','in:light,dark'],
        ];
    }
}
