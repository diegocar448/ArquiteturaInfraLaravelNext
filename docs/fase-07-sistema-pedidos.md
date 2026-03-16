# Fase 7 - Sistema de Pedidos

Nesta fase vamos implementar o **Sistema de Pedidos** — o core operacional do restaurante. Um pedido agrega produtos com quantidades e precos, opcionalmente vinculado a uma mesa, e segue um fluxo de status desde a criacao ate a entrega.

**O que vamos construir:**
- Migrations: tabela `orders` + pivot `order_product` (com `qty` e `price`)
- Model com Observer (UUID + identify auto-gerado), relacionamentos N:N com Products
- Repository + Actions (Clean Architecture) com logica de status
- Controller com endpoints de CRUD + transicao de status
- Seeder com pedidos de exemplo
- Frontend: pagina de pedidos com listagem, filtro por status, criacao e transicao

**Dependencia:** Fase 6 concluida (mesas com QR Code).

---

## Passo 7.1 - Conceito: Pedidos e fluxo de status

### O que e um Pedido no sistema?

Um **Pedido** (order) representa uma solicitacao feita em uma mesa do restaurante. Cada pedido pertence a um tenant e pode ter:

| Campo | Tipo | Descricao |
|---|---|---|
| `id` | bigint | PK auto-increment (interno) |
| `tenant_id` | FK → tenants | Isolamento multi-tenant |
| `uuid` | uuid | Identificador publico |
| `identify` | string | Codigo legivel auto-gerado ("ORD-000001") |
| `table_id` | FK? → tables | Mesa (opcional — pedido para delivery) |
| `client_id` | int? | Cliente (sera FK na Fase 8) |
| `status` | string | Status atual do pedido |
| `total` | decimal(10,2) | Valor total calculado |
| `comment` | text? | Observacoes do cliente |

### Tabela pivot `order_product`

Diferente de `category_product` (que so vincula IDs), a pivot de pedidos armazena dados extras:

| Campo | Tipo | Descricao |
|---|---|---|
| `order_id` | FK → orders | Pedido |
| `product_id` | FK → products | Produto |
| `qty` | int | Quantidade solicitada |
| `price` | decimal(10,2) | Preco unitario **no momento do pedido** |

> **Por que guardar `price` na pivot?** Se o produto mudar de preco depois, o pedido historico deve manter o valor original. Isso se chama **price snapshot** — um padrao essencial em sistemas de e-commerce.

### Fluxo de status

```
┌──────────┐    ┌──────────┐    ┌───────────┐    ┌──────┐    ┌───────────┐
│  open    │───►│ accepted │───►│ preparing │───►│ done │───►│ delivered │
└──────────┘    └──────────┘    └───────────┘    └──────┘    └───────────┘
      │
      └──────────────────────────────────────────────────────►┌──────────┐
                                                              │ rejected │
                                                              └──────────┘
```

| Status | Descricao | Quem transiciona |
|---|---|---|
| `open` | Pedido criado, aguardando aceite | Sistema (automatico) |
| `accepted` | Pedido aceito pelo restaurante | Gerente/garcom |
| `rejected` | Pedido recusado | Gerente |
| `preparing` | Em preparo na cozinha | Cozinha |
| `done` | Pronto para servir | Cozinha |
| `delivered` | Entregue ao cliente | Garcom |

### Transicoes validas

Nem toda transicao e permitida. O sistema valida:

```php
$validTransitions = [
    'open' => ['accepted', 'rejected'],
    'accepted' => ['preparing', 'rejected'],
    'preparing' => ['done'],
    'done' => ['delivered'],
];
```

Exemplos de transicoes **invalidas**: `open → done`, `delivered → open`, `rejected → accepted`.

---

## Passo 7.2 - Migration: tabela orders + order_product

### Migration orders

