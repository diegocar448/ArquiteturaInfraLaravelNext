<?php

namespace App\DTOs\Product;

use App\Http\Requests\Product\StoreProductRequest;

final readonly class CreateProductDTO
{
    public function __construct(
        public string $title,
        public float $price,
        public ?string $flag,
        public ?string $image,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProductRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            price: $request->validated('price'),
            flag: $request->validated('flag'),
            image: $request->validated('image'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'price' => $this->price,
            'flag' => $this->flag,
            'image' => $this->image,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}