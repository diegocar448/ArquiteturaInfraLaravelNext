<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use Illuminate\Auth\AuthenticationException;

final class LoginAction
{
    public function execute(LoginDTO $dto): string
    {
        $token = auth('api')->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        if (! $token) {
            throw new AuthenticationException('Credenciais invalidas.');
        }

        return $token;
    }
}
