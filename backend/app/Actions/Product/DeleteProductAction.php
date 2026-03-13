<?php

namespace App\Actions\Product;

use App\Repositories\Contracts\ProductRepositoryInterface;

final class DeleteProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
