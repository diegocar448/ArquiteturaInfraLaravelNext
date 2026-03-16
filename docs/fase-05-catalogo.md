## Fase 5 - Catalogo: Categorias + Produtos

Nesta fase construimos o modulo de catalogo — categorias e produtos — que e o coracao de qualquer sistema de delivery. Cada tenant (restaurante) tera suas proprias categorias e produtos, completamente isolados dos outros tenants.

**O que vamos construir:**

```
┌─────────────────────────────────────────────────────────────┐
│                    Catalogo (tenant-scoped)                   │
│                                                               │
│  ┌──────────────┐    N:N    ┌──────────────────┐             │
│  │  Categories   │◄────────►│    Products       │             │
│  │              │           │                  │             │
│  │  - name      │           │  - title         │             │
│  │  - url (slug)│           │  - flag          │             │
│  │  - description│          │  - image         │             │
│  └──────────────┘           │  - price         │             │
│         │                   │  - description   │             │
│         │                   └──────────────────┘             │
│         │                          │                         │
│         └──────────┬───────────────┘                         │
│                    │                                         │
│          ┌─────────┴─────────┐                               │
│          │ category_product  │  (tabela pivot)               │
│          │ - category_id     │                               │
│          │ - product_id      │                               │
│          └───────────────────┘                               │
└─────────────────────────────────────────────────────────────┘
```

**Relacao N:N (muitos-para-muitos):**
Um produto pode pertencer a varias categorias (ex: "Coca-Cola" esta em "Bebidas" e "Promocoes"), e uma categoria pode conter varios produtos.

**Pre-requisitos:** Fase 4 concluida (ACL + permissoes + middleware).

---

## Passo 5.1 - Conceito: Catalogo multi-tenant

Antes de codar, entenda como o catalogo se encaixa na arquitetura multi-tenant:

```
┌──────────────────────────────────────────────┐
│              Tenant A (Pizzaria)               │
│                                                │
│  Categories: Pizzas, Bebidas, Sobremesas      │
│  Products: Margherita, Coca-Cola, Pudim       │
│                                                │
│  Vinculo: Coca-Cola → [Bebidas, Promocoes]    │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│              Tenant B (Hamburgueria)           │
│                                                │
│  Categories: Hambúrgueres, Bebidas, Combos    │
│  Products: X-Bacon, Suco Natural, Combo 1     │
│                                                │
│  Vinculo: Combo 1 → [Combos, Hambúrgueres]   │
└──────────────────────────────────────────────┘
```

**Isolamento automatico:**
- Ambas as tabelas (`categories` e `products`) tem `tenant_id`
- O trait `BelongsToTenant` (criado na Fase 3) aplica o `TenantScope` automaticamente
- Quando o gerente da Pizzaria faz `GET /api/v1/categories`, ele so ve as categorias da Pizzaria
- O `tenant_id` e preenchido automaticamente no `creating()` a partir do JWT do usuario autenticado

**Permissoes ja criadas:**
O `PermissionSeeder` da Fase 4 ja criou as permissoes `categories.*` e `products.*`:

```
categories.view    categories.create    categories.edit    categories.delete
products.view      products.create      products.edit      products.delete
```

**Stack completa de cada recurso (mesma da Fase 3 e 4):**

```
Controller → Action → Repository → Model (banco)
     ↑          ↑          ↑
  Request      DTO     Interface
  Resource
```

**Conceitos desta fase:**
- **Slug automatico** — Observer gera `url` a partir do `name`/`title`
- **UUID** — identificador publico (nao expoe IDs sequenciais)
- **Pivot table** — tabela intermediaria para relacoes N:N
- **`flag`** — enum que indica o status do produto (ex: `active`, `inactive`, `featured`)
- **Image upload** — armazenamento de imagem do produto (preparado, implementacao de upload em fase futura)

---

## Passo 5.2 - Migration: tabela categories + Model + Observer

A tabela `categories` armazena as categorias do cardapio de cada tenant.

**Por que `uuid`?**
Em APIs publicas, nunca exponha o `id` sequencial (permite enumeration attack). O `uuid` e o identificador seguro para uso externo.

**Por que `url` (slug)?**
Permite URLs amigaveis como `/categorias/bebidas` em vez de `/categorias/3`.

Crie `backend/database/migrations/0001_01_02_000006_create_categories_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('url')->index(); // slug — index para buscas rapidas
            $table->text('description')->nullable();
            $table->timestamps();

            // Slug unico por tenant (dois tenants podem ter "Bebidas")
            $table->unique(['tenant_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

**Por que `unique(['tenant_id', 'url'])` em vez de `unique('url')`?**
Cada tenant e independente. Dois restaurantes podem ter uma categoria chamada "Bebidas" (mesmo slug `bebidas`). A unicidade so vale **dentro** do mesmo tenant.

**Por que `onDelete('cascade')`?**
Se um tenant for deletado, todas as suas categorias sao removidas automaticamente. Sem orphan records.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Deve exibir:
```
INFO  Running migrations.

  0001_01_02_000006_create_categories_table ... DONE
```

Agora crie o Model `backend/app/Models/Category.php`:

```php
<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(CategoryObserver::class)]
class Category extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'description',
    ];
}
```

**Nota:** O relacionamento `products()` sera adicionado no Passo 5.9 quando criarmos a tabela pivot.

Crie o Observer `backend/app/Observers/CategoryObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryObserver
{
    public function creating(Category $category): void
    {
        if (empty($category->uuid)) {
            $category->uuid = (string) Str::uuid();
        }

        if (empty($category->url)) {
            $category->url = Str::slug($category->name);
        }
    }

    public function updating(Category $category): void
    {
        if ($category->isDirty('name') && !$category->isDirty('url')) {
            $category->url = Str::slug($category->name);
        }
    }
}
```

**Como funciona:**
- `creating` — antes de inserir no banco, gera UUID e slug automaticamente
- `updating` — se o nome mudou e o slug nao foi alterado manualmente, regenera
- `#[ObservedBy]` — registra o Observer sem precisar de `AppServiceProvider`

Crie a Factory `backend/database/factories/CategoryFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'url' => Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}
```

**Testar no tinker:**

```bash
docker compose exec backend php artisan tinker
```

```php
// Primeiro, autenticar para o BelongsToTenant funcionar
$user = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($user);

$cat = App\Models\Category::create(['name' => 'Bebidas']);
echo $cat->uuid;   // "a1b2c3d4-..." (gerado automaticamente)
echo $cat->url;    // "bebidas" (slug gerado)
echo $cat->tenant_id; // tenant do gerente (preenchido automaticamente)

$cat->forceDelete(); // limpar
exit
```

> **Nota:** Usamos `setUser()` em vez de `login()` no tinker. O `login()` do JWT guard retorna um token mas **nao persiste** o usuario na sessao do tinker — `auth('api')->user()` continua retornando `null`. O `setUser()` define o usuario diretamente no guard, permitindo que o `BelongsToTenant` funcione.

> **Dica:** Se voce rodar `Category::create()` sem autenticar, o `tenant_id` sera `null` e a query falhara (coluna NOT NULL). O `BelongsToTenant` depende de um usuario autenticado.

### Arquivos criados

```
backend/
├── database/migrations/0001_01_02_000006_create_categories_table.php
├── database/factories/CategoryFactory.php
├── app/Models/Category.php
└── app/Observers/CategoryObserver.php
```

---

## Passo 5.3 - Category Repository + CRUD completo

Seguindo o padrao de Clean Architecture, criamos a camada completa: Interface → Repository → DTOs → Actions.

**Relembrando a arquitetura:**
```
Controller → Action → Repository → Model (banco)
     ↑          ↑          ↑
  Request      DTO     Interface
```

Crie `backend/app/Repositories/Contracts/CategoryRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Category;

    public function findByUrl(string $url): ?Category;

    public function create(array $data): Category;

    public function update(int $id, array $data): ?Category;

    public function delete(int $id): bool;
}
```

Crie `backend/app/Repositories/Eloquent/CategoryRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly Category $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function findByUrl(string $url): ?Category
    {
        return $this->model->where('url', $url)->first();
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $category = $this->findById($id);

        if (!$category) {
            return null;
        }

        $category->update($data);

        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        $category = $this->findById($id);

        if (!$category) {
            return false;
        }

        return (bool) $category->delete();
    }
}
```