Crie `backend/database/migrations/0001_01_02_000010_create_orders_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('identify')->index(); // "ORD-000001"
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('client_id')->nullable(); // FK sera adicionada na Fase 8
            $table->string('status')->default('open')->index();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            // Identify unico por tenant
            $table->unique(['tenant_id', 'identify']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

> **Nota sobre `client_id`:** Por enquanto e um `unsignedBigInteger` sem FK constraint. Na Fase 8 (Autenticacao de Clientes), criaremos a tabela `clients` e adicionaremos a FK via migration separada.

### Migration order_product

Crie `backend/database/migrations/0001_01_02_000011_create_order_product_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('price', 10, 2); // price snapshot

            $table->primary(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product');
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
0001_01_02_000010_create_orders_table ......... DONE
0001_01_02_000011_create_order_product_table ... DONE
```

---

## Passo 7.3 - Order Model + Observer + relacionamentos

### Model

Crie `backend/app/Models/Order.php`:

```php
<?php

namespace App\Models;

use App\Observers\OrderObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use HasFactory, BelongsToTenant;

    const STATUS_OPEN = 'open';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PREPARING = 'preparing';
    const STATUS_DONE = 'done';
    const STATUS_DELIVERED = 'delivered';

    const VALID_TRANSITIONS = [
        self::STATUS_OPEN => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
        self::STATUS_ACCEPTED => [self::STATUS_PREPARING, self::STATUS_REJECTED],
        self::STATUS_PREPARING => [self::STATUS_DONE],
        self::STATUS_DONE => [self::STATUS_DELIVERED],
    ];

    const ALL_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_PREPARING,
        self::STATUS_DONE,
        self::STATUS_DELIVERED,
    ];

    protected $fillable = [
        'tenant_id',
        'table_id',
        'client_id',
        'status',
        'total',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['qty', 'price']);
    }

    public function calculateTotal(): string
    {
        return $this->products->sum(function ($product) {
            return $product->pivot->qty * $product->pivot->price;
        });
    }
}
```

**Pontos importantes:**
- **Constantes de status** — centralizadas no Model para evitar strings magicas
- **`VALID_TRANSITIONS`** — mapa de transicoes permitidas, consultado por `canTransitionTo()`
- **`withPivot(['qty', 'price'])`** — instrui o Eloquent a carregar os campos extras da pivot
- **`calculateTotal()`** — soma `qty * price` de todos os produtos do pedido

### Observer

Crie `backend/app/Observers/OrderObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderObserver
{
    public function creating(Order $order): void
    {
        if (empty($order->uuid)) {
            $order->uuid = (string) Str::uuid();
        }

        if (empty($order->identify)) {
            $order->identify = $this->generateIdentify($order);
        }
    }

    private function generateIdentify(Order $order): string
    {
        $lastOrder = Order::withoutGlobalScopes()
            ->where('tenant_id', $order->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastOrder ? ((int) Str::after($lastOrder->identify, 'ORD-')) + 1 : 1;

        return 'ORD-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
```

> **`withoutGlobalScopes()`** — necessario porque o `TenantScope` filtraria apenas o tenant atual. Precisamos consultar todos os pedidos do tenant para gerar o proximo numero sequencial. Usamos `where('tenant_id', ...)` explicitamente.

### Testar no tinker

```bash
docker compose exec backend php artisan tinker
```

```php
$gerente = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($gerente);

$order = App\Models\Order::create([
    'table_id' => App\Models\Table::first()->id,
    'status' => 'open',
    'comment' => 'Sem cebola na pizza',
]);

echo "UUID: {$order->uuid}, Identify: {$order->identify}, Status: {$order->status}";
// UUID: 550e8400-..., Identify: ORD-000001, Status: open

echo $order->canTransitionTo('accepted') ? 'SIM' : 'NAO'; // SIM
echo $order->canTransitionTo('done') ? 'SIM' : 'NAO';     // NAO

// Limpar
$order->delete();
auth('api')->forgetUser();
exit;
```

---

## Passo 7.4 - Order Repository + CRUD completo

### Interface

Crie `backend/app/Repositories/Contracts/OrderRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $status = null): LengthAwarePaginator;

    public function findById(int $id): ?Order;

    public function findByUuid(string $uuid): ?Order;

    public function create(array $data): Order;

    public function update(int $id, array $data): ?Order;

    public function delete(int $id): bool;
}
```

> **Novidade:** `paginate()` aceita `$status` opcional para filtrar pedidos por status.

### Implementacao

Crie `backend/app/Repositories/Eloquent/OrderRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly Order $model,
    ) {}

    public function paginate(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = $this->model->with(['products', 'table'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Order
    {
        return $this->model->with(['products', 'table'])->find($id);
    }

    public function findByUuid(string $uuid): ?Order
    {
        return $this->model->with(['products', 'table'])->where('uuid', $uuid)->first();
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Order
    {
        $order = $this->model->find($id);

        if (!$order) {
            return null;
        }

        $order->update($data);

        return $order->fresh(['products', 'table']);
    }

    public function delete(int $id): bool
    {
        $order = $this->model->find($id);

        if (!$order) {
            return false;
        }

        return (bool) $order->delete();
    }
}
```

> **Eager loading:** `with(['products', 'table'])` carrega os relacionamentos automaticamente, evitando N+1 queries na listagem.

### Registrar no Service Provider

Adicione o binding em `backend/app/Providers/RepositoryServiceProvider.php`.

1. Adicione os imports no topo do arquivo:

```php
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Eloquent\OrderRepository;
```

2. Adicione a entrada no array `$repositories`:

```php
private array $repositories = [
    // ... bindings existentes ...
    TableRepositoryInterface::class => TableRepository::class,
    OrderRepositoryInterface::class => OrderRepository::class,    // ← adicionar
];
```

### DTOs

Crie `backend/app/DTOs/Order/CreateOrderDTO.php`:

```php
<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\StoreOrderRequest;

final readonly class CreateOrderDTO
{
    public function __construct(
        public ?int $tableId,
        public ?int $clientId,
        public ?string $comment,
        public array $products, // [{product_id, qty}]
    ) {}

    public static function fromRequest(StoreOrderRequest $request): self
    {
        return new self(
            tableId: $request->validated('table_id'),
            clientId: $request->validated('client_id'),
            comment: $request->validated('comment'),
            products: $request->validated('products'),
        );
    }
}
```

Crie `backend/app/DTOs/Order/UpdateOrderStatusDTO.php`:

```php
<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\UpdateOrderStatusRequest;

final readonly class UpdateOrderStatusDTO
{
    public function __construct(
        public string $status,
    ) {}

    public static function fromRequest(UpdateOrderStatusRequest $request): self
    {
        return new self(
            status: $request->validated('status'),
        );
    }
}
```

### Actions

Crie os arquivos de action em `backend/app/Actions/Order/`:

**`ListOrdersAction.php`:**

```php
<?php

namespace App\Actions\Order;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $status);
    }
}
```

**`ShowOrderAction.php`:**

```php
<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class ShowOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Order
    {
        return $this->repository->findById($id);
    }
}
```

**`CreateOrderAction.php`:**

```php
<?php

