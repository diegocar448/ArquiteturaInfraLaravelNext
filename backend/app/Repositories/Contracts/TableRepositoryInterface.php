<?php

namespace App\Repositories\Contracts;

use App\Models\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TableRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Table;

    public function findByUuid(string $uuid): ?Table;

    public function create(array $data): Table;

    public function update(int $id, array $data): ?Table;

    public function delete(int $id): bool;
}
