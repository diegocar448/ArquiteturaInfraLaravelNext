<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): ?Role;

    public function delete(int $id): bool;
}