namespace App\Actions\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CreateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // 1. Criar o pedido
            $order = $this->repository->create([
                'table_id' => $dto->tableId,
                'client_id' => $dto->clientId,
                'comment' => $dto->comment,
                'status' => Order::STATUS_OPEN,
            ]);

            // 2. Vincular produtos com qty e price snapshot
            $pivotData = [];
            foreach ($dto->products as $item) {
                $product = Product::findOrFail($item['product_id']);
                $pivotData[$product->id] = [
                    'qty' => $item['qty'],
                    'price' => $product->price, // snapshot do preco atual
                ];
            }
            $order->products()->attach($pivotData);

            // 3. Calcular e salvar total
            $order->load('products');
            $order->update(['total' => $order->calculateTotal()]);

            return $order->fresh(['products', 'table']);
        });
    }
}
```

> **`DB::transaction()`** — garante que se algo falhar (produto inexistente, constraint violation), todo o pedido e revertido. Sem transacao, poderiamos ter pedidos sem produtos.

> **Price snapshot** — `$product->price` e capturado no momento da criacao. Mesmo que o produto mude de preco depois, o pedido historico mantem o valor original.

**`UpdateOrderStatusAction.php`:**

```php
<?php

namespace App\Actions\Order;

use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final class UpdateOrderStatusAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    /**
     * @return Order|string Order atualizado ou mensagem de erro
     */
    public function execute(int $id, UpdateOrderStatusDTO $dto): Order|string
    {
        $order = $this->repository->findById($id);

        if (!$order) {
            return 'Pedido nao encontrado.';
        }

        if (!$order->canTransitionTo($dto->status)) {
            return "Transicao de '{$order->status}' para '{$dto->status}' nao e permitida.";
        }

        $this->repository->update($id, ['status' => $dto->status]);

        return $order->fresh(['products', 'table']);
    }
}
```

> **Union type `Order|string`** — retorna o Order atualizado em caso de sucesso, ou uma string de erro. O controller decide o HTTP status code.

**`DeleteOrderAction.php`:**

```php
<?php

namespace App\Actions\Order;

use App\Repositories\Contracts\OrderRepositoryInterface;

final class DeleteOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

---

## Passo 7.5 - Order Controller + Routes + FormRequests + Resource

### FormRequests

Crie `backend/app/Http/Requests/Order/ListOrdersRequest.php`:

```php
<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(Order::ALL_STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

> **Por que um FormRequest para listagem?** O Scramble gera automaticamente os campos de query parameter no Swagger a partir das regras de validacao do FormRequest. Sem ele, os campos `status` e `per_page` nao apareceriam na documentacao interativa.

Crie `backend/app/Http/Requests/Order/StoreOrderRequest.php`:

```php
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'client_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'O pedido deve ter pelo menos um produto.',
            'products.min' => 'O pedido deve ter pelo menos um produto.',
            'products.*.product_id.required' => 'O ID do produto e obrigatorio.',
            'products.*.product_id.exists' => 'Produto nao encontrado.',
            'products.*.qty.required' => 'A quantidade e obrigatoria.',
            'products.*.qty.min' => 'A quantidade minima e 1.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Order/UpdateOrderStatusRequest.php`:

```php
<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Order::ALL_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status e obrigatorio.',
            'status.in' => 'Status invalido. Valores aceitos: ' . implode(', ', Order::ALL_STATUSES),
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/OrderResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'identify' => $this->identify,
            'status' => $this->status,
            'total' => $this->total,
            'comment' => $this->comment,
            'table' => new TableResource($this->whenLoaded('table')),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'price' => $product->pivot->price,
                        'qty' => $product->pivot->qty,
                        'subtotal' => number_format($product->pivot->qty * $product->pivot->price, 2, '.', ''),
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

> **Produtos com pivot data:** Em vez de usar `ProductResource`, mapeamos manualmente para incluir `qty`, `price` e `subtotal` da pivot. Isso facilita o consumo no frontend.

### Controller

Crie `backend/app/Http/Controllers/Api/V1/OrderController.php`:

```php
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
```

> **Nota sobre `update()`:** Diferente dos outros controllers que recebem campos editaveis, o update de pedidos so aceita transicao de **status**. Os produtos de um pedido nao sao editaveis apos a criacao (fluxo real de restaurante).

### Routes

Adicione as rotas em `backend/routes/api.php`.

No topo do arquivo, adicione o import:

```php
use App\Http\Controllers\Api\V1\OrderController;
```

Dentro do grupo `Route::middleware('tenant:required')->group(function () {`, adicione apos as rotas de Tables:

```php
            // Orders CRUD
            Route::apiResource('orders', OrderController::class)
                ->middleware([
                    'index' => 'permission:orders.view',
                    'show' => 'permission:orders.view',
                    'store' => 'permission:orders.create',
                    'update' => 'permission:orders.edit',
                    'destroy' => 'permission:orders.delete',
                ]);
```

---

## Passo 7.6 - Order Seeder + teste da API

### Seeder

Crie `backend/database/seeders/OrderSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        $mesa01 = Table::where('tenant_id', $tenant->id)->where('identify', 'Mesa 01')->first();
        $mesa04 = Table::where('tenant_id', $tenant->id)->where('identify', 'Mesa 04')->first();

        $margherita = Product::where('tenant_id', $tenant->id)->where('title', 'Pizza Margherita')->first();
        $xbacon = Product::where('tenant_id', $tenant->id)->where('title', 'X-Bacon Artesanal')->first();
        $coca = Product::where('tenant_id', $tenant->id)->where('title', 'Coca-Cola 350ml')->first();
        $suco = Product::where('tenant_id', $tenant->id)->where('title', 'Suco Natural Laranja')->first();

        if (!$margherita || !$xbacon || !$coca) {
            $this->command->warn('Produtos nao encontrados. Rode ProductSeeder primeiro.');
            return;
        }

        // Pedido 1: Mesa 01 - Pizza + Coca (delivered)
        $order1 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000001'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => $mesa01?->id,
                'status' => Order::STATUS_DELIVERED,
                'comment' => 'Sem azeitona na pizza',
            ],
        );
        $order1->products()->syncWithoutDetaching([
            $margherita->id => ['qty' => 1, 'price' => $margherita->price],
            $coca->id => ['qty' => 2, 'price' => $coca->price],
        ]);
        $order1->update(['total' => $order1->calculateTotal()]);

        // Pedido 2: Mesa 04 - X-Bacon + Suco (preparing)
        $order2 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000002'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => $mesa04?->id,
                'status' => Order::STATUS_PREPARING,
                'comment' => null,
            ],
        );
        $order2->products()->syncWithoutDetaching([
            $xbacon->id => ['qty' => 2, 'price' => $xbacon->price],
            $suco->id => ['qty' => 1, 'price' => $suco->price],
        ]);
        $order2->update(['total' => $order2->calculateTotal()]);

        // Pedido 3: Sem mesa - delivery (open)
        $order3 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000003'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => null,
                'status' => Order::STATUS_OPEN,
                'comment' => 'Pedido para retirada',
            ],
        );
        $order3->products()->syncWithoutDetaching([
            $margherita->id => ['qty' => 2, 'price' => $margherita->price],
        ]);
        $order3->update(['total' => $order3->calculateTotal()]);

        $this->command->info("Pedidos criados para o tenant '{$tenant->name}'.");
    }
}
```

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=OrderSeeder
```

