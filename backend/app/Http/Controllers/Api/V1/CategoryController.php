<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\DTOs\Category\CreateCategoryDTO;
use App\DTOs\Category\UpdateCategoryDTO;
use App\Actions\Category\ListCategoriesAction;
use App\Actions\Category\ShowCategoryAction;
use App\Actions\Category\CreateCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\Actions\Category\DeleteCategoryAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Categorias
 */
class CategoryController extends Controller
{
    /**
     * Listar categorias
     *
     * Retorna todas as categorias do tenant com paginacao. Requer permissao `categories.view`.
     */
    public function index(ListCategoriesAction $action): AnonymousResourceCollection
    {
        $categories = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return CategoryResource::collection($categories);
    }

    /**
     * Criar categoria
     *
     * Cria uma nova categoria no cardapio do tenant. Requer permissao `categories.create`.
     */
    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $category = $action->execute(CreateCategoryDTO::fromRequest($request));

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibir categoria
     *
     * Retorna uma categoria com seus produtos (quando carregados). Requer permissao `categories.view`.
     */
    public function show(int $category, ShowCategoryAction $action): JsonResponse
    {
        $category = $action->execute($category);

        if (!$category) {
            return response()->json(['message' => 'Categoria nao encontrada.'], 404);
        }

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Atualizar categoria
     *
     * Atualiza os dados de uma categoria existente. Requer permissao `categories.edit`.
     */
    public function update(UpdateCategoryRequest $request, int $category, UpdateCategoryAction $action): JsonResponse
    {
        $updated = $action->execute($category, UpdateCategoryDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Categoria nao encontrada.'], 404);
        }

        return response()->json([
            'data' => new CategoryResource($updated),
        ]);
    }

    /**
     * Remover categoria
     *
     * Remove uma categoria do cardapio. Requer permissao `categories.delete`.
     */
    public function destroy(int $category, DeleteCategoryAction $action): JsonResponse
    {
        $deleted = $action->execute($category);

        if (!$deleted) {
            return response()->json(['message' => 'Categoria nao encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Categoria removida com sucesso.',
        ]);
    }
}