**Por que `fresh()` no update?**
O Observer pode modificar o `url` durante o `updating`. O `fresh()` recarrega o model do banco com os valores atualizados.

**Por que nao precisamos filtrar por `tenant_id`?**
O trait `BelongsToTenant` aplica o `TenantScope` automaticamente em todas as queries. O `$this->model->find($id)` ja filtra pelo tenant do usuario autenticado.

Registre o binding no `backend/app/Providers/RepositoryServiceProvider.php`. Adicione as linhas:

```php
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Eloquent\CategoryRepository;
```

E no array `$repositories`:

```php
CategoryRepositoryInterface::class => CategoryRepository::class,
```

O arquivo completo fica:

```php
<?php

namespace App\Providers;

use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Eloquent\DetailPlanRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\ProfileRepository;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\CategoryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        DetailPlanRepositoryInterface::class => DetailPlanRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
        ProfileRepositoryInterface::class => ProfileRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
```

Agora os DTOs. Crie o diretorio e os arquivos:

```bash
mkdir -p backend/app/DTOs/Category
mkdir -p backend/app/Actions/Category
```

Crie `backend/app/DTOs/Category/CreateCategoryDTO.php`:

```php
<?php

namespace App\DTOs\Category;

use App\Http\Requests\Category\StoreCategoryRequest;

final readonly class CreateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreCategoryRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
```

Crie `backend/app/DTOs/Category/UpdateCategoryDTO.php`:

```php
<?php

namespace App\DTOs\Category;

use App\Http\Requests\Category\UpdateCategoryRequest;

final readonly class UpdateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $url,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateCategoryRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            url: $request->validated('url'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
```

Agora as Actions:

Crie `backend/app/Actions/Category/ListCategoriesAction.php`:

```php
<?php

namespace App\Actions\Category;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListCategoriesAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

Crie `backend/app/Actions/Category/ShowCategoryAction.php`:

```php
<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

final class ShowCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Category
    {
        return $this->repository->findById($id);
    }
}
```

Crie `backend/app/Actions/Category/CreateCategoryAction.php`:

```php
<?php

namespace App\Actions\Category;

use App\DTOs\Category\CreateCategoryDTO;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

final class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        return $this->repository->create($dto->toArray());
    }
}
```

Crie `backend/app/Actions/Category/UpdateCategoryAction.php`:

```php
<?php

namespace App\Actions\Category;

use App\DTOs\Category\UpdateCategoryDTO;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

final class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateCategoryDTO $dto): ?Category
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

Crie `backend/app/Actions/Category/DeleteCategoryAction.php`:

```php
<?php

namespace App\Actions\Category;

use App\Repositories\Contracts\CategoryRepositoryInterface;

final class DeleteCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

### Resumo da camada criada

```
app/
├── Actions/Category/
│   ├── ListCategoriesAction.php    (listar paginado)
│   ├── ShowCategoryAction.php      (buscar por ID)
│   ├── CreateCategoryAction.php    (criar)
│   ├── UpdateCategoryAction.php    (atualizar)
│   └── DeleteCategoryAction.php    (deletar)
├── DTOs/Category/
│   ├── CreateCategoryDTO.php       (dados para criacao)
│   └── UpdateCategoryDTO.php       (dados para atualizacao)
└── Repositories/
    ├── Contracts/CategoryRepositoryInterface.php
    └── Eloquent/CategoryRepository.php
```

---

## Passo 5.4 - Category Controller + Routes + FormRequests + Resource

Crie o diretorio para os FormRequests:

```bash
mkdir -p backend/app/Http/Requests/Category
```

Crie `backend/app/Http/Requests/Category/StoreCategoryRequest.php`:

```php
<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria e obrigatorio.',
            'name.max' => 'O nome nao pode ter mais de 255 caracteres.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Category/UpdateCategoryRequest.php`:

```php
<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria e obrigatorio.',
        ];
    }
}
```

Crie `backend/app/Http/Resources/CategoryResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

> **Nota:** No Passo 5.9 (pivot), adicionaremos a linha `'products' => ProductResource::collection($this->whenLoaded('products'))` neste Resource. Por enquanto, sem o `ProductResource` criado, incluir essa linha causaria erro.

Crie `backend/app/Http/Controllers/Api/V1/CategoryController.php`:

```php
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
```

**Antes das rotas:** precisamos evoluir o middleware `IdentifyTenant` para suportar um modo `required`. Ate agora, o middleware aceita usuarios sem tenant (como o super-admin) para que ele possa gerenciar planos e tenants. Mas rotas de catalogo **exigem** um tenant — sem ele, o `BelongsToTenant` nao consegue preencher o `tenant_id` e o INSERT falha.

Edite `backend/app/Http/Middleware/IdentifyTenant.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * @param  string  $mode  'optional' (default) ou 'required'
     */
    public function handle(Request $request, Closure $next, string $mode = 'optional'): Response
    {
        $user = auth('api')->user();

        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;

            if (!$tenant || !$tenant->active) {
                return response()->json([
                    'message' => 'Tenant inativo ou nao encontrado.',
                ], 403);
            }

            app()->instance('currentTenant', $tenant);
        } elseif ($mode === 'required' && !$user?->isSuperAdmin()) {
            return response()->json([
                'message' => 'Esta acao requer um usuario vinculado a um tenant.',
            ], 403);
        }

        return $next($request);
    }
}
```

**O que mudou:**
- O metodo `handle` agora recebe um terceiro parametro `$mode` com valor default `'optional'`
- Quando `$mode === 'required'` e o usuario nao e super-admin e nao tem `tenant_id`, retorna 403
- **Super-admin passa sempre** — pode visualizar dados de todos os tenants (o `TenantScope` nao filtra quando `tenant_id` e null)
- Rotas existentes (plans, tenants, profiles, roles) continuam com `tenant` (opcional)
- Rotas de catalogo (categories, products, tables, orders) usam `tenant:required`

**Como usar nas rotas:**
- `middleware('tenant')` — modo opcional (padrao, compativel com todas as rotas existentes)
- `middleware('tenant:required')` — modo obrigatorio (bloqueia usuarios sem tenant, exceto super-admin)

Adicione as rotas em `backend/routes/api.php`. No topo, adicione o import:

```php
use App\Http\Controllers\Api\V1\CategoryController;
```

Dentro do grupo `middleware('auth:api', 'tenant')`, crie um sub-grupo com `tenant:required` para as rotas de catalogo:

```php
// --- Rotas tenant-scoped (requer usuario vinculado a tenant) ---
Route::middleware('tenant:required')->group(function () {
    // Categories CRUD
    Route::apiResource('categories', CategoryController::class)
        ->middleware([
            'index' => 'permission:categories.view',
            'show' => 'permission:categories.view',
            'store' => 'permission:categories.create',
            'update' => 'permission:categories.edit',
            'destroy' => 'permission:categories.delete',
        ]);
});
```

> **Nota:** O middleware `tenant:required` fica **dentro** do grupo `auth:api, tenant`. Assim, a cadeia e: `auth:api` (verifica JWT) → `tenant` (identifica tenant se houver) → `tenant:required` (bloqueia se nao houver). O duplo `tenant` nao causa problema — o Laravel executa ambos, e o segundo valida o modo `required`.

Limpe o cache de rotas:

```bash
docker compose exec backend php artisan route:clear
```

Verifique se as rotas foram registradas:

```bash
docker compose exec backend php artisan route:list --path=categories
```

Deve exibir:
```
GET|HEAD  api/v1/categories .............. categories.index
POST      api/v1/categories .............. categories.store
GET|HEAD  api/v1/categories/{category} ... categories.show
PUT|PATCH api/v1/categories/{category} ... categories.update
DELETE    api/v1/categories/{category} ... categories.destroy
```

### Arquivos criados

```
backend/
├── app/Http/Middleware/IdentifyTenant.php  (modificado — modo required)
├── app/Http/Controllers/Api/V1/CategoryController.php
├── app/Http/Requests/Category/
│   ├── StoreCategoryRequest.php
│   └── UpdateCategoryRequest.php
├── app/Http/Resources/CategoryResource.php
└── routes/api.php  (modificado — import + rotas + tenant:required)
```

---

## Passo 5.5 - Category Seeder + teste da API

Crie `backend/database/seeders/CategorySeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        $categories = [
            ['name' => 'Pizzas', 'description' => 'Pizzas tradicionais e especiais'],
            ['name' => 'Hambúrgueres', 'description' => 'Hambúrgueres artesanais'],
            ['name' => 'Bebidas', 'description' => 'Refrigerantes, sucos e agua'],
            ['name' => 'Sobremesas', 'description' => 'Doces e sobremesas da casa'],
            ['name' => 'Combos', 'description' => 'Combinacoes com desconto'],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['name']],
                array_merge($data, ['tenant_id' => $tenant->id]),
            );
        }

        $this->command->info("Categorias criadas para o tenant '{$tenant->name}'.");
    }
}
```

**Por que `firstOrCreate`?**
Permite rodar o seeder multiplas vezes sem duplicar registros. Se a categoria "Pizzas" ja existe para esse tenant, pula.

**Por que passamos `tenant_id` explicitamente?**
O `BelongsToTenant` auto-preenche `tenant_id` a partir do JWT do usuario autenticado. Mas em seeders nao ha usuario autenticado, entao precisamos informar manualmente.

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=CategorySeeder
```

