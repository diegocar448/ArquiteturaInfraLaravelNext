# Fase 3 - Multi-tenancy + Planos de Assinatura

> **Objetivo:** Criar a base SaaS do sistema — planos de assinatura, tenants (empresas/restaurantes) e a infraestrutura de multi-tenancy com Global Scopes.
> Ao final desta fase, teremos CRUD completo de planos e tenants (backend + frontend), e a isolacao automatica de dados por tenant.

**O que voce vai aprender:**
- Multi-tenancy single-database com `tenant_id` + Global Scopes
- Observer Pattern para auto-geracao de slugs e UUIDs
- Repository Pattern completo (interface + implementacao)
- CRUD completo seguindo Clean Architecture (Action → DTO → Repository)
- Nested Resources (detalhes dentro de planos)
- Middleware customizado para identificacao de tenant
- Frontend com tabelas, formularios e modais (shadcn/ui)
- React Hook Form + Zod para validacao de formularios

**Pre-requisitos:**
- Fase 2 concluida (login funcional, dashboard com sidebar)
- Containers rodando (`docker compose up -d`)

---

## Passo 3.1 - Migration: tabela plans

A tabela `plans` armazena os planos de assinatura da plataforma (ex: Basico, Profissional, Enterprise). Cada tenant (restaurante) assina um plano.

**Por que `softDeletes`?**
Planos nao devem ser apagados permanentemente enquanto houver tenants vinculados. O `softDeletes` marca como deletado sem remover do banco.

Crie `backend/database/migrations/0001_01_02_000001_create_plans_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->unique(); // slug do plano
            $table->decimal('price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
```

**Conceitos importantes:**
- `decimal('price', 10, 2)` — 10 digitos no total, 2 casas decimais. Ideal para valores monetarios (evita problemas de arredondamento do `float`).
- `url` com `unique()` — sera o slug do plano na URL (ex: `basico`, `profissional`).

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Deve exibir:
```
INFO  Running migrations.

  0001_01_02_000001_create_plans_table ........... DONE
```

---

## Passo 3.2 - Model Plan + Observer + Factory

Agora criamos o Model Eloquent, um Observer para gerar slugs automaticamente, e uma Factory para testes.

**Observer Pattern:**
O Observer "escuta" eventos do ciclo de vida do model (creating, updating, deleting). Usamos para gerar o slug (`url`) automaticamente a partir do `name`, sem precisar fazer isso manualmente em cada lugar que cria um plano.

Crie `backend/app/Models/Plan.php`:

```php
<?php

namespace App\Models;

use App\Observers\PlanObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PlanObserver::class)]
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'url',
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

**Nota:** As relacoes `details()` e `tenants()` serao adicionadas nos Passos 3.6 e 3.7 respectivamente, quando criarmos esses models.

Crie o diretorio e o Observer `backend/app/Observers/PlanObserver.php`:

```bash
# Criar diretorio (se nao existir)
mkdir -p backend/app/Observers
```

```php
<?php

namespace App\Observers;

use App\Models\Plan;
use Illuminate\Support\Str;

class PlanObserver
{
    public function creating(Plan $plan): void
    {
        if (empty($plan->url)) {
            $plan->url = Str::slug($plan->name);
        }
    }

    public function updating(Plan $plan): void
    {
        if ($plan->isDirty('name') && !$plan->isDirty('url')) {
            $plan->url = Str::slug($plan->name);
        }
    }
}
```

**Como funciona o Observer:**
- `creating` — dispara **antes** de inserir no banco. Se `url` estiver vazio, gera o slug a partir do `name`.
- `updating` — dispara **antes** de atualizar. Se o `name` mudou mas o `url` nao foi alterado manualmente, regenera o slug.
- `#[ObservedBy]` — attribute do PHP 8.1+ que registra o Observer sem precisar de `AppServiceProvider`. Laravel 13 suporta nativamente.
- `isDirty('name')` — verifica se o campo `name` foi modificado no update.

Crie a Factory `backend/database/factories/PlanFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'url' => Str::slug($name),
            'price' => fake()->randomFloat(2, 0, 499.99),
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
$plan = App\Models\Plan::create(['name' => 'Teste Observer', 'price' => 29.90]);
echo $plan->url; // "teste-observer" (gerado automaticamente!)
$plan->forceDelete(); // limpar
exit
```

---

## Passo 3.3 - Plan Repository + DTO + Actions (CRUD)

Seguindo o padrao de Clean Architecture da Fase 2, criamos a camada completa para o CRUD de planos.

**Relembrando a arquitetura:**
```
Controller → Action → Repository → Model (banco)
     ↑          ↑          ↑
  Request      DTO     Interface
```

Crie `backend/app/Repositories/Contracts/PlanRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlanRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Plan;

    public function findByUrl(string $url): ?Plan;

    public function create(array $data): Plan;

    public function update(int $id, array $data): ?Plan;

    public function delete(int $id): bool;
}
```

Crie `backend/app/Repositories/Eloquent/PlanRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PlanRepository implements PlanRepositoryInterface
{
    public function __construct(
        private readonly Plan $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Plan
    {
        return $this->model->find($id);
    }

    public function findByUrl(string $url): ?Plan
    {
        return $this->model->where('url', $url)->first();
    }

    public function create(array $data): Plan
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Plan
    {
        $plan = $this->findById($id);

        if (!$plan) {
            return null;
        }

        $plan->update($data);

        return $plan->fresh();
    }

    public function delete(int $id): bool
    {
        $plan = $this->findById($id);

        if (!$plan) {
            return false;
        }

        return (bool) $plan->delete();
    }
}
```

**Por que `latest()` no paginate?**
Ordena por `created_at DESC` — os planos mais recentes aparecem primeiro. Isso e util para o admin ver rapidamente o que foi criado.

**Por que `fresh()` no update?**
Apos o `update()`, o model em memoria pode estar desatualizado (ex: o Observer pode ter modificado o `url`). O `fresh()` recarrega do banco.

Registre o binding no `backend/app/Providers/RepositoryServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Eloquent\PlanRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
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
mkdir -p backend/app/DTOs/Plan
mkdir -p backend/app/Actions/Plan
```

Crie `backend/app/DTOs/Plan/CreatePlanDTO.php`:

```php
<?php

namespace App\DTOs\Plan;

use App\Http\Requests\Plan\StorePlanRequest;

final readonly class CreatePlanDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public ?string $description,
    ) {}

    public static function fromRequest(StorePlanRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
        ];
    }
}
```

Crie `backend/app/DTOs/Plan/UpdatePlanDTO.php`:

```php
<?php

namespace App\DTOs\Plan;

use App\Http\Requests\Plan\UpdatePlanRequest;

final readonly class UpdatePlanDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public ?string $url,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdatePlanRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            url: $request->validated('url'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'price' => $this->price,
            'url' => $this->url,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
```

**Por que `array_filter` no UpdatePlanDTO?**
No update, campos `null` significam "nao alterar". O `array_filter` remove esses campos para que o Eloquent nao sobrescreva com `null`.

Agora as Actions. Crie `backend/app/Actions/Plan/ListPlansAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPlansAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

Crie `backend/app/Actions/Plan/ShowPlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class ShowPlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Plan
    {
        return $this->repository->findById($id);
    }
}
```

Crie `backend/app/Actions/Plan/CreatePlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\DTOs\Plan\CreatePlanDTO;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class CreatePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(CreatePlanDTO $dto): Plan
    {
        return $this->repository->create($dto->toArray());
    }
}
```

Crie `backend/app/Actions/Plan/UpdatePlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\DTOs\Plan\UpdatePlanDTO;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class UpdatePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdatePlanDTO $dto): ?Plan
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

