<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identify' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'identify.required' => 'O identificador da mesa e obrigatorio.',
            'identify.max' => 'O identificador deve ter no maximo 255 caracteres.',
            'description.max' => 'A descricao deve ter no maximo 1000 caracteres.',
        ];
    }
}
