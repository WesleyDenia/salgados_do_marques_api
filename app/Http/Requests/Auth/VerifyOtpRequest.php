<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'size:6'],
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }
}
