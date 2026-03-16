<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderAvailabilityMinutesRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'hour' => ['required', 'date_format:H:i'],
        ];
    }
}