Crie `backend/app/Actions/Plan/DeletePlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\PlanRepositoryInterface;

final class DeletePlanAction
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

**Resumo da camada criada:**

```
app/
├── Actions/Plan/
│   ├── ListPlansAction.php    (listar paginado)
│   ├── ShowPlanAction.php     (buscar por ID)
│   ├── CreatePlanAction.php   (criar)
│   ├── UpdatePlanAction.php   (atualizar)
│   └── DeletePlanAction.php   (deletar - soft delete)
├── DTOs/Plan/
│   ├── CreatePlanDTO.php      (dados para criacao)
│   └── UpdatePlanDTO.php      (dados para atualizacao)
└── Repositories/
    ├── Contracts/PlanRepositoryInterface.php
    └── Eloquent/PlanRepository.php
```

---

## Passo 3.4 - Plan Controller + Routes + FormRequests + Resource

Agora expomos o CRUD via API REST.

Crie o diretorio e os FormRequests:

```bash
mkdir -p backend/app/Http/Requests/Plan
```

Crie `backend/app/Http/Requests/Plan/StorePlanRequest.php`:

```php
<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ACL vira na Fase 4
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano e obrigatorio.',
            'name.max' => 'O nome deve ter no maximo 255 caracteres.',
            'price.required' => 'O preco e obrigatorio.',
            'price.numeric' => 'O preco deve ser um numero.',
            'price.min' => 'O preco nao pode ser negativo.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Plan/UpdatePlanRequest.php`:

```php
<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'url' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('plans')->ignore($this->route('plan')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano e obrigatorio.',
            'price.required' => 'O preco e obrigatorio.',
            'price.numeric' => 'O preco deve ser um numero.',
            'price.min' => 'O preco nao pode ser negativo.',
            'url.unique' => 'Ja existe um plano com esta URL.',
        ];
    }
}
```

**Conceito importante — `Rule::unique()->ignore()`:**
No update, o campo `url` deve ser unico **exceto** pelo proprio registro sendo editado. Sem o `ignore()`, o update falharia dizendo que a URL ja existe (por si mesmo).

Crie `backend/app/Http/Resources/PlanResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'price' => $this->price,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

**Nota:** No Passo 3.6, vamos adicionar o campo `details` aqui quando criarmos o `DetailPlanResource`.

Crie `backend/app/Http/Controllers/Api/V1/PlanController.php`:

```php
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

class PlanController extends Controller
{
    public function index(ListPlansAction $action): AnonymousResourceCollection
    {
        $plans = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return PlanResource::collection($plans);
    }

    public function store(StorePlanRequest $request, CreatePlanAction $action): JsonResponse
    {
        $plan = $action->execute(CreatePlanDTO::fromRequest($request));

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $plan, ShowPlanAction $action): JsonResponse
    {
        $plan = $action->execute($plan);

        if (!$plan) {
            return response()->json(['message' => 'Plano nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new PlanResource($plan),
        ]);
    }

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
```

**Controller magro (thin controller):**
O controller nao tem logica de negocio. Ele:
1. Recebe o request validado (FormRequest)
2. Converte para DTO
3. Delega para a Action
4. Retorna o Resource formatado

Adicione as rotas em `backend/routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\PlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD
        Route::apiResource('plans', PlanController::class);
    });
});
```

**O que e `apiResource`?**
O `Route::apiResource` gera 5 rotas automaticamente:

| Verbo | URI | Acao | Nome |
|---|---|---|---|
| GET | /api/v1/plans | index | plans.index |
| POST | /api/v1/plans | store | plans.store |
| GET | /api/v1/plans/{plan} | show | plans.show |
| PUT/PATCH | /api/v1/plans/{plan} | update | plans.update |
| DELETE | /api/v1/plans/{plan} | destroy | plans.destroy |

E a versao API do `Route::resource` — sem as rotas `create` e `edit` (que sao para formularios Blade, inuteis numa API).

---

## Passo 3.5 - Plan Seeder + teste da API

Crie `backend/database/seeders/PlanSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basico',
                'price' => 0,
                'description' => 'Plano gratuito para comecar. Ideal para testar a plataforma.',
            ],
            [
                'name' => 'Profissional',
                'price' => 99.90,
                'description' => 'Para restaurantes em crescimento. Recursos avancados.',
            ],
            [
                'name' => 'Enterprise',
                'price' => 299.90,
                'description' => 'Para grandes operacoes. Recursos ilimitados e suporte prioritario.',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}
```

**Por que `firstOrCreate` em vez de `create`?**
Se voce rodar o seeder mais de uma vez, o `firstOrCreate` nao duplica registros. Ele busca pelo `name` — se ja existe, pula. Se nao, cria.

Registre no `backend/database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
```

**Importante:** `PlanSeeder` vem ANTES de `AdminUserSeeder`. No futuro, o admin podera ter um tenant que depende de um plano.

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=PlanSeeder
```

**Testar a API com curl:**

Primeiro, pegue o token JWT:

```bash
curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4
```

Copie o token retornado e use nas chamadas abaixo (substitua `SEU_TOKEN`):

```bash
# Listar planos
curl -s -X GET http://127.0.0.1/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool

# Criar um plano
curl -s -X POST http://127.0.0.1/api/v1/plans \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"name": "Startup", "price": 49.90, "description": "Para quem esta comecando"}' \
  | python3 -m json.tool

# Ver um plano (troque 1 pelo ID retornado)
curl -s -X GET http://127.0.0.1/api/v1/plans/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool

# Atualizar um plano
curl -s -X PUT http://127.0.0.1/api/v1/plans/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"name": "Basico Gratis", "price": 0}' \
  | python3 -m json.tool

# Deletar um plano (soft delete)
curl -s -X DELETE http://127.0.0.1/api/v1/plans/4 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool
```

**Resultado esperado do GET /plans:**
```json
{
    "data": [
        {
            "id": 3,
            "name": "Enterprise",
            "url": "enterprise",
            "price": "299.90",
            "description": "Para grandes operacoes...",
            "created_at": "2026-03-06T...",
            "updated_at": "2026-03-06T..."
        },
        ...
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 3 }
}
```

---

## Passo 3.6 - DetailPlan: migration + Model + CRUD completo

Os "detalhes" de um plano sao as features/limites incluidos (ex: "Ate 10 produtos", "Suporte por email", "Relatorios avancados"). E um CRUD aninhado dentro de Plans.

Crie `backend/database/migrations/0001_01_02_000002_create_detail_plans_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_plans');
    }
};
```

**`cascadeOnDelete`:** Quando um plano for deletado permanentemente, seus detalhes tambem sao removidos automaticamente pelo banco.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Antes de criar o model `DetailPlan`, adicione a relacao `details()` no `backend/app/Models/Plan.php`. Adicione o import e o metodo:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

// Dentro da classe Plan, adicione:
public function details(): HasMany
{
    return $this->hasMany(DetailPlan::class);
}
```

Crie `backend/app/Models/DetailPlan.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'name',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
```

Crie `backend/app/Http/Resources/DetailPlanResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'name' => $this->name,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

Agora atualize o `backend/app/Http/Resources/PlanResource.php` para incluir os detalhes quando carregados:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'price' => $this->price,
            'description' => $this->description,
            'details' => DetailPlanResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

