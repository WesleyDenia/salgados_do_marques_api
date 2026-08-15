<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreparationCapacityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        return [
            'slots' => ['required', 'array'],
            'slots.*.id' => ['nullable', 'integer', Rule::exists('operational_preparation_slots', 'id')],
            'slots.*.name' => ['required', 'string', 'max:80'],
            'slots.*.active' => ['required', 'boolean'],
            'slots.*.display_order' => ['nullable', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
            'settings.*.operational_preparation_slot_id' => ['nullable', 'integer', Rule::exists('operational_preparation_slots', 'id')],
            'settings.*.slot_index' => ['nullable', 'integer', 'min:0'],
            'settings.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'settings.*.batch_size' => ['required', 'integer', 'min:1', 'max:9999'],
            'settings.*.preparation_time_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
        ];
    }
}
