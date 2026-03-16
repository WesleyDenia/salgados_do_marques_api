<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderAvailabilityDatesRequest extends FormRequest
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
        ];
    }
}
