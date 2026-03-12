<?php

use App\Actions\Evaluation\CreateEvaluationAction;
use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderEvaluation;
use App\Repositories\Eloquent\EvaluationRepository;

describe('CreateEvaluationAction', function () {
    it('returns error if order does not exist', function () {
        $repository = Mockery::mock(EvaluationRepository::class);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $dto = new CreateEvaluationDTO(
            orderId: 999,
            stars: 5,
            comment: 'Otimo!',
        );

        // Mock Order::find to return null
        Order::shouldReceive('find')->with(999)->once()->andReturnNull();

        $action = new CreateEvaluationAction($repository);
        $result = $action->execute($dto, $client);

        expect($result)->toBeString()
            ->and($result)->toContain('Pedido nao encontrado');
    });
});