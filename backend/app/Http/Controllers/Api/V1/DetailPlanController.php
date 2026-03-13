<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Plan\CreateDetailPlanAction;
use App\Actions\Plan\DeleteDetailPlanAction;
use App\Actions\Plan\ListDetailPlansAction;
use App\Actions\Plan\UpdateDetailPlanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreDetailPlanRequest;
use App\Http\Requests\Plan\UpdateDetailPlanRequest;
use App\Http\Resources\DetailPlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Detalhes do Plano
 */
class DetailPlanController extends Controller
{
    /**
     * Listar detalhes do plano
     *
     * Retorna todos os detalhes de um plano. Requer permissao `detail_plans.view`.
     */
    public function index(int $plan, ListDetailPlansAction $action): AnonymousResourceCollection
    {
        return DetailPlanResource::collection($action->execute($plan));
    }

    /**
     * Criar detalhe do plano
     *
     * Adiciona um novo detalhe ao plano. Requer permissao `detail_plans.create`.
     */
    public function store(StoreDetailPlanRequest $request, int $plan, CreateDetailPlanAction $action): JsonResponse
    {
        $detail = $action->execute($plan, $request->validated('name'));

        return (new DetailPlanResource($detail))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualizar detalhe do plano
     *
     * Atualiza um detalhe existente. Requer permissao `detail_plans.edit`.
     */
    public function update(UpdateDetailPlanRequest $request, int $plan, int $detail, UpdateDetailPlanAction $action): JsonResponse
    {
        $updated = $action->execute($detail, $request->validated('name'));

        if (! $updated) {
            return response()->json(['message' => 'Detalhe nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new DetailPlanResource($updated),
        ]);
    }

    /**
     * Remover detalhe do plano
     *
     * Remove um detalhe do plano. Requer permissao `detail_plans.delete`.
     */
    public function destroy(int $plan, int $detail, DeleteDetailPlanAction $action): JsonResponse
    {
        $deleted = $action->execute($detail);

        if (! $deleted) {
            return response()->json(['message' => 'Detalhe nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Detalhe removido com sucesso.',
        ]);
    }
}
