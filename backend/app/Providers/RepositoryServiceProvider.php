<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use App\Repositories\Eloquent\DetailPlanRepository;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Eloquent\TenantRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        DetailPlanRepositoryInterface::class => DetailPlanRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}