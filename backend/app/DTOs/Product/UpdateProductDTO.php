<?php

namespace App\DTOs\Product;

use App\Http\Requests\Product\UpdateProductRequest;

final readonly class UpdateProductDTO
{
    public function __construct(
        public string $title,
        public float $price,
        public ?string $url,
        public ?string $flag,
        public ?string $image,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            price: $request->validated('price'),
            url: $request->validated('url'),
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
            'url' => $this->url,
            'flag' => $this->flag,
            'image' => $this->image,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
