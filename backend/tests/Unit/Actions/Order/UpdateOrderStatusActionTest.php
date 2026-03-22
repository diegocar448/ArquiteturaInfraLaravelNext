<?php

use App\Actions\Order\UpdateOrderStatusAction;
use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

describe('UpdateOrderStatusAction', function () {
    it('returns error for invalid transition', function () {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->status = 'open';
        $order->shouldReceive('canTransitionTo')
            ->with('delivered')
            ->andReturn(false);

        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(1)
            ->andReturn($order);

        $producer = Mockery::mock(KafkaProducer::class);

        $action = new UpdateOrderStatusAction($repository, $producer);
        $dto = new UpdateOrderStatusDTO(status: 'delivered');
        $result = $action->execute(1, $dto);

        expect($result)->toBeString()
            ->and($result)->toContain('nao e permitida');
    });

    it('returns updated order for valid transition', function () {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->status = 'open';
        $order->shouldReceive('canTransitionTo')
            ->with('accepted')
            ->andReturn(true);
        $order->shouldReceive('fresh')
            ->andReturn($order);

        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(1)
            ->andReturn($order);
        $repository->shouldReceive('update')
            ->with(1, ['status' => 'accepted'])
            ->once();

        $producer = Mockery::mock(KafkaProducer::class);
        $producer->shouldReceive('publish')->once();

        $action = new UpdateOrderStatusAction($repository, $producer);
        $dto = new UpdateOrderStatusDTO(status: 'accepted');
        $result = $action->execute(1, $dto);

        expect($result)->toBeInstanceOf(Order::class);
    });

    it('returns error when order not found', function () {
        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(999)
            ->andReturnNull();

        $producer = Mockery::mock(KafkaProducer::class);

        $action = new UpdateOrderStatusAction($repository, $producer);
        $dto = new UpdateOrderStatusDTO(status: 'accepted');
        $result = $action->execute(999, $dto);

        expect($result)->toBeString()
            ->and($result)->toContain('nao encontrado');
    });
});
