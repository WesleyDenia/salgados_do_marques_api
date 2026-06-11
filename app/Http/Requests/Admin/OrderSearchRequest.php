<?php

namespace App\Http\Requests\Admin;

use App\Services\OrderService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'week_start' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:end_date'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_date'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('start_date') || ! $this->filled('end_date')) {
                return;
            }

            if ((string) $this->input('end_date') < (string) $this->input('start_date')) {
                $validator->errors()->add(
                    'end_date',
                    'A data final deve ser igual ou posterior à data inicial.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('search');
        $actionMethod = $this->route()?->getActionMethod();

        $payload = [];

        if (is_string($search)) {
            $payload['search'] = trim($search);
        }

        if ($actionMethod === 'daily' && ! $this->filled('day')) {
            /** @var OrderService $orderService */
            $orderService = app(OrderService::class);
            $payload['day'] = now($orderService->orderSettings()['timezone'])->format('Y-m-d');
        }

        if ($actionMethod === 'weekly' && ! $this->filled('week_start')) {
            /** @var OrderService $orderService */
            $orderService = app(OrderService::class);
            $payload['week_start'] = now($orderService->orderSettings()['timezone'])
                ->startOfWeek(CarbonInterface::MONDAY)
                ->format('Y-m-d');
        }

        if ($actionMethod === 'period' && ! $this->filled('start_date') && ! $this->filled('end_date')) {
            /** @var OrderService $orderService */
            $orderService = app(OrderService::class);
            $today = now($orderService->orderSettings()['timezone'])->format('Y-m-d');
            $payload['start_date'] = $today;
            $payload['end_date'] = $today;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
