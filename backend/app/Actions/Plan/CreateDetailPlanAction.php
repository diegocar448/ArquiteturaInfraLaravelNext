<?php

namespace App\Actions\Plan;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class CreateDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $planId, string $name): DetailPlan
    {
        return $this->repository->create([
            'plan_id' => $planId,
            'name' => $name,
        ]);
    }
}
