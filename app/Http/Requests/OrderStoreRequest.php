<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')
                    ->where('is_active', true)
                    ->where('accepts_orders', true),
            ],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('active', true)],
            'items.*.flavors' => ['nullable', 'array'],
            'items.*.flavors.*' => ['integer', 'exists:flavors,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.flavors.array' => 'Os sabores do item devem ser enviados como uma lista de IDs.',
            'items.*.flavors.*.integer' => 'Cada sabor informado deve ser um ID numérico válido.',
            'items.*.flavors.*.exists' => 'Um dos sabores informados não existe.',
            'scheduled_at.required' => 'Informe a data e hora de retirada.',
            'scheduled_at.date' => 'A data e hora de retirada devem ser válidas.',
        ];
    }
}
