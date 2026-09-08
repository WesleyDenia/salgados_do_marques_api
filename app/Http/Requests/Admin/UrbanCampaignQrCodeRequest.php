<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UrbanCampaignQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->input('code')) ? mb_strtoupper(trim($this->input('code'))) : $this->input('code'),
            'type' => is_string($this->input('type')) ? mb_strtolower(trim($this->input('type'))) : $this->input('type'),
        ]);
    }

    public function rules(): array
    {
        $qrCodeId = $this->route('qrCode')?->id;

        return [
            'type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'code' => ['required', 'string', 'min:4', 'max:120', 'regex:/^[A-Z0-9-]+$/', Rule::unique('qr_codes', 'code')->ignore($qrCodeId)],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
