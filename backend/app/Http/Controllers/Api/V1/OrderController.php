<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\ListOrdersRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Actions\Order\ListOrdersAction;
use App\Actions\Order\ShowOrderAction;
use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\UpdateOrderStatusAction;
use App\Actions\Order\DeleteOrderAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Pedidos
 */
class OrderController extends Controller
{
    /**
     * Listar pedidos
     *
     * Retorna todos os pedidos do tenant com paginacao.
     * Aceita query parameter `status` para filtrar (ex: `?status=open`).
     * Requer permissao `orders.view`.
     */
    public function index(ListOrdersRequest $request, ListOrdersAction $action): AnonymousResourceCollection
    {
        $orders = $action->execute(
            perPage: $request->integer('per_page', 15),
            status: $request->validated('status'),
        );

        return OrderResource::collection($orders);
    }

    /**
     * Criar pedido
     *
     * Cria um novo pedido com produtos. O total e calculado automaticamente
     * a partir dos precos atuais dos produtos (price snapshot).
     * Requer permissao `orders.create`.
     */
    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute(CreateOrderDTO::fromRequest($request));

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir pedido
     *
     * Retorna um pedido com seus produtos e mesa. Requer permissao `orders.view`.
     */
    public function show(int $order, ShowOrderAction $action): JsonResponse
    {
        $order = $action->execute($order);

        if (!$order) {
            return response()->json(['message' => 'Pedido nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Atualizar status do pedido
     *
     * Transiciona o status do pedido. Apenas transicoes validas sao aceitas:
     * open → accepted/rejected, accepted → preparing/rejected, preparing → done, done → delivered.
     * Requer permissao `orders.edit`.
     */
    public function update(UpdateOrderStatusRequest $request, int $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $result = $action->execute($order, UpdateOrderStatusDTO::fromRequest($request));

        if (is_string($result)) {
            $status = str_contains($result, 'nao encontrado') ? 404 : 422;
            return response()->json(['message' => $result], $status);
        }

        return response()->json([
            'data' => new OrderResource($result),
        ]);
    }

    /**
     * Remover pedido
     *
     * Remove um pedido e seus produtos vinculados (cascade).
     * Requer permissao `orders.delete`.
     */
    public function destroy(int $order, DeleteOrderAction $action): JsonResponse
    {
        $deleted = $action->execute($order);

        if (!$deleted) {
            return response()->json(['message' => 'Pedido nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Pedido removido com sucesso.',
        ]);
    }
}