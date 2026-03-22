<?php

use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Models\Product;

describe('Orders API', function () {
    beforeEach(function () {
        $mock = Mockery::mock(KafkaProducer::class);
        $mock->shouldReceive('publish');
        $this->app->instance(KafkaProducer::class, $mock);
    });

    it('can list orders', function () {
        $user = createAdminUser();

        Order::factory()->count(3)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'total'],
                ],
            ]);
    });

    it('can create an order with products', function () {
        $user = createAdminUser();

        $products = Product::factory()->count(2)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/orders', [
                'products' => [
                    ['product_id' => $products[0]->id, 'qty' => 2],
                    ['product_id' => $products[1]->id, 'qty' => 1],
                ],
                'comment' => 'Sem cebola',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['status' => 'open']);
    });

    it('can update order status with valid transition', function () {
        $user = createAdminUser();

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'accepted',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['status' => 'accepted']);
    });

    it('rejects invalid status transition', function () {
        $user = createAdminUser();

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'delivered',
            ]);

        $response->assertUnprocessable();
    });

    it('can filter orders by status', function () {
        $user = createAdminUser();

        Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);
        Order::factory()->delivered()->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/orders?status=open');

        $response->assertOk();

        $orders = $response->json('data');
        foreach ($orders as $order) {
            expect($order['status'])->toBe('open');
        }
    });
});
