<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tenants,email'],
            'cnpj' => ['nullable', 'string', 'unique:tenants,cnpj'],
            'logo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'O plano e obrigatorio.',
            'plan_id.exists' => 'Plano nao encontrado.',
            'name.required' => 'O nome e obrigatorio.',
            'email.required' => 'O email e obrigatorio.',
            'email.email' => 'Informe um email valido.',
            'email.unique' => 'Ja existe um tenant com este email.',
            'cnpj.unique' => 'Ja existe um tenant com este CNPJ.',
        ];
    }
}
