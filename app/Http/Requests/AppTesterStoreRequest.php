<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppTesterStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'operating_system' => ['required', 'string', Rule::in(['android', 'ios'])],
            'consent' => ['accepted'],
            'source_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique o seu nome.',
            'email.required' => 'Indique o seu email.',
            'email.email' => 'Indique um email válido.',
            'phone.required' => 'Indique o seu telefone ou WhatsApp.',
            'operating_system.required' => 'Selecione o sistema operativo.',
            'operating_system.in' => 'Selecione Android ou iPhone.',
            'consent.accepted' => 'É necessário aceitar o tratamento dos dados para receber o convite.',
        ];
    }
}
