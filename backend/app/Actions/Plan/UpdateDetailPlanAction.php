<?php

namespace App\Actions\Plan;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class UpdateDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id, string $name): ?DetailPlan
    {
        return $this->repository->update($id, ['name' => $name]);
    }
}
