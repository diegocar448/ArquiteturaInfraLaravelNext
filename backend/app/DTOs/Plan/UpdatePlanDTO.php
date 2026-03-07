<?php

namespace App\DTOs\Plan;

use App\Http\Requests\Plan\UpdatePlanRequest;

final readonly class UpdatePlanDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public ?string $url,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdatePlanRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            url: $request->validated('url'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        // No update, campos null significam "nao alterar". O array_filter remove esses campos para que o Eloquent nao sobrescreva com null
        return array_filter([
            'name' => $this->name,
            'price' => $this->price,
            'url' => $this->url,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}