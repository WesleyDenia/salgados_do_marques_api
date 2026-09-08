<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UrbanCampaignQrCodeShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->route('code');

        if (is_string($code)) {
            $this->merge([
                'code' => mb_strtoupper(trim($code)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do QR Code.',
        ];
    }
}
