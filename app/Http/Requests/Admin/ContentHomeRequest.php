<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $selectedKey = $this->route('content_home')?->component_name;

        $componentKeys = HomeComponent::query()
            ->when($selectedKey, function ($query, $selectedKey) {
                $query->where('is_active', true)
                    ->orWhere('key', $selectedKey);
            }, function ($query) {
                $query->where('is_active', true);
            })
            ->pluck('key')
            ->all();

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'show_component_title' => ['nullable', 'boolean'],
            'text_body' => ['nullable', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['text', 'image', 'only_image', 'component'])],
            'layout' => ['required', 'string', 'max:50'],
            'component_name' => ['nullable', 'string', 'max:100', Rule::in($componentKeys)],
            'component_props' => ['nullable', 'json'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'cta_image_only' => ['nullable', 'boolean'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'publish_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
