<?php

use App\Http\Controllers\Api\V1\AclSyncController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api', 'tenant')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD
        Route::apiResource('plans', PlanController::class);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show']);

        // Tenants CRUD
        Route::apiResource('tenants', TenantController::class);

        // Profiles CRUD
        Route::apiResource('profiles', ProfileController::class);

        // Roles CRUD
        Route::apiResource('roles', RoleController::class);

        // ACL Sync
        Route::get('permissions', [AclSyncController::class, 'listPermissions']);
        Route::post('profiles/{profile}/permissions', [AclSyncController::class, 'syncProfilePermissions']);
        Route::post('plans/{plan}/profiles', [AclSyncController::class, 'syncPlanProfiles']);
        Route::post('roles/{role}/permissions', [AclSyncController::class, 'syncRolePermissions']);
        Route::post('users/{user}/roles', [AclSyncController::class, 'syncUserRoles']);
    });
});