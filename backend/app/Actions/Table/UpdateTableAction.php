<?php

namespace App\Actions\Table;

use App\DTOs\Table\UpdateTableDTO;
use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class UpdateTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateTableDTO $dto): ?Table
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
