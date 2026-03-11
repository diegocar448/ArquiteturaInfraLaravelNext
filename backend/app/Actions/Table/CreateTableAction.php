<?php

namespace App\Actions\Table;

use App\DTOs\Table\CreateTableDTO;
use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class CreateTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(CreateTableDTO $dto): Table
    {
        return $this->repository->create($dto->toArray());
    }
}