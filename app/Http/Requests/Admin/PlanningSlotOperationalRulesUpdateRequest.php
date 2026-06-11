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
        return [
            'lead_times' => ['required', 'array'],
            'lead_times.manha' => ['required', 'integer', 'min:0'],
            'lead_times.tarde' => ['required', 'integer', 'min:0'],
            'lead_times.noite' => ['required', 'integer', 'min:0'],
            'blocked_dates' => ['present', 'array'],
            'blocked_dates.*.date' => ['required', 'date_format:Y-m-d'],
            'blocked_dates.*.slots' => ['required', 'array', 'min:1'],
            'blocked_dates.*.slots.*' => ['required', 'string', Rule::in(PlanningSlotCapacityService::CANONICAL_SLOTS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'lead_times.manha' => 'Lead Time (Manhã)',
            'lead_times.tarde' => 'Lead Time (Tarde)',
            'lead_times.noite' => 'Lead Time (Noite)',
            'blocked_dates.*.date' => 'Data Bloqueada',
            'blocked_dates.*.slots' => 'Slots da Data Bloqueada',
        ];
    }
}
