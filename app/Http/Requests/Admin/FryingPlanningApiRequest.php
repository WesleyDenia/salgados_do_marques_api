<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FryingPlanningApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('planning.view');
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
        ];
    }
}
