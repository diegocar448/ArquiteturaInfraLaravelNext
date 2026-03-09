<?php

namespace App\Repositories\Eloquent;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly Profile $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Profile
    {
        return $this->model->find($id);
    }

    public function create(array $data): Profile
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Profile
    {
        $profile = $this->findById($id);

        if (!$profile) {
            return null;
        }

        $profile->update($data);

        return $profile->fresh();
    }

    public function delete(int $id): bool
    {
        $profile = $this->findById($id);

        if (!$profile) {
            return false;
        }

        return (bool) $profile->delete();
    }
}