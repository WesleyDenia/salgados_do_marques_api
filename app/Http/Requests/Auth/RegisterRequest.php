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
}
