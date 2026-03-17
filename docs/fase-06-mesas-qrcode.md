# Fase 6 - Mesas com QR Code

Nesta fase vamos implementar o CRUD de **Mesas** (tables) — entidade tenant-scoped que representa as mesas fisicas do restaurante. Cada mesa tera um **QR Code** unico gerado automaticamente a partir do seu UUID, permitindo que clientes facam pedidos escaneando o codigo.

**O que vamos construir:**
- Migration, Model, Observer (UUID + identify auto-gerado)
- Repository + Actions (Clean Architecture)
- Controller com FormRequests e Resource
- Seeder com mesas de exemplo
- Endpoint dedicado para gerar QR Code via biblioteca PHP
- Frontend com pagina CRUD + visualizacao/download do QR Code

**Dependencia:** Fase 5 concluida (catalogo de categorias + produtos).

---

## Passo 6.1 - Conceito: Mesas e QR Codes

### O que e uma Mesa no sistema?

Uma **Mesa** (table) representa uma mesa fisica no restaurante. Cada mesa pertence a um tenant e possui:

| Campo | Tipo | Descricao |
|---|---|---|
| `id` | bigint | PK auto-increment (interno) |
| `tenant_id` | FK → tenants | Isolamento multi-tenant |
| `uuid` | uuid | Identificador publico (URLs, QR Codes) |
| `identify` | string | Codigo legivel da mesa ("Mesa 01", "VIP-03") |
| `description` | text? | Descricao opcional ("Varanda", "Area interna") |

### Fluxo do QR Code

```
┌─────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ Admin cria  │───►│ Observer gera    │───►│ QR Code aponta  │
│ Mesa "01"   │    │ UUID automatico  │    │ para URL publica │
└─────────────┘    └──────────────────┘    └─────────────────┘
                                                     │
                   ┌──────────────────┐              │
                   │ Cliente escaneia │◄─────────────┘
                   │ e ve o cardapio  │
                   └──────────────────┘
```

O QR Code codifica uma URL no formato:
```
https://{tenant_url}.orderly.com/menu?table={uuid}
```

Por enquanto (desenvolvimento), usaremos:
```
http://localhost/menu?table={uuid}
```

### Por que UUID no QR Code?

- **Seguranca:** IDs sequenciais (1, 2, 3) sao previsiveis e permitem enumeracao
- **Imutabilidade:** O UUID nao muda mesmo se a mesa for renomeada
- **Unicidade global:** Cada QR Code e unico em todo o sistema, nao so no tenant

---

## Passo 6.2 - Migration: tabela tables + Model + Observer

### Migration

Crie o arquivo `backend/database/migrations/0001_01_02_000009_create_tables_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('identify'); // "Mesa 01", "VIP-03"
            $table->text('description')->nullable();
            $table->timestamps();

            // Identify unico por tenant (dois tenants podem ter "Mesa 01")
            $table->unique(['tenant_id', 'identify']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
```

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

Saida esperada:

```
Running migrations.
0001_01_02_000009_create_tables_table .... DONE
```

### Model

Crie `backend/app/Models/Table.php`:

```php
<?php

namespace App\Models;

use App\Observers\TableObserver;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(TableObserver::class)]
class Table extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'identify',
        'description',
    ];
}
```

> **Nota:** O model se chama `Table` mas o Laravel nao confunde com a palavra reservada SQL porque o Eloquent pluraliza para `tables` automaticamente. Se preferir, pode adicionar `protected $table = 'tables';` explicitamente, mas nao e necessario.

### Observer

Crie `backend/app/Observers/TableObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Table;
use Illuminate\Support\Str;

class TableObserver
{
    public function creating(Table $table): void
    {
        if (empty($table->uuid)) {
            $table->uuid = (string) Str::uuid();
        }
    }
}
```

> **Diferenca dos outros Observers:** A mesa nao precisa de slug (`url`) porque o campo `identify` e livre (ex: "Mesa 01", "VIP-03"). O Observer so gera o UUID que sera usado no QR Code.

### Testar no tinker

```bash
docker compose exec backend php artisan tinker
```

```php
$gerente = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($gerente);

$table = App\Models\Table::create([
    'identify' => 'Mesa Teste',
    'description' => 'Mesa temporaria para teste',
]);

echo "ID: {$table->id}, UUID: {$table->uuid}, Identify: {$table->identify}";
// ID: 1, UUID: 550e8400-..., Identify: Mesa Teste

// Limpar
$table->delete();
auth('api')->forgetUser();
exit;
```

