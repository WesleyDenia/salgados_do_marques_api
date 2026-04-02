<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCouponActivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_id' => ['required', 'integer', 'exists:coupons,id'],
        ];
    }
}
