<?php

use App\Models\Plan;

describe('Plans API', function () {
    it('can list plans', function () {
        $user = createAdminUser();
        Plan::factory()->count(3)->create();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'description'],
                ],
            ]);
    });

    it('can create a plan', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/plans', [
                'name' => 'Plano Premium',
                'price' => 99.90,
                'description' => 'Plano com recursos premium',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Plano Premium']);
    });

    it('validates required fields on create', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/plans', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price']);
    });

    it('can show a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson("/api/v1/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => $plan->name]);
    });

    it('can update a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/plans/{$plan->id}", [
                'name' => 'Plano Atualizado',
                'price' => 149.90,
                'description' => $plan->description,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Plano Atualizado']);
    });

    it('can delete a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->deleteJson("/api/v1/plans/{$plan->id}");

        $response->assertOk();
    });
});
