<?php

namespace App\Repositories\Contracts;

use App\Models\DetailPlan;
use Illuminate\Database\Eloquent\Collection;

interface DetailPlanRepositoryInterface
{
    public function allByPlan(int $planId): Collection;

    public function findById(int $id): ?DetailPlan;

    public function create(array $data): DetailPlan;

    public function update(int $id, array $data): ?DetailPlan;

    public function delete(int $id): bool;
}