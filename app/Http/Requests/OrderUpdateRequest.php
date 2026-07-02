<?php

namespace App\Http\Requests;

class OrderUpdateRequest extends OrderStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage') ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'items.*.parent_order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);
    }
}
