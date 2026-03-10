<?php

namespace App\DTOs\Role;

use App\Http\Requests\Role\StoreRoleRequest;

final readonly class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreRoleRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}