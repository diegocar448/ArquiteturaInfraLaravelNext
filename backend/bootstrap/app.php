<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\PrometheusMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'permission' => CheckPermission::class,
        ]);
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            RequestIdMiddleware::class,
        ]);
        $middleware->api(append: [
            PrometheusMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
