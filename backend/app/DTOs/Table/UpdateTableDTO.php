<?php

namespace App\DTOs\Table;

use App\Http\Requests\Table\UpdateTableRequest;

final readonly class UpdateTableDTO
{
    public function __construct(
        public string $identify,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateTableRequest $request): self
    {
        return new self(
            identify: $request->validated('identify'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'identify' => $this->identify,
            'description' => $this->description,
        ];
    }
}