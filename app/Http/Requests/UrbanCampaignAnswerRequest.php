<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UrbanCampaignAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge([
                'code' => mb_strtoupper(trim($code)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:4', 'max:120'],
            'question_id' => ['required', 'integer'],
            'response_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do QR Code.',
            'question_id.required' => 'Informe a pergunta respondida.',
            'response_id.required' => 'Escolha uma resposta.',
        ];
    }
}
