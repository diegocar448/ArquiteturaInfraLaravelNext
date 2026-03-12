<?php

use App\Models\User;

describe('Auth API', function () {
    it('can login with valid credentials', function () {
        $user = createAdminUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    });

    it('returns 401 for invalid credentials', function () {
        $user = createAdminUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    });

    it('can get authenticated user', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    });

    it('can logout', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Successfully logged out']);
    });
});