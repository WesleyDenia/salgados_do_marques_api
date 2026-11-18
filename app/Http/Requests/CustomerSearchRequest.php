<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = $this->input('query');

        if (is_string($query)) {
            $this->merge(['query' => trim($query)]);
        }
    }
}
