<?php

namespace App\Actions\Table;

use App\Repositories\Contracts\TableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTablesAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}