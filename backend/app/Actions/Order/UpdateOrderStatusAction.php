<?php

namespace App\Actions\Order;

use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class UpdateOrderStatusAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    /**
     * @return Order|string Order atualizado ou mensagem de erro
     */
    public function execute(int $id, UpdateOrderStatusDTO $dto): Order|string
    {
        $order = $this->repository->findById($id);

        if (! $order) {
            return 'Pedido nao encontrado.';
        }

        if (! $order->canTransitionTo($dto->status)) {
            return "Transicao de '{$order->status}' para '{$dto->status}' nao e permitida.";
        }

        $this->repository->update($id, ['status' => $dto->status]);

        return $order->fresh(['products', 'table']);
    }
}
