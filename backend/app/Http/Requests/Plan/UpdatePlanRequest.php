<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'url' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('plans')->ignore($this->route('plan')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano e obrigatorio.',
            'price.required' => 'O preco e obrigatorio.',
            'price.numeric' => 'O preco deve ser um numero.',
            'price.min' => 'O preco nao pode ser negativo.',
            'url.unique' => 'Ja existe um plano com esta URL.',
        ];
    }
}