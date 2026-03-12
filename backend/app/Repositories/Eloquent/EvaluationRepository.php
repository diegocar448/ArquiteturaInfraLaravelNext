<?php

namespace App\Repositories\Eloquent;

use App\Models\OrderEvaluation;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EvaluationRepository implements EvaluationRepositoryInterface
{
    public function __construct(
        private readonly OrderEvaluation $model,
    ) {}

    public function paginateByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['client', 'order'])
            ->whereHas('order', fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?OrderEvaluation
    {
        return $this->model->with(['client', 'order'])->find($id);
    }

    public function findByOrderAndClient(int $orderId, int $clientId): ?OrderEvaluation
    {
        return $this->model
            ->where('order_id', $orderId)
            ->where('client_id', $clientId)
            ->first();
    }

    public function create(array $data): OrderEvaluation
    {
        return $this->model->create($data);
    }

    public function delete(int $id): bool
    {
        $evaluation = $this->model->find($id);

        return $evaluation ? (bool) $evaluation->delete() : false;
    }
}