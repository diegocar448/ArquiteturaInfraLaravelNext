<?php

namespace App\Actions\Profile;

use App\Repositories\Contracts\ProfileRepositoryInterface;

final class DeleteProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}