Agora teste a API completa. Primeiro, obtenha um token JWT:

```bash
# Login como gerente do tenant demo
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

echo $TOKEN
```

**Listar categorias:**

```bash
curl -s http://localhost/api/v1/categories \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

Deve retornar as 5 categorias do tenant com paginacao.

**Criar categoria:**

```bash
curl -s -X POST http://localhost/api/v1/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Entradas", "description": "Aperitivos e entradas"}' \
  | python3 -m json.tool
```

**Exibir categoria:**

```bash
curl -s http://localhost/api/v1/categories/1 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Atualizar categoria:**

```bash
curl -s -X PUT http://localhost/api/v1/categories/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Pizzas Especiais"}' \
  | python3 -m json.tool
```

Note que o `url` muda de `pizzas` para `pizzas-especiais` automaticamente (Observer).

**Deletar categoria:**

```bash
curl -s -X DELETE http://localhost/api/v1/categories/6 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

> **Dica:** Tambem pode testar pela documentacao interativa em http://localhost/docs/api — o CategoryController ja aparece agrupado como "Categorias" na sidebar (PHPDoc `@tags`).

---

## Passo 5.6 - Migration: tabela products + Model + Observer

A tabela `products` armazena os itens do cardapio de cada tenant.

**Campos especiais:**
- `flag` — status do produto. Valores possiveis: `active` (disponivel), `inactive` (indisponivel), `featured` (destaque)
- `image` — caminho da imagem do produto (upload sera implementado em fase futura)
- `price` — valor em decimal, mesma estrategia dos planos

Crie `backend/database/migrations/0001_01_02_000007_create_products_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('url')->index();
            $table->string('flag')->default('active'); // active, inactive, featured
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**Por que `flag` como `string` em vez de `enum`?**
PostgreSQL suporta enums nativos, mas alteracoes no enum exigem migrations mais complexas. Com `string`, basta adicionar novos valores sem migration extra. Validamos os valores no FormRequest.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Crie o Model `backend/app/Models/Product.php`:

```php
<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'url',
        'flag',
        'image',
        'price',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
```

**Nota:** O relacionamento `categories()` sera adicionado no Passo 5.9.

Crie o Observer `backend/app/Observers/ProductObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        if (empty($product->uuid)) {
            $product->uuid = (string) Str::uuid();
        }

        if (empty($product->url)) {
            $product->url = Str::slug($product->title);
        }
    }

    public function updating(Product $product): void
    {
        if ($product->isDirty('title') && !$product->isDirty('url')) {
            $product->url = Str::slug($product->title);
        }
    }
}
```

**Diferenca do CategoryObserver:** Aqui o slug vem do `title` (nao `name`), porque no schema do produto o campo principal e `title`.

Crie a Factory `backend/database/factories/ProductFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'url' => Str::slug($title),
            'flag' => fake()->randomElement(['active', 'inactive', 'featured']),
            'price' => fake()->randomFloat(2, 5, 99.99),
            'description' => fake()->sentence(),
        ];
    }
}
```

**Testar no tinker:**

```bash
docker compose exec backend php artisan tinker
```

```php
$user = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($user);

$product = App\Models\Product::create([
    'title' => 'Pizza Margherita',
    'price' => 39.90,
    'flag' => 'featured',
]);
echo $product->uuid;      // UUID gerado
echo $product->url;       // "pizza-margherita"
echo $product->tenant_id; // tenant do gerente

$product->forceDelete();
exit
```

### Arquivos criados

```
backend/
├── database/migrations/0001_01_02_000007_create_products_table.php
├── database/factories/ProductFactory.php
├── app/Models/Product.php
└── app/Observers/ProductObserver.php
```

---

## Passo 5.7 - Product Repository + CRUD completo

Mesma estrutura do Category, adaptada para Product.

Crie `backend/app/Repositories/Contracts/ProductRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Product;

    public function findByUrl(string $url): ?Product;

    public function create(array $data): Product;

    public function update(int $id, array $data): ?Product;

    public function delete(int $id): bool;
}
```

Crie `backend/app/Repositories/Eloquent/ProductRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly Product $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findByUrl(string $url): ?Product
    {
        return $this->model->where('url', $url)->first();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Product
    {
        $product = $this->findById($id);

        if (!$product) {
            return null;
        }

        $product->update($data);

        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);

        if (!$product) {
            return false;
        }

        return (bool) $product->delete();
    }
}
```

Registre no `backend/app/Providers/RepositoryServiceProvider.php`. Adicione os imports:

```php
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
```

E no array `$repositories`:

```php
ProductRepositoryInterface::class => ProductRepository::class,
```

Crie os DTOs:

```bash
mkdir -p backend/app/DTOs/Product
mkdir -p backend/app/Actions/Product
```

Crie `backend/app/DTOs/Product/CreateProductDTO.php`:

```php
<?php

namespace App\DTOs\Product;

use App\Http\Requests\Product\StoreProductRequest;

final readonly class CreateProductDTO
{
    public function __construct(
        public string $title,
        public float $price,
        public ?string $flag,
        public ?string $image,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProductRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            price: $request->validated('price'),
            flag: $request->validated('flag'),
            image: $request->validated('image'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'price' => $this->price,
            'flag' => $this->flag,
            'image' => $this->image,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
```

Crie `backend/app/DTOs/Product/UpdateProductDTO.php`:

```php
<?php

namespace App\DTOs\Product;

use App\Http\Requests\Product\UpdateProductRequest;

final readonly class UpdateProductDTO
{
    public function __construct(
        public string $title,
        public float $price,
        public ?string $url,
        public ?string $flag,
        public ?string $image,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            price: $request->validated('price'),
            url: $request->validated('url'),
            flag: $request->validated('flag'),
            image: $request->validated('image'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'price' => $this->price,
            'url' => $this->url,
            'flag' => $this->flag,
            'image' => $this->image,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
```

Agora as Actions:

Crie `backend/app/Actions/Product/ListProductsAction.php`:

```php
<?php

namespace App\Actions\Product;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProductsAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

Crie `backend/app/Actions/Product/ShowProductAction.php`:

```php
<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

final class ShowProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Product
    {
        return $this->repository->findById($id);
    }
}
```

Crie `backend/app/Actions/Product/CreateProductAction.php`:

```php
<?php

namespace App\Actions\Product;

use App\DTOs\Product\CreateProductDTO;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

final class CreateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        return $this->repository->create($dto->toArray());
    }
}
```

Crie `backend/app/Actions/Product/UpdateProductAction.php`:

```php
<?php

namespace App\Actions\Product;

use App\DTOs\Product\UpdateProductDTO;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

final class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateProductDTO $dto): ?Product
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

Crie `backend/app/Actions/Product/DeleteProductAction.php`:

