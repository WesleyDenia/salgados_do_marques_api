<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Services\PlanningSlotCapacityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanningSlotOperationalRulesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Setting::class) ?? false;
    }

    public function rules(): array
    {
        $slots = app(PlanningSlotCapacityService::class)->slotKeys();
        $leadTimeRules = [];

        foreach ($slots as $slot) {
            $leadTimeRules["lead_times.{$slot}"] = ['required', 'integer', 'min:0'];
        }

        return [
            'lead_times' => ['required', 'array'],
            ...$leadTimeRules,
            'blocked_dates' => ['present', 'array'],
            'blocked_dates.*.date' => ['required', 'date_format:Y-m-d'],
            'blocked_dates.*.slots' => ['required', 'array', 'min:1'],
            'blocked_dates.*.slots.*' => ['required', 'string', Rule::in($slots)],
        ];
    }

    public function attributes(): array
    {
        $slots = app(PlanningSlotCapacityService::class)->slotLabels();
        $leadTimeAttributes = [];

        foreach ($slots as $slot => $label) {
            $leadTimeAttributes["lead_times.{$slot}"] = "Lead Time ({$label})";
        }

        return [
            ...$leadTimeAttributes,
            'blocked_dates.*.date' => 'Data Bloqueada',
            'blocked_dates.*.slots' => 'Slots da Data Bloqueada',
        ];
    }
}