---

## Passo 6.3 - Table Repository + CRUD completo

### Interface

Crie `backend/app/Repositories/Contracts/TableRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TableRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Table;

    public function findByUuid(string $uuid): ?Table;

    public function create(array $data): Table;

    public function update(int $id, array $data): ?Table;

    public function delete(int $id): bool;
}
```

> **Novidade:** `findByUuid()` sera usado no endpoint de QR Code para buscar a mesa pelo UUID publico.

### Implementacao

Crie `backend/app/Repositories/Eloquent/TableRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TableRepository implements TableRepositoryInterface
{
    public function __construct(
        private readonly Table $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Table
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Table
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): Table
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Table
    {
        $table = $this->findById($id);

        if (!$table) {
            return null;
        }

        $table->update($data);

        return $table->fresh();
    }

    public function delete(int $id): bool
    {
        $table = $this->findById($id);

        if (!$table) {
            return false;
        }

        return (bool) $table->delete();
    }
}
```

### Registrar no Service Provider

Adicione o binding em `backend/app/Providers/RepositoryServiceProvider.php`.

1. Adicione os imports no topo do arquivo:

```php
use App\Repositories\Contracts\TableRepositoryInterface;
use App\Repositories\Eloquent\TableRepository;
```

2. Adicione a entrada no array `$repositories`:

```php
private array $repositories = [
    // ... bindings existentes ...
    ProductRepositoryInterface::class => ProductRepository::class,
    TableRepositoryInterface::class => TableRepository::class,    // ← adicionar
];
```

### DTOs

Crie `backend/app/DTOs/Table/CreateTableDTO.php`:

```php
<?php

namespace App\DTOs\Table;

use App\Http\Requests\Table\StoreTableRequest;

final readonly class CreateTableDTO
{
    public function __construct(
        public string $identify,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreTableRequest $request): self
    {
        return new self(
            identify: $request->validated('identify'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'identify' => $this->identify,
            'description' => $this->description,
        ];
    }
}
```

Crie `backend/app/DTOs/Table/UpdateTableDTO.php`:

```php
<?php

namespace App\DTOs\Table;

use App\Http\Requests\Table\UpdateTableRequest;

final readonly class UpdateTableDTO
{
    public function __construct(
        public string $identify,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateTableRequest $request): self
    {
        return new self(
            identify: $request->validated('identify'),
            description: $request->validated('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'identify' => $this->identify,
            'description' => $this->description,
        ];
    }
}
```

### Actions

Crie os 5 arquivos de action em `backend/app/Actions/Table/`:

**`ListTablesAction.php`:**

```php
<?php

namespace App\Actions\Table;

use App\Repositories\Contracts\TableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTablesAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

**`ShowTableAction.php`:**

```php
<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class ShowTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Table
    {
        return $this->repository->findById($id);
    }
}
```

**`CreateTableAction.php`:**

```php
<?php

namespace App\Actions\Table;

use App\DTOs\Table\CreateTableDTO;
use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class CreateTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(CreateTableDTO $dto): Table
    {
        return $this->repository->create($dto->toArray());
    }
}
```

**`UpdateTableAction.php`:**

```php
<?php

namespace App\Actions\Table;

use App\DTOs\Table\UpdateTableDTO;
use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;

final class UpdateTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateTableDTO $dto): ?Table
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

**`DeleteTableAction.php`:**

```php
<?php

namespace App\Actions\Table;

use App\Repositories\Contracts\TableRepositoryInterface;

final class DeleteTableAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

---

## Passo 6.4 - Table Controller + Routes + FormRequests + Resource

### FormRequests

Crie `backend/app/Http/Requests/Table/StoreTableRequest.php`:

```php
<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identify' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'identify.required' => 'O identificador da mesa e obrigatorio.',
            'identify.max' => 'O identificador deve ter no maximo 255 caracteres.',
            'description.max' => 'A descricao deve ter no maximo 1000 caracteres.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Table/UpdateTableRequest.php`:

