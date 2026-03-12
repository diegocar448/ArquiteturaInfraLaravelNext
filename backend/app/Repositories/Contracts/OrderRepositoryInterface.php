<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $status = null): LengthAwarePaginator;

    public function findById(int $id): ?Order;

    public function findByUuid(string $uuid): ?Order;

    public function create(array $data): Order;

    public function update(int $id, array $data): ?Order;

    public function delete(int $id): bool;
}