<?php

use App\Http\Controllers\Api\V1\AclSyncController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT + tenant)
    Route::middleware('auth:api', 'tenant')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD (protegido por permissao)
        Route::apiResource('plans', PlanController::class)
            ->middleware([
                'index' => 'permission:plans.view',
                'show' => 'permission:plans.view',
                'store' => 'permission:plans.create',
                'update' => 'permission:plans.edit',
                'destroy' => 'permission:plans.delete',
            ]);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show'])
            ->middleware([
                'index' => 'permission:detail_plans.view',
                'store' => 'permission:detail_plans.create',
                'update' => 'permission:detail_plans.edit',
                'destroy' => 'permission:detail_plans.delete',
            ]);

        // Tenants CRUD
        Route::apiResource('tenants', TenantController::class)
            ->middleware([
                'index' => 'permission:tenants.view',
                'show' => 'permission:tenants.view',
                'store' => 'permission:tenants.create',
                'update' => 'permission:tenants.edit',
                'destroy' => 'permission:tenants.delete',
            ]);

        // Profiles CRUD
        Route::apiResource('profiles', ProfileController::class)
            ->middleware([
                'index' => 'permission:profiles.view',
                'show' => 'permission:profiles.view',
                'store' => 'permission:profiles.create',
                'update' => 'permission:profiles.edit',
                'destroy' => 'permission:profiles.delete',
            ]);

        // Roles CRUD
        Route::apiResource('roles', RoleController::class)
            ->middleware([
                'index' => 'permission:roles.view',
                'show' => 'permission:roles.view',
                'store' => 'permission:roles.create',
                'update' => 'permission:roles.edit',
                'destroy' => 'permission:roles.delete',
            ]);

        // ACL Sync (permissoes granulares)
        Route::get('permissions', [AclSyncController::class, 'listPermissions']);
        Route::post('profiles/{profile}/permissions', [AclSyncController::class, 'syncProfilePermissions'])
            ->middleware('permission:profiles.edit');
        Route::post('plans/{plan}/profiles', [AclSyncController::class, 'syncPlanProfiles'])
            ->middleware('permission:plans.edit');
        Route::post('roles/{role}/permissions', [AclSyncController::class, 'syncRolePermissions'])
            ->middleware('permission:roles.edit');
        Route::post('users/{user}/roles', [AclSyncController::class, 'syncUserRoles'])
            ->middleware('permission:users.edit');


            // Categories CRUD
        Route::apiResource('categories', CategoryController::class)
            ->middleware([
                'index' => 'permission:categories.view',
                'show' => 'permission:categories.view',
                'store' => 'permission:categories.create',
                'update' => 'permission:categories.edit',
                'destroy' => 'permission:categories.delete',
            ]);

        // Products CRUD
        Route::apiResource('products', ProductController::class)
            ->middleware([
                'index' => 'permission:products.view',
                'show' => 'permission:products.view',
                'store' => 'permission:products.create',
                'update' => 'permission:products.edit',
                'destroy' => 'permission:products.delete',
            ]);
        
        // Product ↔ Category sync
        Route::post('products/{product}/categories', [ProductController::class, 'syncCategories'])
            ->middleware('permission:products.edit');
    });
});