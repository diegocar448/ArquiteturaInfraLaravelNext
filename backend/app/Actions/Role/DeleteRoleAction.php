<?php

namespace App\Actions\Role;

use App\Repositories\Contracts\RoleRepositoryInterface;

final class DeleteRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