### Teste da API

**Login como gerente:**

```bash
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")
```

**Listar pedidos:**

```bash
curl -s http://localhost/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Filtrar por status:**

```bash
curl -s "http://localhost/api/v1/orders?status=open" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Criar pedido:**

```bash
curl -s -X POST http://localhost/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "table_id": 1,
    "comment": "Sem mostarda",
    "products": [
      {"product_id": 3, "qty": 2},
      {"product_id": 5, "qty": 1}
    ]
  }' | python3 -m json.tool
```

Saida esperada:

```json
{
    "data": {
        "id": 4,
        "uuid": "...",
        "identify": "ORD-000004",
        "status": "open",
        "total": "73.30",
        "comment": "Sem mostarda",
        "table": { "id": 1, "identify": "Mesa 01", ... },
        "products": [
            { "id": 3, "title": "X-Bacon Artesanal", "price": "32.90", "qty": 2, "subtotal": "65.80" },
            { "id": 5, "title": "Coca-Cola 350ml", "price": "7.50", "qty": 1, "subtotal": "7.50" }
        ],
        ...
    }
}
```

**Transicionar status (aceitar pedido):**

```bash
curl -s -X PUT http://localhost/api/v1/orders/4 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"accepted"}' | python3 -m json.tool
```

**Tentar transicao invalida (open → done):**

```bash
curl -s -X PUT http://localhost/api/v1/orders/3 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"done"}' | python3 -m json.tool
```

Saida esperada (422):

```json
{
    "message": "Transicao de 'open' para 'done' nao e permitida."
}
```

**Deletar pedido:**

```bash
curl -s -X DELETE http://localhost/api/v1/orders/4 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

---

## Passo 7.7 - Frontend: tipos TypeScript + servico de Pedidos

### Tipos TypeScript

Crie `frontend/src/types/order.ts`:

```typescript
import type { Table } from "./catalog";

export type OrderStatus = "open" | "accepted" | "rejected" | "preparing" | "done" | "delivered";

export interface OrderProduct {
  id: number;
  title: string;
  price: string;
  qty: number;
  subtotal: string;
}

export interface Order {
  id: number;
  uuid: string;
  identify: string;
  status: OrderStatus;
  total: string;
  comment: string | null;
  table: Table | null;
  products: OrderProduct[];
  created_at: string;
  updated_at: string;
}

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  open: "Aberto",
  accepted: "Aceito",
  rejected: "Rejeitado",
  preparing: "Preparando",
  done: "Pronto",
  delivered: "Entregue",
};

export const ORDER_STATUS_COLORS: Record<OrderStatus, string> = {
  open: "bg-blue-100 text-blue-800",
  accepted: "bg-green-100 text-green-800",
  rejected: "bg-red-100 text-red-800",
  preparing: "bg-yellow-100 text-yellow-800",
  done: "bg-purple-100 text-purple-800",
  delivered: "bg-gray-100 text-gray-800",
};

export const VALID_TRANSITIONS: Record<string, OrderStatus[]> = {
  open: ["accepted", "rejected"],
  accepted: ["preparing", "rejected"],
  preparing: ["done"],
  done: ["delivered"],
};
```

> **Constantes no frontend:** `ORDER_STATUS_LABELS`, `ORDER_STATUS_COLORS` e `VALID_TRANSITIONS` espelham a logica do backend. Os labels sao em portugues (pt-BR) e as cores usam classes Tailwind para badges.

### Servico

Crie `frontend/src/services/order-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Order } from "@/types/order";
import type { PaginatedResponse } from "@/types/plan";

export async function getOrders(
  page = 1,
  status?: string
): Promise<PaginatedResponse<Order>> {
  const params = new URLSearchParams({ page: String(page) });
  if (status) params.set("status", status);

  return apiClient<PaginatedResponse<Order>>(
    `/v1/orders?${params.toString()}`
  );
}

export async function getOrder(
  id: number
): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>(`/v1/orders/${id}`);
}

export async function createOrder(data: {
  table_id?: number | null;
  comment?: string;
  products: { product_id: number; qty: number }[];
}): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>("/v1/orders", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateOrderStatus(
  id: number,
  status: string
): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>(`/v1/orders/${id}`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  });
}

