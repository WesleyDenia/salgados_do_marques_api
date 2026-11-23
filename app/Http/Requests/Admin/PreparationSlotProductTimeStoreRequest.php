<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreparationSlotProductTimeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'operational_preparation_slot_id' => [
                'required',
                'integer',
                Rule::exists('operational_preparation_slots', 'id'),
            ],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'batch_size' => ['required', 'integer', 'min:1', 'max:9999'],
            // Minutes accepts decimals, e.g. 3.5 = 3 minutes and 30 seconds.
            'preparation_minutes' => ['required', 'numeric', 'min:0.1', 'max:1440'],
        ];
    }
}
