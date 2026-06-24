<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderTagUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage');
    }

    public function rules(): array
    {
        /** @var OrderTag|null $currentTag */
        $currentTag = $this->route('orderTag');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('order_tags', 'name')->ignore($currentTag?->id),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique o nome da tag.',
            'name.unique' => 'Já existe uma tag com esse nome.',
            'color.required' => 'Selecione uma cor para a tag.',
            'color.regex' => 'A cor da tag deve estar em formato hexadecimal, por exemplo #92400e.',
        ];
    }
}
