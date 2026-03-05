<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\DTOs\Auth\LoginDTO;
use App\Actions\Auth\LoginAction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
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

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

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