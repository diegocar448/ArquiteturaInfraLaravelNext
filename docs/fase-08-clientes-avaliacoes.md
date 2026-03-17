# Fase 8 - Autenticacao de Clientes + Avaliacoes

Nesta fase vamos implementar dois subsistemas interligados:
1. **Autenticacao de Clientes** — sistema de login separado dos usuarios admin, com guard JWT dedicado
2. **Avaliacoes de Pedidos** — clientes podem avaliar pedidos entregues com estrelas (1-5) e comentario

**O que vamos construir:**
- Tabela `clients` com autenticacao JWT independente (guard `client`)
- Endpoints publicos de registro e login para clientes
- Migration para FK `orders.client_id` + tabela `order_evaluations`
- CRUD de avaliacoes (apenas clientes autenticados criam; admin visualiza)
- Painel admin para visualizar avaliacoes recebidas

**Dependencia:** Fase 7 concluida (Sistema de Pedidos).

---

## Passo 8.1 - Conceito: Clientes vs Usuarios e Avaliacoes

### Por que dois modelos de autenticacao?

O sistema tem dois tipos de "usuarios":

| | **User** (Admin) | **Client** (Cliente) |
|---|---|---|
| **Quem e** | Dono do restaurante, gerente, garcom | Cliente final que faz pedidos |
| **Tabela** | `users` | `clients` |
| **Guard JWT** | `api` | `client` |
| **Tem tenant?** | Sim (`tenant_id`) | Nao (interage com qualquer tenant) |
| **Sidebar** | Sim (painel admin) | Nao (app publico futuro) |
| **Permissoes** | ACL dupla camada | Nenhuma (acesso limitado por design) |

> **Por que nao usar a mesma tabela?** Clientes nao tem `tenant_id`, nao tem roles/permissions, e o fluxo de autenticacao e diferente (registro publico vs convite). Separar evita complexidade desnecessaria e mantem o Model `User` focado no admin.

### Guard separado

O Laravel permite multiplos guards JWT. Cada guard usa um provider (model) diferente:

```
Guard "api"    → Provider "users"    → Model User::class
Guard "client" → Provider "clients"  → Model Client::class
```

### Avaliacoes

Uma avaliacao vincula um **cliente** a um **pedido entregue**:

| Campo | Tipo | Descricao |
|---|---|---|
| `id` | bigint | PK |
| `order_id` | FK → orders | Pedido avaliado |
| `client_id` | FK → clients | Cliente que avaliou |
| `stars` | tinyint (1-5) | Nota em estrelas |
| `comment` | text? | Comentario opcional |

Regras de negocio:
- Apenas pedidos com status `delivered` podem ser avaliados
- Cada cliente pode avaliar um pedido **apenas uma vez** (unique: `order_id` + `client_id`)
- Apenas o cliente vinculado ao pedido (via `orders.client_id`) pode avaliar

---

## Passo 8.2 - Migration: tabela clients + guard JWT

### Migration clients

Crie `backend/database/migrations/0001_01_02_000012_create_clients_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
```

### Configurar guard JWT para clients

Edite `backend/config/auth.php` e adicione o guard `client` e o provider `clients`:

```php
<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'client' => [
            'driver' => 'jwt',
            'provider' => 'clients',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
        'clients' => [
            'driver' => 'eloquent',
            'model' => App\Models\Client::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

> **Guard `client`:** Usa o driver `jwt` (tymon/jwt-auth) apontando para o provider `clients`, que por sua vez usa o model `Client`. Isso permite `auth('client')->attempt(...)` independente de `auth('api')`.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

---

## Passo 8.3 - Client Model + Observer + JWTSubject

### Model

Crie `backend/app/Models/Client.php`:

```php
<?php

namespace App\Models;

use App\Observers\ClientObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[ObservedBy(ClientObserver::class)]
class Client extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'guard' => 'client',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(OrderEvaluation::class);
    }
}
```

**Pontos importantes:**
- Extende `Authenticatable` (nao `Model`) — necessario para autenticacao JWT
- Implementa `JWTSubject` — mesma interface do `User`, mas com claim `guard: client` para diferenciar
- `password` cast como `hashed` — Laravel faz hash automaticamente ao salvar
- Sem `BelongsToTenant` — clientes sao globais, interagem com qualquer tenant

### Observer

Crie `backend/app/Observers/ClientObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Str;

class ClientObserver
{
    public function creating(Client $client): void
    {
        if (empty($client->uuid)) {
            $client->uuid = (string) Str::uuid();
        }
    }
}
```

### Adicionar relacionamento no Order Model

Edite `backend/app/Models/Order.php` e adicione o relacionamento `client()`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Adicionar ao Model (apos o metodo table()):
public function client(): BelongsTo
{
    return $this->belongsTo(Client::class);
}
```

### Testar no tinker

```bash
docker compose exec backend php artisan tinker
```

```php
$client = App\Models\Client::create([
    'name' => 'Joao Silva',
    'email' => 'joao@email.com',
    'password' => 'password',
]);

echo "UUID: {$client->uuid}, Email: {$client->email}";
// UUID: 550e8400-..., Email: joao@email.com

// Testar autenticacao
$token = auth('client')->attempt(['email' => 'joao@email.com', 'password' => 'password']);
echo "Token: " . substr($token, 0, 30) . "...";

// Verificar claims
$payload = auth('client')->payload();
echo "Guard: " . $payload->get('guard'); // "client"

auth('client')->logout();
$client->delete();
exit;
```

