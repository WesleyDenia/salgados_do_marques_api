<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FryingPlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('date')) {
            $this->merge(['date' => now('Europe/Lisbon')->format('Y-m-d')]);
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'store_id' => ['nullable', 'integer', Rule::exists('stores', 'id')],
        ];
    }
}
