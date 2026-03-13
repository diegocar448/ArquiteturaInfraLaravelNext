<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\UpdateOrderStatusRequest;

final readonly class UpdateOrderStatusDTO
{
    public function __construct(
        public string $status,
    ) {}

    public static function fromRequest(UpdateOrderStatusRequest $request): self
    {
        return new self(
            status: $request->validated('status'),
        );
    }
}
