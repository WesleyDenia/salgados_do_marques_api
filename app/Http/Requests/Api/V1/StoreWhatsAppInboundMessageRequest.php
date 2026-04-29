<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppInboundMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => ['nullable', 'string', 'max:191'],
            'from' => ['required', 'string', 'max:191'],
            'to' => ['nullable', 'string', 'max:191'],
            'chat_id' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string', 'max:191'],
            'push_name' => ['nullable', 'string', 'max:191'],
            'author' => ['nullable', 'string', 'max:191'],
            'timestamp' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:64'],
            'is_group' => ['nullable', 'boolean'],
            'has_media' => ['nullable', 'boolean'],
            'media_mime_type' => ['nullable', 'string', 'max:191'],
        ];
    }
}
