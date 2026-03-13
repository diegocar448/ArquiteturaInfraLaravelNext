<?php

namespace App\Actions\Profile;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class ShowProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Profile
    {
        return $this->repository->findById($id);
    }
}
