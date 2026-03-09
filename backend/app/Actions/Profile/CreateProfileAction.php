<?php

namespace App\Actions\Profile;

use App\DTOs\Profile\CreateProfileDTO;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class CreateProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(CreateProfileDTO $dto): Profile
    {
        return $this->repository->create($dto->toArray());
    }
}