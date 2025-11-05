<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCouponUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'usage_limit' => ['sometimes','nullable','integer','min:0'],
            'usage_count' => ['sometimes','nullable','integer','min:0'],
            'expires_at'  => ['sometimes','nullable','date'],
            'active'      => ['sometimes','boolean'],
        ];
    }
}
