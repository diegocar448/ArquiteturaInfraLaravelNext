<?php

namespace App\Actions\Product;

use App\DTOs\Product\UpdateProductDTO;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

final class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateProductDTO $dto): ?Product
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
