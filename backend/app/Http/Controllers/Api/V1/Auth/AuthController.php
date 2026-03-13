<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\DTOs\Auth\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Autentica o usuario e retorna um token JWT.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            $token = $action->execute(LoginDTO::fromRequest($request));

            return $this->respondWithToken($token);
        } catch (AuthenticationException) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }
    }

    /**
     * Usuario autenticado
     *
     * Retorna os dados do usuario autenticado no token JWT.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()),
        ]);
    }

    /**
     * Logout
     *
     * Invalida o token JWT atual.
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Refresh token
     *
     * Gera um novo token JWT a partir do token atual.
     */
    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();

        return $this->respondWithToken($token);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
