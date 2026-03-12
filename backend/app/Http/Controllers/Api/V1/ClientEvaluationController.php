<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Actions\Evaluation\CreateEvaluationAction;
use Illuminate\Http\JsonResponse;

/**
 * @tags Avaliacoes (Cliente)
 */
class ClientEvaluationController extends Controller
{
    /**
     * Criar avaliacao
     *
     * Permite que um cliente autenticado avalie um pedido entregue.
     * Requer autenticacao via guard `client`.
     * Apenas pedidos com status `delivered` e pertencentes ao cliente podem ser avaliados.
     */
    public function store(StoreEvaluationRequest $request, CreateEvaluationAction $action): JsonResponse
    {
        $clientId = auth('client')->id();

        $result = $action->execute(
            CreateEvaluationDTO::fromRequest($request),
            $clientId,
        );

        if (is_string($result)) {
            return response()->json(['message' => $result], 422);
        }

        $result->load(['client', 'order']);

        return (new EvaluationResource($result))
            ->response()
            ->setStatusCode(201);
    }
}