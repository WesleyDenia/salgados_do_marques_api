<?php

namespace App\Http\Requests;

class OrderUpdateRequest extends OrderStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage') ?? false;
    }
}
