<?php

use App\Actions\Order\UpdateOrderStatusAction;
use App\Models\Order;
use App\Repositories\Eloquent\OrderRepository;

describe('UpdateOrderStatusAction', function () {
    it('returns error for invalid transition', function () {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getAttribute')
            ->with('status')
            ->andReturn('open');

        $repository = Mockery::mock(OrderRepository::class);

        $action = new UpdateOrderStatusAction($repository);
        $result = $action->execute($order, 'delivered');

        expect($result)->toBeString()
            ->and($result)->toContain('Transicao invalida');
    });

    it('returns updated order for valid transition', function () {
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getAttribute')
            ->with('status')
            ->andReturn('open');

        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldReceive('updateStatus')
            ->with($order, 'accepted')
            ->once()
            ->andReturn($order);

        $action = new UpdateOrderStatusAction($repository);
        $result = $action->execute($order, 'accepted');

        expect($result)->toBeInstanceOf(Order::class);
    });
});