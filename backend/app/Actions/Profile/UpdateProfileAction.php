<?php

namespace App\Actions\Profile;

use App\DTOs\Profile\UpdateProfileDTO;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class UpdateProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateProfileDTO $dto): ?Profile
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
