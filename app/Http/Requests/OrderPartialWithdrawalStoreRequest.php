<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderPartialWithdrawalStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'requested_units' => ['required', 'integer', 'min:25'],
            'scheduled_at' => ['required', 'date'],
            'generate_child_order' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('generate_child_order')) {
            $this->merge(['generate_child_order' => true]);
        }
    }
}