```php
<?php

namespace App\Actions\Product;

use App\Repositories\Contracts\ProductRepositoryInterface;

final class DeleteProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

### Resumo da camada criada

```
app/
├── Actions/Product/
│   ├── ListProductsAction.php
│   ├── ShowProductAction.php
│   ├── CreateProductAction.php
│   ├── UpdateProductAction.php
│   └── DeleteProductAction.php
├── DTOs/Product/
│   ├── CreateProductDTO.php
│   └── UpdateProductDTO.php
└── Repositories/
    ├── Contracts/ProductRepositoryInterface.php
    └── Eloquent/ProductRepository.php
```

---

## Passo 5.8 - Product Controller + Routes + FormRequests + Resource

```bash
mkdir -p backend/app/Http/Requests/Product
```

Crie `backend/app/Http/Requests/Product/StoreProductRequest.php`:

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'flag' => ['nullable', 'string', 'in:active,inactive,featured'],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O titulo do produto e obrigatorio.',
            'price.required' => 'O preco e obrigatorio.',
            'price.numeric' => 'O preco deve ser um valor numerico.',
            'price.min' => 'O preco nao pode ser negativo.',
            'flag.in' => 'O status deve ser: active, inactive ou featured.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Product/UpdateProductRequest.php`:

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'url' => ['nullable', 'string', 'max:255'],
            'flag' => ['nullable', 'string', 'in:active,inactive,featured'],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O titulo do produto e obrigatorio.',
            'price.required' => 'O preco e obrigatorio.',
            'flag.in' => 'O status deve ser: active, inactive ou featured.',
        ];
    }
}
```

Crie `backend/app/Http/Resources/ProductResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'url' => $this->url,
            'flag' => $this->flag,
            'image' => $this->image,
            'price' => $this->price,
            'description' => $this->description,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

Crie `backend/app/Http/Controllers/Api/V1/ProductController.php`:

```php
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
}
```

Adicione as rotas em `backend/routes/api.php`. No topo, adicione:

```php
use App\Http\Controllers\Api\V1\ProductController;
```

Dentro do grupo `tenant:required` (criado no Passo 5.4), adicione junto com as categories:

```php
Route::middleware('tenant:required')->group(function () {
    // Categories CRUD (Passo 5.4)
    Route::apiResource('categories', CategoryController::class)
        ->middleware([...]);

    // Products CRUD
    Route::apiResource('products', ProductController::class)
        ->middleware([
            'index' => 'permission:products.view',
            'show' => 'permission:products.view',
            'store' => 'permission:products.create',
            'update' => 'permission:products.edit',
            'destroy' => 'permission:products.delete',
        ]);
});
```

Limpe o cache:

```bash
docker compose exec backend php artisan route:clear
docker compose exec backend php artisan route:list --path=products
```

### Arquivos criados

```
backend/
├── app/Http/Controllers/Api/V1/ProductController.php
├── app/Http/Requests/Product/
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
└── app/Http/Resources/ProductResource.php
```

---

## Passo 5.9 - Pivot category_product + relacionamentos

Agora criamos a tabela pivot que conecta categorias e produtos em uma relacao muitos-para-muitos.

**O que e uma tabela pivot?**
E uma tabela intermediaria que armazena os vinculos entre duas entidades. Sem ela, seria impossivel representar uma relacao N:N no banco relacional.

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  categories  │     │ category_product │     │  products    │
│              │     │                  │     │              │
│  id: 1      │◄───│  category_id: 1  │───►│  id: 1       │
│  Pizzas     │     │  product_id: 1   │     │  Margherita  │
│              │     │                  │     │              │
│  id: 2      │◄───│  category_id: 2  │───►│  id: 1       │
│  Promocoes  │     │  product_id: 1   │     │  Margherita  │
└─────────────┘     └──────────────────┘     └─────────────┘

A Margherita pertence a "Pizzas" E "Promocoes"
```

Crie `backend/database/migrations/0001_01_02_000008_create_category_product_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->primary(['category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
```

**Detalhes da migration:**
- `primary(['category_id', 'product_id'])` — chave primaria composta. Impede vinculos duplicados (mesma categoria + mesmo produto)
- `onDelete('cascade')` — se uma categoria ou produto for deletado, os vinculos sao removidos automaticamente
- **Sem `timestamps()`** — tabelas pivot simples nao precisam de datas
- **Sem `id()`** — a chave primaria composta substitui o ID auto-increment

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Agora adicione os relacionamentos nos Models.

Edite `backend/app/Models/Category.php` — adicione o import e o metodo:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

```php
public function products(): BelongsToMany
{
    return $this->belongsToMany(Product::class);
}
```

O Model completo:

```php
<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(CategoryObserver::class)]
class Category extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'description',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
```

Edite `backend/app/Models/Product.php` — adicione o import e o metodo:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

```php
public function categories(): BelongsToMany
{
    return $this->belongsToMany(Category::class);
}
```

O Model completo:

```php
<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'url',
        'flag',
        'image',
        'price',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
```

Agora atualize os Resources para incluir os relacionamentos.

Edite `backend/app/Http/Resources/CategoryResource.php` — adicione a linha `products` no array de retorno:

```php
'description' => $this->description,
'products' => ProductResource::collection($this->whenLoaded('products')),
'created_at' => $this->created_at->toISOString(),
```

Edite `backend/app/Http/Resources/ProductResource.php` — adicione a linha `categories` no array de retorno:

```php
'description' => $this->description,
'categories' => CategoryResource::collection($this->whenLoaded('categories')),
'created_at' => $this->created_at->toISOString(),
```

> **Por que so agora?** No Passo 5.4, o `CategoryResource` foi criado sem a linha `products` porque o `ProductResource` ainda nao existia. Agora que ambos os Resources existem (Passo 5.8), podemos adicionar as referencias cruzadas. O `whenLoaded()` garante que so serializa quando o relacionamento for explicitamente carregado com `->load()`.

Agora adicione um endpoint de sync para vincular/desvincular categorias de um produto. Edite `backend/app/Http/Controllers/Api/V1/ProductController.php` — adicione o metodo:

```php
use Illuminate\Http\Request;
```

```php
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
```

Adicione a rota em `backend/routes/api.php`, dentro do grupo `tenant:required` (junto com categories e products):

```php
// Product ↔ Category sync
Route::post('products/{product}/categories', [ProductController::class, 'syncCategories'])
    ->middleware('permission:products.edit');
```

**Como funciona o `sync()`:**
O metodo `sync()` do Eloquent e inteligente:
- Recebe um array de IDs: `[1, 3, 5]`
- Remove vinculos que nao estao no array
- Adiciona vinculos novos
- Mantem vinculos que ja existem
- Resultado: o produto fica vinculado **exatamente** as categorias informadas

**Testar no tinker:**

```bash
docker compose exec backend php artisan tinker
```

```php
$user = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($user);

// Criar um produto
$product = App\Models\Product::create(['title' => 'Coca-Cola', 'price' => 7.50]);

// Buscar categorias
$bebidas = App\Models\Category::where('name', 'Bebidas')->first();
$combos = App\Models\Category::where('name', 'Combos')->first();

// Vincular
$product->categories()->sync([$bebidas->id, $combos->id]);

// Verificar
$product->categories->pluck('name'); // ["Bebidas", "Combos"]

// Verificar no sentido inverso
$bebidas->products->pluck('title'); // ["Coca-Cola"]

$product->forceDelete();
exit
```

### Arquivos criados/modificados

```
backend/
├── database/migrations/0001_01_02_000008_create_category_product_table.php  (novo)
├── app/Models/Category.php          (modificado — products())
├── app/Models/Product.php           (modificado — categories())
├── app/Http/Controllers/Api/V1/ProductController.php  (modificado — syncCategories)
└── routes/api.php                   (modificado — rota sync)
```

---

## Passo 5.10 - Product Seeder + teste da API

Crie `backend/database/seeders/ProductSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        // Buscar categorias
        $pizzas = Category::where('tenant_id', $tenant->id)->where('name', 'Pizzas')->first();
        $hamburgueres = Category::where('tenant_id', $tenant->id)->where('name', 'Hambúrgueres')->first();
        $bebidas = Category::where('tenant_id', $tenant->id)->where('name', 'Bebidas')->first();
        $sobremesas = Category::where('tenant_id', $tenant->id)->where('name', 'Sobremesas')->first();
        $combos = Category::where('tenant_id', $tenant->id)->where('name', 'Combos')->first();

        $products = [
            [
                'data' => ['title' => 'Pizza Margherita', 'price' => 39.90, 'flag' => 'featured', 'description' => 'Molho de tomate, mussarela e manjericao'],
                'categories' => [$pizzas],
            ],
            [
                'data' => ['title' => 'Pizza Calabresa', 'price' => 42.90, 'flag' => 'active', 'description' => 'Calabresa fatiada com cebola'],
                'categories' => [$pizzas],
            ],
            [
                'data' => ['title' => 'X-Bacon Artesanal', 'price' => 32.90, 'flag' => 'featured', 'description' => 'Hamburguer 180g, bacon crocante, queijo cheddar'],
                'categories' => [$hamburgueres],
            ],
            [
                'data' => ['title' => 'X-Salada', 'price' => 27.90, 'flag' => 'active', 'description' => 'Hamburguer 150g, alface, tomate, queijo'],
                'categories' => [$hamburgueres],
            ],
            [
                'data' => ['title' => 'Coca-Cola 350ml', 'price' => 7.50, 'flag' => 'active', 'description' => 'Lata gelada'],
                'categories' => [$bebidas],
            ],
            [
                'data' => ['title' => 'Suco Natural Laranja', 'price' => 9.90, 'flag' => 'active', 'description' => 'Suco natural 300ml'],
                'categories' => [$bebidas],
            ],
            [
                'data' => ['title' => 'Petit Gateau', 'price' => 19.90, 'flag' => 'active', 'description' => 'Bolo de chocolate com sorvete de creme'],
                'categories' => [$sobremesas],
            ],
            [
                'data' => ['title' => 'Combo X-Bacon', 'price' => 44.90, 'flag' => 'featured', 'description' => 'X-Bacon + Coca-Cola + Batata frita'],
                'categories' => [$hamburgueres, $combos],
            ],
        ];

        foreach ($products as $item) {
            $product = Product::firstOrCreate(
                ['tenant_id' => $tenant->id, 'title' => $item['data']['title']],
                array_merge($item['data'], ['tenant_id' => $tenant->id]),
            );

            // Sync categorias (sem duplicar se rodar novamente)
            $categoryIds = collect($item['categories'])
                ->filter()
                ->pluck('id')
                ->toArray();

            $product->categories()->syncWithoutDetaching($categoryIds);
        }

        $this->command->info("Produtos criados para o tenant '{$tenant->name}'.");
    }
}
```

**Por que `syncWithoutDetaching`?**
Diferente do `sync()` que remove vinculos nao listados, o `syncWithoutDetaching()` so adiciona novos vinculos sem remover os existentes. Isso permite rodar o seeder multiplas vezes sem perder vinculos adicionados manualmente.

Rode os seeders:

```bash
# Se ainda nao rodou o CategorySeeder
docker compose exec backend php artisan db:seed --class=CategorySeeder

