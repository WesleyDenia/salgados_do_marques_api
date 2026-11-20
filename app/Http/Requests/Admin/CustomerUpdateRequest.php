<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(['name', 'email', 'phone', 'nif'])
            ->filter(fn (string $field) => $this->has($field))
            ->mapWithKeys(fn (string $field) => [
                $field => $this->input($field) === null || $this->input($field) === ''
                    ? null
                    : trim((string) $this->input($field)),
            ])
            ->all());
    }

    public function rules(): array
    {
        $customerId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'nif' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
        ];
    }
}
