<?php

namespace App\Repositories\Contracts;

use App\Models\OrderEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EvaluationRepositoryInterface
{
    public function paginateByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?OrderEvaluation;

    public function findByOrderAndClient(int $orderId, int $clientId): ?OrderEvaluation;

    public function create(array $data): OrderEvaluation;

    public function delete(int $id): bool;
}
