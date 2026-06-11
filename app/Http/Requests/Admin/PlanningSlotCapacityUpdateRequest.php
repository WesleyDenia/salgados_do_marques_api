<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlanningSlotCapacityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', \App\Models\Setting::class) ?? false;
    }

    public function rules(): array
    {
        $slots = \App\Services\PlanningSlotCapacityService::CANONICAL_SLOTS;
        $rules = [];

        foreach ($slots as $slot) {
            $rules[$slot] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $slots = \App\Services\PlanningSlotCapacityService::CANONICAL_SLOTS;
            $unknownSlots = array_diff(array_keys($this->all()), $slots);

            if ($unknownSlots !== []) {
                $validator->errors()->add(
                    'slot_capacities',
                    'A capacidade base só aceita os slots canónicos ' . implode(', ', $slots) . '.'
                );
            }
        });
    }
}
