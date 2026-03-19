<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());

        // Disponibilizar para toda a aplicacao
        app()->instance('request_id', $requestId);

        // Adicionar ao contexto de log
        \Illuminate\Support\Facades\Log::shareContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        // Retornar o request ID no header da resposta
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}