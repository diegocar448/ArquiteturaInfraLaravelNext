<?php

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlanRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Plan;

    public function findByUrl(string $url): ?Plan;

    public function create(array $data): Plan;

    public function update(int $id, array $data): ?Plan;

    public function delete(int $id): bool;
}
