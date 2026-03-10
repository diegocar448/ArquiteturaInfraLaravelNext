<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\Actions\Product\ListProductsAction;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\CreateProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Actions\Product\DeleteProductAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Produtos
 */
class ProductController extends Controller
{
    /**
     * Listar produtos
     *
     * Retorna todos os produtos do tenant com paginacao. Requer permissao `products.view`.
     */
    public function index(ListProductsAction $action): AnonymousResourceCollection
    {
        $products = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return ProductResource::collection($products);
    }

    /**
     * Criar produto
     *
     * Cria um novo produto no cardapio do tenant. Requer permissao `products.create`.
     */
    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute(CreateProductDTO::fromRequest($request));

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir produto
     *
     * Retorna um produto com suas categorias (quando carregadas). Requer permissao `products.view`.
     */
    public function show(int $product, ShowProductAction $action): JsonResponse
    {
        $product = $action->execute($product);

        if (!$product) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        $product->load('categories');

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Atualizar produto
     *
     * Atualiza os dados de um produto existente. Requer permissao `products.edit`.
     */
    public function update(UpdateProductRequest $request, int $product, UpdateProductAction $action): JsonResponse
    {
        $updated = $action->execute($product, UpdateProductDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new ProductResource($updated),
        ]);
    }

    /**
     * Remover produto
     *
     * Remove um produto do cardapio. Requer permissao `products.delete`.
     */
    public function destroy(int $product, DeleteProductAction $action): JsonResponse
    {
        $deleted = $action->execute($product);

        if (!$deleted) {
            return response()->json(['message' => 'Produto nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Produto removido com sucesso.',
        ]);
    }



    /**
     * Sincronizar categorias do produto
     *
     * Substitui todas as categorias vinculadas a um produto pelos IDs informados.
     * Requer permissao `products.edit`.
     */
    public function syncCategories(Request $request, int $product): JsonResponse
    {
        $request->validate([
            'categories' => ['required', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ]);

        $product = \App\Models\Product::findOrFail($product);
        $product->categories()->sync($request->categories);
        $product->load('categories');

        return response()->json([
            'message' => 'Categorias do produto atualizadas.',
            'data' => new ProductResource($product),
        ]);
    }
}