**O que e `whenLoaded`?**
Se a relacao `details` foi carregada (via `->load('details')` ou `->with('details')`), inclui os detalhes na resposta. Senao, omite. Isso evita N+1 queries — os detalhes so aparecem quando voce pede explicitamente.

Crie o Repository `backend/app/Repositories/Contracts/DetailPlanRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\DetailPlan;
use Illuminate\Database\Eloquent\Collection;

interface DetailPlanRepositoryInterface
{
    public function allByPlan(int $planId): Collection;

    public function findById(int $id): ?DetailPlan;

    public function create(array $data): DetailPlan;

    public function update(int $id, array $data): ?DetailPlan;

    public function delete(int $id): bool;
}
```

Crie `backend/app/Repositories/Eloquent/DetailPlanRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class DetailPlanRepository implements DetailPlanRepositoryInterface
{
    public function __construct(
        private readonly DetailPlan $model,
    ) {}

    public function allByPlan(int $planId): Collection
    {
        return $this->model->where('plan_id', $planId)->get();
    }

    public function findById(int $id): ?DetailPlan
    {
        return $this->model->find($id);
    }

    public function create(array $data): DetailPlan
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?DetailPlan
    {
        $detail = $this->findById($id);

        if (!$detail) {
            return null;
        }

        $detail->update($data);

        return $detail->fresh();
    }

    public function delete(int $id): bool
    {
        $detail = $this->findById($id);

        if (!$detail) {
            return false;
        }

        return (bool) $detail->delete();
    }
}
```

Registre no `backend/app/Providers/RepositoryServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use App\Repositories\Eloquent\DetailPlanRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        DetailPlanRepositoryInterface::class => DetailPlanRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
```

Crie as Actions:

```bash
# As Actions de DetailPlan ficam no mesmo diretorio Plan (sao sub-recurso)
```

Crie `backend/app/Actions/Plan/ListDetailPlansAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ListDetailPlansAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $planId): Collection
    {
        return $this->repository->allByPlan($planId);
    }
}
```

Crie `backend/app/Actions/Plan/CreateDetailPlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class CreateDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $planId, string $name): DetailPlan
    {
        return $this->repository->create([
            'plan_id' => $planId,
            'name' => $name,
        ]);
    }
}
```

Crie `backend/app/Actions/Plan/UpdateDetailPlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Models\DetailPlan;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class UpdateDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id, string $name): ?DetailPlan
    {
        return $this->repository->update($id, ['name' => $name]);
    }
}
```

Crie `backend/app/Actions/Plan/DeleteDetailPlanAction.php`:

```php
<?php

namespace App\Actions\Plan;

use App\Repositories\Contracts\DetailPlanRepositoryInterface;

final class DeleteDetailPlanAction
{
    public function __construct(
        private readonly DetailPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

Crie os FormRequests:

`backend/app/Http/Requests/Plan/StoreDetailPlanRequest.php`:

```php
<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StoreDetailPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do detalhe e obrigatorio.',
        ];
    }
}
```

`backend/app/Http/Requests/Plan/UpdateDetailPlanRequest.php`:

```php
<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetailPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do detalhe e obrigatorio.',
        ];
    }
}
```

Crie o Controller `backend/app/Http/Controllers/Api/V1/DetailPlanController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreDetailPlanRequest;
use App\Http\Requests\Plan\UpdateDetailPlanRequest;
use App\Http\Resources\DetailPlanResource;
use App\Actions\Plan\ListDetailPlansAction;
use App\Actions\Plan\CreateDetailPlanAction;
use App\Actions\Plan\UpdateDetailPlanAction;
use App\Actions\Plan\DeleteDetailPlanAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DetailPlanController extends Controller
{
    public function index(int $plan, ListDetailPlansAction $action): AnonymousResourceCollection
    {
        return DetailPlanResource::collection($action->execute($plan));
    }

    public function store(StoreDetailPlanRequest $request, int $plan, CreateDetailPlanAction $action): JsonResponse
    {
        $detail = $action->execute($plan, $request->validated('name'));

        return (new DetailPlanResource($detail))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDetailPlanRequest $request, int $plan, int $detail, UpdateDetailPlanAction $action): JsonResponse
    {
        $updated = $action->execute($detail, $request->validated('name'));

        if (!$updated) {
            return response()->json(['message' => 'Detalhe nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new DetailPlanResource($updated),
        ]);
    }

    public function destroy(int $plan, int $detail, DeleteDetailPlanAction $action): JsonResponse
    {
        $deleted = $action->execute($detail);

        if (!$deleted) {
            return response()->json(['message' => 'Detalhe nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Detalhe removido com sucesso.',
        ]);
    }
}
```

Adicione as rotas aninhadas em `backend/routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD
        Route::apiResource('plans', PlanController::class);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show']);
    });
});
```

**Rotas nested:**
O `plans.details` gera rotas como `/api/v1/plans/{plan}/details` e `/api/v1/plans/{plan}/details/{detail}`. O `except(['show'])` remove o GET individual (nao faz sentido ver um detalhe isolado).

Agora atualize o metodo `show()` no `backend/app/Http/Controllers/Api/V1/PlanController.php` para carregar os detalhes:

```php
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
```

**Testar com curl:**

```bash
# Criar detalhe no plano 1 (Basico)
curl -s -X POST http://127.0.0.1/api/v1/plans/1/details \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"name": "Ate 10 produtos"}' | python3 -m json.tool

# Listar detalhes do plano 1
curl -s -X GET http://127.0.0.1/api/v1/plans/1/details \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool

# Ver plano 1 com detalhes incluidos
curl -s -X GET http://127.0.0.1/api/v1/plans/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool
```

---

## Passo 3.7 - Migration: tabela tenants + Model Tenant

Tenants sao os restaurantes/empresas que usam a plataforma. Cada tenant assina um plano e tem seus dados isolados.

Crie `backend/database/migrations/0001_01_02_000003_create_tenants_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained();
            $table->uuid('uuid')->unique();
            $table->string('cnpj')->unique()->nullable();
            $table->string('name');
            $table->string('url')->unique(); // slug do tenant
            $table->string('email');
            $table->string('logo')->nullable();
            $table->boolean('active')->default(true);

            // Campos de assinatura (para integracao futura com gateway de pagamento)
            $table->string('subscription')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('subscription_id')->nullable();
            $table->boolean('subscription_active')->default(false);
            $table->boolean('subscription_suspended')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

**Sobre os campos de assinatura:**
Os campos `subscription*` e `expires_at` sao para integracao futura com gateway de pagamento (Stripe, PagSeguro, etc.). Por enquanto ficam nullable/default.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Adicione a relacao `tenants()` no `backend/app/Models/Plan.php` (se ainda nao tiver o import `HasMany`, adicione tambem):

```php
public function tenants(): HasMany
{
    return $this->hasMany(Tenant::class);
}
```

Crie `backend/app/Models/Tenant.php`:

