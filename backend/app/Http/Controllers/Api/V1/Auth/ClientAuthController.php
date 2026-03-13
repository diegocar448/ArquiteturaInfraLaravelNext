<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientAuth\LoginClientRequest;
use App\Http\Requests\ClientAuth\RegisterClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth Cliente
 */
class ClientAuthController extends Controller
{
    /**
     * Registrar cliente
     *
     * Cria uma nova conta de cliente. Retorna o token JWT.
     *
     * @unauthenticated
     */
    public function register(RegisterClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        $token = auth('client')->login($client);

        return $this->respondWithToken($token, $client, 201);
    }

    /**
     * Login cliente
     *
     * Autentica um cliente e retorna um token JWT.
     *
     * @unauthenticated
     */
    public function login(LoginClientRequest $request): JsonResponse
    {
        $token = auth('client')->attempt($request->validated());

        if (! $token) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }

        return $this->respondWithToken($token, auth('client')->user());
    }

    /**
     * Cliente autenticado
     *
     * Retorna os dados do cliente autenticado.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new ClientResource(auth('client')->user()),
        ]);
    }

    /**
     * Logout cliente
     *
     * Invalida o token JWT do cliente.
     */
    public function logout(): JsonResponse
    {
        auth('client')->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    private function respondWithToken(string $token, Client $client, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('client')->factory()->getTTL() * 60,
            'client' => new ClientResource($client),
        ], $status);
    }
}
