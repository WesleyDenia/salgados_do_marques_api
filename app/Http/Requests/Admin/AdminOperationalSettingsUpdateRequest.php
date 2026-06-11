<?php

namespace App\Http\Requests\Admin;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminOperationalSettingsUpdateRequest extends FormRequest
{
    public const E164_REGEX = '/^\+[1-9]\d{7,14}$/';

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('WHATSAPP_ORDER_TO') && is_string($this->input('WHATSAPP_ORDER_TO'))) {
            $this->merge([
                'WHATSAPP_ORDER_TO' => trim($this->input('WHATSAPP_ORDER_TO')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer'],
            'ORDER_START_TIME' => ['sometimes', 'required', 'date_format:H:i'],
            'ORDER_END_TIME' => ['sometimes', 'required', 'date_format:H:i'],
            'ORDER_MINIMUM_MINUTES' => ['sometimes', 'required', 'integer', 'min:0'],
            'ORDER_CANCEL_MINUTES' => ['sometimes', 'required', 'integer', 'min:0'],
            'ORDER_SCHEDULING_WINDOW_DAYS' => ['sometimes', 'required', 'integer', 'min:1'],
            'WHATSAPP_ORDER_TO' => ['sometimes', 'nullable', 'string', 'regex:' . self::E164_REGEX],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $settings = app(SettingService::class);

                if (! $validator->errors()->has('ORDER_START_TIME') && ! $validator->errors()->has('ORDER_END_TIME')) {
                    $start = (string) $this->input('ORDER_START_TIME', $settings->get('ORDER_START_TIME', '12:00'));
                    $end = (string) $this->input('ORDER_END_TIME', $settings->get('ORDER_END_TIME', '20:00'));

                    if ($end <= $start) {
                        $validator->errors()->add('ORDER_END_TIME', 'A hora de fim deve ser estritamente posterior à hora de início.');
                    }
                }

                if (! $validator->errors()->has('ORDER_MINIMUM_MINUTES') && ! $validator->errors()->has('ORDER_CANCEL_MINUTES')) {
                    $minimumMinutes = (int) $this->input('ORDER_MINIMUM_MINUTES', $settings->get('ORDER_MINIMUM_MINUTES', 30));
                    $cancelMinutes = (int) $this->input('ORDER_CANCEL_MINUTES', $settings->get('ORDER_CANCEL_MINUTES', 60));

                    if ($cancelMinutes < $minimumMinutes) {
                        $validator->errors()->add('ORDER_CANCEL_MINUTES', 'A janela de cancelamento não pode ser inferior à antecedência mínima de pedido.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'ORDER_SCHEDULING_WINDOW_DAYS.min' => 'A janela de agendamento deve ser de pelo menos 1 dia.',
            'WHATSAPP_ORDER_TO.regex' => 'O número WhatsApp deve estar no formato E.164.',
        ];
    }
}
