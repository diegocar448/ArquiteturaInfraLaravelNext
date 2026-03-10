<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Actions\Role\ListRolesAction;
use App\Actions\Role\ShowRoleAction;
use App\Actions\Role\CreateRoleAction;
use App\Actions\Role\UpdateRoleAction;
use App\Actions\Role\DeleteRoleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Papeis (Roles)
 */
class RoleController extends Controller
{
    /**
     * Listar papeis
     *
     * Retorna todos os papeis do tenant com paginacao. Requer permissao `roles.view`.
     */
    public function index(ListRolesAction $action): AnonymousResourceCollection
    {
        $roles = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return RoleResource::collection($roles);
    }

    /**
     * Criar papel
     *
     * Cria um novo papel no tenant. Requer permissao `roles.create`.
     */
    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $role = $action->execute(CreateRoleDTO::fromRequest($request));

        return (new RoleResource($role))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir papel
     *
     * Retorna um papel com suas permissoes. Requer permissao `roles.view`.
     */
    public function show(int $role, ShowRoleAction $action): JsonResponse
    {
        $role = $action->execute($role);

        if (!$role) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        $role->load('permissions');

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Atualizar papel
     *
     * Atualiza os dados de um papel existente. Requer permissao `roles.edit`.
     */
    public function update(UpdateRoleRequest $request, int $role, UpdateRoleAction $action): JsonResponse
    {
        $updated = $action->execute($role, UpdateRoleDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new RoleResource($updated),
        ]);
    }

    /**
     * Remover papel
     *
     * Remove um papel do tenant. Requer permissao `roles.delete`.
     */
    public function destroy(int $role, DeleteRoleAction $action): JsonResponse
    {
        $deleted = $action->execute($role);

        if (!$deleted) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Papel removido com sucesso.',
        ]);
    }
}