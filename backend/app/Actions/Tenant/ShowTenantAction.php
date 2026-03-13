<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class ShowTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Tenant
    {
        return $this->repository->findById($id);
    }
}
