<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    private const DAY_KEYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->route('store')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')->where(fn ($query) => $query->where('city', $this->input('city')))->ignore($storeId),
            ],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::in(['principal', 'revenda'])],
            'is_active' => ['nullable', 'boolean'],
            'accepts_orders' => ['nullable', 'boolean'],
            'default_store' => ['nullable', 'boolean'],
            'pickup_weekly_schedule' => ['required', 'array', 'size:7'],
            'pickup_date_exceptions' => ['nullable', 'array'],
            'pickup_date_exceptions.*.date' => ['nullable', 'date_format:Y-m-d'],
            'pickup_date_exceptions.*.is_open' => ['nullable', 'boolean'],
            'pickup_date_exceptions.*.start_time' => ['nullable', 'date_format:H:i'],
            'pickup_date_exceptions.*.end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator): void
    {
        foreach (self::DAY_KEYS as $day) {
            $validator->addRules([
                "pickup_weekly_schedule.{$day}" => ['required', 'array'],
                "pickup_weekly_schedule.{$day}.is_open" => ['required', 'boolean'],
                "pickup_weekly_schedule.{$day}.start_time" => ['nullable', 'date_format:H:i'],
                "pickup_weekly_schedule.{$day}.end_time" => ['nullable', 'date_format:H:i'],
            ]);
        }

        $validator->after(function ($validator): void {
            $schedule = $this->input('pickup_weekly_schedule', []);

            foreach (self::DAY_KEYS as $day) {
                $this->validateWindow($validator, "pickup_weekly_schedule.{$day}", $schedule[$day] ?? null);
            }

            $exceptions = $this->input('pickup_date_exceptions', []);
            $dates = [];

            foreach ($exceptions as $index => $exception) {
                $date = $exception['date'] ?? null;
                $hasTimes = !empty($exception['start_time']) || !empty($exception['end_time']);

                if (($date === null || $date === '') && !$hasTimes) {
                    continue;
                }

                if ($date === null || $date === '') {
                    $validator->errors()->add(
                        "pickup_date_exceptions.{$index}.date",
                        'Informe a data da exceção.'
                    );
                    continue;
                }

                if (in_array($date, $dates, true)) {
                    $validator->errors()->add(
                        "pickup_date_exceptions.{$index}.date",
                        'Cada exceção deve usar uma data única.'
                    );
                }

                $dates[] = $date;
                $this->validateWindow($validator, "pickup_date_exceptions.{$index}", $exception);
            }
        });
    }

    protected function validateWindow($validator, string $path, mixed $window): void
    {
        if (!is_array($window)) {
            return;
        }

        $isOpen = filter_var($window['is_open'] ?? false, FILTER_VALIDATE_BOOL);
        $startTime = $window['start_time'] ?? null;
        $endTime = $window['end_time'] ?? null;

        if ($isOpen) {
            if (!$startTime) {
                $validator->errors()->add("{$path}.start_time", 'Informe a hora inicial quando o dia estiver aberto.');
            }

            if (!$endTime) {
                $validator->errors()->add("{$path}.end_time", 'Informe a hora final quando o dia estiver aberto.');
            }

            if ($startTime && $endTime && $startTime > $endTime) {
                $validator->errors()->add("{$path}.end_time", 'A hora final deve ser igual ou posterior à hora inicial.');
            }

            return;
        }

        if ($startTime || $endTime) {
            $validator->errors()->add("{$path}.start_time", 'Dias fechados não podem ter horário configurado.');
        }
    }
}
