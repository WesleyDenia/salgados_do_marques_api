<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recurrence' => $this->input('recurrence') ?: null,
            'category_id' => $this->input('category_id') ?: null,
            'type' => $this->input('type') ?: 'money',
        ]);
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'recurrence' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['money', 'percent'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
