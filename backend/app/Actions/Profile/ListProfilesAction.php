<?php

namespace App\Actions\Profile;

use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProfilesAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
