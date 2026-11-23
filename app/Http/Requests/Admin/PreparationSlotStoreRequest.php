<?php

namespace App\Http\Requests\Admin;

use App\Models\OperationalPreparationSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreparationSlotStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'name' => ['required', 'string', 'max:80'],
            'heating_minutes' => ['nullable', 'numeric', 'min:0', 'max:1440'],
            'clone_from_slot_id' => ['nullable', 'integer', Rule::exists('operational_preparation_slots', 'id')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $sourceId = $this->integer('clone_from_slot_id');

            if ($sourceId <= 0) {
                return;
            }

            $belongsToStore = OperationalPreparationSlot::query()
                ->whereKey($sourceId)
                ->where('store_id', $this->integer('store_id'))
                ->exists();

            if (! $belongsToStore) {
                $validator->errors()->add(
                    'clone_from_slot_id',
                    'A cuba a clonar tem de pertencer à loja selecionada.'
                );
            }
        });
    }
}
