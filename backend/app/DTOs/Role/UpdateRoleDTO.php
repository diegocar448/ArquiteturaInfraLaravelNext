<?php

namespace App\DTOs\Role;

use App\Http\Requests\Role\UpdateRoleRequest;

final readonly class UpdateRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateRoleRequest $request): self
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