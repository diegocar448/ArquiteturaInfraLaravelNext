<?php

namespace App\Repositories\Contracts;

use App\Models\Profile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProfileRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Profile;

    public function create(array $data): Profile;

    public function update(int $id, array $data): ?Profile;

    public function delete(int $id): bool;
}