<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class DeleteDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
