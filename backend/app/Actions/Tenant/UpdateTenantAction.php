<?php

namespace App\Actions\Tenant;

use App\DTOs\Tenant\UpdateTenantDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class UpdateTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateTenantDTO $dto): ?Tenant
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
