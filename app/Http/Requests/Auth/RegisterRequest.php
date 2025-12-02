<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','email','unique:users,email'],
            'password'              => ['required','string','min:6','confirmed'],
            'nif'                   => ['nullable','string','max:20'],
            'phone'                 => ['required', 'string', 'max:30'],
            'birth_date'            => ['nullable', 'date'],
            'lgpd'                  => ['required', 'array'],
            'lgpd.accepted'         => ['required', 'accepted'],
            'lgpd.version'          => ['required', 'string', 'max:100'],
            'lgpd.hash'             => ['required', 'string', 'size:64'],
            'lgpd.channel'          => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Informe o nome.',
            'name.string'           => 'O nome deve ser um texto.',
            'name.max'              => 'O nome pode ter no máximo 255 caracteres.',

            'email.required'        => 'Informe o e-mail.',
            'email.email'           => 'Informe um e-mail válido.',
            'email.unique'          => 'Este e-mail já está em uso.',

            'password.required'     => 'Informe a senha.',
            'password.string'       => 'A senha deve ser um texto.',
            'password.min'          => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed'    => 'A confirmação da senha não confere.',

            'nif.max'               => 'O NIF pode ter no máximo 20 caracteres.',
            'phone.required'        => 'Informe o telefone.',
            'phone.max'             => 'O telefone pode ter no máximo 30 caracteres.',
            'birth_date.date'       => 'Informe uma data de nascimento válida.',

            'lgpd.required'         => 'O termo LGPD é obrigatório.',
            'lgpd.accepted.required'=> 'É necessário aceitar o termo LGPD.',
            'lgpd.accepted.accepted'=> 'É necessário aceitar o termo LGPD.',
            'lgpd.version.required' => 'Versão do termo LGPD é obrigatória.',
            'lgpd.version.string'   => 'Versão do termo LGPD inválida.',
            'lgpd.version.max'      => 'Versão do termo LGPD muito longa.',
            'lgpd.hash.required'    => 'Hash do termo LGPD é obrigatório.',
            'lgpd.hash.string'      => 'Hash do termo LGPD inválido.',
            'lgpd.hash.size'        => 'Hash do termo LGPD inválido.',
            'lgpd.channel.string'   => 'Canal do termo LGPD inválido.',
            'lgpd.channel.max'      => 'Canal do termo LGPD muito longo.',
        ];
    }
}
