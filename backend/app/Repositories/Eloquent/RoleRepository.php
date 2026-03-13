<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private readonly Role $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return $this->model->find($id);
    }

    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Role
    {
        $role = $this->findById($id);

        if (! $role) {
            return null;
        }

        $role->update($data);

        return $role->fresh();
    }

    public function delete(int $id): bool
    {
        $role = $this->findById($id);

        if (! $role) {
            return false;
        }

        return (bool) $role->delete();
    }
}