export async function deleteOrder(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/orders/${id}`, {
    method: "DELETE",
  });
}
```

---

## Passo 7.8 - Frontend: pagina de Pedidos (listagem + status)

Instale o componente shadcn/ui `select` (se ainda nao instalou):

```bash
docker compose exec frontend npx shadcn@latest add select
```

### Componentes

Crie o diretorio para componentes de pedidos:

```bash
mkdir -p frontend/src/components/orders
```

**Badge de status** — `frontend/src/components/orders/order-status-badge.tsx`:

```tsx
import type { OrderStatus } from "@/types/order";
import { ORDER_STATUS_LABELS, ORDER_STATUS_COLORS } from "@/types/order";

interface OrderStatusBadgeProps {
  status: OrderStatus;
}

export function OrderStatusBadge({ status }: OrderStatusBadgeProps) {
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${ORDER_STATUS_COLORS[status]}`}
    >
      {ORDER_STATUS_LABELS[status]}
    </span>
  );
}
```

**Dialog de transicao de status** — `frontend/src/components/orders/update-status-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { updateOrderStatus } from "@/services/order-service";
import type { Order, OrderStatus } from "@/types/order";
import { VALID_TRANSITIONS, ORDER_STATUS_LABELS } from "@/types/order";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface UpdateStatusDialogProps {
  order: Order | null;
  onOpenChange: (open: boolean) => void;
  onUpdated: () => void;
}

