<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UrbanCampaignQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $codeType = $this->input('code_type');

        if (is_string($codeType)) {
            $this->merge([
                'code_type' => mb_strtolower(trim($codeType)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'code_type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'question' => ['required', 'string', 'max:2000'],
            'secret_number' => ['required', 'integer', 'min:1', 'max:255'],
            'collection_label' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'reward_when_correct' => ['required', 'integer', 'min:0', 'max:100'],
            'reward_when_wrong' => ['required', 'integer', 'min:0', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
