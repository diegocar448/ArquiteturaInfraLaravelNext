<?php

namespace App\Actions\Plan;

use App\DTOs\Plan\CreatePlanDTO;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class CreatePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(CreatePlanDTO $dto): Plan
    {
        return $this->repository->create($dto->toArray());
    }
}
