<?php

namespace App\Repositories\Eloquent;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TableRepository implements TableRepositoryInterface
{
    public function __construct(
        private readonly Table $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Table
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Table
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): Table
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Table
    {
        $table = $this->findById($id);

        if (! $table) {
            return null;
        }

        $table->update($data);

        return $table->fresh();
    }

    public function delete(int $id): bool
    {
        $table = $this->findById($id);

        if (! $table) {
            return false;
        }

        return (bool) $table->delete();
    }
}
