<?php

namespace App\Actions\Role;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class ShowRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Role
    {
        return $this->repository->findById($id);
    }
}
