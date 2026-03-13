<?php

namespace App\DTOs\Tenant;

use App\Http\Requests\Tenant\UpdateTenantRequest;

final readonly class UpdateTenantDTO
{
    public function __construct(
        public int $planId,
        public string $name,
        public string $email,
        public ?string $cnpj,
        public ?string $logo,
        public ?bool $active,
    ) {}

    public static function fromRequest(UpdateTenantRequest $request): self
    {
        return new self(
            planId: $request->validated('plan_id'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            cnpj: $request->validated('cnpj'),
            logo: $request->validated('logo'),
            active: $request->validated('active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'plan_id' => $this->planId,
            'name' => $this->name,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'logo' => $this->logo,
            'active' => $this->active,
        ], fn ($value) => $value !== null);
    }
}
