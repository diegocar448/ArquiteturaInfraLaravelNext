<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Table\CreateTableAction;
use App\Actions\Table\DeleteTableAction;
use App\Actions\Table\GenerateQrCodeAction;
use App\Actions\Table\ListTablesAction;
use App\Actions\Table\ShowTableAction;
use App\Actions\Table\UpdateTableAction;
use App\DTOs\Table\CreateTableDTO;
use App\DTOs\Table\UpdateTableDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Table\StoreTableRequest;
use App\Http\Requests\Table\UpdateTableRequest;
use App\Http\Resources\TableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Mesas
 */
class TableController extends Controller
{
    /**
     * Listar mesas
     *
     * Retorna todas as mesas do tenant com paginacao. Requer permissao `tables.view`.
     */
    public function index(ListTablesAction $action): AnonymousResourceCollection
    {
        $tables = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return TableResource::collection($tables);
    }

    /**
     * Criar mesa
     *
     * Cria uma nova mesa no tenant. UUID e gerado automaticamente pelo Observer.
     * Requer permissao `tables.create`.
     */
    public function store(StoreTableRequest $request, CreateTableAction $action): JsonResponse
    {
        $table = $action->execute(CreateTableDTO::fromRequest($request));

        return (new TableResource($table))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir mesa
     *
     * Retorna os dados de uma mesa especifica. Requer permissao `tables.view`.
     */
    public function show(int $table, ShowTableAction $action): JsonResponse
    {
        $table = $action->execute($table);

        if (! $table) {
            return response()->json(['message' => 'Mesa nao encontrada.'], 404);
        }

        return response()->json([
            'data' => new TableResource($table),
        ]);
    }

    /**
     * Atualizar mesa
     *
     * Atualiza os dados de uma mesa existente. Requer permissao `tables.edit`.
     */
    public function update(UpdateTableRequest $request, int $table, UpdateTableAction $action): JsonResponse
    {
        $updated = $action->execute($table, UpdateTableDTO::fromRequest($request));

        if (! $updated) {
            return response()->json(['message' => 'Mesa nao encontrada.'], 404);
        }

        return response()->json([
            'data' => new TableResource($updated),
        ]);
    }

    /**
     * Remover mesa
     *
     * Remove uma mesa do tenant. Requer permissao `tables.delete`.
     */
    public function destroy(int $table, DeleteTableAction $action): JsonResponse
    {
        $deleted = $action->execute($table);

        if (! $deleted) {
            return response()->json(['message' => 'Mesa nao encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Mesa removida com sucesso.',
        ]);
    }

    /**
     * QR Code da mesa
     *
     * Gera e retorna o QR Code da mesa como imagem base64.
     * O QR Code aponta para a URL publica do cardapio com o UUID da mesa.
     * Requer permissao `tables.view`.
     */
    public function qrcode(int $table, GenerateQrCodeAction $action): JsonResponse
    {
        $result = $action->execute($table);

        if (! $result) {
            return response()->json(['message' => 'Mesa nao encontrada.'], 404);
        }

        return response()->json([
            'data' => [
                'table' => new TableResource($result['table']),
                'qrcode' => $result['qrcode'],
                'url' => $result['url'],
            ],
        ]);
    }
}
