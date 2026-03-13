<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Evaluation\DeleteEvaluationAction;
use App\Actions\Evaluation\ListEvaluationsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Avaliacoes
 */
class EvaluationController extends Controller
{
    /**
     * Listar avaliacoes do tenant
     *
     * Retorna todas as avaliacoes de pedidos do tenant. Requer permissao `orders.view`.
     */
    public function index(ListEvaluationsAction $action): AnonymousResourceCollection
    {
        $user = auth('api')->user();
        $tenantId = $user->tenant_id;

        // Super-admin ve todas
        if ($user->isSuperAdmin()) {
            $tenantId = 0; // trigger para buscar todas
        }

        $evaluations = $action->execute(
            tenantId: $tenantId,
            perPage: request()->integer('per_page', 15),
        );

        return EvaluationResource::collection($evaluations);
    }

    /**
     * Remover avaliacao
     *
     * Remove uma avaliacao de pedido. Requer permissao `orders.delete`.
     */
    public function destroy(int $evaluation, DeleteEvaluationAction $action): JsonResponse
    {
        $deleted = $action->execute($evaluation);

        if (! $deleted) {
            return response()->json(['message' => 'Avaliacao nao encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Avaliacao removida com sucesso.',
        ]);
    }
}
