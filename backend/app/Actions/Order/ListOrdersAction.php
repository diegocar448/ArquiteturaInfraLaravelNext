<?php

namespace App\Actions\Order;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $status);
    }
}