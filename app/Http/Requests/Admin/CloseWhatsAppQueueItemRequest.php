<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CloseWhatsAppQueueItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        return [
            'manual_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
