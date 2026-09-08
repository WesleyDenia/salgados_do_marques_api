<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UrbanCampaignClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        $this->merge([
            'code' => is_string($code) ? mb_strtoupper(trim($code)) : $code,
            'phone' => $this->normalizePhone((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:4', 'max:120'],
            'question_id' => ['required', 'integer'],
            'response_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'regex:/^3519\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Informe o número de telemóvel.',
            'phone.regex' => 'Introduz um número de telemóvel português válido.',
            'question_id.required' => 'Informe a pergunta respondida.',
            'response_id.required' => 'Escolha uma resposta.',
        ];
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if (preg_match('/^9\d{8}$/', $digits)) {
            return '351'.$digits;
        }

        return $digits;
    }
}
