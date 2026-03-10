<?php

namespace App\Actions\Role;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRolesAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}