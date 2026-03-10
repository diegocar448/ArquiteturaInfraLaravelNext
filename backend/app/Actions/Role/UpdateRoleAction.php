<?php

namespace App\Actions\Role;

use App\DTOs\Role\UpdateRoleDTO;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class UpdateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateRoleDTO $dto): ?Role
    {
        return $this->repository->update($id, $dto->toArray());
    }
}