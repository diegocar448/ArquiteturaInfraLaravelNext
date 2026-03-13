<?php

namespace App\Repositories\Eloquent;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class DetailPlanRepository implements DetailPlanRepositoryInterface
{
    public function __construct(
        private readonly DetailPlan $model,
    ) {}

    public function allByPlan(int $planId): Collection
    {
        return $this->model->where('plan_id', $planId)->get();
    }

    public function findById(int $id): ?DetailPlan
    {
        return $this->model->find($id);
    }

    public function create(array $data): DetailPlan
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?DetailPlan
    {
        $detail = $this->findById($id);

        if (! $detail) {
            return null;
        }

        $detail->update($data);

        return $detail->fresh();
    }

    public function delete(int $id): bool
    {
        $detail = $this->findById($id);

        if (! $detail) {
            return false;
        }

        return (bool) $detail->delete();
    }
}