```php
<?php

namespace App\Models;

use App\Observers\TenantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TenantObserver::class)]
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_id',
        'cnpj',
        'name',
        'url',
        'email',
        'logo',
        'active',
        'subscription',
        'expires_at',
        'subscription_id',
        'subscription_active',
        'subscription_suspended',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'subscription_active' => 'boolean',
            'subscription_suspended' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

Crie `backend/app/Observers/TenantObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->uuid)) {
            $tenant->uuid = (string) Str::uuid();
        }

        if (empty($tenant->url)) {
            $tenant->url = Str::slug($tenant->name);
        }
    }

    public function updating(Tenant $tenant): void
    {
        if ($tenant->isDirty('name') && !$tenant->isDirty('url')) {
            $tenant->url = Str::slug($tenant->name);
        }
    }
}
```

**UUID vs ID:**
O `id` (auto-increment) e usado internamente. O `uuid` e usado em URLs publicas e integracao com APIs externas — nao expoe a sequencia de IDs.

Crie a Factory `backend/database/factories/TenantFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'plan_id' => Plan::factory(),
            'uuid' => (string) Str::uuid(),
            'cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'name' => $name,
            'url' => Str::slug($name),
            'email' => fake()->unique()->companyEmail(),
            'active' => true,
        ];
    }
}
```

**Testar no tinker:**

```bash
docker compose exec backend php artisan tinker
```

```php
$plan = App\Models\Plan::first();
$tenant = App\Models\Tenant::create(['plan_id' => $plan->id, 'name' => 'Restaurante Teste', 'email' => 'teste@teste.com']);
echo $tenant->uuid; // UUID gerado automaticamente
echo $tenant->url;  // "restaurante-teste"
$tenant->forceDelete();
exit
```

---

## Passo 3.8 - Tenant Repository + CRUD (Backend API)

Seguindo o mesmo padrao dos Plans, criamos o CRUD completo para Tenants.

Crie `backend/app/Repositories/Contracts/TenantRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TenantRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Tenant;

    public function findByUuid(string $uuid): ?Tenant;

    public function create(array $data): Tenant;

    public function update(int $id, array $data): ?Tenant;

    public function delete(int $id): bool;
}
```

Crie `backend/app/Repositories/Eloquent/TenantRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TenantRepository implements TenantRepositoryInterface
{
    public function __construct(
        private readonly Tenant $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('plan')->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Tenant
    {
        return $this->model->with('plan')->find($id);
    }

    public function findByUuid(string $uuid): ?Tenant
    {
        return $this->model->with('plan')->where('uuid', $uuid)->first();
    }

    public function create(array $data): Tenant
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Tenant
    {
        $tenant = $this->model->find($id);

        if (!$tenant) {
            return null;
        }

        $tenant->update($data);

        return $tenant->fresh('plan');
    }

    public function delete(int $id): bool
    {
        $tenant = $this->model->find($id);

        if (!$tenant) {
            return false;
        }

        return (bool) $tenant->delete();
    }
}
```

**Nota:** Usamos `with('plan')` nos metodos de leitura para carregar o plano junto (eager loading). Isso evita N+1 queries.

Registre no `backend/app/Providers/RepositoryServiceProvider.php` — adicione as novas linhas:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Contracts\DetailPlanRepositoryInterface;
use App\Repositories\Eloquent\DetailPlanRepository;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Eloquent\TenantRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        DetailPlanRepositoryInterface::class => DetailPlanRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
```

Crie os DTOs:

```bash
mkdir -p backend/app/DTOs/Tenant
mkdir -p backend/app/Actions/Tenant
```

`backend/app/DTOs/Tenant/CreateTenantDTO.php`:

```php
<?php

namespace App\DTOs\Tenant;

use App\Http\Requests\Tenant\StoreTenantRequest;

final readonly class CreateTenantDTO
{
    public function __construct(
        public int $planId,
        public string $name,
        public string $email,
        public ?string $cnpj,
        public ?string $logo,
    ) {}

    public static function fromRequest(StoreTenantRequest $request): self
    {
        return new self(
            planId: $request->validated('plan_id'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            cnpj: $request->validated('cnpj'),
            logo: $request->validated('logo'),
        );
    }

    public function toArray(): array
    {
        return [
            'plan_id' => $this->planId,
            'name' => $this->name,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'logo' => $this->logo,
        ];
    }
}
```

`backend/app/DTOs/Tenant/UpdateTenantDTO.php`:

```php
<?php

namespace App\DTOs\Tenant;

use App\Http\Requests\Tenant\UpdateTenantRequest;

final readonly class UpdateTenantDTO
{
    public function __construct(
        public int $planId,
        public string $name,
        public string $email,
        public ?string $cnpj,
        public ?string $logo,
        public ?bool $active,
    ) {}

    public static function fromRequest(UpdateTenantRequest $request): self
    {
        return new self(
            planId: $request->validated('plan_id'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            cnpj: $request->validated('cnpj'),
            logo: $request->validated('logo'),
            active: $request->validated('active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'plan_id' => $this->planId,
            'name' => $this->name,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'logo' => $this->logo,
            'active' => $this->active,
        ], fn ($value) => $value !== null);
    }
}
```

Crie as Actions:

`backend/app/Actions/Tenant/ListTenantsAction.php`:

```php
<?php

namespace App\Actions\Tenant;

use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTenantsAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

`backend/app/Actions/Tenant/ShowTenantAction.php`:

```php
<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class ShowTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Tenant
    {
        return $this->repository->findById($id);
    }
}
```

`backend/app/Actions/Tenant/CreateTenantAction.php`:

```php
<?php

namespace App\Actions\Tenant;

use App\DTOs\Tenant\CreateTenantDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class CreateTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(CreateTenantDTO $dto): Tenant
    {
        return $this->repository->create($dto->toArray());
    }
}
```

`backend/app/Actions/Tenant/UpdateTenantAction.php`:

```php
<?php

namespace App\Actions\Tenant;

use App\DTOs\Tenant\UpdateTenantDTO;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class UpdateTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateTenantDTO $dto): ?Tenant
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

`backend/app/Actions/Tenant/DeleteTenantAction.php`:

```php
<?php

namespace App\Actions\Tenant;

use App\Repositories\Contracts\TenantRepositoryInterface;

final class DeleteTenantAction
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

Crie os FormRequests:

```bash
mkdir -p backend/app/Http/Requests/Tenant
```

`backend/app/Http/Requests/Tenant/StoreTenantRequest.php`:

```php
<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tenants,email'],
            'cnpj' => ['nullable', 'string', 'unique:tenants,cnpj'],
            'logo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'O plano e obrigatorio.',
            'plan_id.exists' => 'Plano nao encontrado.',
            'name.required' => 'O nome e obrigatorio.',
            'email.required' => 'O email e obrigatorio.',
            'email.email' => 'Informe um email valido.',
            'email.unique' => 'Ja existe um tenant com este email.',
            'cnpj.unique' => 'Ja existe um tenant com este CNPJ.',
        ];
    }
}
```

`backend/app/Http/Requests/Tenant/UpdateTenantRequest.php`:

```php
<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('tenants')->ignore($tenantId)],
            'cnpj' => ['nullable', 'string', Rule::unique('tenants')->ignore($tenantId)],
            'logo' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'O plano e obrigatorio.',
            'plan_id.exists' => 'Plano nao encontrado.',
            'name.required' => 'O nome e obrigatorio.',
            'email.required' => 'O email e obrigatorio.',
            'email.unique' => 'Ja existe um tenant com este email.',
            'cnpj.unique' => 'Ja existe um tenant com este CNPJ.',
        ];
    }
}
```

Crie `backend/app/Http/Resources/TenantResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'cnpj' => $this->cnpj,
            'name' => $this->name,
            'url' => $this->url,
            'email' => $this->email,
            'logo' => $this->logo,
            'active' => $this->active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