---

## Passo 8.4 - Client Auth Controller + Routes

### FormRequests

Crie `backend/app/Http/Requests/ClientAuth/RegisterClientRequest.php`:

```php
<?php

namespace App\Http\Requests\ClientAuth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:clients,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}
```

Crie `backend/app/Http/Requests/ClientAuth/LoginClientRequest.php`:

```php
<?php

namespace App\Http\Requests\ClientAuth;

use Illuminate\Foundation\Http\FormRequest;

class LoginClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/ClientResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### Controller

Crie `backend/app/Http/Controllers/Api/V1/Auth/ClientAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientAuth\LoginClientRequest;
use App\Http\Requests\ClientAuth\RegisterClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth Cliente
 */
class ClientAuthController extends Controller
{
    /**
     * Registrar cliente
     *
     * Cria uma nova conta de cliente. Retorna o token JWT.
     *
     * @unauthenticated
     */
    public function register(RegisterClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        $token = auth('client')->login($client);

        return $this->respondWithToken($token, $client, 201);
    }

    /**
     * Login cliente
     *
     * Autentica um cliente e retorna um token JWT.
     *
     * @unauthenticated
     */
    public function login(LoginClientRequest $request): JsonResponse
    {
        $token = auth('client')->attempt($request->validated());

        if (!$token) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }

        return $this->respondWithToken($token, auth('client')->user());
    }

    /**
     * Cliente autenticado
     *
     * Retorna os dados do cliente autenticado.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new ClientResource(auth('client')->user()),
        ]);
    }

    /**
     * Logout cliente
     *
     * Invalida o token JWT do cliente.
     */
    public function logout(): JsonResponse
    {
        auth('client')->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    private function respondWithToken(string $token, Client $client, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('client')->factory()->getTTL() * 60,
            'client' => new ClientResource($client),
        ], $status);
    }
}
```

### Routes

Adicione as rotas em `backend/routes/api.php`.

No topo, adicione o import:

```php
use App\Http\Controllers\Api\V1\Auth\ClientAuthController;
```

Dentro do bloco `Route::prefix('v1')->group(function () {`, adicione apos as rotas publicas de admin auth:

```php
    // --- Rotas publicas de clientes ---
    Route::prefix('client/auth')->group(function () {
        Route::post('/register', [ClientAuthController::class, 'register']);
        Route::post('/login', [ClientAuthController::class, 'login']);
    });

    // --- Rotas protegidas de clientes (requer JWT guard "client") ---
    Route::middleware('auth:client')->prefix('client')->group(function () {
        Route::get('/auth/me', [ClientAuthController::class, 'me']);
        Route::post('/auth/logout', [ClientAuthController::class, 'logout']);
    });
```

> **Prefixo `/client/`:** Separa endpoints de clientes dos de admin. Endpoints finais:
> - `POST /api/v1/client/auth/register` (publico)
> - `POST /api/v1/client/auth/login` (publico)
> - `GET /api/v1/client/auth/me` (requer token de client)
> - `POST /api/v1/client/auth/logout` (requer token de client)

---

## Passo 8.5 - Migration: FK orders.client_id + tabela order_evaluations

### Migration para FK em orders.client_id

Crie `backend/database/migrations/0001_01_02_000013_add_client_fk_to_orders_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
    }
};
```

> **Nota:** A coluna `client_id` ja existe como `unsignedBigInteger` nullable (criada no Passo 7.2). Agora adicionamos apenas a FK constraint.

### Migration order_evaluations

Crie `backend/database/migrations/0001_01_02_000014_create_order_evaluations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('stars'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            // Um cliente so pode avaliar um pedido uma vez
            $table->unique(['order_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_evaluations');
    }
};
```

Rode as migrations:

```bash
docker compose exec backend php artisan migrate
```

Saida esperada:

```
Running migrations.
0001_01_02_000013_add_client_fk_to_orders_table ....... DONE
0001_01_02_000014_create_order_evaluations_table ...... DONE
```

> **Nota:** A migration `0001_01_02_000012_create_clients_table` ja foi executada no Passo 8.2.

---

## Passo 8.6 - Evaluation Model + Repository + Actions

### Model

Crie `backend/app/Models/OrderEvaluation.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'client_id',
        'stars',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
```

Adicione o relacionamento no `Order` Model (`backend/app/Models/Order.php`):

```php
use Illuminate\Database\Eloquent\Relations\HasOne;

