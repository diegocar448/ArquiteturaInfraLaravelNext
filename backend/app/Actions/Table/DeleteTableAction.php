<?php

namespace App\Actions\Table;

use App\Repositories\Contracts\TableRepositoryInterface;

final class DeleteTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
