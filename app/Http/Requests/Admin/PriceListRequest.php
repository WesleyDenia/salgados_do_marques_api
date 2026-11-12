<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $priceList = $this->route('price_list');

        return [
            'name' => ['required', 'string', 'max:120'],
            'priority' => ['nullable', 'integer', 'min:'.($priceList?->is_default ? 0 : 1)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $startsAt = $this->input('starts_at');

                    if ($startsAt !== null && Carbon::parse($value)->lessThan(Carbon::parse($startsAt))) {
                        $fail('A data final deve ser posterior ou igual à inicial.');
                    }
                },
            ],
            'active' => ['nullable', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('order_tags', 'id')->where('active', true)],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'entries.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'entries.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
