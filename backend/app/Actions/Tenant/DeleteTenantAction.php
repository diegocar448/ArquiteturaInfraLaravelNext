<?php

namespace App\Actions\Tenant;

use App\Repositories\Contracts\TenantRepositoryInterface;

final class DeleteTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}