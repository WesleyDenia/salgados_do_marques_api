<?php

namespace App\Http\Requests\Admin;

use App\Models\ErpSyncTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateErpSyncTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        return [
            'target_status' => ['required', 'string', Rule::in([
                ErpSyncTask::STATUS_MANUAL_REVIEW,
                ErpSyncTask::STATUS_CANCELLED,
            ])],
            'manual_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
