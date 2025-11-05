<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes','string'],
            'body'        => ['sometimes','string'],
            'code'        => ['sometimes','string','unique:coupons,code,'.$this->coupon->id],
            'recurrence'  => ['sometimes','in:none,daily,weekly,monthly,yearly'],
            'starts_at'   => ['sometimes','date'],
            'ends_at'     => ['sometimes','date','after_or_equal:starts_at'],
            'active'      => ['sometimes','boolean'],
            'type'        => ['sometimes','in:money,percent'],
            'amount'      => ['sometimes','numeric','min:0'],
        ];
    }
}
