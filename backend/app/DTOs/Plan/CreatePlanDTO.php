<?php

namespace App\DTOs\Plan;

use App\Http\Requests\Plan\StorePlanRequest;

final readonly class CreatePlanDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public ?string $description,
    ) {}

    public static function fromRequest(StorePlanRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
        ];
    }
}
