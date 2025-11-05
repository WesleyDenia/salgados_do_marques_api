<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description' => ['required','string'],
            'code'        => ['nullable','string','unique:promotions,code'],
            'starts_at'   => ['nullable','date'],
            'ends_at'     => ['nullable','date','after_or_equal:starts_at'],
            'is_global'   => ['nullable','boolean'],
            'active'      => ['nullable','boolean'],
        ];
    }
}
