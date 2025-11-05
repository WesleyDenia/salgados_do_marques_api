<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes','exists:categories,id'],
            'name'        => ['sometimes','string','max:255'],
            'description' => ['sometimes','string','nullable'],
            'price'       => ['sometimes','numeric','min:0'],
            'image_url'   => ['sometimes','url','nullable'],
            'image'       => ['sometimes','file','image','max:5120'],
            'active'      => ['sometimes','boolean'],
        ];
    }
}
