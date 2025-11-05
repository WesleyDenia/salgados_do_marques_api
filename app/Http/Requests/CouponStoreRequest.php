<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['required','string'],
            'body'        => ['required','string'],
            'code'        => ['required','string','unique:coupons,code'],
            'recurrence'  => ['nullable','in:none,daily,weekly,monthly,yearly'],
            'starts_at'   => ['nullable','date'],
            'ends_at'     => ['nullable','date','after_or_equal:starts_at'],
            'active'      => ['nullable','boolean'],
            'type'        => ['required','in:money,percent'],
            'amount'      => ['required','numeric','min:0'],
        ];
    }
}
