<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT'),
            );

            // Adicionar example=1 em todos os path parameters integer
            // para evitar que o Stoplight UI gere UUIDs como exemplo
            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    foreach ($operation->parameters as $parameter) {
                        if (
                            $parameter->in === 'path'
                            && $parameter->schema
                            && $parameter->schema->type instanceof IntegerType
                        ) {
                            $parameter->example(1);
                        }
                    }
                }
            }
        });
    }
}
