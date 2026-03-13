<?php

namespace App\Actions\Evaluation;

use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListEvaluationsAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    public function execute(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByTenant($tenantId, $perPage);
    }
}
