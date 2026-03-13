<?php

namespace App\Actions\Order;

use App\Repositories\Contracts\OrderRepositoryInterface;

final class DeleteOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