# Rodar o ProductSeeder
docker compose exec backend php artisan db:seed --class=ProductSeeder
```

Agora teste a API completa:

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")
```

**Listar produtos:**

```bash
curl -s http://localhost/api/v1/products \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Exibir produto com categorias:**

```bash
curl -s http://localhost/api/v1/products/1 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

Deve retornar o produto com o array `categories` populado (por causa do `$product->load('categories')` no `show()`).

**Criar produto:**

```bash
curl -s -X POST http://localhost/api/v1/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Agua Mineral 500ml", "price": 4.50, "flag": "active"}' \
  | python3 -m json.tool
```

**Sincronizar categorias:**

```bash
# Vincular o produto recem-criado a categoria "Bebidas"
curl -s -X POST http://localhost/api/v1/products/9/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"categories": [3]}' \
  | python3 -m json.tool
```

**Testar validacao de flag invalida:**

```bash
curl -s -X POST http://localhost/api/v1/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Teste", "price": 10, "flag": "invalido"}' \
  | python3 -m json.tool
```

Deve retornar erro 422: `"O status deve ser: active, inactive ou featured."`

> **Dica:** Acesse http://localhost/docs/api para testar todos os endpoints de Categorias e Produtos na documentacao interativa. Os novos controllers ja aparecem na sidebar.

---

## Passo 5.11 - Frontend: tipos TypeScript + servicos do Catalogo

**Primeiro**, atualize a interface `User` no auth store para incluir `tenant_id` e `is_super_admin` (a API `/auth/me` ja retorna esses campos, mas o frontend nao usava ate agora).

Edite `frontend/src/stores/auth-store.ts` — atualize a interface `User`:

```typescript
interface User {
  id: number;
  tenant_id: number | null;
  name: string;
  email: string;
  is_super_admin: boolean;
  created_at: string;
}
```

**Por que precisamos disso?**
No Passo 5.4, adicionamos o middleware `tenant:required` que bloqueia o super-admin nas rotas de catalogo. Agora o frontend precisa saber se o usuario tem tenant para exibir um alerta amigavel em vez de um erro generico.

Agora crie os tipos TypeScript para o catalogo.

Crie `frontend/src/types/catalog.ts`:

```typescript
export interface Category {
  id: number;
  uuid: string;
  name: string;
  url: string;
  description: string | null;
  products?: Product[];
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  uuid: string;
  title: string;
  url: string;
  flag: "active" | "inactive" | "featured";
  image: string | null;
  price: string;
  description: string | null;
  categories?: Category[];
  created_at: string;
  updated_at: string;
}
```

> **Por que `price` e `string` e nao `number`?** A API retorna `"39.90"` (decimal serializado como string pelo Laravel). No frontend, fazemos `Number(price)` apenas quando precisamos calcular.

