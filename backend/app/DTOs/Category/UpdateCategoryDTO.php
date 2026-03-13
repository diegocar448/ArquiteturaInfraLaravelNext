<?php

namespace App\DTOs\Category;

use App\Http\Requests\Category\UpdateCategoryRequest;

final readonly class UpdateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $url,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateCategoryRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            url: $request->validated('url'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