export function UpdateStatusDialog({
  order,
  onOpenChange,
  onUpdated,
}: UpdateStatusDialogProps) {
  const [loading, setLoading] = useState(false);

  const transitions = order ? (VALID_TRANSITIONS[order.status] ?? []) : [];

  const handleTransition = async (newStatus: OrderStatus) => {
    if (!order) return;

    setLoading(true);
    try {
      await updateOrderStatus(order.id, newStatus);
      onUpdated();
    } catch (error) {
      console.error("Erro ao atualizar status:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!order} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Atualizar Status — {order?.identify}</DialogTitle>
          <DialogDescription>
            Status atual: <strong>{order ? ORDER_STATUS_LABELS[order.status] : ""}</strong>.
            Selecione o novo status:
          </DialogDescription>
        </DialogHeader>

        {transitions.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Nenhuma transicao disponivel para este status.
          </p>
        ) : (
          <div className="flex flex-col gap-2">
            {transitions.map((status) => (
              <Button
                key={status}
                variant={status === "rejected" ? "destructive" : "default"}
                onClick={() => handleTransition(status)}
                disabled={loading}
              >
                {ORDER_STATUS_LABELS[status]}
              </Button>
            ))}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
```

**Dialog de exclusao** — `frontend/src/components/orders/delete-order-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { deleteOrder } from "@/services/order-service";
import type { Order } from "@/types/order";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteOrderDialogProps {
  order: Order | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteOrderDialog({
  order,
  onOpenChange,
  onDeleted,
}: DeleteOrderDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!order) return;

    setLoading(true);
    try {
      await deleteOrder(order.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover pedido:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!order} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Pedido</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover o pedido &quot;{order?.identify}
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

### Pagina de Pedidos

Crie `frontend/src/app/(admin)/orders/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getOrders } from "@/services/order-service";
import type { Order, OrderStatus } from "@/types/order";
import { ORDER_STATUS_LABELS } from "@/types/order";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, ArrowRightLeft, Trash2, Eye } from "lucide-react";
import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { UpdateStatusDialog } from "@/components/orders/update-status-dialog";
import { DeleteOrderDialog } from "@/components/orders/delete-order-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";
import Link from "next/link";

export default function OrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [statusOrder, setStatusOrder] = useState<Order | null>(null);
  const [deleteState, setDeleteState] = useState<Order | null>(null);

  const fetchOrders = async () => {
    try {
      const status = statusFilter === "all" ? undefined : statusFilter;
      const response = await getOrders(1, status);
      setOrders(response.data);
    } catch (error) {
      console.error("Erro ao carregar pedidos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setLoading(true);
    fetchOrders();
  }, [statusFilter]);

  const handleStatusUpdated = () => {
    setStatusOrder(null);
    fetchOrders();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchOrders();
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="pedidos" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Pedidos</h1>
        <div className="flex items-center gap-2">
          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="Filtrar status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todos</SelectItem>
              {(Object.keys(ORDER_STATUS_LABELS) as OrderStatus[]).map((status) => (
                <SelectItem key={status} value={status}>
                  {ORDER_STATUS_LABELS[status]}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button asChild>
            <Link href="/orders/new">
              <Plus className="mr-2 h-4 w-4" />
              Novo Pedido
            </Link>
          </Button>
        </div>
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
              <TableHead>Codigo</TableHead>
              <TableHead>Mesa</TableHead>
              <TableHead>Itens</TableHead>
              <TableHead>Total</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Data</TableHead>
              <TableHead className="w-[120px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {orders.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground">
                  Nenhum pedido encontrado.
                </TableCell>
              </TableRow>
            ) : (
              orders.map((order) => (
                <TableRow key={order.id}>
                  <TableCell className="font-mono font-medium">
                    {order.identify}
                  </TableCell>
                  <TableCell>
                    {order.table?.identify || "—"}
                  </TableCell>
                  <TableCell className="text-muted-foreground">
                    {order.products.length} {order.products.length === 1 ? "item" : "itens"}
                  </TableCell>
                  <TableCell className="font-medium">
                    R$ {order.total}
                  </TableCell>
                  <TableCell>
                    <OrderStatusBadge status={order.status} />
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm">
                    {new Date(order.created_at).toLocaleDateString("pt-BR")}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Detalhes"
                        asChild
                      >
                        <Link href={`/orders/${order.id}`}>
                          <Eye className="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Atualizar status"
                        onClick={() => setStatusOrder(order)}
                      >
                        <ArrowRightLeft className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Remover"
                        onClick={() => setDeleteState(order)}
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

      <UpdateStatusDialog
        order={statusOrder}
        onOpenChange={() => setStatusOrder(null)}
        onUpdated={handleStatusUpdated}
      />

      <DeleteOrderDialog
        order={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />
    </div>
  );
}
```

### Pagina de detalhes do pedido

Crie `frontend/src/app/(admin)/orders/[id]/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { getOrder } from "@/services/order-service";
import type { Order } from "@/types/order";
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
import { ArrowLeft, ArrowRightLeft } from "lucide-react";
import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { UpdateStatusDialog } from "@/components/orders/update-status-dialog";
import Link from "next/link";

export default function OrderDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [statusOpen, setStatusOpen] = useState(false);

  const fetchOrder = async () => {
    try {
      const response = await getOrder(Number(params.id));
      setOrder(response.data);
    } catch (error) {
      console.error("Erro ao carregar pedido:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrder();
  }, [params.id]);

  const handleStatusUpdated = () => {
    setStatusOpen(false);
    fetchOrder();
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  if (!order) {
    return <p className="text-muted-foreground">Pedido nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/orders">
            <ArrowLeft className="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{order.identify}</h1>
          <p className="text-sm text-muted-foreground">
            {order.table ? `Mesa: ${order.table.identify}` : "Sem mesa (delivery/retirada)"}
            {order.comment && ` — "${order.comment}"`}
          </p>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <OrderStatusBadge status={order.status} />
          <Button
            variant="outline"
            size="sm"
            onClick={() => setStatusOpen(true)}
          >
            <ArrowRightLeft className="mr-2 h-4 w-4" />
            Alterar Status
          </Button>
        </div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Produto</TableHead>
            <TableHead className="text-right">Preco Unit.</TableHead>
            <TableHead className="text-right">Qtd</TableHead>
            <TableHead className="text-right">Subtotal</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {order.products.map((product) => (
            <TableRow key={product.id}>
              <TableCell className="font-medium">{product.title}</TableCell>
              <TableCell className="text-right">R$ {product.price}</TableCell>
              <TableCell className="text-right">{product.qty}</TableCell>
              <TableCell className="text-right font-medium">R$ {product.subtotal}</TableCell>
            </TableRow>
          ))}
          <TableRow>
            <TableCell colSpan={3} className="text-right font-bold">
              Total
            </TableCell>
            <TableCell className="text-right font-bold text-lg">
              R$ {order.total}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <p className="text-sm text-muted-foreground">
        Criado em: {new Date(order.created_at).toLocaleString("pt-BR")}
      </p>

      <UpdateStatusDialog
        order={statusOpen ? order : null}
        onOpenChange={() => setStatusOpen(false)}
        onUpdated={handleStatusUpdated}
      />
    </div>
  );
}
```

### Loading global (UX)

Ao navegar entre paginas, a aplicacao pode parecer "congelada" enquanto carrega. Vamos adicionar duas camadas de feedback visual:

**1. Progress bar no topo (nextjs-toploader):**

Instale a dependencia:

```bash
docker compose exec frontend npm install nextjs-toploader
```

Atualize `frontend/src/app/layout.tsx` para incluir o componente:

```tsx
import type { Metadata } from "next";
import NextTopLoader from "nextjs-toploader";
import "./globals.css";

export const metadata: Metadata = {
  title: "Orderly - Plataforma SaaS de Delivery",
  description: "Sistema completo de gestao para restaurantes e delivery",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR" className="dark">
      <body className="min-h-screen bg-background text-foreground antialiased">
        <NextTopLoader color="#3b82f6" showSpinner={false} />
        {children}
      </body>
    </html>
  );
}
```

> **`NextTopLoader`** renderiza uma barra de progresso fina no topo da pagina (estilo YouTube/GitHub) durante toda navegacao client-side. `showSpinner={false}` remove o spinner circular, mantendo apenas a barra.

**2. Skeleton loading para conteudo (loading.tsx):**

Crie `frontend/src/app/(admin)/loading.tsx`:

```tsx
import { Skeleton } from "@/components/ui/skeleton";

export default function AdminLoading() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-8 w-48" />
      <div className="space-y-2">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-12 w-full" />
        ))}
      </div>
    </div>
  );
}
```

> **`loading.tsx`** e uma convencao do Next.js App Router. Quando colocado dentro de uma pasta de layout, ele e exibido automaticamente como fallback durante a navegacao entre paginas filhas. Nao precisa de nenhum import manual — o Next.js integra via React Suspense.

**Resultado:** Ao clicar em qualquer item da sidebar:
1. Barra azul aparece no topo imediatamente (feedback instantaneo)
2. Skeletons pulsantes aparecem na area de conteudo
3. Quando a pagina carrega, ambos desaparecem

---

## Passo 7.9 - Frontend: dialog de criacao de pedido

A criacao de pedido e mais complexa que os outros CRUDs: o usuario seleciona uma mesa (opcional), adiciona produtos com quantidades, e envia.

### Pagina de criacao

Crie `frontend/src/app/(admin)/orders/new/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getProducts } from "@/services/product-service";
import { getTables } from "@/services/table-service";
import { createOrder } from "@/services/order-service";
import type { Product, Table as TableType } from "@/types/catalog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ArrowLeft, Plus, Minus, ShoppingBag } from "lucide-react";
import { ApiError } from "@/lib/api";
import Link from "next/link";

interface CartItem {
  product: Product;
  qty: number;
}

export default function NewOrderPage() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [tables, setTables] = useState<TableType[]>([]);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [tableId, setTableId] = useState<string>("none");
  const [comment, setComment] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    getProducts().then((res) => setProducts(res.data));
    getTables().then((res) => setTables(res.data));
  }, []);

  const addToCart = (product: Product) => {
    setCart((prev) => {
      const existing = prev.find((item) => item.product.id === product.id);
      if (existing) {
        return prev.map((item) =>
          item.product.id === product.id
            ? { ...item, qty: item.qty + 1 }
            : item
        );
      }
      return [...prev, { product, qty: 1 }];
    });
  };

  const updateQty = (productId: number, delta: number) => {
    setCart((prev) =>
      prev
        .map((item) =>
          item.product.id === productId
            ? { ...item, qty: Math.max(0, item.qty + delta) }
            : item
        )
        .filter((item) => item.qty > 0)
    );
  };

  const total = cart.reduce(
    (sum, item) => sum + item.qty * parseFloat(item.product.price),
    0
  );

  const handleSubmit = async () => {
    if (cart.length === 0) {
      setError("Adicione pelo menos um produto.");
      return;
    }

    setSubmitting(true);
    setError("");

    try {
      await createOrder({
        table_id: tableId !== "none" ? Number(tableId) : null,
        comment: comment || undefined,
        products: cart.map((item) => ({
          product_id: item.product.id,
          qty: item.qty,
        })),
      });
      router.push("/orders");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/orders">
            <ArrowLeft className="h-4 w-4" />
          </Link>
        </Button>
        <h1 className="text-2xl font-bold">Novo Pedido</h1>
      </div>

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Produtos disponiveis */}
        <div className="space-y-3">
          <h2 className="text-lg font-semibold">Produtos</h2>
          <div className="space-y-2 max-h-[500px] overflow-y-auto">
            {products.map((product) => (
              <div
                key={product.id}
                className="flex items-center justify-between rounded-lg border p-3"
              >
                <div>
                  <p className="font-medium">{product.title}</p>
                  <p className="text-sm text-muted-foreground">
                    R$ {product.price}
                  </p>
                </div>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => addToCart(product)}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>
        </div>

        {/* Carrinho */}
        <div className="space-y-3">
          <h2 className="text-lg font-semibold">
            <ShoppingBag className="inline mr-2 h-5 w-5" />
            Carrinho ({cart.length} {cart.length === 1 ? "item" : "itens"})
          </h2>

          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Mesa (opcional)</Label>
              <Select value={tableId} onValueChange={setTableId}>
                <SelectTrigger>
                  <SelectValue placeholder="Sem mesa" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Sem mesa (delivery/retirada)</SelectItem>
                  {tables.map((table) => (
                    <SelectItem key={table.id} value={String(table.id)}>
                      {table.identify}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Observacoes</Label>
              <Textarea
                placeholder="Sem cebola, bem passado..."
                value={comment}
                onChange={(e) => setComment(e.target.value)}
              />
            </div>

            {cart.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-4">
                Nenhum produto adicionado.
              </p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Produto</TableHead>
                    <TableHead className="text-center">Qtd</TableHead>
                    <TableHead className="text-right">Subtotal</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {cart.map((item) => (
                    <TableRow key={item.product.id}>
                      <TableCell className="font-medium">
                        {item.product.title}
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center justify-center gap-1">
                          <Button
                            size="icon"
                            variant="ghost"
                            className="h-6 w-6"
                            onClick={() => updateQty(item.product.id, -1)}
                          >
                            <Minus className="h-3 w-3" />
                          </Button>
                          <span className="w-8 text-center">{item.qty}</span>
                          <Button
                            size="icon"
                            variant="ghost"
                            className="h-6 w-6"
                            onClick={() => updateQty(item.product.id, 1)}
                          >
                            <Plus className="h-3 w-3" />
                          </Button>
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        R$ {(item.qty * parseFloat(item.product.price)).toFixed(2)}
                      </TableCell>
                    </TableRow>
                  ))}
                  <TableRow>
                    <TableCell colSpan={2} className="text-right font-bold">
                      Total
                    </TableCell>
                    <TableCell className="text-right font-bold">
                      R$ {total.toFixed(2)}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            )}

            <Button
              className="w-full"
              onClick={handleSubmit}
              disabled={submitting || cart.length === 0}
            >
              {submitting ? "Criando pedido..." : "Criar Pedido"}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
```

> **Padrao "carrinho":** A pagina de criacao usa um state local `cart` (array de `CartItem`) para acumular produtos antes de enviar. O total e calculado client-side para feedback instantaneo, e recalculado server-side com price snapshot.

### Sidebar ja configurada

A sidebar ja tem o link "Pedidos" no grupo **Operacao** (configurado no Passo 5.12). Nenhuma alteracao necessaria.

---

## Passo 7.10 - Verificacao end-to-end da Fase 7

### Checklist de verificacao

**Backend:**

- [ ] Migrations `create_orders_table` e `create_order_product_table` rodaram sem erros
- [ ] `Order` model com `BelongsToTenant`, `ObservedBy(OrderObserver)`, constantes de status
- [ ] `canTransitionTo()` valida transicoes de status
- [ ] `withPivot(['qty', 'price'])` no relacionamento `products()`
- [ ] `calculateTotal()` soma qty * price dos produtos
- [ ] `OrderObserver` gera UUID + identify auto ("ORD-000001")
- [ ] `OrderRepository` com filtro por status na paginacao
- [ ] `CreateOrderAction` usa `DB::transaction()` + price snapshot
- [ ] `UpdateOrderStatusAction` valida transicoes e retorna erro descritivo
- [ ] Rotas dentro do grupo `tenant:required`
- [ ] Permissoes `orders.*` ja existem no `PermissionSeeder`
- [ ] Seeder cria 3 pedidos com diferentes status
- [ ] Swagger mostra endpoints na tag "Pedidos"

**Frontend:**

- [ ] Tipos `Order`, `OrderProduct`, `OrderStatus` em `order.ts`
- [ ] Constantes `ORDER_STATUS_LABELS`, `ORDER_STATUS_COLORS`, `VALID_TRANSITIONS`
- [ ] Servico `order-service.ts` com CRUD + filtro por status
- [ ] Pagina `/orders` lista pedidos com filtro por status (Select)
- [ ] Pagina `/orders/[id]` exibe detalhes com tabela de produtos
- [ ] Pagina `/orders/new` com carrinho de produtos + selecao de mesa
- [ ] `OrderStatusBadge` exibe badge colorido por status
- [ ] `UpdateStatusDialog` mostra apenas transicoes validas
- [ ] `TenantRequiredAlert` aparece para super-admin sem tenant

### Fluxo completo de teste

1. Acesse `http://localhost` e faca login como `gerente@demo.com` / `password`
2. No sidebar, clique em **Pedidos**
3. Verifique que os 3 pedidos do seeder aparecem (ORD-000001, 000002, 000003)
4. Use o filtro de status para filtrar apenas "Aberto"
5. Clique em **Novo Pedido** → adicione produtos ao carrinho → selecione mesa → **Criar Pedido**
6. Clique no icone **Detalhes** (olho) de um pedido → verifique a tabela de produtos
7. Clique em **Alterar Status** → aceite o pedido → verifique a transicao
8. Tente aceitar um pedido ja entregue → verifique que nao ha transicoes disponiveis
9. No terminal, teste transicao invalida via curl:
   ```bash
   curl -s -X PUT http://localhost/api/v1/orders/1 \
     -H "Authorization: Bearer $TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"status":"open"}' | python3 -m json.tool
   ```
   Resultado: 422 com mensagem de erro

### Resumo dos arquivos da Fase 7

**Backend:**

```
backend/
├── database/
│   ├── migrations/
│   │   ├── 0001_01_02_000010_create_orders_table.php
│   │   └── 0001_01_02_000011_create_order_product_table.php
│   └── seeders/OrderSeeder.php
├── app/
│   ├── Models/Order.php
│   ├── Observers/OrderObserver.php
│   ├── Repositories/
│   │   ├── Contracts/OrderRepositoryInterface.php
│   │   └── Eloquent/OrderRepository.php
│   ├── DTOs/Order/
│   │   ├── CreateOrderDTO.php
│   │   └── UpdateOrderStatusDTO.php
│   ├── Actions/Order/
│   │   ├── ListOrdersAction.php
│   │   ├── ShowOrderAction.php
│   │   ├── CreateOrderAction.php
│   │   ├── UpdateOrderStatusAction.php
│   │   └── DeleteOrderAction.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/OrderController.php
│   │   ├── Requests/Order/
│   │   │   ├── ListOrdersRequest.php
│   │   │   ├── StoreOrderRequest.php
│   │   │   └── UpdateOrderStatusRequest.php
│   │   └── Resources/OrderResource.php
│   └── Providers/RepositoryServiceProvider.php (modificado)
└── routes/api.php (modificado)
```

**Frontend:**

```
frontend/src/
├── types/order.ts
├── services/order-service.ts
├── app/
│   ├── layout.tsx                  (modificado — NextTopLoader)
│   └── (admin)/
│       ├── loading.tsx             (skeleton loading global)
│       └── orders/
│           ├── page.tsx            (listagem + filtro)
│           ├── [id]/page.tsx       (detalhes)
│           └── new/page.tsx        (criacao com carrinho)
└── components/orders/
    ├── order-status-badge.tsx      (badge colorido)
    ├── update-status-dialog.tsx    (transicao de status)
    └── delete-order-dialog.tsx     (confirmacao de exclusao)
```

**Conceitos aprendidos:**
- **Price snapshot** — salvar o preco no momento do pedido na tabela pivot, imune a alteracoes futuras do produto
- **Pivot com dados extras** — `withPivot(['qty', 'price'])` carrega campos adicionais na relacao N:N
- **`DB::transaction()`** — garante atomicidade: se algo falhar, todas as operacoes sao revertidas
- **Maquina de estados** — `VALID_TRANSITIONS` define transicoes permitidas, `canTransitionTo()` valida
- **Union types** — `Order|string` como retorno de Action permite erro descritivo sem exceptions
- **`withoutGlobalScopes()`** — necessario para consultas que devem ignorar o TenantScope (ex: gerar sequence)
- **Carrinho client-side** — state local com `CartItem[]` para montar pedidos antes de enviar ao backend
- **Filtro por query parameter** — `?status=open` filtra no backend, `Select` controla no frontend
- **`loading.tsx`** — convencao do Next.js App Router que exibe fallback automatico (via React Suspense) durante navegacao entre paginas
- **`nextjs-toploader`** — progress bar no topo da pagina para feedback visual imediato durante navegacao client-side
- **FormRequest para query params** — o Scramble gera campos no Swagger a partir das `rules()` do FormRequest (nao suporta `@queryParam`)

**Proximo:** Fase 8 - Autenticacao de Clientes + Avaliacoes

---


---

[Voltar ao README](../README.md)
