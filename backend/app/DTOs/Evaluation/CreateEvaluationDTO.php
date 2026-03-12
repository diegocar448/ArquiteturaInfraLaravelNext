<?php

namespace App\DTOs\Evaluation;

use App\Http\Requests\Evaluation\StoreEvaluationRequest;

final readonly class CreateEvaluationDTO
{
    public function __construct(
        public int $orderId,
        public int $stars,
        public ?string $comment,
    ) {}

    public static function fromRequest(StoreEvaluationRequest $request): self
    {
        return new self(
            orderId: $request->validated('order_id'),
            stars: $request->validated('stars'),
            comment: $request->validated('comment'),
        );
    }
}