```php
<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identify' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'identify.required' => 'O identificador da mesa e obrigatorio.',
            'identify.max' => 'O identificador deve ter no maximo 255 caracteres.',
            'description.max' => 'A descricao deve ter no maximo 1000 caracteres.',
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/TableResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'identify' => $this->identify,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### Controller

Crie `backend/app/Http/Controllers/Api/V1/TableController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Table\StoreTableRequest;
use App\Http\Requests\Table\UpdateTableRequest;
use App\Http\Resources\TableResource;
use App\DTOs\Table\CreateTableDTO;
use App\DTOs\Table\UpdateTableDTO;
use App\Actions\Table\ListTablesAction;
use App\Actions\Table\ShowTableAction;
use App\Actions\Table\CreateTableAction;
use App\Actions\Table\UpdateTableAction;
use App\Actions\Table\DeleteTableAction;
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

        if (!$table) {
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

        if (!$updated) {
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

        if (!$deleted) {
            return response()->json(['message' => 'Mesa nao encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Mesa removida com sucesso.',
        ]);
    }
}
```

### Routes

Adicione as rotas em `backend/routes/api.php`.

No topo do arquivo, adicione o import:

```php
use App\Http\Controllers\Api\V1\TableController;
```

Dentro do grupo `Route::middleware('tenant:required')->group(function () {`, adicione apos as rotas de Products:

```php
            // Tables CRUD
            Route::apiResource('tables', TableController::class)
                ->middleware([
                    'index' => 'permission:tables.view',
                    'show' => 'permission:tables.view',
                    'store' => 'permission:tables.create',
                    'update' => 'permission:tables.edit',
                    'destroy' => 'permission:tables.delete',
                ]);
```

> **Nota:** As rotas de mesas ficam dentro do grupo `tenant:required` porque mesas pertencem a um tenant. Super-admin sem tenant recebe 403.

---

## Passo 6.5 - Table Seeder + teste da API

### Seeder

Crie `backend/database/seeders/TableSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        $tables = [
            ['identify' => 'Mesa 01', 'description' => 'Area interna - 4 lugares'],
            ['identify' => 'Mesa 02', 'description' => 'Area interna - 4 lugares'],
            ['identify' => 'Mesa 03', 'description' => 'Area interna - 6 lugares'],
            ['identify' => 'Mesa 04', 'description' => 'Varanda - 4 lugares'],
            ['identify' => 'Mesa 05', 'description' => 'Varanda - 4 lugares'],
            ['identify' => 'VIP-01', 'description' => 'Sala reservada - 8 lugares'],
        ];

        foreach ($tables as $data) {
            Table::firstOrCreate(
                ['tenant_id' => $tenant->id, 'identify' => $data['identify']],
                array_merge($data, ['tenant_id' => $tenant->id]),
            );
        }

        $this->command->info("Mesas criadas para o tenant '{$tenant->name}'.");
    }
}
```

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=TableSeeder
```

Saida esperada:

```
Mesas criadas para o tenant 'Restaurante Demo'.
```

### Teste da API

**Login como gerente (usuario com tenant):**

```bash
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

echo $TOKEN
```

**Listar mesas:**

```bash
curl -s http://localhost/api/v1/tables \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

Saida esperada (6 mesas):

```json
{
    "data": [
        {
            "id": 6,
            "uuid": "...",
            "identify": "VIP-01",
            "description": "Sala reservada - 8 lugares",
            "created_at": "...",
            "updated_at": "..."
        },
        ...
    ],
    "links": { ... },
    "meta": { "total": 6, ... }
}
```

**Criar mesa:**

```bash
curl -s -X POST http://localhost/api/v1/tables \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"identify":"Mesa 06","description":"Area externa - 2 lugares"}' | python3 -m json.tool
```

**Atualizar mesa:**

```bash
curl -s -X PUT http://localhost/api/v1/tables/7 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"identify":"Mesa 06","description":"Area externa - 4 lugares (ampliada)"}' | python3 -m json.tool
```

**Deletar mesa:**

```bash
curl -s -X DELETE http://localhost/api/v1/tables/7 \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

**Testar bloqueio super-admin sem tenant:**

```bash
ADMIN_TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s http://localhost/api/v1/tables \
  -H "Authorization: Bearer $ADMIN_TOKEN" | python3 -m json.tool
```

Saida esperada (403):

```json
{
    "message": "Esta acao requer um usuario vinculado a um tenant."
}
```

### Verificar no tinker

```bash
docker compose exec backend php artisan tinker
```

```php
$gerente = App\Models\User::where('email', 'gerente@demo.com')->first();
auth('api')->setUser($gerente);

App\Models\Table::count();
// 6

App\Models\Table::pluck('identify')->toArray();
// ["Mesa 01", "Mesa 02", "Mesa 03", "Mesa 04", "Mesa 05", "VIP-01"]

auth('api')->forgetUser();
exit;
```

---

## Passo 6.6 - QR Code: geracao e endpoint

### Instalar biblioteca de QR Code

Vamos usar a biblioteca `chillerlan/php-qrcode` para gerar QR Codes no backend:

```bash
docker compose exec backend composer require chillerlan/php-qrcode
```

> **Por que gerar no backend?** O QR Code codifica uma URL que depende da configuracao do tenant. Gerar no backend garante que o formato e consistente e que podemos cachear/armazenar os QR Codes futuramente.

### Action para gerar QR Code

Crie `backend/app/Actions/Table/GenerateQrCodeAction.php`:

```php
<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class GenerateQrCodeAction
{
    public function __construct(
        private readonly TableRepositoryInterface $repository,
    ) {}

    /**
     * Gera o QR Code como string base64 (data URI) para a mesa.
     *
     * @return array{table: Table, qrcode: string, url: string}|null
     */
    public function execute(int $id): ?array
    {
        $table = $this->repository->findById($id);

        if (!$table) {
            return null;
        }

        $menuUrl = $this->buildMenuUrl($table);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'imageBase64' => true,
        ]);

        $qrcode = (new QRCode($options))->render($menuUrl);

        return [
            'table' => $table,
            'qrcode' => $qrcode,
            'url' => $menuUrl,
        ];
    }

    private function buildMenuUrl(Table $table): string
    {
        $baseUrl = config('app.frontend_url', 'http://localhost');

        return "{$baseUrl}/menu?table={$table->uuid}";
    }
}
```

### Configurar URL do frontend

Adicione a variavel `FRONTEND_URL` no `.env` do backend:

```bash
# Em backend/.env
FRONTEND_URL=http://localhost
```

Registre no config `backend/config/app.php`. Adicione ao array de configuracoes (dentro do `return []`):

```php
'frontend_url' => env('FRONTEND_URL', 'http://localhost'),
```

> **Dica:** Procure o final do array `return [...]` em `config/app.php` e adicione antes do `];`.

### Endpoint no Controller

Adicione o metodo `qrcode` no `TableController`:

```php
use App\Actions\Table\GenerateQrCodeAction;

// Adicione este metodo no final da classe TableController:

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

        if (!$result) {
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
```

### Rota do QR Code

Adicione a rota no grupo `tenant:required` em `backend/routes/api.php`, logo apos o `apiResource` de tables:

```php
            // Table QR Code
            Route::get('tables/{table}/qrcode', [TableController::class, 'qrcode'])
                ->middleware('permission:tables.view');
```

### Testar o QR Code

```bash
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gerente@demo.com","password":"password"}' | \
  python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s http://localhost/api/v1/tables/1/qrcode \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

Saida esperada:

```json
{
    "data": {
        "table": {
            "id": 1,
            "uuid": "550e8400-...",
            "identify": "Mesa 01",
            "description": "Area interna - 4 lugares",
            ...
        },
        "qrcode": "data:image/png;base64,iVBORw0KGgo...",
        "url": "http://localhost/menu?table=550e8400-..."
    }
}
```

> **O campo `qrcode`** e uma string base64 que pode ser usada diretamente em uma tag `<img src="...">` no frontend.

### Verificar no Swagger

Acesse `http://127.0.0.1/docs/api` e verifique que o endpoint `GET /api/v1/tables/{table}/qrcode` aparece na tag **Mesas**.

---

## Passo 6.7 - Frontend: tipos TypeScript + servico de Mesas

### Tipo TypeScript

Adicione a interface `Table` em `frontend/src/types/catalog.ts`:

```typescript
export interface Table {
  id: number;
  uuid: string;
  identify: string;
  description: string | null;
  created_at: string;
  updated_at: string;
}

export interface TableQrCode {
  table: Table;
  qrcode: string;
  url: string;
}
```

### Servico

Crie `frontend/src/services/table-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Table, TableQrCode } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getTables(
  page = 1
): Promise<PaginatedResponse<Table>> {
  return apiClient<PaginatedResponse<Table>>(
    `/v1/tables?page=${page}`
  );
}

export async function getTable(
  id: number
): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>(`/v1/tables/${id}`);
}

export async function createTable(data: {
  identify: string;
  description?: string;
}): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>("/v1/tables", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateTable(
  id: number,
  data: { identify: string; description?: string }
): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>(`/v1/tables/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteTable(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/tables/${id}`, {
    method: "DELETE",
  });
}

export async function getTableQrCode(
  id: number
): Promise<{ data: TableQrCode }> {
  return apiClient<{ data: TableQrCode }>(`/v1/tables/${id}/qrcode`);
}
```

---

## Passo 6.8 - Frontend: pagina de Mesas (CRUD + QR Code)

### Componentes

Crie o diretorio para componentes de mesas:

```bash
mkdir -p frontend/src/components/tables
```

**Dialog de formulario** — `frontend/src/components/tables/table-form-dialog.tsx`:

```tsx
"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { createTable, updateTable } from "@/services/table-service";
import type { Table } from "@/types/catalog";
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

const tableSchema = z.object({
  identify: z.string().min(1, "O identificador e obrigatorio"),
  description: z.string().optional(),
});

type TableFormData = z.infer<typeof tableSchema>;

interface TableFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
  table?: Table;
}

export function TableFormDialog({
  open,
  onOpenChange,
  onSaved,
  table,
}: TableFormDialogProps) {
  const isEditing = !!table;

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<TableFormData>({
    resolver: zodResolver(tableSchema),
    defaultValues: {
      identify: table?.identify || "",
      description: table?.description || "",
    },
  });

  useEffect(() => {
    if (open) {
      reset({
        identify: table?.identify || "",
        description: table?.description || "",
      });
    }
  }, [open, table, reset]);

  const onSubmit = async (data: TableFormData) => {
    try {
      if (isEditing) {
        await updateTable(table.id, data);
      } else {
        await createTable(data);
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
            {isEditing ? "Editar Mesa" : "Nova Mesa"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {errors.root && (
            <p className="text-sm text-destructive">{errors.root.message}</p>
          )}

          <div className="space-y-2">
            <Label htmlFor="identify">Identificador</Label>
            <Input id="identify" placeholder="Mesa 01, VIP-03..." {...register("identify")} />
            {errors.identify && (
              <p className="text-sm text-destructive">{errors.identify.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descricao</Label>
            <Textarea id="description" placeholder="Area interna, 4 lugares..." {...register("description")} />
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

**Dialog de exclusao** — `frontend/src/components/tables/delete-table-dialog.tsx`:

```tsx
"use client";

import { useState } from "react";
import { deleteTable } from "@/services/table-service";
import type { Table } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteTableDialogProps {
  table: Table | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteTableDialog({
  table,
  onOpenChange,
  onDeleted,
}: DeleteTableDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!table) return;

    setLoading(true);
    try {
      await deleteTable(table.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover mesa:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!table} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Mesa</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover a mesa &quot;{table?.identify}
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

**Dialog de QR Code** — `frontend/src/components/tables/qrcode-dialog.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getTableQrCode } from "@/services/table-service";
import type { Table, TableQrCode } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Download } from "lucide-react";

interface QrCodeDialogProps {
  table: Table | null;
  onOpenChange: (open: boolean) => void;
}

export function QrCodeDialog({ table, onOpenChange }: QrCodeDialogProps) {
  const [data, setData] = useState<TableQrCode | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!table) {
      setData(null);
      return;
    }

    setLoading(true);
    getTableQrCode(table.id)
      .then((res) => setData(res.data))
      .catch((err) => console.error("Erro ao carregar QR Code:", err))
      .finally(() => setLoading(false));
  }, [table]);

  const handleDownload = () => {
    if (!data) return;

    const link = document.createElement("a");
    link.href = data.qrcode;
    link.download = `qrcode-${table?.identify?.replace(/\s+/g, "-").toLowerCase()}.png`;
    link.click();
  };

  return (
    <Dialog open={!!table} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>QR Code — {table?.identify}</DialogTitle>
        </DialogHeader>

        {loading ? (
          <Skeleton className="h-64 w-64 mx-auto" />
        ) : data ? (
          <div className="flex flex-col items-center gap-4">
            <img
              src={data.qrcode}
              alt={`QR Code para ${table?.identify}`}
              className="w-64 h-64"
            />
            <p className="text-sm text-muted-foreground text-center break-all">
              {data.url}
            </p>
            <Button onClick={handleDownload} variant="outline" className="w-full">
              <Download className="mr-2 h-4 w-4" />
              Baixar QR Code
            </Button>
          </div>
        ) : (
          <p className="text-sm text-muted-foreground text-center">
            Erro ao carregar QR Code.
          </p>
        )}
      </DialogContent>
    </Dialog>
  );
}
```

> **Como funciona o download:** Criamos um elemento `<a>` temporario com o `href` apontando para o data URI base64 e acionamos o click programaticamente. O navegador baixa a imagem PNG.

### Pagina de Mesas

Crie `frontend/src/app/(admin)/tables/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getTables } from "@/services/table-service";
import type { Table } from "@/types/catalog";
import {
  Table as UITable,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2, QrCode } from "lucide-react";
import { TableFormDialog } from "@/components/tables/table-form-dialog";
import { DeleteTableDialog } from "@/components/tables/delete-table-dialog";
import { QrCodeDialog } from "@/components/tables/qrcode-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

export default function TablesPage() {
  const [tables, setTables] = useState<Table[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editTable, setEditTable] = useState<Table | null>(null);
  const [deleteState, setDeleteState] = useState<Table | null>(null);
  const [qrTable, setQrTable] = useState<Table | null>(null);

  const fetchTables = async () => {
    try {
      const response = await getTables();
      setTables(response.data);
    } catch (error) {
      console.error("Erro ao carregar mesas:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTables();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditTable(null);
    fetchTables();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchTables();
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="mesas" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Mesas</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Mesa
        </Button>
      </div>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <UITable>
          <TableHeader>
            <TableRow>
              <TableHead>Identificador</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead>UUID</TableHead>
              <TableHead className="w-[140px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {tables.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-center text-muted-foreground">
                  Nenhuma mesa cadastrada.
                </TableCell>
              </TableRow>
            ) : (
              tables.map((table) => (
                <TableRow key={table.id}>
                  <TableCell className="font-medium">{table.identify}</TableCell>
                  <TableCell>{table.description || "—"}</TableCell>
                  <TableCell className="text-muted-foreground text-xs font-mono">
                    {table.uuid.substring(0, 8)}...
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        title="QR Code"
                        onClick={() => setQrTable(table)}
                      >
                        <QrCode className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Editar"
                        onClick={() => setEditTable(table)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Remover"
                        onClick={() => setDeleteState(table)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </UITable>
      )}

      <TableFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editTable && (
        <TableFormDialog
          open={!!editTable}
          onOpenChange={() => setEditTable(null)}
          onSaved={handleSaved}
          table={editTable}
        />
      )}

      <DeleteTableDialog
        table={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />

      <QrCodeDialog
        table={qrTable}
        onOpenChange={() => setQrTable(null)}
      />
    </div>
  );
}
```

> **Nota sobre nome:** Importamos o componente `Table` do shadcn como `UITable` para nao conflitar com o tipo `Table` do nosso dominio.

### Sidebar ja configurada

A sidebar ja foi configurada no Passo 5.12 com o item "Mesas" no grupo **Operacao** (visivel para usuarios com tenant ou super-admin):

```tsx
const tenantItems = [
  { title: "Categorias", url: "/categories", icon: FolderTree },
  { title: "Produtos", url: "/products", icon: ShoppingBasket },
  { title: "Pedidos", url: "/orders", icon: ShoppingBag },
  { title: "Mesas", url: "/tables", icon: QrCode },       // ← ja existe
  { title: "Clientes", url: "/customers", icon: Users },
  { title: "Avaliacoes", url: "/reviews", icon: Star },
];
```

Nenhuma alteracao necessaria na sidebar.

### Arquivos criados

```
frontend/src/
├── types/catalog.ts                    (modificado — Table + TableQrCode)
├── services/table-service.ts           (novo — CRUD + QR Code)
├── app/(admin)/tables/page.tsx         (novo — pagina de mesas)
├── components/tables/
│   ├── table-form-dialog.tsx           (novo — form create/edit)
│   ├── delete-table-dialog.tsx         (novo — confirmacao de exclusao)
│   └── qrcode-dialog.tsx              (novo — visualizacao + download QR)
```

---

## Passo 6.9 - Verificacao end-to-end da Fase 6

### Checklist de verificacao

**Backend:**

- [ ] Migration `create_tables_table` rodou sem erros
- [ ] `Table` model com `BelongsToTenant` e `ObservedBy(TableObserver)`
- [ ] `TableObserver` gera UUID automaticamente no `creating`
- [ ] `TableRepository` implementa `findByUuid()`
- [ ] 5 Actions (List, Show, Create, Update, Delete) + `GenerateQrCodeAction`
- [ ] `TableController` com 5 metodos CRUD + `qrcode()`
- [ ] Rotas dentro do grupo `tenant:required`
- [ ] Permissoes `tables.*` ja existem no `PermissionSeeder`
- [ ] Seeder cria 6 mesas para "Restaurante Demo"
- [ ] `chillerlan/php-qrcode` instalado
- [ ] Endpoint `GET /tables/{id}/qrcode` retorna QR Code base64
- [ ] Swagger mostra endpoints na tag "Mesas"

**Frontend:**

- [ ] Tipo `Table` e `TableQrCode` em `catalog.ts`
- [ ] Servico `table-service.ts` com CRUD + `getTableQrCode()`
- [ ] Pagina `/tables` lista mesas com CRUD
- [ ] Dialog de QR Code exibe imagem e permite download
- [ ] `TenantRequiredAlert` aparece para super-admin sem tenant
- [ ] Sidebar ja tem link "Mesas" no grupo Operacao

### Fluxo completo de teste

1. Acesse `http://localhost` e faca login como `gerente@demo.com` / `password`
2. No sidebar, clique em **Mesas** (grupo Operacao)
3. Verifique que as 6 mesas do seeder aparecem na tabela
4. Clique em **Nova Mesa** → preencha "Mesa 07" e "Teste" → **Salvar**
5. Clique no icone **QR Code** da Mesa 01 → verifique a imagem e URL
6. Clique em **Baixar QR Code** → verifique que o PNG foi baixado
7. Clique no icone **Editar** da Mesa 07 → altere a descricao → **Salvar**
8. Clique no icone **Remover** da Mesa 07 → confirme a exclusao
9. Faca logout e login como `admin@orderly.com` / `password`
10. Acesse `/tables` → verifique que o alerta "Tenant necessario" aparece

### Resumo dos arquivos da Fase 6

**Backend:**

```
backend/
├── database/
│   ├── migrations/0001_01_02_000009_create_tables_table.php
│   └── seeders/TableSeeder.php
├── app/
│   ├── Models/Table.php
│   ├── Observers/TableObserver.php
│   ├── Repositories/
│   │   ├── Contracts/TableRepositoryInterface.php
│   │   └── Eloquent/TableRepository.php
│   ├── DTOs/Table/
│   │   ├── CreateTableDTO.php
│   │   └── UpdateTableDTO.php
│   ├── Actions/Table/
│   │   ├── ListTablesAction.php
│   │   ├── ShowTableAction.php
│   │   ├── CreateTableAction.php
│   │   ├── UpdateTableAction.php
│   │   ├── DeleteTableAction.php
│   │   └── GenerateQrCodeAction.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/TableController.php
│   │   ├── Requests/Table/
│   │   │   ├── StoreTableRequest.php
│   │   │   └── UpdateTableRequest.php
│   │   └── Resources/TableResource.php
│   └── Providers/RepositoryServiceProvider.php (modificado)
├── config/app.php (modificado — frontend_url)
└── routes/api.php (modificado — rotas de tables)
```

**Frontend:**

```
frontend/src/
├── types/catalog.ts                    (modificado — Table + TableQrCode)
├── services/table-service.ts
├── app/(admin)/tables/page.tsx
└── components/tables/
    ├── table-form-dialog.tsx
    ├── delete-table-dialog.tsx
    └── qrcode-dialog.tsx
```

**Conceitos aprendidos:**
- **QR Code server-side** — geracao de QR Code via biblioteca PHP, retornando base64 para o frontend
- **Data URI** — formato `data:image/png;base64,...` permite exibir imagens inline sem arquivo separado
- **Download programatico** — criar elemento `<a>` temporario com `click()` para download de data URI
- **`findByUuid()`** — busca por identificador publico em vez de ID sequencial
- **`chillerlan/php-qrcode`** — biblioteca leve para gerar QR Codes em PHP
- **Conflito de nomes** — renomear import (`Table as UITable`) quando o componente UI conflita com tipo do dominio
- **Reutilizacao de infraestrutura** — `BelongsToTenant`, `TenantScope`, `tenant:required` e `TenantRequiredAlert` funcionam sem alteracoes

**Proximo:** Fase 7 - Sistema de Pedidos

---


---

[Voltar ao README](../README.md)
