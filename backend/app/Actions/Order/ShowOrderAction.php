<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class ShowOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Order
    {
        return $this->repository->findById($id);
    }
}
