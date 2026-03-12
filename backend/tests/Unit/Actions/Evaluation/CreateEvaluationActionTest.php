<?php

use App\Actions\Evaluation\CreateEvaluationAction;
use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Models\Order;
use App\Repositories\Contracts\EvaluationRepositoryInterface;

describe('CreateEvaluationAction', function () {
    it('returns error if order does not exist', function () {
        $repository = Mockery::mock(EvaluationRepositoryInterface::class);

        $dto = new CreateEvaluationDTO(
            orderId: 999,
            stars: 5,
            comment: 'Otimo!',
        );

        $action = new CreateEvaluationAction($repository);
        $result = $action->execute($dto, 1);

        expect($result)->toBeString()
            ->and($result)->toContain('Pedido nao encontrado');
    });
});
