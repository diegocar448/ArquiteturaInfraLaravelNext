<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\PlanRepositoryInterface;

final class DeletePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
