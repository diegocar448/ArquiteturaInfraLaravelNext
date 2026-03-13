<?php

use App\Models\Client;

describe('Client Auth API', function () {
    it('can register a new client', function () {
        $response = $this->postJson('/api/v1/client/auth/register', [
            'name' => 'Joao Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type']);
    });

    it('validates unique email on register', function () {
        Client::factory()->create(['email' => 'joao@example.com']);

        $response = $this->postJson('/api/v1/client/auth/register', [
            'name' => 'Joao Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('can login as client', function () {
        Client::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/client/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token']);
    });

    it('returns 401 for wrong client password', function () {
        Client::factory()->create([
            'email' => 'joao@example.com',
        ]);

        $response = $this->postJson('/api/v1/client/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized();
    });
});
