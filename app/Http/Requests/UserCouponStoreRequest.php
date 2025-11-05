<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCouponStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'     => ['required','exists:users,id'],
            'coupon_id'   => ['required','exists:coupons,id'],
            'usage_limit' => ['nullable','integer','min:0'],
            'usage_count' => ['nullable','integer','min:0'],
            'expires_at'  => ['nullable','date'],
            'active'      => ['nullable','boolean'],
        ];
    }
}
