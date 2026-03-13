<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TenantRepository implements TenantRepositoryInterface
{
    public function __construct(
        private readonly Tenant $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('plan')->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Tenant
    {
        return $this->model->with('plan')->find($id);
    }

    public function findByUuid(string $uuid): ?Tenant
    {
        return $this->model->with('plan')->where('uuid', $uuid)->first();
    }

    public function create(array $data): Tenant
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Tenant
    {
        $tenant = $this->model->find($id);

        if (! $tenant) {
            return null;
        }

        $tenant->update($data);

        return $tenant->fresh('plan');
    }

    public function delete(int $id): bool
    {
        $tenant = $this->model->find($id);

        if (! $tenant) {
            return false;
        }

        return (bool) $tenant->delete();
    }
}
