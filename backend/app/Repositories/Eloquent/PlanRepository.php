<?php

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PlanRepository implements PlanRepositoryInterface
{
    public function __construct(
        private readonly Plan $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Plan
    {
        return $this->model->find($id);
    }

    public function findByUrl(string $url): ?Plan
    {
        return $this->model->where('url', $url)->first();
    }

    public function create(array $data): Plan
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Plan
    {
        $plan = $this->findById($id);

        if (! $plan) {
            return null;
        }

        $plan->update($data);

        // Apos o update(), o model em memoria pode estar desatualizado (ex: o Observer pode ter modificado o url). O fresh() recarrega do banco.
        return $plan->fresh();
    }

    public function delete(int $id): bool
    {
        $plan = $this->findById($id);

        if (! $plan) {
            return false;
        }

        return (bool) $plan->delete();
    }
}
