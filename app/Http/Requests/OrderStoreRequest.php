<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            // Keeping these nullable for backward compatibility with the mobile app.
            // The panel enforces these via frontend validation (zod).
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:255'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')
                    ->where('is_active', true)
                    ->where('accepts_orders', true),
            ],
            'scheduled_at' => ['required', 'date'],
            'allow_schedule_exception' => ['nullable', 'boolean'],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'partial', 'paid'])],
            'slot' => ['nullable', 'string', Rule::in(['manha', 'tarde', 'noite'])],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('order_tags', 'id')],
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
            'store_id.required' => 'Selecione uma loja válida.',
            'store_id.exists' => 'A loja selecionada não está disponível.',
            'items.*.flavors.array' => 'Os sabores do item devem ser enviados como uma lista de IDs.',
            'items.*.flavors.*.integer' => 'Cada sabor informado deve ser um ID numérico válido.',
            'items.*.flavors.*.exists' => 'Um dos sabores informados não existe.',
            'scheduled_at.required' => 'Indique a data e hora da encomenda.',
            'scheduled_at.date' => 'A data e hora da encomenda devem ser válidas.',
            'allow_schedule_exception.boolean' => 'A exceção de horário deve ser verdadeira ou falsa.',
            'payment_status.in' => 'O estado de pagamento informado não é válido.',
            'slot.in' => 'O slot operacional informado não é válido.',
            'tag_ids.array' => 'As tags devem ser enviadas como uma lista de IDs.',
            'tag_ids.*.integer' => 'Cada tag deve ser identificada por um ID numérico válido.',
            'tag_ids.*.exists' => 'Uma das tags selecionadas não existe.',
        ];
    }
}
