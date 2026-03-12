<?php

namespace App\Actions\Evaluation;

use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Models\Order;
use App\Models\OrderEvaluation;
use App\Repositories\Contracts\EvaluationRepositoryInterface;

final class CreateEvaluationAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    /**
     * @return OrderEvaluation|string Avaliacao criada ou mensagem de erro
     */
    public function execute(CreateEvaluationDTO $dto, int $clientId): OrderEvaluation|string
    {
        $order = Order::withoutGlobalScopes()->find($dto->orderId);

        if (!$order) {
            return 'Pedido nao encontrado.';
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return 'Apenas pedidos entregues podem ser avaliados.';
        }

        if ($order->client_id !== $clientId) {
            return 'Voce so pode avaliar seus proprios pedidos.';
        }

        $existing = $this->repository->findByOrderAndClient($dto->orderId, $clientId);

        if ($existing) {
            return 'Voce ja avaliou este pedido.';
        }

        return $this->repository->create([
            'order_id' => $dto->orderId,
            'client_id' => $clientId,
            'stars' => $dto->stars,
            'comment' => $dto->comment,
        ]);
    }
}