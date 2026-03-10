<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Actions\Plan\ListPlansAction;
use App\Actions\Plan\ShowPlanAction;
use App\Actions\Plan\CreatePlanAction;
use App\Actions\Plan\UpdatePlanAction;
use App\Actions\Plan\DeletePlanAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Planos
 */
class PlanController extends Controller
{
    /**
     * Listar planos
     *
     * Retorna todos os planos com paginacao. Requer permissao `plans.view`.
     */
    public function index(ListPlansAction $action): AnonymousResourceCollection
    {
        $plans = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return PlanResource::collection($plans);
    }

    /**
     * Criar plano
     *
     * Cria um novo plano de assinatura. Requer permissao `plans.create`.
     */
    public function store(StorePlanRequest $request, CreatePlanAction $action): JsonResponse
    {
        $plan = $action->execute(CreatePlanDTO::fromRequest($request));

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir plano
     *
     * Retorna um plano especifico com seus detalhes. Requer permissao `plans.view`.
     */
    public function show(int $plan, ShowPlanAction $action): JsonResponse
    {
        $plan = $action->execute($plan);

        if (!$plan) {
            return response()->json(['message' => 'Plano nao encontrado.'], 404);
        }

        $plan->load('details');

        return response()->json([
            'data' => new PlanResource($plan),
        ]);
    }

    /**
     * Atualizar plano
     *
     * Atualiza os dados de um plano existente. Requer permissao `plans.edit`.
     */
    public function update(UpdatePlanRequest $request, int $plan, UpdatePlanAction $action): JsonResponse
    {
        $updated = $action->execute($plan, UpdatePlanDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Plano nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new PlanResource($updated),
        ]);
    }

    /**
     * Remover plano
     *
     * Remove um plano de assinatura. Requer permissao `plans.delete`.
     */
    public function destroy(int $plan, DeletePlanAction $action): JsonResponse
    {
        $deleted = $action->execute($plan);

        if (!$deleted) {
            return response()->json(['message' => 'Plano nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Plano removido com sucesso.',
        ]);
    }
}