Crie o servico `frontend/src/services/category-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Category } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getCategories(
  page = 1
): Promise<PaginatedResponse<Category>> {
  return apiClient<PaginatedResponse<Category>>(
    `/v1/categories?page=${page}`
  );
}

export async function getCategory(
  id: number
): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>(`/v1/categories/${id}`);
}

export async function createCategory(data: {
  name: string;
  description?: string;
}): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>("/v1/categories", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateCategory(
  id: number,
  data: { name: string; url?: string; description?: string }
): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>(`/v1/categories/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteCategory(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/categories/${id}`, {
    method: "DELETE",
  });
}
```

Crie o servico `frontend/src/services/product-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Product } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getProducts(
  page = 1
): Promise<PaginatedResponse<Product>> {
  return apiClient<PaginatedResponse<Product>>(
    `/v1/products?page=${page}`
  );
}

export async function getProduct(
  id: number
): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>(`/v1/products/${id}`);
}

export async function createProduct(data: {
  title: string;
  price: number;
  flag?: string;
  image?: string;
  description?: string;
}): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>("/v1/products", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateProduct(
  id: number,
  data: {
    title: string;
    price: number;
    url?: string;
    flag?: string;
    image?: string;
    description?: string;
  }
): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>(`/v1/products/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteProduct(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/products/${id}`, {
    method: "DELETE",
  });
}

export async function syncProductCategories(
  productId: number,
  categoryIds: number[]
): Promise<{ message: string; data: Product }> {
  return apiClient<{ message: string; data: Product }>(
    `/v1/products/${productId}/categories`,
    {
      method: "POST",
      body: JSON.stringify({ categories: categoryIds }),
    }
  );
}
```

### Arquivos criados

```
frontend/src/
├── types/catalog.ts
└── services/
    ├── category-service.ts
    └── product-service.ts
```

---

## Passo 5.12 - Frontend: pagina de Categorias (CRUD)

Instale os componentes shadcn/ui necessarios (se ainda nao instalou na Fase 3):

```bash
docker compose exec frontend npx shadcn@latest add table badge dialog textarea alert
```

**Componente reutilizavel: TenantRequiredAlert**

Antes de criar as paginas, crie um componente que exibe um alerta quando o usuario logado nao tem tenant (ex: super-admin). Esse componente sera reutilizado em todas as paginas tenant-scoped.

Crie `frontend/src/components/tenant-required-alert.tsx`:

```tsx
"use client";

import { useAuthStore } from "@/stores/auth-store";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { ShieldAlert } from "lucide-react";

interface TenantRequiredAlertProps {
  resource: string;
}

export function TenantRequiredAlert({ resource }: TenantRequiredAlertProps) {
  const user = useAuthStore((s) => s.user);

  if (!user || user.tenant_id) return null;

  return (
    <Alert>
      <ShieldAlert className="h-4 w-4" />
      <AlertTitle>Modo visualizacao</AlertTitle>
      <AlertDescription>
        Voce esta logado como super-admin e pode visualizar {resource} de todos
        os tenants. Para criar ou editar, faca login com um usuario vinculado a
        um tenant (ex: <strong>gerente@demo.com</strong>).
      </AlertDescription>
    </Alert>
  );
}
```

**Como funciona:**
- Le o `user` do auth store (Zustand)
- Se `user.tenant_id` existe, retorna `null` (nao renderiza nada)
- Se nao tem tenant, exibe um `Alert` informativo (nao bloqueante)
- Super-admin pode **visualizar** dados de todos os tenants, mas nao criar/editar (requer tenant)
- A prop `resource` permite personalizar: `"categorias"`, `"produtos"`, `"mesas"`, etc.

Crie o diretorio para componentes de categorias:

```bash
mkdir -p frontend/src/components/categories
```

Crie o dialog de formulario `frontend/src/components/categories/category-form-dialog.tsx`:

```tsx
"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { createCategory, updateCategory } from "@/services/category-service";
import type { Category } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api";

const categorySchema = z.object({
  name: z.string().min(1, "O nome e obrigatorio"),
  description: z.string().optional(),
});

type CategoryFormData = z.infer<typeof categorySchema>;

interface CategoryFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
  category?: Category;
}

export function CategoryFormDialog({
  open,
  onOpenChange,
  onSaved,
  category,
}: CategoryFormDialogProps) {
  const isEditing = !!category;

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<CategoryFormData>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: category?.name || "",
      description: category?.description || "",
    },
  });

  useEffect(() => {
    if (open) {
      reset({
        name: category?.name || "",
        description: category?.description || "",
      });
    }
  }, [open, category, reset]);

  const onSubmit = async (data: CategoryFormData) => {
    try {
      if (isEditing) {
        await updateCategory(category.id, data);
      } else {
        await createCategory(data);
      }
      onSaved();
    } catch (error) {
      if (error instanceof ApiError) {
        setError("root", { message: error.message });
      }
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {isEditing ? "Editar Categoria" : "Nova Categoria"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {errors.root && (
            <p className="text-sm text-destructive">{errors.root.message}</p>
          )}

          <div className="space-y-2">
            <Label htmlFor="name">Nome</Label>
            <Input id="name" {...register("name")} />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descricao</Label>
            <Textarea id="description" {...register("description")} />
          </div>

          <div className="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Salvando..." : "Salvar"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
```

Crie o dialog de exclusao `frontend/src/components/categories/delete-category-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { deleteCategory } from "@/services/category-service";
import type { Category } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteCategoryDialogProps {
  category: Category | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteCategoryDialog({
  category,
  onOpenChange,
  onDeleted,
}: DeleteCategoryDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!category) return;

    setLoading(true);
    try {
      await deleteCategory(category.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover categoria:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!category} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Categoria</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover a categoria &quot;{category?.name}
            &quot;? Esta acao nao pode ser desfeita.
          </DialogDescription>
        </DialogHeader>

        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            variant="destructive"
            onClick={handleDelete}
            disabled={loading}
          >
            {loading ? "Removendo..." : "Remover"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
```

Crie a pagina `frontend/src/app/(admin)/categories/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getCategories } from "@/services/category-service";
import type { Category } from "@/types/catalog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { CategoryFormDialog } from "@/components/categories/category-form-dialog";
import { DeleteCategoryDialog } from "@/components/categories/delete-category-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

export default function CategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editCategory, setEditCategory] = useState<Category | null>(null);
  const [deleteState, setDeleteState] = useState<Category | null>(null);

  const fetchCategories = async () => {
    try {
      const response = await getCategories();
      setCategories(response.data);
    } catch (error) {
      console.error("Erro ao carregar categorias:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditCategory(null);
    fetchCategories();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchCategories();
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="categorias" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Categorias</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Categoria
        </Button>
      </div>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>Slug</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {categories.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-center text-muted-foreground">
                  Nenhuma categoria cadastrada.
                </TableCell>
              </TableRow>
            ) : (
              categories.map((cat) => (
                <TableRow key={cat.id}>
                  <TableCell className="font-medium">{cat.name}</TableCell>
                  <TableCell className="text-muted-foreground">{cat.url}</TableCell>
                  <TableCell>{cat.description || "—"}</TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setEditCategory(cat)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setDeleteState(cat)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      )}

      <CategoryFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editCategory && (
        <CategoryFormDialog
          open={!!editCategory}
          onOpenChange={() => setEditCategory(null)}
          onSaved={handleSaved}
          category={editCategory}
        />
      )}

      <DeleteCategoryDialog
        category={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />
    </div>
  );
}
```

**Sidebar condicional por perfil de usuario:**

Ate agora a sidebar mostrava todos os itens para todos os usuarios. Agora vamos separar em grupos:
- **Plataforma** (super-admin): Planos, Tenants, Perfis, Papeis
- **Operacao** (usuario com tenant **ou** super-admin): Categorias, Produtos, Pedidos, Mesas, etc.
- **Geral** (todos): Dashboard, Configuracoes

> **Super-admin ve tudo:** O admin tem acesso a todos os grupos da sidebar. Na area de Operacao, ele visualiza dados de todos os tenants (o `TenantScope` nao filtra quando o usuario nao tem `tenant_id`). Para criar/editar, deve usar um usuario vinculado a um tenant.

Reescreva `frontend/src/components/app-sidebar.tsx`:

```tsx
"use client";

import {
  LayoutDashboard,
  ShoppingBag,
  Users,
  QrCode,
  Star,
  Settings,
  CreditCard,
  Shield,
  UserCog,
  FolderTree,
  ShoppingBasket,
  Building2,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useAuthStore } from "@/stores/auth-store";
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarSeparator,
} from "@/components/ui/sidebar";

const adminItems = [
  { title: "Planos", url: "/plans", icon: CreditCard },
  { title: "Tenants", url: "/tenants", icon: Building2 },
  { title: "Perfis", url: "/profiles", icon: Shield },
  { title: "Papeis", url: "/roles", icon: UserCog },
];

const tenantItems = [
  { title: "Categorias", url: "/categories", icon: FolderTree },
  { title: "Produtos", url: "/products", icon: ShoppingBasket },
  { title: "Pedidos", url: "/orders", icon: ShoppingBag },
  { title: "Mesas", url: "/tables", icon: QrCode },
  { title: "Clientes", url: "/customers", icon: Users },
  { title: "Avaliacoes", url: "/reviews", icon: Star },
];

export function AppSidebar() {
  const pathname = usePathname();
  const user = useAuthStore((s) => s.user);

  const isSuperAdmin = user?.is_super_admin ?? false;
  const hasTenant = !!user?.tenant_id;
  const showTenantItems = hasTenant || isSuperAdmin;

  return (
    <Sidebar>
      <SidebarHeader className="border-b px-6 py-4">
        <h2 className="text-lg font-bold">Orderly</h2>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>Geral</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <SidebarMenuButton asChild isActive={pathname === "/dashboard"}>
                  <Link href="/dashboard">
                    <LayoutDashboard />
                    <span>Dashboard</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        {isSuperAdmin && (
          <>
            <SidebarSeparator />
            <SidebarGroup>
              <SidebarGroupLabel>Plataforma</SidebarGroupLabel>
              <SidebarGroupContent>
                <SidebarMenu>
                  {adminItems.map((item) => (
                    <SidebarMenuItem key={item.title}>
                      <SidebarMenuButton asChild isActive={pathname === item.url}>
                        <Link href={item.url}>
                          <item.icon />
                          <span>{item.title}</span>
                        </Link>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  ))}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          </>
        )}

        {showTenantItems && (
          <>
            <SidebarSeparator />
            <SidebarGroup>
              <SidebarGroupLabel>Operacao</SidebarGroupLabel>
              <SidebarGroupContent>
                <SidebarMenu>
                  {tenantItems.map((item) => (
                    <SidebarMenuItem key={item.title}>
                      <SidebarMenuButton asChild isActive={pathname === item.url}>
                        <Link href={item.url}>
                          <item.icon />
                          <span>{item.title}</span>
                        </Link>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  ))}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          </>
        )}

        <SidebarSeparator />
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <SidebarMenuButton asChild isActive={pathname === "/settings"}>
                  <Link href="/settings">
                    <Settings />
                    <span>Configuracoes</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
    </Sidebar>
  );
}
```

**Como funciona:**
- `useAuthStore` le `is_super_admin` e `tenant_id` do usuario logado
- `isSuperAdmin` → mostra grupo "Plataforma" (Planos, Tenants, Perfis, Papeis)
- `hasTenant` → mostra grupo "Operacao" (Categorias, Produtos, Pedidos, etc.)
- Dashboard e Configuracoes ficam visiveis para todos
- Os grupos sao separados por `SidebarSeparator` para clareza visual

**Resultado por usuario:**

| Usuario | Sidebar |
|---|---|
| `admin@orderly.com` (super-admin, sem tenant) | Dashboard, **Plataforma** (Planos, Tenants, Perfis, Papeis), Configuracoes |
| `gerente@demo.com` (tenant) | Dashboard, **Operacao** (Categorias, Produtos, Pedidos, Mesas, Clientes, Avaliacoes), Configuracoes |

**Fix: sidebar vazia apos F5 (page refresh)**

O Zustand so persiste o `token` (via `partialize`), nao o `user`. Ao dar F5, o `user` e `null` ate que `fetchUser()` seja chamado — e a sidebar nao renderiza os grupos condicionais.

Para corrigir, atualize `frontend/src/app/(admin)/layout.tsx` para chamar `fetchUser()` quando houver token mas nao houver user:

```tsx
"use client";

import { useEffect } from "react";
import { SidebarProvider, SidebarInset } from "@/components/ui/sidebar";
import { AppSidebar } from "@/components/app-sidebar";
import { AppHeader } from "@/components/app-header";
import { useAuthStore } from "@/stores/auth-store";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const token = useAuthStore((s) => s.token);
  const user = useAuthStore((s) => s.user);
  const fetchUser = useAuthStore((s) => s.fetchUser);

  useEffect(() => {
    if (token && !user) {
      fetchUser();
    }
  }, [token, user, fetchUser]);

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <AppHeader />
        <main className="flex-1 p-6">{children}</main>
      </SidebarInset>
    </SidebarProvider>
  );
}
```

**Como funciona:**
- Na hidratacao, o Zustand restaura apenas o `token` do `localStorage`
- O `useEffect` detecta que tem `token` mas nao tem `user` e chama `fetchUser()`
- `fetchUser()` faz `GET /api/v1/auth/me` e popula o `user` no store
- A sidebar re-renderiza com `is_super_admin` e `tenant_id` corretos

### Arquivos criados

```
frontend/src/
├── stores/auth-store.ts                  (modificado — tenant_id + is_super_admin)
├── components/tenant-required-alert.tsx  (novo — alerta reutilizavel)
├── app/(admin)/layout.tsx               (modificado — fetchUser on hydration)
├── app/(admin)/categories/page.tsx
├── components/categories/
│   ├── category-form-dialog.tsx
│   └── delete-category-dialog.tsx
└── components/app-sidebar.tsx  (modificado — link Categorias)
```

---

## Passo 5.13 - Frontend: pagina de Produtos (CRUD)

Crie o diretorio para componentes de produtos:

```bash
mkdir -p frontend/src/components/products
```

Crie o dialog de formulario `frontend/src/components/products/product-form-dialog.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { createProduct, updateProduct } from "@/services/product-service";
import { getCategories } from "@/services/category-service";
import type { Product, Category } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api";

const productSchema = z.object({
  title: z.string().min(1, "O titulo e obrigatorio"),
  price: z.coerce.number().min(0, "O preco nao pode ser negativo"),
  flag: z.enum(["active", "inactive", "featured"]).optional(),
  description: z.string().optional(),
});

type ProductFormData = z.infer<typeof productSchema>;

interface ProductFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
  product?: Product;
}

export function ProductFormDialog({
  open,
  onOpenChange,
  onSaved,
  product,
}: ProductFormDialogProps) {
  const isEditing = !!product;

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<ProductFormData>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      title: product?.title || "",
      price: product ? Number(product.price) : 0,
      flag: product?.flag || "active",
      description: product?.description || "",
    },
  });

  useEffect(() => {
    if (open) {
      reset({
        title: product?.title || "",
        price: product ? Number(product.price) : 0,
        flag: product?.flag || "active",
        description: product?.description || "",
      });
    }
  }, [open, product, reset]);

  const onSubmit = async (data: ProductFormData) => {
    try {
      if (isEditing) {
        await updateProduct(product.id, data);
      } else {
        await createProduct(data);
      }
      onSaved();
    } catch (error) {
      if (error instanceof ApiError) {
        setError("root", { message: error.message });
      }
    }
  };

  const flagOptions = [
    { value: "active", label: "Ativo" },
    { value: "inactive", label: "Inativo" },
    { value: "featured", label: "Destaque" },
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {isEditing ? "Editar Produto" : "Novo Produto"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {errors.root && (
            <p className="text-sm text-destructive">{errors.root.message}</p>
          )}

          <div className="space-y-2">
            <Label htmlFor="title">Titulo</Label>
            <Input id="title" {...register("title")} />
            {errors.title && (
              <p className="text-sm text-destructive">{errors.title.message}</p>
            )}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="price">Preco (R$)</Label>
              <Input
                id="price"
                type="number"
                step="0.01"
                min="0"
                {...register("price")}
              />
              {errors.price && (
                <p className="text-sm text-destructive">
                  {errors.price.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="flag">Status</Label>
              <select
                id="flag"
                {...register("flag")}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
              >
                {flagOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descricao</Label>
            <Textarea id="description" {...register("description")} />
          </div>

          <div className="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Salvando..." : "Salvar"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
```

Crie o dialog de exclusao `frontend/src/components/products/delete-product-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { deleteProduct } from "@/services/product-service";
import type { Product } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteProductDialogProps {
  product: Product | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteProductDialog({
  product,
  onOpenChange,
  onDeleted,
}: DeleteProductDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!product) return;

    setLoading(true);
    try {
      await deleteProduct(product.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover produto:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!product} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Produto</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover o produto &quot;{product?.title}
            &quot;? Esta acao nao pode ser desfeita.
          </DialogDescription>
        </DialogHeader>

        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            variant="destructive"
            onClick={handleDelete}
            disabled={loading}
          >
            {loading ? "Removendo..." : "Remover"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
```

Crie a pagina `frontend/src/app/(admin)/products/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getProducts } from "@/services/product-service";
import type { Product } from "@/types/catalog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { ProductFormDialog } from "@/components/products/product-form-dialog";
import { DeleteProductDialog } from "@/components/products/delete-product-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

const flagLabels: Record<string, string> = {
  active: "Ativo",
  inactive: "Inativo",
  featured: "Destaque",
};

const flagVariants: Record<string, "default" | "secondary" | "destructive"> = {
  active: "default",
  inactive: "destructive",
  featured: "secondary",
};

export default function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editProduct, setEditProduct] = useState<Product | null>(null);
  const [deleteState, setDeleteState] = useState<Product | null>(null);

  const fetchProducts = async () => {
    try {
      const response = await getProducts();
      setProducts(response.data);
    } catch (error) {
      console.error("Erro ao carregar produtos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditProduct(null);
    fetchProducts();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchProducts();
  };

  const formatPrice = (price: string) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(Number(price));
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="produtos" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Produtos</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Produto
        </Button>
      </div>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Titulo</TableHead>
              <TableHead>Preco</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {products.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground">
                  Nenhum produto cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              products.map((product) => (
                <TableRow key={product.id}>
                  <TableCell className="font-medium">{product.title}</TableCell>
                  <TableCell>{formatPrice(product.price)}</TableCell>
                  <TableCell>
                    <Badge variant={flagVariants[product.flag] || "default"}>
                      {flagLabels[product.flag] || product.flag}
                    </Badge>
                  </TableCell>
                  <TableCell className="max-w-[200px] truncate">
                    {product.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setEditProduct(product)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setDeleteState(product)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      )}

      <ProductFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editProduct && (
        <ProductFormDialog
          open={!!editProduct}
          onOpenChange={() => setEditProduct(null)}
          onSaved={handleSaved}
          product={editProduct}
        />
      )}

      <DeleteProductDialog
        product={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />
    </div>
  );
}
```

> **Nota:** A sidebar ja foi atualizada no Passo 5.12 com os itens de Categorias e Produtos no grupo "Operacao".

### Arquivos criados

```
frontend/src/
├── app/(admin)/products/page.tsx
├── components/products/
│   ├── product-form-dialog.tsx
│   └── delete-product-dialog.tsx
└── components/app-sidebar.tsx  (modificado — link Produtos)
```

---

## Passo 5.14 - Verificacao end-to-end da Fase 5

**Checklist de verificacao:**

**Backend — Categorias:**
```bash
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

# Listar (deve retornar 5 categorias do seeder)
curl -s http://localhost/api/v1/categories \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool | head -5

# Criar
curl -s -X POST http://localhost/api/v1/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Teste E2E"}' | python3 -m json.tool

# Atualizar (slug deve mudar automaticamente)
curl -s -X PUT http://localhost/api/v1/categories/6 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Teste E2E Atualizado"}' | python3 -m json.tool

# Deletar
curl -s -X DELETE http://localhost/api/v1/categories/6 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Backend — Produtos:**
```bash
# Listar (deve retornar 8 produtos do seeder)
curl -s http://localhost/api/v1/products \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool | head -5

# Criar
curl -s -X POST http://localhost/api/v1/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Produto E2E","price":15.50,"flag":"active"}' | python3 -m json.tool

# Exibir com categorias
curl -s http://localhost/api/v1/products/1 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Sync categorias
curl -s -X POST http://localhost/api/v1/products/1/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"categories":[1,5]}' | python3 -m json.tool

# Deletar produto de teste
curl -s -X DELETE http://localhost/api/v1/products/9 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Backend — Permissoes:**
```bash
# Login como admin (super-admin) — deve funcionar
curl -s http://localhost/api/v1/categories \
  -H "Authorization: Bearer $(curl -s -X POST http://localhost/api/v1/auth/login \
    -H 'Content-Type: application/json' \
    -d '{"email":"admin@orderly.com","password":"password"}' \
    | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")" \
  | python3 -m json.tool | head -3
```

**Backend — Documentacao:**
```bash
# Verificar que os novos endpoints aparecem na spec OpenAPI
curl -s http://localhost/docs/api.json | python3 -c "
import sys, json
spec = json.load(sys.stdin)
paths = [p for p in spec['paths'] if 'categories' in p or 'products' in p]
for p in sorted(paths):
    print(p)
"
```

Deve listar:
```
/api/v1/categories
/api/v1/categories/{category}
/api/v1/products
/api/v1/products/{product}
/api/v1/products/{product}/categories
```

**Frontend:**
1. Acesse http://localhost no navegador
2. Faca login como `gerente@demo.com` / `password`
3. Na sidebar, clique em **Categorias** — deve listar as 5 categorias
4. Clique em **Nova Categoria** — preencha e salve
5. Edite uma categoria — o slug deve atualizar
6. Delete uma categoria
7. Na sidebar, clique em **Produtos** — deve listar os 8 produtos com badges de status
8. Crie, edite e delete um produto
9. Acesse http://localhost/docs/api — os endpoints de Categorias e Produtos devem aparecer na sidebar

**Multi-tenancy:**
```bash
# Verificar no tinker que categorias sao isoladas por tenant
docker compose exec backend php artisan tinker
```

```php
// Gerente so ve categorias do seu tenant
$user = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($user);
echo App\Models\Category::count(); // 5 (so do Restaurante Demo)

// Sem autenticacao, TenantScope nao aplica filtro
auth('api')->forgetUser();
echo App\Models\Category::withoutGlobalScopes()->count(); // todas
exit
```

### Resumo da Fase 5

**Arquivos criados:**

```
backend/
├── database/migrations/
│   ├── 0001_01_02_000006_create_categories_table.php
│   ├── 0001_01_02_000007_create_products_table.php
│   └── 0001_01_02_000008_create_category_product_table.php
├── database/seeders/
│   ├── CategorySeeder.php
│   └── ProductSeeder.php
├── database/factories/
│   ├── CategoryFactory.php
│   └── ProductFactory.php
├── app/Models/
│   ├── Category.php
│   └── Product.php
├── app/Observers/
│   ├── CategoryObserver.php
│   └── ProductObserver.php
├── app/Repositories/
│   ├── Contracts/
│   │   ├── CategoryRepositoryInterface.php
│   │   └── ProductRepositoryInterface.php
│   └── Eloquent/
│       ├── CategoryRepository.php
│       └── ProductRepository.php
├── app/DTOs/
│   ├── Category/
│   │   ├── CreateCategoryDTO.php
│   │   └── UpdateCategoryDTO.php
│   └── Product/
│       ├── CreateProductDTO.php
│       └── UpdateProductDTO.php
├── app/Actions/
│   ├── Category/
│   │   ├── ListCategoriesAction.php
│   │   ├── ShowCategoryAction.php
│   │   ├── CreateCategoryAction.php
│   │   ├── UpdateCategoryAction.php
│   │   └── DeleteCategoryAction.php
│   └── Product/
│       ├── ListProductsAction.php
│       ├── ShowProductAction.php
│       ├── CreateProductAction.php
│       ├── UpdateProductAction.php
│       └── DeleteProductAction.php
├── app/Http/Controllers/Api/V1/
│   ├── CategoryController.php
│   └── ProductController.php
├── app/Http/Requests/
│   ├── Category/
│   │   ├── StoreCategoryRequest.php
│   │   └── UpdateCategoryRequest.php
│   └── Product/
│       ├── StoreProductRequest.php
│       └── UpdateProductRequest.php
├── app/Http/Resources/
│   ├── CategoryResource.php
│   └── ProductResource.php
├── app/Http/Middleware/IdentifyTenant.php        (modificado — modo required)
├── app/Providers/RepositoryServiceProvider.php  (modificado)
└── routes/api.php  (modificado — tenant:required)

frontend/src/
├── stores/auth-store.ts                  (modificado — tenant_id + is_super_admin)
├── types/catalog.ts
├── services/
│   ├── category-service.ts
│   └── product-service.ts
├── app/(admin)/
│   ├── categories/page.tsx
│   └── products/page.tsx
├── components/
│   ├── tenant-required-alert.tsx          (novo — alerta reutilizavel)
│   ├── categories/
│   │   ├── category-form-dialog.tsx
│   │   └── delete-category-dialog.tsx
│   ├── products/
│   │   ├── product-form-dialog.tsx
│   │   └── delete-product-dialog.tsx
│   └── app-sidebar.tsx  (modificado)
```

**Conceitos aprendidos:**
- **Tabela pivot** — relacionamento N:N (muitos-para-muitos) entre categorias e produtos
- **`sync()` vs `syncWithoutDetaching()`** — substituir todos os vinculos vs adicionar sem remover
- **`belongsToMany()`** — declaracao de relacao N:N no Eloquent
- **Chave primaria composta** — `primary(['category_id', 'product_id'])` em vez de `id()` auto-increment
- **Slug unico por tenant** — `unique(['tenant_id', 'url'])` permite duplicatas entre tenants
- **UUID como identificador publico** — nunca exponha IDs sequenciais em APIs
- **`flag` como string** — mais flexivel que enum nativo, validado no FormRequest
- **`BelongsToTenant` reutilizado** — mesma infraestrutura de multi-tenancy da Fase 3
- **Isolamento automatico** — TenantScope filtra categorias e produtos sem codigo extra
- **Middleware parametrizado** — `tenant:required` vs `tenant` (opcional) para controlar acesso por tipo de rota
- **Alerta frontend reutilizavel** — componente `TenantRequiredAlert` exibe aviso amigavel para super-admin sem tenant

**Proximo:** Fase 6 - Mesas com QR Code

---


---

[Voltar ao README](../README.md)
