<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Configura a classe base para todos os testes.
| RefreshDatabase reseta o banco antes de cada teste.
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Cria um usuario admin com tenant e permissoes para testes.
 */
function createAdminUser(): \App\Models\User
{
    $plan = \App\Models\Plan::factory()->create();

    $tenant = \App\Models\Tenant::factory()->create([
        'plan_id' => $plan->id,
    ]);

    return \App\Models\User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
}

/**
 * Retorna headers de autenticacao JWT para um usuario.
 */
function authHeaders(\App\Models\User $user): array
{
    $token = auth('api')->login($user);

    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}