Crie `backend/app/Http/Controllers/Api/V1/TenantController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Actions\Tenant\ListTenantsAction;
use App\Actions\Tenant\ShowTenantAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\Actions\Tenant\DeleteTenantAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function index(ListTenantsAction $action): AnonymousResourceCollection
    {
        $tenants = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return TenantResource::collection($tenants);
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $tenant = $action->execute(CreateTenantDTO::fromRequest($request));

        $tenant->load('plan');

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $tenant, ShowTenantAction $action): JsonResponse
    {
        $tenant = $action->execute($tenant);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($tenant),
        ]);
    }

    public function update(UpdateTenantRequest $request, int $tenant, UpdateTenantAction $action): JsonResponse
    {
        $updated = $action->execute($tenant, UpdateTenantDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new TenantResource($updated),
        ]);
    }

    public function destroy(int $tenant, DeleteTenantAction $action): JsonResponse
    {
        $deleted = $action->execute($tenant);

        if (!$deleted) {
            return response()->json(['message' => 'Tenant nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Tenant removido com sucesso.',
        ]);
    }
}
```

Adicione as rotas no `backend/routes/api.php` — acrescente a linha do TenantController:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD
        Route::apiResource('plans', PlanController::class);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show']);

        // Tenants CRUD
        Route::apiResource('tenants', TenantController::class);
    });
});
```

**Testar com curl:**

```bash
# Criar tenant
curl -s -X POST http://127.0.0.1/api/v1/tenants \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"plan_id": 1, "name": "Pizzaria do Ze", "email": "ze@pizzaria.com"}' \
  | python3 -m json.tool

# Listar tenants
curl -s -X GET http://127.0.0.1/api/v1/tenants \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" | python3 -m json.tool
```

---

## Passo 3.9 - User-Tenant relationship + Migration

Agora conectamos usuarios a tenants. O admin (admin@orderly.com) fica SEM tenant — e o super-admin da plataforma.

Crie `backend/database/migrations/0001_01_02_000004_add_tenant_id_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
```

**Por que `nullable`?**
O super-admin (admin@orderly.com) nao pertence a nenhum tenant — ele gerencia a plataforma inteira. Por isso `tenant_id` pode ser `null`.

**`nullOnDelete`:** Se um tenant for deletado, os usuarios vinculados ficam com `tenant_id = null` em vez de serem deletados junto.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Crie o arquivo de configuracao `backend/config/orderly.php`:

```php
<?php

return [
    'super_admin_emails' => explode(',', env('SUPER_ADMIN_EMAILS', 'admin@orderly.com')),
];
```

**Por que um arquivo de config?**
Centraliza a lista de emails super-admin. Em producao, pode ser definido via variavel de ambiente: `SUPER_ADMIN_EMAILS=admin@orderly.com,outro@admin.com`.

Atualize o Model `backend/app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // JWT: identificador unico do usuario no token
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    // JWT: claims customizados — inclui tenant_id no payload do token
    public function getJWTCustomClaims(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->email, config('orderly.super_admin_emails'));
    }
}
```

**O que mudou:**
1. `tenant_id` adicionado ao `$fillable`
2. `getJWTCustomClaims()` agora inclui `tenant_id` no payload JWT — assim o frontend e middleware sabem de qual tenant o usuario pertence
3. `tenant()` — relacao BelongsTo com Tenant
4. `isSuperAdmin()` — verifica se o email esta na whitelist de super-admins

Atualize tambem o `backend/app/Http/Resources/UserResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'is_super_admin' => $this->isSuperAdmin(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Testar:**

```bash
docker compose exec backend php artisan tinker
```

```php
$user = App\Models\User::where('email', 'admin@orderly.com')->first();
echo $user->isSuperAdmin() ? 'SIM' : 'NAO'; // SIM
echo $user->tenant_id; // null (super admin)
exit
```

---

## Passo 3.10 - Seeders: Plans + Tenant + Usuario tenant

Agora criamos seeders para ter dados de desenvolvimento: um tenant demo e um usuario gerente vinculado a ele.

Crie `backend/database/seeders/TenantSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('name', 'Profissional')->first();

        if (!$plan) {
            $this->command->warn('Plano "Profissional" nao encontrado. Rode PlanSeeder primeiro.');
            return;
        }

        $tenant = Tenant::firstOrCreate(
            ['email' => 'contato@restaurantedemo.com'],
            [
                'plan_id' => $plan->id,
                'name' => 'Restaurante Demo',
                'email' => 'contato@restaurantedemo.com',
                'cnpj' => '12.345.678/0001-90',
                'active' => true,
            ],
        );

        // Criar usuario gerente vinculado ao tenant
        User::firstOrCreate(
            ['email' => 'gerente@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Gerente Demo',
                'email' => 'gerente@demo.com',
                'password' => Hash::make('password'),
            ],
        );
    }
}
```

Atualize o `backend/database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            AdminUserSeeder::class,
            TenantSeeder::class,
        ]);
    }
}
```

Rode tudo do zero:

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

**Resultado esperado:**
```
Dropping all tables ... DONE
Running migrations ... DONE
Running seeders ...
  PlanSeeder .............. DONE
  AdminUserSeeder ......... DONE
  TenantSeeder ............ DONE
```

**Testar os dois logins:**

```bash
# Login como super-admin (sem tenant)
curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | python3 -m json.tool

# Login como gerente (com tenant)
curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "gerente@demo.com", "password": "password"}' \
  | python3 -m json.tool
```

Teste o `/me` com cada token:

```bash
# /me do super-admin -> tenant_id: null, is_super_admin: true
curl -s -X GET http://127.0.0.1/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_ADMIN" | python3 -m json.tool

# /me do gerente -> tenant_id: 1, is_super_admin: false
curl -s -X GET http://127.0.0.1/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GERENTE" | python3 -m json.tool
```

---

## Passo 3.11 - Multi-tenancy: Global Scope + Trait

Aqui esta o coracao do multi-tenancy. Criamos um Global Scope que **automaticamente** filtra todas as queries por `tenant_id`, e um Trait que os models tenant-scoped usarao.

**Como funciona:**
```
Sem scope:  SELECT * FROM products;                    -- retorna TUDO
Com scope:  SELECT * FROM products WHERE tenant_id = 1; -- so do tenant logado
```

Crie o diretorio e os arquivos:

```bash
mkdir -p backend/app/Scopes
mkdir -p backend/app/Traits
```

Crie `backend/app/Scopes/TenantScope.php`:

```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth('api')->user();

        // Super-admin (sem tenant) ve tudo — nao aplica filtro
        if ($user && $user->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        }
    }
}
```

Crie `backend/app/Traits/BelongsToTenant.php`:

```php
<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Filtro automatico em todas as queries
        static::addGlobalScope(new TenantScope());

        // Auto-preenche tenant_id ao criar registros
        static::creating(function (Model $model) {
            $user = auth('api')->user();

            if ($user && $user->tenant_id && !$model->tenant_id) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

**O trait faz duas coisas:**

1. **Leitura isolada (Global Scope):** Toda query `SELECT` recebe automaticamente `WHERE tenant_id = X`. O usuario do tenant 1 nunca ve dados do tenant 2.

2. **Escrita isolada (creating):** Ao criar um registro, o `tenant_id` e preenchido automaticamente a partir do usuario logado. Nao precisa passar `tenant_id` manualmente.

**Super-admin:**
O super-admin tem `tenant_id = null`. Nesse caso, o scope NAO e aplicado — ele ve todos os dados de todos os tenants. Isso e util para o painel de administracao da plataforma.

**Como usar (exemplo futuro com Products):**
```php
class Product extends Model
{
    use BelongsToTenant; // so isso! tudo e automatico
}
```

**Testar:**
O scope sera testado na pratica quando criarmos Categories e Products (Fase 4). Por enquanto, a infraestrutura esta pronta.

---

## Passo 3.12 - Middleware IdentifyTenant

O middleware carrega o tenant do usuario logado e disponibiliza globalmente na aplicacao.

**Por que precisamos disso alem do Global Scope?**
O Scope filtra queries no banco. O middleware disponibiliza o objeto `Tenant` completo para qualquer parte do codigo (controllers, services, views) — util para verificar se o tenant esta ativo, qual plano ele tem, etc.

Crie `backend/app/Http/Middleware/IdentifyTenant.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
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
        }

        return $next($request);
    }
}
```

**O que o middleware faz:**
1. Pega o usuario autenticado
2. Se tem `tenant_id`, carrega o tenant
3. Se o tenant esta inativo ou nao existe, retorna 403
4. Registra o tenant como singleton no container — acessivel via `app('currentTenant')` em qualquer lugar

Registre o middleware no `backend/bootstrap/app.php`. O arquivo provavelmente ja existe — voce precisa adicionar o alias do middleware:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**Importante:** Verifique se o `bootstrap/app.php` ja tem conteudo e apenas adicione o `->withMiddleware(...)` se ainda nao existir, ou adicione o alias dentro do callback existente.

Agora aplique o middleware nas rotas protegidas em `backend/routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT + identificacao do tenant)
    Route::middleware(['auth:api', 'tenant'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD
        Route::apiResource('plans', PlanController::class);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show']);

        // Tenants CRUD
        Route::apiResource('tenants', TenantController::class);
    });
});
```

**Testar:**

```bash
# Login como gerente (tenant ativo) — deve funcionar
curl -s -X GET http://127.0.0.1/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GERENTE" | python3 -m json.tool

