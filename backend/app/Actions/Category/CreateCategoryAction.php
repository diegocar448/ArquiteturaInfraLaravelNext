<?php

namespace App\Actions\Category;

use App\DTOs\Category\CreateCategoryDTO;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

final class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        return $this->repository->create($dto->toArray());
    }
}
