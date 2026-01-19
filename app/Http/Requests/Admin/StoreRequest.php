<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->route('store')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')->where(fn ($query) => $query->where('city', $this->input('city')))->ignore($storeId),
            ],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::in(['principal', 'revenda'])],
            'is_active' => ['nullable', 'boolean'],
            'accepts_orders' => ['nullable', 'boolean'],
            'default_store' => ['nullable', 'boolean'],
        ];
    }
}
