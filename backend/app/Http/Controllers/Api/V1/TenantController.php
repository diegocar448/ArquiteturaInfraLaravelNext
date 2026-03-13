<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\DeleteTenantAction;
use App\Actions\Tenant\ListTenantsAction;
use App\Actions\Tenant\ShowTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Tenants
 */
class TenantController extends Controller
{
    /**
     * Listar tenants
     *
     * Retorna todos os tenants com paginacao. Requer permissao `tenants.view`.
     */
    public function index(ListTenantsAction $action): AnonymousResourceCollection
    {
        $tenants = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return TenantResource::collection($tenants);
    }

    /**
     * Criar tenant
     *
     * Cria um novo tenant (restaurante). Requer permissao `tenants.create`.
     */
    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $tenant = $action->execute(CreateTenantDTO::fromRequest($request));

        $tenant->load('plan');

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir tenant
     *
     * Retorna os dados de um tenant especifico. Requer permissao `tenants.view`.
     */
    public function show(int $tenant, ShowTenantAction $action): JsonResponse
    {
        $tenant = $action->execute($tenant);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($tenant),
        ]);
    }

    /**
     * Atualizar tenant
     *
     * Atualiza os dados de um tenant existente. Requer permissao `tenants.edit`.
     */
    public function update(UpdateTenantRequest $request, int $tenant, UpdateTenantAction $action): JsonResponse
    {
        $updated = $action->execute($tenant, UpdateTenantDTO::fromRequest($request));

        if (! $updated) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($updated),
        ]);
    }

    /**
     * Remover tenant
     *
     * Remove um tenant do sistema. Requer permissao `tenants.delete`.
     */
    public function destroy(int $tenant, DeleteTenantAction $action): JsonResponse
    {
        $deleted = $action->execute($tenant);

        if (! $deleted) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Tenant removido com sucesso.',
        ]);
    }
}