// Adicionar ao Model:
public function evaluation(): HasOne
{
    return $this->hasOne(OrderEvaluation::class);
}
```

### Repository Interface

Crie `backend/app/Repositories/Contracts/EvaluationRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\OrderEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EvaluationRepositoryInterface
{
    public function paginateByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?OrderEvaluation;

    public function findByOrderAndClient(int $orderId, int $clientId): ?OrderEvaluation;

    public function create(array $data): OrderEvaluation;

    public function delete(int $id): bool;
}
```

### Repository Implementation

Crie `backend/app/Repositories/Eloquent/EvaluationRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\OrderEvaluation;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EvaluationRepository implements EvaluationRepositoryInterface
{
    public function __construct(
        private readonly OrderEvaluation $model,
    ) {}

    public function paginateByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['client', 'order'])
            ->whereHas('order', fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?OrderEvaluation
    {
        return $this->model->with(['client', 'order'])->find($id);
    }

    public function findByOrderAndClient(int $orderId, int $clientId): ?OrderEvaluation
    {
        return $this->model
            ->where('order_id', $orderId)
            ->where('client_id', $clientId)
            ->first();
    }

    public function create(array $data): OrderEvaluation
    {
        return $this->model->create($data);
    }

    public function delete(int $id): bool
    {
        $evaluation = $this->model->find($id);

        return $evaluation ? (bool) $evaluation->delete() : false;
    }
}
```

> **`paginateByTenant()`:** Avaliacoes nao tem `tenant_id` diretamente — filtramos pelo `tenant_id` do pedido via `whereHas`. Isso permite que o admin veja apenas avaliacoes dos pedidos do seu tenant.

### Registrar no Service Provider

Adicione o binding em `backend/app/Providers/RepositoryServiceProvider.php`:

1. Adicione os imports:

```php
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use App\Repositories\Eloquent\EvaluationRepository;
```

2. Adicione ao array `$repositories`:

```php
EvaluationRepositoryInterface::class => EvaluationRepository::class,
```

### DTO

Crie `backend/app/DTOs/Evaluation/CreateEvaluationDTO.php`:

```php
<?php

namespace App\DTOs\Evaluation;

use App\Http\Requests\Evaluation\StoreEvaluationRequest;

final readonly class CreateEvaluationDTO
{
    public function __construct(
        public int $orderId,
        public int $stars,
        public ?string $comment,
    ) {}

    public static function fromRequest(StoreEvaluationRequest $request): self
    {
        return new self(
            orderId: $request->validated('order_id'),
            stars: $request->validated('stars'),
            comment: $request->validated('comment'),
        );
    }
}
```

### Actions

Crie `backend/app/Actions/Evaluation/CreateEvaluationAction.php`:

```php
<?php

namespace App\Actions\Evaluation;

use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Models\Order;
use App\Models\OrderEvaluation;
use App\Repositories\Contracts\EvaluationRepositoryInterface;

final class CreateEvaluationAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    /**
     * @return OrderEvaluation|string Avaliacao criada ou mensagem de erro
     */
    public function execute(CreateEvaluationDTO $dto, int $clientId): OrderEvaluation|string
    {
        $order = Order::withoutGlobalScopes()->find($dto->orderId);

        if (!$order) {
            return 'Pedido nao encontrado.';
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return 'Apenas pedidos entregues podem ser avaliados.';
        }

        if ($order->client_id !== $clientId) {
            return 'Voce so pode avaliar seus proprios pedidos.';
        }

        $existing = $this->repository->findByOrderAndClient($dto->orderId, $clientId);

        if ($existing) {
            return 'Voce ja avaliou este pedido.';
        }

        return $this->repository->create([
            'order_id' => $dto->orderId,
            'client_id' => $clientId,
            'stars' => $dto->stars,
            'comment' => $dto->comment,
        ]);
    }
}
```

> **Validacoes de negocio na Action:** Verificamos status do pedido, propriedade do cliente, e unicidade — tudo antes de criar. Essas regras nao pertencem ao FormRequest porque dependem de estado do banco.

Crie `backend/app/Actions/Evaluation/ListEvaluationsAction.php`:

```php
<?php

namespace App\Actions\Evaluation;

use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListEvaluationsAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    public function execute(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByTenant($tenantId, $perPage);
    }
}
```

Crie `backend/app/Actions/Evaluation/DeleteEvaluationAction.php`:

```php
<?php

namespace App\Actions\Evaluation;

use App\Repositories\Contracts\EvaluationRepositoryInterface;

