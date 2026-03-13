<?php

namespace App\Actions\Category;

use App\Repositories\Contracts\CategoryRepositoryInterface;

final class DeleteCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
