<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * @param  string  $mode  'optional' (default) ou 'required'
     */
    public function handle(Request $request, Closure $next, string $mode = 'optional'): Response
    {
        $user = auth('api')->user();

        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;

            if (! $tenant || ! $tenant->active) {
                return response()->json([
                    'message' => 'Tenant inativo ou nao encontrado.',
                ], 403);
            }

            app()->instance('currentTenant', $tenant);
        } elseif ($mode === 'required' && ! $user?->isSuperAdmin()) {
            return response()->json([
                'message' => 'Esta acao requer um usuario vinculado a um tenant.',
            ], 403);
        }

        return $next($request);
    }
}
