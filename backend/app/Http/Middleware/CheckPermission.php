<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verifica se o usuario tem a permissao necessaria.
     * Uso: ->middleware('permission:plans.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Voce nao tem permissao para esta acao.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
