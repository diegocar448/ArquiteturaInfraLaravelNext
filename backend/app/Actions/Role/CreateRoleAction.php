<?php

namespace App\Actions\Role;

use App\DTOs\Role\CreateRoleDTO;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class CreateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(CreateRoleDTO $dto): Role
    {
        return $this->repository->create($dto->toArray());
    }
}