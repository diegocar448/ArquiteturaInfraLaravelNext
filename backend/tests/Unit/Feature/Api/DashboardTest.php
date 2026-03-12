<?php

use App\Models\Order;

describe('Dashboard API', function () {
    it('returns metrics structure', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'cards' => [
                        'orders_today',
                        'revenue_today',
                        'total_clients',
                        'total_products',
                    ],
                    'orders_per_day',
                    'orders_by_status',
                    'latest_evaluations',
                ],
            ]);
    });

    it('counts orders created today', function () {
        $user = createAdminUser();

        Order::factory()->count(3)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertOk();
        expect($response->json('data.cards.orders_today'))->toBe(3);
    });

    it('excludes rejected orders from revenue', function () {
        $user = createAdminUser();

        Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'total' => 100.00,
            'status' => 'open',
        ]);
        Order::factory()->rejected()->create([
            'tenant_id' => $user->tenant_id,
            'total' => 50.00,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        expect($response->json('data.cards.revenue_today'))->toBe('100.00');
    });

    it('returns 7 days in orders_per_day', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        expect($response->json('data.orders_per_day'))->toHaveCount(7);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/dashboard/metrics');

        $response->assertUnauthorized();
    });
});