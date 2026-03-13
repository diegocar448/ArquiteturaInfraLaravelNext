<?php

namespace App\Actions\Plan;

use App\DTOs\Plan\UpdatePlanDTO;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class UpdatePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdatePlanDTO $dto): ?Plan
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
