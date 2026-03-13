<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'url' => ['nullable', 'string', 'max:255'],
            'flag' => ['nullable', 'string', 'in:active,inactive,featured'],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O titulo do produto e obrigatorio.',
            'price.required' => 'O preco e obrigatorio.',
            'flag.in' => 'O status deve ser: active, inactive ou featured.',
        ];
    }
}