final class DeleteEvaluationAction
{
    public function __construct(
        private readonly EvaluationRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

---

## Passo 8.7 - Evaluation Controller + Routes + FormRequests + Resource

### FormRequest

Crie `backend/app/Http/Requests/Evaluation/StoreEvaluationRequest.php`:

```php
<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'stars.min' => 'A nota minima e 1 estrela.',
            'stars.max' => 'A nota maxima e 5 estrelas.',
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/EvaluationResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'client' => new ClientResource($this->whenLoaded('client')),
            'order' => [
                'id' => $this->order->id,
                'identify' => $this->order->identify,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### Controller (Client-side — criar avaliacao)

Crie `backend/app/Http/Controllers/Api/V1/ClientEvaluationController.php`:

```php
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
```

### Controller (Admin-side — listar e deletar avaliacoes)

Crie `backend/app/Http/Controllers/Api/V1/EvaluationController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Actions\Evaluation\ListEvaluationsAction;
use App\Actions\Evaluation\DeleteEvaluationAction;
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

        if (!$deleted) {
            return response()->json(['message' => 'Avaliacao nao encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Avaliacao removida com sucesso.',
        ]);
    }
}
```

Atualize o `EvaluationRepository::paginateByTenant()` para suportar super-admin (tenantId = 0):

No metodo `paginateByTenant`, ajuste:

```php
public function paginateByTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
{
    $query = $this->model->with(['client', 'order'])->latest();

    if ($tenantId > 0) {
        $query->whereHas('order', fn ($q) => $q->where('tenant_id', $tenantId));
    }

    return $query->paginate($perPage);
}
```

### Routes

Adicione os imports no topo de `backend/routes/api.php` (o `ClientAuthController` ja foi adicionado no Passo 8.4):

```php
use App\Http\Controllers\Api\V1\ClientEvaluationController;
use App\Http\Controllers\Api\V1\EvaluationController;
```

Rotas de clientes (ja adicionadas no Passo 8.4). Agora adicione a rota de avaliacao do cliente dentro do grupo `auth:client`:

```php
    // --- Rotas protegidas de clientes (requer JWT guard "client") ---
    Route::middleware('auth:client')->prefix('client')->group(function () {
        Route::get('/auth/me', [ClientAuthController::class, 'me']);
        Route::post('/auth/logout', [ClientAuthController::class, 'logout']);

        // Avaliacoes (cliente cria)
        Route::post('/evaluations', [ClientEvaluationController::class, 'store']);
    });
```

Adicione as rotas admin de avaliacoes dentro do grupo `tenant:required`:

```php
            // Evaluations (admin visualiza e remove)
            Route::get('evaluations', [EvaluationController::class, 'index'])
                ->middleware('permission:orders.view');
            Route::delete('evaluations/{evaluation}', [EvaluationController::class, 'destroy'])
                ->middleware('permission:orders.delete');
```

---

## Passo 8.8 - Seeders + teste da API

### Client Seeder

Crie `backend/database/seeders/ClientSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            ['name' => 'Joao Silva', 'email' => 'joao@email.com', 'password' => 'password'],
            ['name' => 'Maria Santos', 'email' => 'maria@email.com', 'password' => 'password'],
            ['name' => 'Pedro Oliveira', 'email' => 'pedro@email.com', 'password' => 'password'],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['email' => $data['email']],
                $data,
            );
        }

        $this->command->info('Clientes criados: joao@email.com, maria@email.com, pedro@email.com');
    }
}
```

### Evaluation Seeder

Crie `backend/database/seeders/EvaluationSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderEvaluation;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $joao = Client::where('email', 'joao@email.com')->first();
        $maria = Client::where('email', 'maria@email.com')->first();

        if (!$joao || !$maria) {
            $this->command->warn('Clientes nao encontrados. Rode ClientSeeder primeiro.');
            return;
        }

        // Vincular clientes aos pedidos entregues
        $order1 = Order::where('identify', 'ORD-000001')->first(); // delivered

        if (!$order1 || $order1->status !== 'delivered') {
            $this->command->warn('Pedido ORD-000001 nao encontrado ou nao esta entregue.');
            return;
        }

        // Vincular client_id ao pedido
        $order1->update(['client_id' => $joao->id]);

        // Avaliacao do Joao para o pedido 1
        OrderEvaluation::firstOrCreate(
            ['order_id' => $order1->id, 'client_id' => $joao->id],
            [
                'stars' => 5,
                'comment' => 'Pizza excelente! Entrega rapida.',
            ],
        );

        $this->command->info('Avaliacoes criadas com sucesso.');
    }
}
```

Rode os seeders:

```bash
docker compose exec backend php artisan db:seed --class=ClientSeeder
docker compose exec backend php artisan db:seed --class=EvaluationSeeder
```

### Teste da API

**Registrar um novo cliente:**

```bash
curl -s -X POST http://localhost/api/v1/client/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ana Costa",
    "email": "ana@email.com",
    "password": "password",
    "password_confirmation": "password"
  }' | python3 -m json.tool
```

Saida esperada:

```json
{
    "access_token": "eyJ...",
    "token_type": "bearer",
    "expires_in": 3600,
    "client": {
        "id": 4,
        "uuid": "...",
        "name": "Ana Costa",
        "email": "ana@email.com",
        ...
    }
}
```

**Login como cliente existente:**

```bash
CLIENT_TOKEN=$(curl -s -X POST http://localhost/api/v1/client/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"joao@email.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")
```

**Dados do cliente autenticado:**

```bash
curl -s http://localhost/api/v1/client/auth/me \
  -H "Authorization: Bearer $CLIENT_TOKEN" | python3 -m json.tool
```

**Criar avaliacao (como Joao, dono do pedido ORD-000001):**

```bash
curl -s -X POST http://localhost/api/v1/client/evaluations \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 1, "stars": 4, "comment": "Muito bom!"}' | python3 -m json.tool
```

> **Nota:** Se o seeder ja criou a avaliacao, esse curl retornara `"Voce ja avaliou este pedido."` (422).

**Listar avaliacoes (como admin):**

```bash
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s http://localhost/api/v1/evaluations \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

---

## Passo 8.9 - Frontend: tipos TypeScript + servicos

### Tipos

Crie `frontend/src/types/evaluation.ts`:

```typescript
export interface Evaluation {
  id: number;
  stars: number;
  comment: string | null;
  client: {
    id: number;
    uuid: string;
    name: string;
    email: string;
  };
  order: {
    id: number;
    identify: string;
  };
  created_at: string;
}
```

### Servico

Crie `frontend/src/services/evaluation-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Evaluation } from "@/types/evaluation";
import type { PaginatedResponse } from "@/types/plan";

export async function getEvaluations(
  page = 1
): Promise<PaginatedResponse<Evaluation>> {
  return apiClient<PaginatedResponse<Evaluation>>(
    `/v1/evaluations?page=${page}`
  );
}

export async function deleteEvaluation(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/evaluations/${id}`, {
    method: "DELETE",
  });
}
```

---

## Passo 8.10 - Frontend: pagina de Avaliacoes (admin)

Crie `frontend/src/app/(admin)/reviews/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getEvaluations, deleteEvaluation } from "@/services/evaluation-service";
import type { Evaluation } from "@/types/evaluation";
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
import { Star, Trash2 } from "lucide-react";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