# Desativar o tenant no tinker e testar de novo:
docker compose exec backend php artisan tinker
```

```php
$tenant = App\Models\Tenant::first();
$tenant->update(['active' => false]);
exit
```

```bash
# Agora o gerente recebe 403
curl -s -X GET http://127.0.0.1/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GERENTE" | python3 -m json.tool
# {"message": "Tenant inativo ou nao encontrado."}
```

Reative o tenant:

```bash
docker compose exec backend php artisan tinker
```

```php
App\Models\Tenant::first()->update(['active' => true]);
exit
```

---

## Passo 3.13 - Frontend: pagina de listagem de Planos

Agora vamos criar a interface de gerenciamento de planos no frontend.

Primeiro, instale os componentes shadcn/ui necessarios:

```bash
docker compose exec frontend npx shadcn@latest add table badge dialog
```

Crie os tipos TypeScript `frontend/src/types/plan.ts`:

```typescript
export interface Plan {
  id: number;
  name: string;
  url: string;
  price: string;
  description: string | null;
  details?: DetailPlan[];
  created_at: string;
  updated_at: string;
}

export interface DetailPlan {
  id: number;
  plan_id: number;
  name: string;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
```

Crie o servico de API `frontend/src/services/plan-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Plan, PaginatedResponse } from "@/types/plan";

export async function getPlans(page = 1): Promise<PaginatedResponse<Plan>> {
  return apiClient<PaginatedResponse<Plan>>(`/v1/plans?page=${page}`);
}

export async function getPlan(id: number): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>(`/v1/plans/${id}`);
}

export async function createPlan(data: {
  name: string;
  price: number;
  description?: string;
}): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>("/v1/plans", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updatePlan(
  id: number,
  data: { name: string; price: number; url?: string; description?: string }
): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>(`/v1/plans/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deletePlan(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/plans/${id}`, {
    method: "DELETE",
  });
}
```

Crie a pagina `frontend/src/app/(admin)/plans/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getPlans, deletePlan } from "@/services/plan-service";
import type { Plan } from "@/types/plan";
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
import { PlanFormDialog } from "@/components/plans/plan-form-dialog";
import { DeletePlanDialog } from "@/components/plans/delete-plan-dialog";

export default function PlansPage() {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editPlan, setEditPlan] = useState<Plan | null>(null);
  const [deletePlanState, setDeletePlanState] = useState<Plan | null>(null);

  const fetchPlans = async () => {
    try {
      const response = await getPlans();
      setPlans(response.data);
    } catch (error) {
      console.error("Erro ao carregar planos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPlans();
  }, []);

  const handleDeleted = () => {
    setDeletePlanState(null);
    fetchPlans();
  };

  const handleSaved = () => {
    setCreateOpen(false);
    setEditPlan(null);
    fetchPlans();
  };

  const formatPrice = (price: string) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(Number(price));
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Planos</h1>
          <p className="text-muted-foreground">
            Gerencie os planos de assinatura da plataforma.
          </p>
        </div>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Plano
        </Button>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>URL</TableHead>
              <TableHead>Preco</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {plans.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center py-8">
                  Nenhum plano cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              plans.map((plan) => (
                <TableRow key={plan.id}>
                  <TableCell className="font-medium">{plan.name}</TableCell>
                  <TableCell>
                    <Badge variant="secondary">{plan.url}</Badge>
                  </TableCell>
                  <TableCell>{formatPrice(plan.price)}</TableCell>
                  <TableCell className="max-w-xs truncate">
                    {plan.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setEditPlan(plan)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setDeletePlanState(plan)}
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
      </div>

      {/* Dialog de criar */}
      <PlanFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {/* Dialog de editar */}
      {editPlan && (
        <PlanFormDialog
          open={!!editPlan}
          onOpenChange={(open) => !open && setEditPlan(null)}
          onSaved={handleSaved}
          plan={editPlan}
        />
      )}

      {/* Dialog de deletar */}
      {deletePlanState && (
        <DeletePlanDialog
          open={!!deletePlanState}
          onOpenChange={(open) => !open && setDeletePlanState(null)}
          onDeleted={handleDeleted}
          plan={deletePlanState}
        />
      )}
    </div>
  );
}
```

Adicione "Planos" no sidebar `frontend/src/components/app-sidebar.tsx` — adicione ao array de menu items:

```tsx
import {
  LayoutDashboard,
  ShoppingBag,
  UtensilsCrossed,
  Users,
  QrCode,
  Star,
  Settings,
  CreditCard, // NOVO
} from "lucide-react";

// No array de items do menu, adicione apos Dashboard:
const menuItems = [
  { title: "Dashboard", url: "/dashboard", icon: LayoutDashboard },
  { title: "Planos", url: "/plans", icon: CreditCard },        // NOVO
  { title: "Pedidos", url: "/orders", icon: ShoppingBag },
  { title: "Cardapio", url: "/products", icon: UtensilsCrossed },
  { title: "Clientes", url: "/customers", icon: Users },
  { title: "Mesas", url: "/tables", icon: QrCode },
  { title: "Avaliacoes", url: "/reviews", icon: Star },
  { title: "Configuracoes", url: "/settings", icon: Settings },
];
```

Adicione `/plans` nas rotas protegidas do proxy `frontend/src/proxy.ts`:

```typescript
export const config = {
  matcher: [
    "/dashboard/:path*",
    "/plans/:path*",        // NOVO
    "/orders/:path*",
    "/products/:path*",
    "/customers/:path*",
    "/tables/:path*",
    "/reviews/:path*",
    "/settings/:path*",
    "/login",
  ],
};
```

---

## Passo 3.14 - Frontend: formularios de criar/editar Plano

Crie o diretorio para componentes de planos:

```bash
mkdir -p frontend/src/components/plans
```

Crie `frontend/src/components/plans/plan-form-dialog.tsx`:

```tsx
"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { standardSchemaResolver } from "@hookform/resolvers/standard-schema";
import { z } from "zod";
import { createPlan, updatePlan } from "@/services/plan-service";
import type { Plan } from "@/types/plan";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ApiError } from "@/lib/api";

const planSchema = z.object({
  name: z.string().min(1, "O nome e obrigatorio"),
  price: z.coerce.number().min(0, "O preco nao pode ser negativo"),
  description: z.string().optional(),
});

type PlanFormData = z.infer<typeof planSchema>;

interface PlanFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
  plan?: Plan;
}

