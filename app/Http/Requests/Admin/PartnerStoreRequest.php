<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PartnerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        $name = $this->input('name');

        $this->merge([
            'slug' => Str::slug($slug ?: (string) $name),
        ]);
    }

    public function rules(): array
    {
        $partnerId = $this->route('partner')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('partners', 'slug')->ignore($partnerId)],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
