<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly Order $model,
    ) {}

    public function paginate(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = $this->model->with(['products', 'table'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Order
    {
        return $this->model->with(['products', 'table'])->find($id);
    }

    public function findByUuid(string $uuid): ?Order
    {
        return $this->model->with(['products', 'table'])->where('uuid', $uuid)->first();
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Order
    {
        $order = $this->model->find($id);

        if (! $order) {
            return null;
        }

        $order->update($data);

        return $order->fresh(['products', 'table']);
    }

    public function delete(int $id): bool
    {
        $order = $this->model->find($id);

        if (! $order) {
            return false;
        }

        return (bool) $order->delete();
    }
}
