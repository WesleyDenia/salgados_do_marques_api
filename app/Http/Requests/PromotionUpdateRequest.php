<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description' => ['sometimes','string'],
            'code'        => ['sometimes','string','unique:promotions,code,'.$this->promotion->id],
            'starts_at'   => ['sometimes','date'],
            'ends_at'     => ['sometimes','date','after_or_equal:starts_at'],
            'is_global'   => ['sometimes','boolean'],
            'active'      => ['sometimes','boolean'],
        ];
    }
}
