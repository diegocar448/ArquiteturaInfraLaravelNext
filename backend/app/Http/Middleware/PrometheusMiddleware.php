<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function __construct(
        private CollectorRegistry $registry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;
        $method = $request->method();
        $route = $request->route()?->uri() ?? 'unknown';
        $status = (string) $response->getStatusCode();

        // Contador de requests
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );
        $counter->inc([$method, $route, $status]);

        // Histograma de latencia
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0]
        );
        $histogram->observe($duration, [$method, $route]);

        return $response;
    }
}