<?php

namespace App\Http\Requests\Admin;

use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $statusKeys = array_keys($orderService->statusLabels());

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in($statusKeys)],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'partial', 'paid'])],
            'slot' => ['nullable', 'string', Rule::in(['manha', 'tarde', 'noite'])],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'day' => ['nullable', 'date_format:Y-m-d'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('search');

        $payload = [];

        if (is_string($search)) {
            $payload['search'] = trim($search);
        }

        if ($this->routeIs('admin.orders.daily') && ! $this->filled('day')) {
            /** @var OrderService $orderService */
            $orderService = app(OrderService::class);
            $payload['day'] = now($orderService->orderSettings()['timezone'])->format('Y-m-d');
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