export function PlanFormDialog({
  open,
  onOpenChange,
  onSaved,
  plan,
}: PlanFormDialogProps) {
  const isEditing = !!plan;

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<PlanFormData>({
    resolver: standardSchemaResolver(planSchema),
    defaultValues: {
      name: plan?.name || "",
      price: plan ? Number(plan.price) : 0,
      description: plan?.description || "",
    },
  });

  useEffect(() => {
    if (open) {
      reset({
        name: plan?.name || "",
        price: plan ? Number(plan.price) : 0,
        description: plan?.description || "",
      });
    }
  }, [open, plan, reset]);

  const onSubmit = async (data: PlanFormData) => {
    try {
      if (isEditing) {
        await updatePlan(plan.id, data);
      } else {
        await createPlan(data);
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
            {isEditing ? "Editar Plano" : "Novo Plano"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {errors.root && (
            <p className="text-sm text-destructive">{errors.root.message}</p>
          )}

          <div className="space-y-2">
            <Label htmlFor="name">Nome</Label>
            <Input
              id="name"
              placeholder="Ex: Profissional"
              {...register("name")}
            />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="price">Preco (R$)</Label>
            <Input
              id="price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              {...register("price")}
            />
            {errors.price && (
              <p className="text-sm text-destructive">{errors.price.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descricao (opcional)</Label>
            <Input
              id="description"
              placeholder="Descricao do plano"
              {...register("description")}
            />
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

Crie `frontend/src/components/plans/delete-plan-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { deletePlan } from "@/services/plan-service";
import type { Plan } from "@/types/plan";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeletePlanDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
  plan: Plan;
}

export function DeletePlanDialog({
  open,
  onOpenChange,
  onDeleted,
  plan,
}: DeletePlanDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    setLoading(true);
    try {
      await deletePlan(plan.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao deletar plano:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Excluir Plano</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja excluir o plano &quot;{plan.name}&quot;? Esta
            acao nao pode ser desfeita.
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
            {loading ? "Excluindo..." : "Excluir"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
```

**Testar:**
- Acesse `http://127.0.0.1/plans` no navegador (logado)
- Veja a tabela com os 3 planos seedados
- Clique em "Novo Plano" — dialog de criacao
- Clique no icone de lapis — dialog de edicao
- Clique no icone de lixeira — dialog de confirmacao de exclusao

---

## Passo 3.15 - Frontend: gerenciamento de detalhes do Plano

Crie o servico `frontend/src/services/detail-plan-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { DetailPlan } from "@/types/plan";

export async function getDetailPlans(
  planId: number
): Promise<{ data: DetailPlan[] }> {
  return apiClient<{ data: DetailPlan[] }>(`/v1/plans/${planId}/details`);
}

export async function createDetailPlan(
  planId: number,
  name: string
): Promise<{ data: DetailPlan }> {
  return apiClient<{ data: DetailPlan }>(`/v1/plans/${planId}/details`, {
    method: "POST",
    body: JSON.stringify({ name }),
  });
}

export async function updateDetailPlan(
  planId: number,
  detailId: number,
  name: string
): Promise<{ data: DetailPlan }> {
  return apiClient<{ data: DetailPlan }>(
    `/v1/plans/${planId}/details/${detailId}`,
    {
      method: "PUT",
      body: JSON.stringify({ name }),
    }
  );
}

export async function deleteDetailPlan(
  planId: number,
  detailId: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(
    `/v1/plans/${planId}/details/${detailId}`,
    {
      method: "DELETE",
    }
  );
}
```

Crie a pagina de detalhes `frontend/src/app/(admin)/plans/[id]/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { getPlan } from "@/services/plan-service";
import {
  getDetailPlans,
  createDetailPlan,
  deleteDetailPlan,
} from "@/services/detail-plan-service";
import type { Plan, DetailPlan } from "@/types/plan";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";

export default function PlanDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const planId = Number(params.id);

  const [plan, setPlan] = useState<Plan | null>(null);
  const [details, setDetails] = useState<DetailPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [newDetail, setNewDetail] = useState("");
  const [adding, setAdding] = useState(false);

  const fetchData = async () => {
    try {
      const [planRes, detailsRes] = await Promise.all([
        getPlan(planId),
        getDetailPlans(planId),
      ]);
      setPlan(planRes.data);
      setDetails(detailsRes.data);
    } catch (error) {
      console.error("Erro ao carregar plano:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [planId]);

  const handleAddDetail = async () => {
    if (!newDetail.trim()) return;

    setAdding(true);
    try {
      await createDetailPlan(planId, newDetail.trim());
      setNewDetail("");
      fetchData();
    } catch (error) {
      console.error("Erro ao adicionar detalhe:", error);
    } finally {
      setAdding(false);
    }
  };

  const handleDeleteDetail = async (detailId: number) => {
    try {
      await deleteDetailPlan(planId, detailId);
      fetchData();
    } catch (error) {
      console.error("Erro ao remover detalhe:", error);
    }
  };

  const formatPrice = (price: string) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(Number(price));
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  if (!plan) {
    return <p>Plano nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.push("/plans")}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{plan.name}</h1>
          <p className="text-muted-foreground">
            {formatPrice(plan.price)} &middot;{" "}
            <Badge variant="secondary">{plan.url}</Badge>
          </p>
        </div>
      </div>

      {plan.description && (
        <p className="text-muted-foreground">{plan.description}</p>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Detalhes do Plano</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-2">
            <Input
              placeholder="Ex: Ate 50 produtos"
              value={newDetail}
              onChange={(e) => setNewDetail(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && handleAddDetail()}
            />
            <Button onClick={handleAddDetail} disabled={adding}>
              <Plus className="mr-2 h-4 w-4" />
              Adicionar
            </Button>
          </div>

          {details.length === 0 ? (
            <p className="text-sm text-muted-foreground py-4 text-center">
              Nenhum detalhe cadastrado. Adicione as features deste plano.
            </p>
          ) : (
            <ul className="space-y-2">
              {details.map((detail) => (
                <li
                  key={detail.id}
                  className="flex items-center justify-between rounded-md border px-4 py-2"
                >
                  <span>{detail.name}</span>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => handleDeleteDetail(detail.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
```

Agora atualize a pagina de listagem para que o nome do plano seja um link.

Na tabela em `frontend/src/app/(admin)/plans/page.tsx`, altere a celula do nome para incluir um link:

```tsx
// No import, adicione:
import Link from "next/link";

// Na TableCell do nome, troque:
<TableCell className="font-medium">{plan.name}</TableCell>

// Por:
<TableCell className="font-medium">
  <Link href={`/plans/${plan.id}`} className="hover:underline">
    {plan.name}
  </Link>
</TableCell>
```

**Testar:**
- Na pagina de planos, clique no nome de um plano
- A pagina de detalhes abre mostrando info do plano
- Adicione detalhes como "Ate 10 produtos", "Suporte por email"
- Delete detalhes clicando no icone de lixeira

---

## Passo 3.16 - Verificacao end-to-end da Fase 3

**Checklist de verificacao:**

```bash
# 1. Resetar banco e rodar seeders
docker compose exec backend php artisan migrate:fresh --seed

# 2. Verificar planos seedados
docker compose exec backend php artisan tinker --execute="echo App\Models\Plan::count() . ' planos criados';"
# Esperado: 3 planos criados

# 3. Verificar tenant seedado
docker compose exec backend php artisan tinker --execute="echo App\Models\Tenant::count() . ' tenants criados';"
# Esperado: 1 tenants criados

# 4. Verificar usuarios
docker compose exec backend php artisan tinker --execute="
\$admin = App\Models\User::where('email', 'admin@orderly.com')->first();
echo 'Admin: tenant_id=' . (\$admin->tenant_id ?? 'null') . ', super_admin=' . (\$admin->isSuperAdmin() ? 'sim' : 'nao') . PHP_EOL;
\$gerente = App\Models\User::where('email', 'gerente@demo.com')->first();
echo 'Gerente: tenant_id=' . \$gerente->tenant_id . ', super_admin=' . (\$gerente->isSuperAdmin() ? 'sim' : 'nao');
"
# Esperado:
# Admin: tenant_id=null, super_admin=sim
# Gerente: tenant_id=1, super_admin=nao
```

**Testar API com curl:**

```bash
# Login como admin
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Plans CRUD
curl -s http://127.0.0.1/api/v1/plans -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Tenants CRUD
curl -s http://127.0.0.1/api/v1/tenants -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Plan Details
curl -s http://127.0.0.1/api/v1/plans/1/details -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Testar no navegador:**

1. Acesse `http://127.0.0.1/login`
2. Login com `admin@orderly.com` / `password`
3. Clique em "Planos" no sidebar
4. Veja os 3 planos na tabela
5. Clique em "Novo Plano" — crie um plano de teste
6. Clique no nome de um plano — veja a pagina de detalhes
7. Adicione detalhes (features) ao plano
8. Volte e edite/delete planos

**Resumo dos arquivos criados/modificados na Fase 3:**

```
backend/
├── app/
│   ├── Actions/
│   │   ├── Plan/
│   │   │   ├── ListPlansAction.php
│   │   │   ├── ShowPlanAction.php
│   │   │   ├── CreatePlanAction.php
│   │   │   ├── UpdatePlanAction.php
│   │   │   ├── DeletePlanAction.php
│   │   │   ├── ListDetailPlansAction.php
│   │   │   ├── CreateDetailPlanAction.php
│   │   │   ├── UpdateDetailPlanAction.php
│   │   │   └── DeleteDetailPlanAction.php
│   │   └── Tenant/
│   │       ├── ListTenantsAction.php
│   │       ├── ShowTenantAction.php
│   │       ├── CreateTenantAction.php
│   │       ├── UpdateTenantAction.php
│   │       └── DeleteTenantAction.php
│   ├── DTOs/
│   │   ├── Plan/
│   │   │   ├── CreatePlanDTO.php
│   │   │   └── UpdatePlanDTO.php
│   │   └── Tenant/
│   │       ├── CreateTenantDTO.php
│   │       └── UpdateTenantDTO.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── PlanController.php
│   │   │   ├── DetailPlanController.php
│   │   │   └── TenantController.php
│   │   ├── Middleware/
│   │   │   └── IdentifyTenant.php
│   │   ├── Requests/
│   │   │   ├── Plan/
│   │   │   │   ├── StorePlanRequest.php
│   │   │   │   ├── UpdatePlanRequest.php
│   │   │   │   ├── StoreDetailPlanRequest.php
│   │   │   │   └── UpdateDetailPlanRequest.php
│   │   │   └── Tenant/
│   │   │       ├── StoreTenantRequest.php
│   │   │       └── UpdateTenantRequest.php
│   │   └── Resources/
│   │       ├── PlanResource.php
│   │       ├── DetailPlanResource.php
│   │       └── TenantResource.php
│   ├── Models/
│   │   ├── Plan.php
│   │   ├── DetailPlan.php
│   │   ├── Tenant.php
│   │   └── User.php (modificado - tenant_id, isSuperAdmin)
│   ├── Observers/
│   │   ├── PlanObserver.php
│   │   └── TenantObserver.php
│   ├── Providers/
│   │   └── RepositoryServiceProvider.php (modificado - 3 novos bindings)
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── PlanRepositoryInterface.php
│   │   │   ├── DetailPlanRepositoryInterface.php
│   │   │   └── TenantRepositoryInterface.php
│   │   └── Eloquent/
│   │       ├── PlanRepository.php
│   │       ├── DetailPlanRepository.php
│   │       └── TenantRepository.php
│   ├── Scopes/
│   │   └── TenantScope.php
│   └── Traits/
│       └── BelongsToTenant.php
├── bootstrap/app.php (modificado - middleware tenant)
├── config/orderly.php
├── database/
│   ├── factories/
│   │   ├── PlanFactory.php
│   │   └── TenantFactory.php
│   ├── migrations/
│   │   ├── 0001_01_02_000001_create_plans_table.php
│   │   ├── 0001_01_02_000002_create_detail_plans_table.php
│   │   ├── 0001_01_02_000003_create_tenants_table.php
│   │   └── 0001_01_02_000004_add_tenant_id_to_users_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php (modificado)
│       ├── PlanSeeder.php
│       └── TenantSeeder.php
└── routes/api.php (modificado - plans, details, tenants)

frontend/
├── src/
│   ├── app/(admin)/plans/
│   │   ├── page.tsx (listagem)
│   │   └── [id]/page.tsx (detalhes)
│   ├── components/plans/
│   │   ├── plan-form-dialog.tsx
│   │   └── delete-plan-dialog.tsx
│   ├── services/
│   │   ├── plan-service.ts
│   │   └── detail-plan-service.ts
│   ├── types/
│   │   └── plan.ts
│   ├── components/app-sidebar.tsx (modificado - item Planos)
│   └── proxy.ts (modificado - rota /plans)
```

**Conceitos aprendidos:**
- Multi-tenancy single-database (tenant_id + Global Scope)
- Observer Pattern (auto-geracao de slugs e UUIDs)
- Repository Pattern completo (interface → implementacao → ServiceProvider)
- CRUD completo em Clean Architecture (Controller → Action → DTO → Repository)
- SoftDeletes (exclusao logica)
- Nested Resources (detalhes dentro de planos)
- Middleware customizado (identificacao de tenant)
- FormRequests com validacao e Rule::unique()->ignore()
- API Resources com whenLoaded (eager loading condicional)
- Frontend: shadcn/ui Table, Dialog, Badge
- Frontend: React Hook Form + Zod em modals
- Frontend: rotas dinamicas Next.js [id]

**Proximo:** Fase 4 - ACL (Roles, Permissions, Profiles)

---


---

[Voltar ao README](../README.md)
