<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Order::ALL_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status e obrigatorio.',
            'status.in' => 'Status invalido. Valores aceitos: '.implode(', ', Order::ALL_STATUSES),
        ];
    }
}