function StarRating({ stars }: { stars: number }) {
  return (
    <div className="flex gap-0.5">
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          className={`h-4 w-4 ${
            i < stars
              ? "fill-yellow-400 text-yellow-400"
              : "text-gray-300"
          }`}
        />
      ))}
    </div>
  );
}

export default function ReviewsPage() {
  const [evaluations, setEvaluations] = useState<Evaluation[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchEvaluations = async () => {
    try {
      const response = await getEvaluations();
      setEvaluations(response.data);
    } catch (error) {
      console.error("Erro ao carregar avaliacoes:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEvaluations();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("Tem certeza que deseja remover esta avaliacao?")) return;

    try {
      await deleteEvaluation(id);
      fetchEvaluations();
    } catch (error) {
      console.error("Erro ao remover avaliacao:", error);
    }
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="avaliacoes" />

      <h1 className="text-2xl font-bold">Avaliacoes</h1>

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
              <TableHead>Pedido</TableHead>
              <TableHead>Cliente</TableHead>
              <TableHead>Nota</TableHead>
              <TableHead>Comentario</TableHead>
              <TableHead>Data</TableHead>
              <TableHead className="w-[60px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {evaluations.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground">
                  Nenhuma avaliacao encontrada.
                </TableCell>
              </TableRow>
            ) : (
              evaluations.map((evaluation) => (
                <TableRow key={evaluation.id}>
                  <TableCell className="font-mono font-medium">
                    {evaluation.order.identify}
                  </TableCell>
                  <TableCell>{evaluation.client.name}</TableCell>
                  <TableCell>
                    <StarRating stars={evaluation.stars} />
                  </TableCell>
                  <TableCell className="max-w-xs truncate text-muted-foreground">
                    {evaluation.comment || "—"}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm">
                    {new Date(evaluation.created_at).toLocaleDateString("pt-BR")}
                  </TableCell>
                  <TableCell>
                    <Button
                      size="icon"
                      variant="ghost"
                      title="Remover"
                      onClick={() => handleDelete(evaluation.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
```

### Sidebar ja configurada

A sidebar ja tem o link "Avaliacoes" no grupo **Operacao** (configurado no Passo 5.12). Nenhuma alteracao necessaria.

---

## Passo 8.11 - Frontend: Client Auth Store + paginas de login/cadastro/pedidos

Ate agora, o login em `/login` e exclusivo para **admins/gerentes** (guard `api`). Os **clientes** precisam de um fluxo separado com guard `client`, cookie proprio e paginas dedicadas.

### Store de autenticacao do cliente

Crie `frontend/src/stores/client-auth-store.ts`:

```ts
import { create } from "zustand";
import { persist } from "zustand/middleware";
import { ApiError } from "@/lib/api";

// Cookie separado para nao conflitar com o admin
function setClientTokenCookie(token: string) {
    document.cookie = `client_token=${token}; path=/; max-age=${60 * 60}; SameSite=Lax`;
}

function removeClientTokenCookie() {
    document.cookie = "client_token=; path=/; max-age=0";
}

interface ClientUser {
    id: number;
    uuid: string;
    name: string;
    email: string;
}

interface ClientAuthState {
    token: string | null;
    client: ClientUser | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (name: string, email: string, password: string) => Promise<void>;
    logout: () => Promise<void>;
    fetchClient: () => Promise<void>;
    clear: () => void;
}

interface ClientLoginResponse {
    access_token: string;
    token_type: string;
    expires_in: number;
    client: ClientUser;
}

interface ClientMeResponse {
    data: ClientUser;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || "/api";

/** Fetch wrapper que usa o token do cliente (guard client) */
async function clientApi<T>(
    endpoint: string,
    token: string | null,
    options: RequestInit = {},
): Promise<T> {
    const headers: Record<string, string> = {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(options.headers as Record<string, string>),
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`${API_URL}${endpoint}`, {
        ...options,
        headers,
    });

    if (!response.ok) {
        const data = await response.json().catch(() => null);
        throw new ApiError(
            response.status,
            data?.message || "Erro na requisicao",
            data,
        );
    }

    return response.json();
}

export const useClientAuthStore = create<ClientAuthState>()(
    persist(
        (set, get) => ({
            token: null,
            client: null,
            isAuthenticated: false,
            isLoading: false,

            login: async (email: string, password: string) => {
                set({ isLoading: true });
                try {
                    const response = await clientApi<ClientLoginResponse>(
                        "/v1/client/auth/login",
                        null,
                        {
                            method: "POST",
                            body: JSON.stringify({ email, password }),
                        },
                    );

                    setClientTokenCookie(response.access_token);
                    set({
                        token: response.access_token,
                        client: response.client,
                        isAuthenticated: true,
                        isLoading: false,
                    });
                } catch (error) {
                    set({ isLoading: false });
                    throw error;
                }
            },

            register: async (
                name: string,
                email: string,
                password: string,
            ) => {
                set({ isLoading: true });
                try {
                    const response = await clientApi<ClientLoginResponse>(
                        "/v1/client/auth/register",
                        null,
                        {
                            method: "POST",
                            body: JSON.stringify({ name, email, password }),
                        },
                    );

                    setClientTokenCookie(response.access_token);
                    set({
                        token: response.access_token,
                        client: response.client,
                        isAuthenticated: true,
                        isLoading: false,
                    });
                } catch (error) {
                    set({ isLoading: false });
                    throw error;
                }
            },

            fetchClient: async () => {
                try {
                    const response = await clientApi<ClientMeResponse>(
                        "/v1/client/auth/me",
                        get().token,
                    );
                    set({ client: response.data });
                } catch {
                    get().clear();
                }
            },

            logout: async () => {
                try {
                    await clientApi("/v1/client/auth/logout", get().token, {
                        method: "POST",
                    });
                } catch {
                    // Limpar mesmo se der erro no backend
                }
                get().clear();
            },

            clear: () => {
                removeClientTokenCookie();
                set({
                    token: null,
                    client: null,
                    isAuthenticated: false,
                });
            },
        }),
        {
            name: "client-auth-storage",
            partialize: (state) => ({ token: state.token }),
        },
    ),
);
```

> **Por que um store separado?** O `apiClient` global em `lib/api.ts` le o token do `auth-storage` (admin). Se reutilizassemos o mesmo store, o token do cliente sobrescreveria o do admin (e vice-versa). Com stores separados (`auth-storage` vs `client-auth-storage`) e cookies separados (`token` vs `client_token`), as sessoes sao completamente independentes.

### Pagina de login do cliente

Crie `frontend/src/app/client/login/page.tsx`:

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useClientAuthStore } from "@/stores/client-auth-store";
import { ApiError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const loginSchema = z.object({
    email: z.string().email("Informe um email valido"),
    password: z.string().min(6, "A senha deve ter no minimo 6 caracteres"),
});

type LoginForm = z.infer<typeof loginSchema>;

export default function ClientLoginPage() {
    const router = useRouter();
    const { login, isLoading } = useClientAuthStore();
    const [error, setError] = useState<string | null>(null);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<LoginForm>({
        resolver: zodResolver(loginSchema),
    });

    const onSubmit = async (data: LoginForm) => {
        setError(null);
        try {
            await login(data.email, data.password);
            router.push("/client/pedidos");
        } catch (err) {
            if (err instanceof ApiError) {
                setError(err.message);
            } else {
                setError("Erro ao conectar com o servidor.");
            }
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-muted/50 px-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl font-bold">
                        Orderly
                    </CardTitle>
                    <CardDescription>
                        Acesse sua conta de cliente
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={handleSubmit(onSubmit)}
                        className="space-y-4"
                    >
                        {error && (
                            <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                                {error}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="joao@email.com"
                                {...register("email")}
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Senha</Label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="••••••••"
                                {...register("password")}
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">
                                    {errors.password.message}
                                </p>
                            )}
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={isLoading}
                        >
                            {isLoading ? "Entrando..." : "Entrar"}
                        </Button>
                    </form>
                </CardContent>
                <CardFooter className="justify-center">
                    <p className="text-sm text-muted-foreground">
                        Nao tem conta?{" "}
                        <Link
                            href="/client/register"
                            className="text-primary underline-offset-4 hover:underline"
                        >
                            Cadastre-se
                        </Link>
                    </p>
                </CardFooter>
            </Card>
        </div>
    );
}
```

### Pagina de cadastro do cliente

Crie `frontend/src/app/client/register/page.tsx`:

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useClientAuthStore } from "@/stores/client-auth-store";
import { ApiError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const registerSchema = z
    .object({
        name: z.string().min(3, "O nome deve ter no minimo 3 caracteres"),
        email: z.string().email("Informe um email valido"),
        password: z
            .string()
            .min(6, "A senha deve ter no minimo 6 caracteres"),
        password_confirmation: z.string(),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: "As senhas nao conferem",
        path: ["password_confirmation"],
    });

type RegisterForm = z.infer<typeof registerSchema>;

export default function ClientRegisterPage() {
    const router = useRouter();
    const { register: registerClient, isLoading } = useClientAuthStore();
    const [error, setError] = useState<string | null>(null);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<RegisterForm>({
        resolver: zodResolver(registerSchema),
    });

    const onSubmit = async (data: RegisterForm) => {
        setError(null);
        try {
            await registerClient(data.name, data.email, data.password);
            router.push("/client/pedidos");
        } catch (err) {
            if (err instanceof ApiError) {
                if (
                    err.data &&
                    typeof err.data === "object" &&
                    "errors" in err.data
                ) {
                    const validationErrors = err.data as {
                        errors: Record<string, string[]>;
                    };
                    const firstError =
                        Object.values(validationErrors.errors)[0];
                    setError(firstError?.[0] || err.message);
                } else {
                    setError(err.message);
                }
            } else {
                setError("Erro ao conectar com o servidor.");
            }
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-muted/50 px-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl font-bold">
                        Orderly
                    </CardTitle>
                    <CardDescription>
                        Crie sua conta de cliente
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={handleSubmit(onSubmit)}
                        className="space-y-4"
                    >
                        {error && (
                            <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                                {error}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                type="text"
                                placeholder="Seu nome completo"
                                {...register("name")}
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">
                                    {errors.name.message}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="seu@email.com"
                                {...register("email")}
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Senha</Label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="••••••••"
                                {...register("password")}
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">
                                    {errors.password.message}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">
                                Confirmar Senha
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                {...register("password_confirmation")}
                            />
                            {errors.password_confirmation && (
                                <p className="text-sm text-destructive">
                                    {errors.password_confirmation.message}
                                </p>
                            )}
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={isLoading}
                        >
                            {isLoading ? "Cadastrando..." : "Cadastrar"}
                        </Button>
                    </form>
                </CardContent>
                <CardFooter className="justify-center">
                    <p className="text-sm text-muted-foreground">
                        Ja tem conta?{" "}
                        <Link
                            href="/client/login"
                            className="text-primary underline-offset-4 hover:underline"
                        >
                            Entrar
                        </Link>
                    </p>
                </CardFooter>
            </Card>
        </div>
    );
}
```

### Middleware — proteger rotas do cliente

Atualize `frontend/src/middleware.ts` para suportar as rotas de cliente com cookie separado:

```ts
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
    const { pathname } = request.nextUrl;

    // --- Rotas de cliente ---
    const isClientLoginPage = pathname === "/client/login";
    const isClientRegisterPage = pathname === "/client/register";
    const isClientProtectedRoute =
        pathname.startsWith("/client/") &&
        !isClientLoginPage &&
        !isClientRegisterPage;

    if (
        isClientProtectedRoute ||
        isClientLoginPage ||
        isClientRegisterPage
    ) {
        const clientToken = request.cookies.get("client_token")?.value;

        if (isClientProtectedRoute && !clientToken) {
            return NextResponse.redirect(
                new URL("/client/login", request.url),
            );
        }

        if ((isClientLoginPage || isClientRegisterPage) && clientToken) {
            return NextResponse.redirect(
                new URL("/client/pedidos", request.url),
            );
        }

        return NextResponse.next();
    }

    // --- Rotas de admin (sem alteracoes) ---
    const token = request.cookies.get("token")?.value;

    const isLoginPage = pathname === "/login";
    const isProtectedRoute =
        pathname.startsWith("/dashboard") ||
        pathname.startsWith("/plans") ||
        pathname.startsWith("/profiles") ||
        pathname.startsWith("/roles") ||
        pathname.startsWith("/orders") ||
        pathname.startsWith("/products") ||
        pathname.startsWith("/customers") ||
        pathname.startsWith("/tables") ||
        pathname.startsWith("/reviews") ||
        pathname.startsWith("/settings");

    if (isProtectedRoute && !token) {
        return NextResponse.redirect(new URL("/login", request.url));
    }

    if (isLoginPage && token) {
        return NextResponse.redirect(new URL("/dashboard", request.url));
    }

    return NextResponse.next();
}

export const config = {
    matcher: [
        "/dashboard/:path*",
        "/plans/:path*",
        "/profiles/:path*",
        "/roles/:path*",
        "/orders/:path*",
        "/products/:path*",
        "/customers/:path*",
        "/tables/:path*",
        "/reviews/:path*",
        "/settings/:path*",
        "/login",
        "/client/:path*",
    ],
};
```

> **Importante:** O middleware usa `client_token` (cookie separado do `token` do admin). Isso permite que um usuario esteja logado como admin e como cliente simultaneamente em abas diferentes.

### Pagina de pedidos do cliente (placeholder)

Crie `frontend/src/app/client/pedidos/page.tsx`:

```tsx
"use client";

import { useRouter } from "next/navigation";
import { useClientAuthStore } from "@/stores/client-auth-store";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { LogOut, User } from "lucide-react";

export default function ClientPedidosPage() {
    const router = useRouter();
    const { client, logout } = useClientAuthStore();

    const handleLogout = async () => {
        await logout();
        router.push("/client/login");
    };

    return (
        <div className="min-h-screen bg-muted/50">
            <header className="border-b bg-background">
                <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
                    <h1 className="text-lg font-bold">Orderly</h1>
                    <div className="flex items-center gap-3">
                        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <User className="h-4 w-4" />
                            {client?.name}
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={handleLogout}
                        >
                            <LogOut className="mr-1.5 h-4 w-4" />
                            Sair
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-4xl px-4 py-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Meus Pedidos</CardTitle>
                        <CardDescription>
                            Acompanhe seus pedidos e avalie apos a entrega.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Nenhum pedido encontrado. Em breve voce podera
                            acompanhar seus pedidos por aqui.
                        </p>
                    </CardContent>
                </Card>
            </main>
        </div>
    );
}
```

> **Nota:** Esta e uma pagina placeholder. A listagem real de pedidos do cliente sera implementada em uma fase futura, integrando com a API `GET /client/orders`.

---

## Passo 8.12 - Verificacao end-to-end da Fase 8

### Checklist de verificacao

**Backend — Autenticacao de Clientes:**

- [ ] Migration `create_clients_table` rodou sem erros
- [ ] Guard `client` configurado em `config/auth.php` com provider `clients`
- [ ] Model `Client` extende `Authenticatable`, implementa `JWTSubject`
- [ ] `ClientObserver` gera UUID
- [ ] Endpoints: register, login, me, logout em `/api/v1/client/auth/*`
- [ ] Token JWT do client tem claim `guard: client`
- [ ] Registro cria conta + retorna token
- [ ] Login autentica + retorna token
- [ ] Guard `client` e `api` sao independentes (tokens nao sao intercambiaveis)

**Backend — Avaliacoes:**

- [ ] Migrations `add_client_fk_to_orders` e `create_order_evaluations_table` rodaram
- [ ] `OrderEvaluation` model com relacionamentos `order()` e `client()`
- [ ] `Order` model tem `client()` (BelongsTo) e `evaluation()` (HasOne)
- [ ] Unique constraint `[order_id, client_id]` impede avaliacoes duplicadas
- [ ] `CreateEvaluationAction` valida: status delivered, propriedade do cliente, unicidade
- [ ] Rota `POST /client/evaluations` requer guard `client`
- [ ] Rota `GET /evaluations` requer guard `api` + permission `orders.view`
- [ ] `EvaluationRepository::paginateByTenant()` filtra via `whereHas('order', ...)`
- [ ] Seeder cria clientes e avaliacao de exemplo

**Frontend — Avaliacoes (admin):**

- [ ] Tipos `Evaluation` em `evaluation.ts`
- [ ] Servico `evaluation-service.ts` com listagem e exclusao
- [ ] Pagina `/reviews` lista avaliacoes com estrelas visuais
- [ ] `TenantRequiredAlert` aparece para super-admin
- [ ] Swagger mostra tags "Auth Cliente" e "Avaliacoes"

**Frontend — Area do Cliente:**

- [ ] Store `client-auth-store.ts` com login, register, logout (cookie `client_token`)
- [ ] Pagina `/client/login` funciona com guard `client`
- [ ] Pagina `/client/register` cria conta e redireciona para `/client/pedidos`
- [ ] Link entre login e register funciona
- [ ] Middleware redireciona rotas `/client/*` protegidas para `/client/login`
- [ ] Middleware redireciona `/client/login` para `/client/pedidos` se ja autenticado
- [ ] Cookies `token` (admin) e `client_token` (cliente) sao independentes

### Fluxo completo de teste

1. **Registrar cliente:** `POST /client/auth/register` com nome, email, senha
2. **Login cliente:** `POST /client/auth/login` → obter token
3. **Criar pedido como gerente** (se necessario) e marcar como `delivered`
4. **Criar avaliacao como cliente:** `POST /client/evaluations` com token de client
5. **Tentar avaliar novamente** → erro 422 "Voce ja avaliou este pedido."
6. **Listar avaliacoes como admin:** `GET /evaluations` com token de admin
7. **No frontend**, acessar `/reviews` e verificar a tabela com estrelas
8. **No frontend**, acessar `/client/login` e logar com `joao@email.com` / `password`
9. **No frontend**, acessar `/client/register` e criar nova conta de cliente

### Resumo dos arquivos da Fase 8

**Backend:**

```
backend/
├── config/auth.php (modificado — guard client + provider clients)
├── database/
│   ├── migrations/
│   │   ├── 0001_01_02_000012_create_clients_table.php
│   │   ├── 0001_01_02_000013_add_client_fk_to_orders_table.php
│   │   └── 0001_01_02_000014_create_order_evaluations_table.php
│   └── seeders/
│       ├── ClientSeeder.php
│       └── EvaluationSeeder.php
├── app/
│   ├── Models/
│   │   ├── Client.php
│   │   ├── OrderEvaluation.php
│   │   └── Order.php (modificado — client() + evaluation())
│   ├── Observers/ClientObserver.php
│   ├── Repositories/
│   │   ├── Contracts/EvaluationRepositoryInterface.php
│   │   └── Eloquent/EvaluationRepository.php
│   ├── DTOs/Evaluation/CreateEvaluationDTO.php
│   ├── Actions/Evaluation/
│   │   ├── CreateEvaluationAction.php
│   │   ├── ListEvaluationsAction.php
│   │   └── DeleteEvaluationAction.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── Auth/ClientAuthController.php
│   │   │   ├── ClientEvaluationController.php
│   │   │   └── EvaluationController.php
│   │   ├── Requests/
│   │   │   ├── ClientAuth/
│   │   │   │   ├── RegisterClientRequest.php
│   │   │   │   └── LoginClientRequest.php
│   │   │   └── Evaluation/StoreEvaluationRequest.php
│   │   └── Resources/
│   │       ├── ClientResource.php
│   │       └── EvaluationResource.php
│   └── Providers/RepositoryServiceProvider.php (modificado)
└── routes/api.php (modificado)
```

**Frontend:**

```
frontend/src/
├── stores/client-auth-store.ts
├── types/evaluation.ts
├── services/evaluation-service.ts
├── middleware.ts (modificado — rotas /client/* + cookie client_token)
├── app/
│   ├── (admin)/reviews/page.tsx
│   └── client/
│       ├── login/page.tsx
│       ├── register/page.tsx
│       └── pedidos/page.tsx
```

**Conceitos aprendidos:**
- **Multi-guard JWT** — dois guards (`api` para admin, `client` para cliente) com providers e models separados
- **`JWTSubject` em multiplos models** — tanto `User` quanto `Client` implementam a interface
- **Custom claim `guard`** — diferencia tokens de admin e client no payload JWT
- **Registro publico** — endpoint sem autenticacao que cria conta + retorna token em uma unica chamada
- **`whereHas()` para filtro indireto** — avaliacoes nao tem `tenant_id`, mas filtramos pelo `tenant_id` do pedido relacionado
- **Validacao de negocio na Action** — status do pedido, propriedade do cliente, unicidade — regras que dependem de estado do banco
- **Unique composite constraint** — `[order_id, client_id]` impede avaliacoes duplicadas a nivel de banco
- **Componente `StarRating`** — renderizacao condicional de icones SVG com classes Tailwind
- **Stores separados (Zustand)** — `auth-storage` para admin e `client-auth-storage` para cliente, evitando conflito de tokens
- **Cookies separados no middleware** — `token` (admin) e `client_token` (cliente) permitem sessoes independentes no Next.js middleware (server-side)

**Proximo:** Fase 9 - Dashboard com Metricas

---


---

[Voltar ao README](../README.md)
