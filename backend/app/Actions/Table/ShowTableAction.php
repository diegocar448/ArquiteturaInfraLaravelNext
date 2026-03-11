<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class ShowTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Table
    {
        return $this->repository->findById($id);
    }
}