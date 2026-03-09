<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Actions\Tenant\ListTenantsAction;
use App\Actions\Tenant\ShowTenantAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\Actions\Tenant\DeleteTenantAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function index(ListTenantsAction $action): AnonymousResourceCollection
    {
        $tenants = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return TenantResource::collection($tenants);
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $tenant = $action->execute(CreateTenantDTO::fromRequest($request));

        $tenant->load('plan');

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $tenant, ShowTenantAction $action): JsonResponse
    {
        $tenant = $action->execute($tenant);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($tenant),
        ]);
    }

    public function update(UpdateTenantRequest $request, int $tenant, UpdateTenantAction $action): JsonResponse
    {
        $updated = $action->execute($tenant, UpdateTenantDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($updated),
        ]);
    }

    public function destroy(int $tenant, DeleteTenantAction $action): JsonResponse
    {
        $deleted = $action->execute($tenant);

        if (!$deleted) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Tenant removido com sucesso.',
        ]);
    }
}