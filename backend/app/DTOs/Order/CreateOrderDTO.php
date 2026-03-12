<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\StoreOrderRequest;

final readonly class CreateOrderDTO
{
    public function __construct(
        public ?int $tableId,
        public ?int $clientId,
        public ?string $comment,
        public array $products, // [{product_id, qty}]
    ) {}

    public static function fromRequest(StoreOrderRequest $request): self
    {
        return new self(
            tableId: $request->validated('table_id'),
            clientId: $request->validated('client_id'),
            comment: $request->validated('comment'),
            products: $request->validated('products'),
        );
    }
}