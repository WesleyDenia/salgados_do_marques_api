<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerCampaignStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge([
                'code' => mb_strtoupper(trim($code)),
            ]);
        }
    }

    public function rules(): array
    {
        $campaignId = $this->route('partner_campaign')?->id;

        return [
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'coupon_id' => ['required', 'integer', 'exists:coupons,id'],
            'public_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', Rule::unique('partner_campaigns', 'code')->ignore($campaignId)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
