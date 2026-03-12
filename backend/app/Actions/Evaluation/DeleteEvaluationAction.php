<?php

namespace App\Actions\Evaluation;

use App\Repositories\Contracts\EvaluationRepositoryInterface;

final class DeleteEvaluationAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}