<?php

namespace App\Actions\Tenant;

use App\DTOs\Tenant\CreateTenantDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class CreateTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(CreateTenantDTO $dto): Tenant
    {
        return $this->repository->create($dto->toArray());
    }
}