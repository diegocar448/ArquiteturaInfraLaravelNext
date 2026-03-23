<?php

use App\Kafka\Events\OrderCreatedEvent;
use App\Kafka\Events\OrderStatusChangedEvent;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Models\Product;

describe('Order Kafka Events', function () {
    it('publishes kafka event when creating order', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderCreatedEvent::class));

        $this->app->instance(KafkaProducer::class, $mock);

        $product = Product::factory()->create(['tenant_id' => $user->tenant_id]);

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/orders', [
                'products' => [
                    ['product_id' => $product->id, 'qty' => 2],
                ],
            ]);

        $response->assertCreated();
    });

    it('publishes kafka event when updating order status', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderStatusChangedEvent::class));

        $this->app->instance(KafkaProducer::class, $mock);

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'accepted',
            ]);

        $response->assertOk();
    });

    it('does not publish kafka event on rejected transition', function () {
        $user = createAdminUser();

        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldNotReceive('publish');

        $this->app->instance(KafkaProducer::class, $mock);

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'open',
            ]);

        $response->assertUnprocessable();
    });
});
