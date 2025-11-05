<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes','string','max:255'],
            'phone'       => ['sometimes','string','max:30'],
            'birth_date'  => ['sometimes','date'],
            'street'      => ['sometimes','string','max:255'],
            'city'        => ['sometimes','string','max:100'],
            'postal_code' => ['sometimes','string','max:20'],
            'theme'       => ['sometimes','in:light,dark'],
        ];
    }
}
