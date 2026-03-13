<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'client_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'O pedido deve ter pelo menos um produto.',
            'products.min' => 'O pedido deve ter pelo menos um produto.',
            'products.*.product_id.required' => 'O ID do produto e obrigatorio.',
            'products.*.product_id.exists' => 'Produto nao encontrado.',
            'products.*.qty.required' => 'A quantidade e obrigatoria.',
            'products.*.qty.min' => 'A quantidade minima e 1.',
        ];
    }
}
