<?php

namespace App\Http\Requests\Admin;

use App\Models\UrbanCampaignCouponConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UrbanCampaignCouponConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $couponType = $this->input('coupon_type');

        $this->merge([
            'coupon_type' => is_string($couponType) ? mb_strtolower(trim($couponType)) : $couponType,
            'discount_type' => $this->input('discount_type') ?: UrbanCampaignCouponConfig::TYPE_PERCENT,
        ]);
    }

    public function rules(): array
    {
        $configId = $this->route('couponConfig')?->id;

        return [
            'coupon_type' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('urban_campaign_coupon_configs', 'coupon_type')->ignore($configId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'discount_type' => ['required', Rule::in([UrbanCampaignCouponConfig::TYPE_MONEY, UrbanCampaignCouponConfig::TYPE_PERCENT])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('discount_type') === UrbanCampaignCouponConfig::TYPE_PERCENT && (float) $this->input('amount') > 100) {
                $validator->errors()->add('amount', 'O percentual não pode ser maior que 100.');
            }

            if (
                filled($this->input('starts_at')) &&
                filled($this->input('ends_at')) &&
                strtotime((string) $this->input('ends_at')) < strtotime((string) $this->input('starts_at'))
            ) {
                $validator->errors()->add('ends_at', 'A data final deve ser igual ou posterior à data inicial.');
            }
        });
    }
}
