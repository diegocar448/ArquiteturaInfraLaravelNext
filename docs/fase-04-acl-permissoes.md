# Fase 4 - ACL: Permissoes, Perfis e Papeis

**Objetivo:** Implementar controle de acesso granular com dupla camada — restringindo funcionalidades por plano de assinatura E por papel do usuario.

**O que voce vai aprender:**
- ACL (Access Control List) de dupla camada
- Tabelas pivot many-to-many no Laravel
- Sync de relacionamentos (attach/detach em lote)
- Middleware de autorizacao customizado
- Trait para verificacao de permissoes
- Gate/Policy pattern simplificado

**Pre-requisitos:**
- Fase 3 completa e funcionando
- Banco com plans, tenants e users seedados

---

## Passo 4.1 - Conceito: ACL de dupla camada

Antes de codar, entenda a arquitetura de permissoes do Orderly:

```
┌──────────────────────────────────────────────────┐
│                 CAMADA DO PLANO                   │
│                                                    │
│   Plan ←──── plan_profile ────→ Profile            │
│                                    │               │
│                            permission_profile      │
│                                    │               │
│                              Permission            │
│                                                    │
│   "O que o PLANO permite"                         │
└──────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────┐
│                CAMADA DO USUARIO                  │
│                                                    │
│   User ←──── role_user ────→ Role                 │
│                                  │                 │
│                          permission_role           │
│                                  │                 │
│                            Permission              │
│                                                    │
│   "O que o USUARIO pode fazer"                    │
└──────────────────────────────────────────────────┘

Permissao efetiva = usuario tem via Role AND plano tem via Profile
```

**Exemplo pratico:**

| Cenario | Resultado |
|---|---|
| Plano Basico tem perfil "Admin" com `orders.view` | O plano PERMITE ver pedidos |
| Usuario tem role "Gerente" com `orders.view` | O usuario PODE ver pedidos |
| **Permissao efetiva:** | ✅ Acesso liberado |
| Plano Basico NAO tem `products.create` em nenhum perfil | O plano NAO permite criar produtos |
| Usuario tem role com `products.create` | O usuario teria permissao... |
| **Permissao efetiva:** | ❌ Bloqueado pelo plano |

**Entidades:**
- **Permission** — acao atomica (ex: `plans.create`, `orders.view`)
- **Profile** — template de permissoes vinculado a planos (ex: "Admin", "Gerente")
- **Role** — papel atribuido a usuarios, escopado por tenant (ex: "Administrador", "Atendente")

**Super-admin** (sem tenant) tem todas as permissoes automaticamente — nao passa pelo check de ACL.

---

## Passo 4.2 - Migration: tabela permissions + Model

Crie a migration:

```bash
docker compose exec backend php artisan make:migration create_permissions_table --create=permissions
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

> **Dica de permissao:** O segundo comando corrige as permissoes dos arquivos criados pelo container Docker (que pertencem ao `root`), permitindo editar/renomear no VSCode. Confirme seu UID com `id -u` no terminal (em WSL geralmente e `1000`). Este comando sera repetido apos cada `make:migration`.

Renomeie o arquivo gerado para `0001_01_02_000005_create_permissions_table.php` e edite:

`backend/database/migrations/0001_01_02_000005_create_permissions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // ex: plans.create
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
```

Crie o Model `backend/app/Models/Permission.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

> **Nota:** Os relacionamentos `profiles()` e `roles()` referenciam Models que ainda nao existem. Vamos cria-los nos proximos passos. O Laravel so resolve esses relacionamentos em runtime, entao nao causa erro agora.

Rode a migration:

```bash
docker compose exec backend php artisan migrate
```

**Teste:**

```bash
docker compose exec backend php artisan tinker
> Schema::hasTable('permissions')
# true
```

---

## Passo 4.3 - Migration: tabela profiles + pivots + Model

Vamos criar **3 migrations**: a tabela `profiles` e as duas pivots (`permission_profile` e `plan_profile`).

### Migration: profiles

```bash
docker compose exec backend php artisan make:migration create_profiles_table --create=profiles
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000006_create_profiles_table.php` e edite:

`backend/database/migrations/0001_01_02_000006_create_profiles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
```

### Migration: permission_profile (pivot)

```bash
docker compose exec backend php artisan make:migration create_permission_profile_table --create=permission_profile
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000007_create_permission_profile_table.php` e edite:

`backend/database/migrations/0001_01_02_000007_create_permission_profile_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_profile', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_profile');
    }
};
```

### Migration: plan_profile (pivot)

```bash
docker compose exec backend php artisan make:migration create_plan_profile_table --create=plan_profile
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000008_create_plan_profile_table.php` e edite:

`backend/database/migrations/0001_01_02_000008_create_plan_profile_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_profile', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['plan_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_profile');
    }
};
```

### Model Profile

Crie `backend/app/Models/Profile.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}
```

Rode as migrations:

```bash
docker compose exec backend php artisan migrate
```

**Teste:**

```bash
docker compose exec backend php artisan tinker
> Schema::hasTable('profiles')
# true
> Schema::hasTable('permission_profile')
# true
> Schema::hasTable('plan_profile')
# true
```

> **Convencao Laravel:** Nomes de tabelas pivot seguem ordem alfabetica dos dois models (`permission_profile`, nao `profile_permission`). O Laravel detecta automaticamente.

---

## Passo 4.4 - Migration: tabela roles + pivots + Model

Mesmo padrao: tabela `roles` + duas pivots (`permission_role` e `role_user`).

### Migration: roles

```bash
docker compose exec backend php artisan make:migration create_roles_table --create=roles
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000009_create_roles_table.php` e edite:

`backend/database/migrations/0001_01_02_000009_create_roles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();

            // Mesmo tenant nao pode ter dois roles com mesmo nome
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
```

> **Por que `tenant_id` em roles?** Roles sao escopados por tenant. Cada restaurante cria seus proprios papeis ("Cozinheiro", "Garcom", etc.). O `unique(['tenant_id', 'name'])` garante que nomes nao se repitam dentro do mesmo tenant.

### Migration: permission_role (pivot)

```bash
docker compose exec backend php artisan make:migration create_permission_role_table --create=permission_role
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000010_create_permission_role_table.php` e edite:

`backend/database/migrations/0001_01_02_000010_create_permission_role_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
```

### Migration: role_user (pivot)

```bash
docker compose exec backend php artisan make:migration create_role_user_table --create=role_user
docker compose exec backend chown -R 1000:1000 /var/www/html/database/migrations/
```

Renomeie o arquivo gerado para `0001_01_02_000011_create_role_user_table.php` e edite:

`backend/database/migrations/0001_01_02_000011_create_role_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
```

### Model Role

Crie `backend/app/Models/Role.php`:

```php
<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

> **BelongsToTenant:** O Role usa o mesmo trait dos Models da Fase 3. Queries de roles sao automaticamente filtradas pelo `tenant_id` do usuario logado. Super-admin ve todos.

Rode as migrations:

```bash
docker compose exec backend php artisan migrate
```

**Teste:**

```bash
docker compose exec backend php artisan tinker
> Schema::hasTable('roles')
# true
> Schema::hasTable('permission_role')
# true
> Schema::hasTable('role_user')
# true
```

---

## Passo 4.5 - Atualizar Models existentes (Plan, User)

Agora precisamos adicionar os novos relacionamentos nos Models existentes.

### Plan — adicionar profiles()

Edite `backend/app/Models/Plan.php` e adicione o metodo `profiles()`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// ... dentro da classe Plan, apos tenants():

public function profiles(): BelongsToMany
{
    return $this->belongsToMany(Profile::class);
}
```

O arquivo completo fica:

```php
<?php

namespace App\Models;

use App\Observers\PlanObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function details(): HasMany
    {
        return $this->hasMany(DetailPlan::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class);
    }
}
```

### User — adicionar roles()

Edite `backend/app/Models/User.php` e adicione o metodo `roles()`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// ... dentro da classe User, apos isSuperAdmin():

public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

**Teste no tinker:**

```bash
docker compose exec backend php artisan tinker
> $plan = App\Models\Plan::first()
> $plan->profiles  # Collection vazia (ainda sem dados)
> $user = App\Models\User::first()
> $user->roles     # Collection vazia (ainda sem dados)
```

---

## Passo 4.6 - Permission Seeder

Vamos popular o banco com todas as permissoes do sistema.

Crie `backend/database/seeders/PermissionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            'plans' => 'Planos',
            'detail_plans' => 'Detalhes de planos',
            'tenants' => 'Tenants',
            'categories' => 'Categorias',
            'products' => 'Produtos',
            'tables' => 'Mesas',
            'orders' => 'Pedidos',
            'users' => 'Usuarios',
            'roles' => 'Papeis',
            'profiles' => 'Perfis',
        ];

        $actions = [
            'view' => 'Visualizar',
            'create' => 'Criar',
            'edit' => 'Atualizar',
            'delete' => 'Remover',
        ];

        foreach ($resources as $resource => $resourceLabel) {
            foreach ($actions as $action => $actionLabel) {
                Permission::firstOrCreate(
                    ['name' => "{$resource}.{$action}"],
                    ['description' => "{$actionLabel} {$resourceLabel}"],
                );
            }
        }
    }
}
```

Adicione ao `DatabaseSeeder` (antes do `TenantSeeder`):

```php
// backend/database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        PlanSeeder::class,
        PermissionSeeder::class,  // NOVO
        AdminUserSeeder::class,
        TenantSeeder::class,
    ]);
}
```

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=PermissionSeeder
```

**Teste:**

```bash
docker compose exec backend php artisan tinker
> App\Models\Permission::count()
# 40 (10 recursos x 4 acoes)
> App\Models\Permission::where('name', 'like', 'plans.%')->pluck('name')
# ["plans.view", "plans.create", "plans.edit", "plans.delete"]
```

---

## Passo 4.7 - Profile Repository + CRUD completo

Agora vamos criar o CRUD completo de Profiles seguindo o padrao Clean Architecture.

### Repository Interface

Crie `backend/app/Repositories/Contracts/ProfileRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Profile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProfileRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Profile;

    public function create(array $data): Profile;

    public function update(int $id, array $data): ?Profile;

    public function delete(int $id): bool;
}
```

### Repository Implementation

Crie `backend/app/Repositories/Eloquent/ProfileRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly Profile $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Profile
    {
        return $this->model->find($id);
    }

    public function create(array $data): Profile
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Profile
    {
        $profile = $this->findById($id);

        if (!$profile) {
            return null;
        }

        $profile->update($data);

        return $profile->fresh();
    }

    public function delete(int $id): bool
    {
        $profile = $this->findById($id);

        if (!$profile) {
            return false;
        }

        return (bool) $profile->delete();
    }
}
```

### DTOs

Crie `backend/app/DTOs/Profile/CreateProfileDTO.php`:

```php
<?php

namespace App\DTOs\Profile;

use App\Http\Requests\Profile\StoreProfileRequest;

final readonly class CreateProfileDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProfileRequest $request): self
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

Crie `backend/app/DTOs/Profile/UpdateProfileDTO.php`:

```php
<?php

namespace App\DTOs\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;

final readonly class UpdateProfileDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): self
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

### Actions

Crie os 5 arquivos de Actions em `backend/app/Actions/Profile/`:

`ListProfilesAction.php`:

```php
<?php

namespace App\Actions\Profile;

use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProfilesAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

`ShowProfileAction.php`:

```php
<?php

namespace App\Actions\Profile;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class ShowProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Profile
    {
        return $this->repository->findById($id);
    }
}
```

`CreateProfileAction.php`:

```php
<?php

namespace App\Actions\Profile;

use App\DTOs\Profile\CreateProfileDTO;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class CreateProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(CreateProfileDTO $dto): Profile
    {
        return $this->repository->create($dto->toArray());
    }
}
```

`UpdateProfileAction.php`:

```php
<?php

namespace App\Actions\Profile;

use App\DTOs\Profile\UpdateProfileDTO;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

final class UpdateProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateProfileDTO $dto): ?Profile
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

`DeleteProfileAction.php`:

```php
<?php

namespace App\Actions\Profile;

use App\Repositories\Contracts\ProfileRepositoryInterface;

final class DeleteProfileAction
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

### Form Requests

Crie `backend/app/Http/Requests/Profile/StoreProfileRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do perfil e obrigatorio.',
            'name.max' => 'O nome deve ter no maximo 255 caracteres.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Profile/UpdateProfileRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do perfil e obrigatorio.',
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/ProfileResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

Crie tambem `backend/app/Http/Resources/PermissionResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
```

### Controller

Crie `backend/app/Http/Controllers/Api/V1/ProfileController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\DTOs\Profile\CreateProfileDTO;
use App\DTOs\Profile\UpdateProfileDTO;
use App\Actions\Profile\ListProfilesAction;
use App\Actions\Profile\ShowProfileAction;
use App\Actions\Profile\CreateProfileAction;
use App\Actions\Profile\UpdateProfileAction;
use App\Actions\Profile\DeleteProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    public function index(ListProfilesAction $action): AnonymousResourceCollection
    {
        $profiles = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return ProfileResource::collection($profiles);
    }

    public function store(StoreProfileRequest $request, CreateProfileAction $action): JsonResponse
    {
        $profile = $action->execute(CreateProfileDTO::fromRequest($request));

        return (new ProfileResource($profile))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $profile, ShowProfileAction $action): JsonResponse
    {
        $profile = $action->execute($profile);

        if (!$profile) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        $profile->load('permissions');

        return response()->json([
            'data' => new ProfileResource($profile),
        ]);
    }

    public function update(UpdateProfileRequest $request, int $profile, UpdateProfileAction $action): JsonResponse
    {
        $updated = $action->execute($profile, UpdateProfileDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new ProfileResource($updated),
        ]);
    }

    public function destroy(int $profile, DeleteProfileAction $action): JsonResponse
    {
        $deleted = $action->execute($profile);

        if (!$deleted) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Perfil removido com sucesso.',
        ]);
    }
}
```

### Routes

Edite `backend/routes/api.php` e adicione dentro do grupo protegido:

```php
use App\Http\Controllers\Api\V1\ProfileController;

// Dentro de Route::middleware('auth:api', 'tenant')->group(function () {
    // Profiles CRUD
    Route::apiResource('profiles', ProfileController::class);
```

### Service Provider

Edite `backend/app/Providers/RepositoryServiceProvider.php` e adicione:

```php
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Eloquent\ProfileRepository;

// No array $repositories:
ProfileRepositoryInterface::class => ProfileRepository::class,
```

**Teste da API:**

```bash
# Login
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Criar perfil
curl -s -X POST http://127.0.0.1/api/v1/profiles \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name": "Teste", "description": "Perfil de teste"}' | python3 -m json.tool

# Listar perfis
curl -s http://127.0.0.1/api/v1/profiles \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Deletar perfil de teste
curl -s -X DELETE http://127.0.0.1/api/v1/profiles/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

---

## Passo 4.8 - Role Repository + CRUD completo

Mesmo padrao do Profile, mas Role e escopado por tenant (usa `BelongsToTenant`).

### Repository Interface

Crie `backend/app/Repositories/Contracts/RoleRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): ?Role;

    public function delete(int $id): bool;
}
```

### Repository Implementation

Crie `backend/app/Repositories/Eloquent/RoleRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private readonly Role $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return $this->model->find($id);
    }

    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Role
    {
        $role = $this->findById($id);

        if (!$role) {
            return null;
        }

        $role->update($data);

        return $role->fresh();
    }

    public function delete(int $id): bool
    {
        $role = $this->findById($id);

        if (!$role) {
            return false;
        }

        return (bool) $role->delete();
    }
}
```

### DTOs

Crie `backend/app/DTOs/Role/CreateRoleDTO.php`:

```php
<?php

namespace App\DTOs\Role;

use App\Http\Requests\Role\StoreRoleRequest;

final readonly class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreRoleRequest $request): self
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

Crie `backend/app/DTOs/Role/UpdateRoleDTO.php`:

```php
<?php

namespace App\DTOs\Role;

use App\Http\Requests\Role\UpdateRoleRequest;

final readonly class UpdateRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(UpdateRoleRequest $request): self
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

### Actions

Crie os 5 arquivos em `backend/app/Actions/Role/`:

`ListRolesAction.php`:

```php
<?php

namespace App\Actions\Role;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRolesAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
```

`ShowRoleAction.php`:

```php
<?php

namespace App\Actions\Role;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class ShowRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): ?Role
    {
        return $this->repository->findById($id);
    }
}
```

`CreateRoleAction.php`:

```php
<?php

namespace App\Actions\Role;

use App\DTOs\Role\CreateRoleDTO;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class CreateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(CreateRoleDTO $dto): Role
    {
        return $this->repository->create($dto->toArray());
    }
}
```

`UpdateRoleAction.php`:

```php
<?php

namespace App\Actions\Role;

use App\DTOs\Role\UpdateRoleDTO;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class UpdateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UpdateRoleDTO $dto): ?Role
    {
        return $this->repository->update($id, $dto->toArray());
    }
}
```

`DeleteRoleAction.php`:

```php
<?php

namespace App\Actions\Role;

use App\Repositories\Contracts\RoleRepositoryInterface;

final class DeleteRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
```

### Form Requests

Crie `backend/app/Http/Requests/Role/StoreRoleRequest.php`:

```php
<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do papel e obrigatorio.',
            'name.max' => 'O nome deve ter no maximo 255 caracteres.',
        ];
    }
}
```

Crie `backend/app/Http/Requests/Role/UpdateRoleRequest.php`:

```php
<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do papel e obrigatorio.',
        ];
    }
}
```

### Resource

Crie `backend/app/Http/Resources/RoleResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### Controller

Crie `backend/app/Http/Controllers/Api/V1/RoleController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Actions\Role\ListRolesAction;
use App\Actions\Role\ShowRoleAction;
use App\Actions\Role\CreateRoleAction;
use App\Actions\Role\UpdateRoleAction;
use App\Actions\Role\DeleteRoleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function index(ListRolesAction $action): AnonymousResourceCollection
    {
        $roles = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $role = $action->execute(CreateRoleDTO::fromRequest($request));

        return (new RoleResource($role))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $role, ShowRoleAction $action): JsonResponse
    {
        $role = $action->execute($role);

        if (!$role) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        $role->load('permissions');

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $role, UpdateRoleAction $action): JsonResponse
    {
        $updated = $action->execute($role, UpdateRoleDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new RoleResource($updated),
        ]);
    }

    public function destroy(int $role, DeleteRoleAction $action): JsonResponse
    {
        $deleted = $action->execute($role);

        if (!$deleted) {
            return response()->json(['message' => 'Papel nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Papel removido com sucesso.',
        ]);
    }
}
```

### Routes

Edite `backend/routes/api.php` e adicione:

```php
use App\Http\Controllers\Api\V1\RoleController;

// Dentro do grupo protegido:
    // Roles CRUD
    Route::apiResource('roles', RoleController::class);
```

### Service Provider

Edite `backend/app/Providers/RepositoryServiceProvider.php` e adicione:

```php
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Eloquent\RoleRepository;

// No array $repositories:
RoleRepositoryInterface::class => RoleRepository::class,
```

**Teste da API:**

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Criar role (super-admin cria sem tenant_id)
curl -s -X POST http://127.0.0.1/api/v1/roles \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name": "Teste", "description": "Role de teste"}' | python3 -m json.tool

# Listar roles
curl -s http://127.0.0.1/api/v1/roles \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Deletar role de teste
curl -s -X DELETE http://127.0.0.1/api/v1/roles/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

> **Nota sobre tenant_id:** Quando o super-admin cria um role, `tenant_id` fica `null` (porque `BelongsToTenant::creating` so preenche se o usuario tem `tenant_id`). Quando um usuario-tenant cria, o `tenant_id` e auto-preenchido. Em producao, voce pode querer impedir super-admin de criar roles sem tenant — por ora deixamos flexivel.

---

## Passo 4.9 - Endpoints de Sync (vincular permissoes e perfis)

Agora precisamos de endpoints para vincular:
1. **Permissions ↔ Profile** — quais permissoes um perfil tem
2. **Profiles ↔ Plan** — quais perfis um plano oferece
3. **Permissions ↔ Role** — quais permissoes um papel tem
4. **Roles ↔ User** — quais papeis um usuario tem

Vamos usar o metodo `sync()` do Laravel, que substitui todos os vinculos de uma vez.

### Controller de Sync

Crie `backend/app/Http/Controllers/Api/V1/AclSyncController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AclSyncController extends Controller
{
    /**
     * Sync permissions de um profile.
     * POST /v1/profiles/{profile}/permissions
     * Body: { "permissions": [1, 2, 3] }
     */
    public function syncProfilePermissions(Request $request, int $profile): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $profile = Profile::findOrFail($profile);
        $profile->permissions()->sync($request->permissions);

        $profile->load('permissions');

        return response()->json([
            'message' => 'Permissoes do perfil atualizadas.',
            'data' => [
                'profile_id' => $profile->id,
                'permissions' => $profile->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync profiles de um plan.
     * POST /v1/plans/{plan}/profiles
     * Body: { "profiles": [1, 2] }
     */
    public function syncPlanProfiles(Request $request, int $plan): JsonResponse
    {
        $request->validate([
            'profiles' => ['required', 'array'],
            'profiles.*' => ['integer', 'exists:profiles,id'],
        ]);

        $plan = Plan::findOrFail($plan);
        $plan->profiles()->sync($request->profiles);

        $plan->load('profiles');

        return response()->json([
            'message' => 'Perfis do plano atualizados.',
            'data' => [
                'plan_id' => $plan->id,
                'profiles' => $plan->profiles->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync permissions de um role.
     * POST /v1/roles/{role}/permissions
     * Body: { "permissions": [1, 2, 3] }
     */
    public function syncRolePermissions(Request $request, int $role): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::findOrFail($role);
        $role->permissions()->sync($request->permissions);

        $role->load('permissions');

        return response()->json([
            'message' => 'Permissoes do papel atualizadas.',
            'data' => [
                'role_id' => $role->id,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync roles de um user.
     * POST /v1/users/{user}/roles
     * Body: { "roles": [1, 2] }
     */
    public function syncUserRoles(Request $request, int $user): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($user);
        $user->roles()->sync($request->roles);

        $user->load('roles');

        return response()->json([
            'message' => 'Papeis do usuario atualizados.',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Listar todas as permissions (para exibir em checkboxes no frontend).
     * GET /v1/permissions
     */
    public function listPermissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'data' => $permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
            ]),
        ]);
    }
}
```

### Routes

Edite `backend/routes/api.php` e adicione dentro do grupo protegido:

```php
use App\Http\Controllers\Api\V1\AclSyncController;

// Dentro do grupo protegido:
    // ACL Sync
    Route::get('permissions', [AclSyncController::class, 'listPermissions']);
    Route::post('profiles/{profile}/permissions', [AclSyncController::class, 'syncProfilePermissions']);
    Route::post('plans/{plan}/profiles', [AclSyncController::class, 'syncPlanProfiles']);
    Route::post('roles/{role}/permissions', [AclSyncController::class, 'syncRolePermissions']);
    Route::post('users/{user}/roles', [AclSyncController::class, 'syncUserRoles']);
```

O arquivo `routes/api.php` completo fica:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\AclSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api', 'tenant')->group(function () {
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

        // Profiles CRUD
        Route::apiResource('profiles', ProfileController::class);

        // Roles CRUD
        Route::apiResource('roles', RoleController::class);

        // ACL Sync
        Route::get('permissions', [AclSyncController::class, 'listPermissions']);
        Route::post('profiles/{profile}/permissions', [AclSyncController::class, 'syncProfilePermissions']);
        Route::post('plans/{plan}/profiles', [AclSyncController::class, 'syncPlanProfiles']);
        Route::post('roles/{role}/permissions', [AclSyncController::class, 'syncRolePermissions']);
        Route::post('users/{user}/roles', [AclSyncController::class, 'syncUserRoles']);
    });
});
```

**Teste dos endpoints de Sync:**

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Listar todas as permissoes
curl -s http://127.0.0.1/api/v1/permissions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

Deve retornar as 40 permissoes. Guarde os IDs — voce vai precisar deles nos proximos passos.

---

## Passo 4.10 - Profile Seeder + vinculos com permissoes e planos

Agora vamos criar perfis padrao e vincular permissoes e planos.

Crie `backend/database/seeders/ProfileSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Criar Perfis ────────────────────────────
        $admin = Profile::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Acesso total ao sistema.'],
        );

        $gerente = Profile::firstOrCreate(
            ['name' => 'Gerente'],
            ['description' => 'Gerenciamento do restaurante sem acesso a planos e tenants.'],
        );

        $atendente = Profile::firstOrCreate(
            ['name' => 'Atendente'],
            ['description' => 'Acesso limitado a pedidos e mesas.'],
        );

        // ─── Vincular Permissoes aos Perfis ──────────

        // Admin: TODAS as permissoes
        $allPermissions = Permission::pluck('id')->toArray();
        $admin->permissions()->sync($allPermissions);

        // Gerente: tudo exceto plans.*, tenants.*, profiles.*
        $gerentePermissions = Permission::whereNotIn('name', [
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
            'profiles.view', 'profiles.create', 'profiles.edit', 'profiles.delete',
        ])->pluck('id')->toArray();
        $gerente->permissions()->sync($gerentePermissions);

        // Atendente: apenas visualizar catalogo + gerenciar pedidos e mesas
        $atendentePermissions = Permission::whereIn('name', [
            'categories.view',
            'products.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.edit',
        ])->pluck('id')->toArray();
        $atendente->permissions()->sync($atendentePermissions);

        // ─── Vincular Perfis aos Planos ──────────────

        $basico = Plan::where('name', 'Basico')->first();
        $profissional = Plan::where('name', 'Profissional')->first();
        $enterprise = Plan::where('name', 'Enterprise')->first();

        // Basico: apenas Admin (dono do restaurante faz tudo)
        if ($basico) {
            $basico->profiles()->sync([$admin->id]);
        }

        // Profissional: Admin + Gerente
        if ($profissional) {
            $profissional->profiles()->sync([$admin->id, $gerente->id]);
        }

        // Enterprise: Admin + Gerente + Atendente
        if ($enterprise) {
            $enterprise->profiles()->sync([$admin->id, $gerente->id, $atendente->id]);
        }
    }
}
```

Adicione ao `DatabaseSeeder`:

```php
// backend/database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        PlanSeeder::class,
        PermissionSeeder::class,
        ProfileSeeder::class,     // NOVO
        AdminUserSeeder::class,
        TenantSeeder::class,
    ]);
}
```

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=ProfileSeeder
```

**Teste:**

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Ver perfil Admin com permissoes
curl -s http://127.0.0.1/api/v1/profiles/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Verificar no tinker
docker compose exec backend php artisan tinker
> App\Models\Plan::where('name', 'Profissional')->first()->profiles->pluck('name')
# ["Admin", "Gerente"]
> App\Models\Profile::where('name', 'Gerente')->first()->permissions->pluck('name')
# ["categories.view", "categories.create", ..., "roles.delete"]
```

---

## Passo 4.11 - Role Seeder + vinculos com permissoes e usuarios

Agora vamos criar roles padrao para o tenant existente.

Crie `backend/database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        // ─── Criar Roles para o tenant ───────────────

        $adminRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Administrador'],
            ['description' => 'Administrador do restaurante com acesso total.'],
        );

        $gerenteRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Gerente'],
            ['description' => 'Gerente com acesso a catalogo, pedidos e usuarios.'],
        );

        $atendenteRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Atendente'],
            ['description' => 'Atendente com acesso a pedidos e mesas.'],
        );

        // ─── Vincular Permissoes ─────────────────────

        // Administrador: todas as permissoes operacionais (sem plans/tenants)
        // Inclui profiles.* e roles.* para gerenciar ACL do tenant
        $adminPermissions = Permission::whereNotIn('name', [
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
        ])->pluck('id')->toArray();
        $adminRole->permissions()->sync($adminPermissions);

        // Gerente: catalogo + pedidos + mesas + usuarios (sem roles/profiles)
        $gerentePermissions = Permission::whereIn('name', [
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'tables.view', 'tables.create', 'tables.edit', 'tables.delete',
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'users.view',
        ])->pluck('id')->toArray();
        $gerenteRole->permissions()->sync($gerentePermissions);

        // Atendente: apenas pedidos e mesas (visualizacao + criacao)
        $atendentePermissions = Permission::whereIn('name', [
            'categories.view',
            'products.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.edit',
        ])->pluck('id')->toArray();
        $atendenteRole->permissions()->sync($atendentePermissions);

        // ─── Vincular Role ao usuario gerente ────────

        $gerente = User::where('email', 'gerente@demo.com')->first();
        if ($gerente) {
            $gerente->roles()->sync([$gerenteRole->id]);
        }
    }
}
```

Adicione ao `DatabaseSeeder`:

```php
// backend/database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        PlanSeeder::class,
        PermissionSeeder::class,
        ProfileSeeder::class,
        AdminUserSeeder::class,
        TenantSeeder::class,
        RoleSeeder::class,        // NOVO (apos TenantSeeder)
    ]);
}
```

Rode o seeder:

```bash
docker compose exec backend php artisan db:seed --class=RoleSeeder
```

**Teste:**

```bash
docker compose exec backend php artisan tinker
> $gerente = App\Models\User::where('email', 'gerente@demo.com')->first()
> $gerente->roles->pluck('name')
# ["Gerente"]
> $gerente->roles->first()->permissions->pluck('name')
# ["categories.view", "categories.create", ..., "users.view"]
```

---

## Passo 4.12 - Trait HasPermission no User

Agora vamos criar a logica central de verificacao de permissoes.

Crie `backend/app/Traits/HasPermission.php`:

```php
<?php

namespace App\Traits;

trait HasPermission
{
    /**
     * Verifica se o usuario tem uma permissao efetiva.
     *
     * Permissao efetiva = usuario tem via Role AND plano do tenant tem via Profile.
     * Super-admin tem todas as permissoes automaticamente.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super-admin tem tudo
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Verificar se o usuario tem a permissao em algum de seus roles
        $hasRolePermission = $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.name', $permissionName);
            })
            ->exists();

        if (!$hasRolePermission) {
            return false;
        }

        // Verificar se o plano do tenant inclui essa permissao em algum profile
        $tenant = $this->tenant;

        if (!$tenant || !$tenant->plan) {
            return false;
        }

        return $tenant->plan->profiles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.name', $permissionName);
            })
            ->exists();
    }

    /**
     * Verifica se o usuario tem TODAS as permissoes informadas.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica se o usuario tem PELO MENOS UMA das permissoes informadas.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna todas as permissoes efetivas do usuario (intersecao de role + plan).
     */
    public function effectivePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Permission::pluck('name')->toArray();
        }

        // Permissoes do usuario via roles
        $rolePermissions = \App\Models\Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        })->pluck('name')->toArray();

        if (empty($rolePermissions)) {
            return [];
        }

        // Permissoes do plano via profiles
        $tenant = $this->tenant;

        if (!$tenant || !$tenant->plan) {
            return [];
        }

        $planPermissions = \App\Models\Permission::whereHas('profiles', function ($query) use ($tenant) {
            $query->whereIn('profiles.id', $tenant->plan->profiles()->pluck('profiles.id'));
        })->pluck('name')->toArray();

        // Intersecao = permissoes que existem em ambos
        return array_values(array_intersect($rolePermissions, $planPermissions));
    }
}
```

Adicione o trait ao User. Edite `backend/app/Models/User.php`:

```php
use App\Traits\HasPermission;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasPermission;

    // ... resto do codigo
}
```

**Teste no tinker:**

```bash
docker compose exec backend php artisan tinker

# Super-admin tem tudo
> $admin = App\Models\User::where('email', 'admin@orderly.com')->first()
> $admin->hasPermission('plans.create')
# true
> $admin->hasPermission('qualquer.coisa')
# true (super-admin bypassa tudo)

# Gerente do tenant
> $gerente = App\Models\User::where('email', 'gerente@demo.com')->first()
> $gerente->hasPermission('orders.view')
# true (tem via role Gerente + plano Profissional tem via profile Gerente)
> $gerente->hasPermission('plans.create')
# false (role Gerente nao tem essa permissao)
> $gerente->hasPermission('profiles.view')
# false (plano Profissional nao tem profile com profiles.view)

# Permissoes efetivas
> $gerente->effectivePermissions()
# Array com as permissoes que o gerente realmente pode usar
```

---

## Passo 4.13 - Middleware CheckPermission + proteger rotas

### Criar Middleware

Crie `backend/app/Http/Middleware/CheckPermission.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verifica se o usuario tem a permissao necessaria.
     * Uso: ->middleware('permission:plans.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Voce nao tem permissao para esta acao.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
```

### Registrar Middleware

Edite `backend/bootstrap/app.php` e adicione o alias:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
    $middleware->alias([
        'tenant' => \App\Http\Middleware\IdentifyTenant::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

### Proteger Rotas

Agora edite `backend/routes/api.php` para adicionar permissoes nas rotas existentes:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DetailPlanController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\AclSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT + tenant)
    Route::middleware('auth:api', 'tenant')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Plans CRUD (protegido por permissao)
        Route::apiResource('plans', PlanController::class)
            ->middleware([
                'index' => 'permission:plans.view',
                'show' => 'permission:plans.view',
                'store' => 'permission:plans.create',
                'update' => 'permission:plans.edit',
                'destroy' => 'permission:plans.delete',
            ]);

        // Plan Details (nested)
        Route::apiResource('plans.details', DetailPlanController::class)
            ->except(['show'])
            ->middleware([
                'index' => 'permission:detail_plans.view',
                'store' => 'permission:detail_plans.create',
                'update' => 'permission:detail_plans.edit',
                'destroy' => 'permission:detail_plans.delete',
            ]);

        // Tenants CRUD
        Route::apiResource('tenants', TenantController::class)
            ->middleware([
                'index' => 'permission:tenants.view',
                'show' => 'permission:tenants.view',
                'store' => 'permission:tenants.create',
                'update' => 'permission:tenants.edit',
                'destroy' => 'permission:tenants.delete',
            ]);

        // Profiles CRUD
        Route::apiResource('profiles', ProfileController::class)
            ->middleware([
                'index' => 'permission:profiles.view',
                'show' => 'permission:profiles.view',
                'store' => 'permission:profiles.create',
                'update' => 'permission:profiles.edit',
                'destroy' => 'permission:profiles.delete',
            ]);

        // Roles CRUD
        Route::apiResource('roles', RoleController::class)
            ->middleware([
                'index' => 'permission:roles.view',
                'show' => 'permission:roles.view',
                'store' => 'permission:roles.create',
                'update' => 'permission:roles.edit',
                'destroy' => 'permission:roles.delete',
            ]);

        // ACL Sync (permissoes granulares)
        Route::get('permissions', [AclSyncController::class, 'listPermissions']);
        Route::post('profiles/{profile}/permissions', [AclSyncController::class, 'syncProfilePermissions'])
            ->middleware('permission:profiles.edit');
        Route::post('plans/{plan}/profiles', [AclSyncController::class, 'syncPlanProfiles'])
            ->middleware('permission:plans.edit');
        Route::post('roles/{role}/permissions', [AclSyncController::class, 'syncRolePermissions'])
            ->middleware('permission:roles.edit');
        Route::post('users/{user}/roles', [AclSyncController::class, 'syncUserRoles'])
            ->middleware('permission:users.edit');
    });
});
```

> **Nota sobre `middleware()` em `apiResource`:** O Laravel 12 aceita um array associativo onde a chave e o nome do metodo do controller (`index`, `show`, `store`, `update`, `destroy`) e o valor e o middleware a aplicar. Isso permite permissoes granulares por acao.

**Teste — Super-admin (deve funcionar):**

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Super-admin pode listar planos
curl -s http://127.0.0.1/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
# ✅ Retorna lista de planos
```

**Teste — Gerente (deve ser bloqueado em planos):**

```bash
TOKEN_GERENTE=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "gerente@demo.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Gerente tenta listar planos — deve ser bloqueado
curl -s http://127.0.0.1/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_GERENTE" | python3 -m json.tool
# ❌ {"message": "Voce nao tem permissao para esta acao.", "required_permission": "plans.view"}

# Gerente pode listar roles (tem roles.view via role)
curl -s http://127.0.0.1/api/v1/roles \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_GERENTE" | python3 -m json.tool
# ✅ Retorna lista de roles do tenant
```

Se o gerente conseguir acessar `/plans`, verifique:
1. O gerente tem a role "Gerente" vinculada? (`$gerente->roles->pluck('name')`)
2. A role "Gerente" NAO tem `plans.view`? (`$role->permissions->pluck('name')`)
3. O middleware `permission` esta no bootstrap/app.php?

---

## Passo 4.14 - Frontend: pagina de Perfis (Profiles)

### Tipos

Os tipos de ACL ficam em um arquivo separado do `plan.ts` para manter a organizacao por dominio. O `plan.ts` nao precisa ser alterado.

Crie `frontend/src/types/acl.ts`:

```typescript
export interface Permission {
  id: number;
  name: string;
  description: string | null;
}

export interface Profile {
  id: number;
  name: string;
  description: string | null;
  permissions?: Permission[];
  created_at: string;
  updated_at: string;
}

export interface Role {
  id: number;
  name: string;
  description: string | null;
  permissions?: Permission[];
  created_at: string;
  updated_at: string;
}
```

### Service

Crie `frontend/src/services/acl-service.ts`:

```typescript
import { apiClient } from "@/lib/api";
import type { Permission, Profile, Role } from "@/types/acl";
import type { PaginatedResponse } from "@/types/plan";

// Permissions
export async function getPermissions(): Promise<{ data: Permission[] }> {
  return apiClient<{ data: Permission[] }>("/v1/permissions");
}

// Profiles
export async function getProfiles(page = 1): Promise<PaginatedResponse<Profile>> {
  return apiClient<PaginatedResponse<Profile>>(`/v1/profiles?page=${page}`);
}

export async function getProfile(id: number): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>(`/v1/profiles/${id}`);
}

export async function createProfile(data: {
  name: string;
  description?: string;
}): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>("/v1/profiles", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateProfile(
  id: number,
  data: { name: string; description?: string }
): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>(`/v1/profiles/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteProfile(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/profiles/${id}`, {
    method: "DELETE",
  });
}

export async function syncProfilePermissions(
  profileId: number,
  permissions: number[]
): Promise<unknown> {
  return apiClient(`/v1/profiles/${profileId}/permissions`, {
    method: "POST",
    body: JSON.stringify({ permissions }),
  });
}

// Roles
export async function getRoles(page = 1): Promise<PaginatedResponse<Role>> {
  return apiClient<PaginatedResponse<Role>>(`/v1/roles?page=${page}`);
}

export async function getRole(id: number): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>(`/v1/roles/${id}`);
}

export async function createRole(data: {
  name: string;
  description?: string;
}): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>("/v1/roles", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateRole(
  id: number,
  data: { name: string; description?: string }
): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>(`/v1/roles/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteRole(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/roles/${id}`, {
    method: "DELETE",
  });
}

export async function syncRolePermissions(
  roleId: number,
  permissions: number[]
): Promise<unknown> {
  return apiClient(`/v1/roles/${roleId}/permissions`, {
    method: "POST",
    body: JSON.stringify({ permissions }),
  });
}
```

### Instalar componentes shadcn/ui necessarios

```bash
docker compose exec frontend npx shadcn@latest add checkbox
```

### Pagina de listagem de Perfis

Crie `frontend/src/app/(admin)/profiles/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getProfiles, deleteProfile } from "@/services/acl-service";
import type { Profile } from "@/types/acl";
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
import { Plus, Pencil, Trash2, Shield } from "lucide-react";
import Link from "next/link";

export default function ProfilesPage() {
  const [profiles, setProfiles] = useState<Profile[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchProfiles = async () => {
    try {
      const response = await getProfiles();
      setProfiles(response.data);
    } catch (error) {
      console.error("Erro ao carregar perfis:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProfiles();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("Tem certeza que deseja remover este perfil?")) return;

    try {
      await deleteProfile(id);
      fetchProfiles();
    } catch (error) {
      console.error("Erro ao remover perfil:", error);
    }
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
          <h1 className="text-2xl font-bold">Perfis</h1>
          <p className="text-muted-foreground">
            Gerencie os perfis de acesso vinculados aos planos.
          </p>
        </div>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {profiles.length === 0 ? (
              <TableRow>
                <TableCell colSpan={3} className="text-center py-8">
                  Nenhum perfil cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              profiles.map((profile) => (
                <TableRow key={profile.id}>
                  <TableCell className="font-medium">
                    <Link
                      href={`/profiles/${profile.id}`}
                      className="hover:underline"
                    >
                      {profile.name}
                    </Link>
                  </TableCell>
                  <TableCell className="max-w-xs truncate">
                    {profile.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button variant="ghost" size="icon" asChild>
                        <Link href={`/profiles/${profile.id}`}>
                          <Shield className="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(profile.id)}
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
    </div>
  );
}
```

### Pagina de detalhes do Perfil (com permissoes)

Crie `frontend/src/app/(admin)/profiles/[id]/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import {
  getProfile,
  getPermissions,
  syncProfilePermissions,
} from "@/services/acl-service";
import type { Profile, Permission } from "@/types/acl";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Save } from "lucide-react";

export default function ProfileDetailPage() {
  const params = useParams();
  const router = useRouter();
  const profileId = Number(params.id);

  const [profile, setProfile] = useState<Profile | null>(null);
  const [allPermissions, setAllPermissions] = useState<Permission[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [profileRes, permissionsRes] = await Promise.all([
          getProfile(profileId),
          getPermissions(),
        ]);
        setProfile(profileRes.data);
        setAllPermissions(permissionsRes.data);
        setSelectedIds(
          profileRes.data.permissions?.map((p) => p.id) || []
        );
      } catch (error) {
        console.error("Erro ao carregar perfil:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [profileId]);

  const handleToggle = (permissionId: number) => {
    setSelectedIds((prev) =>
      prev.includes(permissionId)
        ? prev.filter((id) => id !== permissionId)
        : [...prev, permissionId]
    );
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await syncProfilePermissions(profileId, selectedIds);
      alert("Permissoes atualizadas com sucesso!");
    } catch (error) {
      console.error("Erro ao salvar permissoes:", error);
    } finally {
      setSaving(false);
    }
  };

  // Agrupar permissoes por recurso (ex: "plans.view" → grupo "plans")
  const groupedPermissions = allPermissions.reduce(
    (acc, permission) => {
      const resource = permission.name.split(".")[0];
      if (!acc[resource]) acc[resource] = [];
      acc[resource].push(permission);
      return acc;
    },
    {} as Record<string, Permission[]>
  );

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-96 w-full" />
      </div>
    );
  }

  if (!profile) {
    return <p>Perfil nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => router.push("/profiles")}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{profile.name}</h1>
          <p className="text-muted-foreground">
            {profile.description || "Gerencie as permissoes deste perfil."}
          </p>
        </div>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Permissoes</CardTitle>
          <Button onClick={handleSave} disabled={saving}>
            <Save className="mr-2 h-4 w-4" />
            {saving ? "Salvando..." : "Salvar"}
          </Button>
        </CardHeader>
        <CardContent>
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {Object.entries(groupedPermissions).map(
              ([resource, permissions]) => (
                <div key={resource} className="space-y-2">
                  <h3 className="font-semibold capitalize">{resource}</h3>
                  {permissions.map((permission) => (
                    <div
                      key={permission.id}
                      className="flex items-center gap-2"
                    >
                      <Checkbox
                        id={`perm-${permission.id}`}
                        checked={selectedIds.includes(permission.id)}
                        onCheckedChange={() =>
                          handleToggle(permission.id)
                        }
                      />
                      <label
                        htmlFor={`perm-${permission.id}`}
                        className="text-sm cursor-pointer"
                      >
                        {permission.description || permission.name}
                      </label>
                    </div>
                  ))}
                </div>
              )
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
```

### Atualizar middleware e sidebar

Edite `frontend/src/middleware.ts` — adicione `/profiles` e `/roles` nas rotas protegidas:

```typescript
const isProtectedRoute = pathname.startsWith("/dashboard") ||
    pathname.startsWith("/plans") ||
    pathname.startsWith("/profiles") ||
    pathname.startsWith("/roles") ||
    pathname.startsWith("/orders") ||
    pathname.startsWith("/products") ||
    pathname.startsWith("/customers") ||
    pathname.startsWith("/tables") ||
    pathname.startsWith("/reviews") ||
    pathname.startsWith("/settings");
```

E no `matcher`:

```typescript
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
  ],
};
```

Adicione o item "Perfis" e "Papeis" na sidebar `frontend/src/components/app-sidebar.tsx` (no array de items de navegacao):

```typescript
{
  title: "Perfis",
  url: "/profiles",
  icon: Shield,
},
{
  title: "Papeis",
  url: "/roles",
  icon: UserCog,
},
```

Importe os icones no topo:

```typescript
import { Shield, UserCog } from "lucide-react";
```

**Teste:**

Acesse `http://localhost/profiles` no navegador. Voce deve ver a lista de perfis (Admin, Gerente, Atendente). Clique em um perfil para ver e editar suas permissoes.

---

## Passo 4.15 - Frontend: pagina de Papeis (Roles)

### Pagina de listagem de Papeis

Crie `frontend/src/app/(admin)/roles/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getRoles, deleteRole } from "@/services/acl-service";
import type { Role } from "@/types/acl";
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
import { Trash2, Shield } from "lucide-react";
import Link from "next/link";

export default function RolesPage() {
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchRoles = async () => {
    try {
      const response = await getRoles();
      setRoles(response.data);
    } catch (error) {
      console.error("Erro ao carregar papeis:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRoles();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("Tem certeza que deseja remover este papel?")) return;

    try {
      await deleteRole(id);
      fetchRoles();
    } catch (error) {
      console.error("Erro ao remover papel:", error);
    }
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
          <h1 className="text-2xl font-bold">Papeis</h1>
          <p className="text-muted-foreground">
            Gerencie os papeis de usuario do tenant.
          </p>
        </div>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {roles.length === 0 ? (
              <TableRow>
                <TableCell colSpan={3} className="text-center py-8">
                  Nenhum papel cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              roles.map((role) => (
                <TableRow key={role.id}>
                  <TableCell className="font-medium">
                    <Link
                      href={`/roles/${role.id}`}
                      className="hover:underline"
                    >
                      {role.name}
                    </Link>
                  </TableCell>
                  <TableCell className="max-w-xs truncate">
                    {role.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button variant="ghost" size="icon" asChild>
                        <Link href={`/roles/${role.id}`}>
                          <Shield className="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(role.id)}
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
    </div>
  );
}
```

### Pagina de detalhes do Papel (com permissoes)

Crie `frontend/src/app/(admin)/roles/[id]/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import {
  getRole,
  getPermissions,
  syncRolePermissions,
} from "@/services/acl-service";
import type { Role, Permission } from "@/types/acl";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Save } from "lucide-react";

export default function RoleDetailPage() {
  const params = useParams();
  const router = useRouter();
  const roleId = Number(params.id);

  const [role, setRole] = useState<Role | null>(null);
  const [allPermissions, setAllPermissions] = useState<Permission[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [roleRes, permissionsRes] = await Promise.all([
          getRole(roleId),
          getPermissions(),
        ]);
        setRole(roleRes.data);
        setAllPermissions(permissionsRes.data);
        setSelectedIds(
          roleRes.data.permissions?.map((p) => p.id) || []
        );
      } catch (error) {
        console.error("Erro ao carregar papel:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [roleId]);

  const handleToggle = (permissionId: number) => {
    setSelectedIds((prev) =>
      prev.includes(permissionId)
        ? prev.filter((id) => id !== permissionId)
        : [...prev, permissionId]
    );
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await syncRolePermissions(roleId, selectedIds);
      alert("Permissoes atualizadas com sucesso!");
    } catch (error) {
      console.error("Erro ao salvar permissoes:", error);
    } finally {
      setSaving(false);
    }
  };

  const groupedPermissions = allPermissions.reduce(
    (acc, permission) => {
      const resource = permission.name.split(".")[0];
      if (!acc[resource]) acc[resource] = [];
      acc[resource].push(permission);
      return acc;
    },
    {} as Record<string, Permission[]>
  );

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-96 w-full" />
      </div>
    );
  }

  if (!role) {
    return <p>Papel nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => router.push("/roles")}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{role.name}</h1>
          <p className="text-muted-foreground">
            {role.description || "Gerencie as permissoes deste papel."}
          </p>
        </div>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Permissoes</CardTitle>
          <Button onClick={handleSave} disabled={saving}>
            <Save className="mr-2 h-4 w-4" />
            {saving ? "Salvando..." : "Salvar"}
          </Button>
        </CardHeader>
        <CardContent>
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {Object.entries(groupedPermissions).map(
              ([resource, permissions]) => (
                <div key={resource} className="space-y-2">
                  <h3 className="font-semibold capitalize">{resource}</h3>
                  {permissions.map((permission) => (
                    <div
                      key={permission.id}
                      className="flex items-center gap-2"
                    >
                      <Checkbox
                        id={`perm-${permission.id}`}
                        checked={selectedIds.includes(permission.id)}
                        onCheckedChange={() =>
                          handleToggle(permission.id)
                        }
                      />
                      <label
                        htmlFor={`perm-${permission.id}`}
                        className="text-sm cursor-pointer"
                      >
                        {permission.description || permission.name}
                      </label>
                    </div>
                  ))}
                </div>
              )
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
```

**Teste:**

Acesse `http://localhost/roles` no navegador. Voce deve ver os 3 papeis (Administrador, Gerente, Atendente). Clique em um para ver/editar permissoes.

---

## Passo 4.16 - Verificacao end-to-end da Fase 4

Execute todos os testes em sequencia:

### 1. Reset do banco (opcional, para garantir estado limpo)

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

### 2. Verificar permissoes no banco

```bash
docker compose exec backend php artisan tinker
> App\Models\Permission::count()
# 40

> App\Models\Profile::count()
# 3 (Admin, Gerente, Atendente)

> App\Models\Role::count()
# 3 (Administrador, Gerente, Atendente — do Restaurante Demo)

> App\Models\Profile::where('name', 'Admin')->first()->permissions->count()
# 40 (todas)

> App\Models\Profile::where('name', 'Atendente')->first()->permissions->pluck('name')
# ["categories.view", "products.view", "tables.view", "orders.view", "orders.create", "orders.edit"]
```

### 3. Testar ACL via API

```bash
# Login super-admin
TOKEN=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@orderly.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Super-admin pode tudo
curl -s http://127.0.0.1/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
# ✅ Retorna planos

curl -s http://127.0.0.1/api/v1/profiles \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
# ✅ Retorna perfis

# Login gerente
TOKEN_GERENTE=$(curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "gerente@demo.com", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# Gerente NAO pode ver planos
curl -s http://127.0.0.1/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_GERENTE" | python3 -m json.tool
# ❌ 403 "Voce nao tem permissao para esta acao."

# Gerente pode ver roles (tem roles.view)
curl -s http://127.0.0.1/api/v1/roles \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN_GERENTE" | python3 -m json.tool
# ✅ Retorna roles do tenant

# Verificar permissoes efetivas no tinker
docker compose exec backend php artisan tinker
> $gerente = App\Models\User::where('email', 'gerente@demo.com')->first()
> $gerente->effectivePermissions()
# Array com a intersecao de permissoes do role + plano
> $gerente->hasPermission('orders.view')
# true
> $gerente->hasPermission('plans.view')
# false
```

### 4. Testar frontend

1. Acesse `http://localhost/login` e faca login como admin
2. Navegue para `http://localhost/profiles` — deve listar os 3 perfis
3. Clique em "Admin" — deve mostrar todas as permissoes marcadas
4. Navegue para `http://localhost/roles` — deve listar os 3 papeis
5. Clique em "Gerente" — deve mostrar as permissoes do papel

### 5. Testar sync de permissoes

```bash
# Sync: remover uma permissao do perfil Gerente
GERENTE_PROFILE_ID=2  # ajuste conforme seu banco

# Pegar IDs das permissoes atuais do perfil Gerente (sem users.view)
curl -s -X POST http://127.0.0.1/api/v1/profiles/$GERENTE_PROFILE_ID/permissions \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"permissions": [9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28]}' \
  | python3 -m json.tool
# ✅ "Permissoes do perfil atualizadas."
```

### Resumo de arquivos criados/modificados na Fase 4

```
backend/
├── app/
│   ├── Actions/
│   │   ├── Profile/
│   │   │   ├── CreateProfileAction.php
│   │   │   ├── DeleteProfileAction.php
│   │   │   ├── ListProfilesAction.php
│   │   │   ├── ShowProfileAction.php
│   │   │   └── UpdateProfileAction.php
│   │   └── Role/
│   │       ├── CreateRoleAction.php
│   │       ├── DeleteRoleAction.php
│   │       ├── ListRolesAction.php
│   │       ├── ShowRoleAction.php
│   │       └── UpdateRoleAction.php
│   ├── DTOs/
│   │   ├── Profile/
│   │   │   ├── CreateProfileDTO.php
│   │   │   └── UpdateProfileDTO.php
│   │   └── Role/
│   │       ├── CreateRoleDTO.php
│   │       └── UpdateRoleDTO.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AclSyncController.php
│   │   │   ├── ProfileController.php
│   │   │   └── RoleController.php
│   │   ├── Middleware/
│   │   │   └── CheckPermission.php
│   │   ├── Requests/
│   │   │   ├── Profile/
│   │   │   │   ├── StoreProfileRequest.php
│   │   │   │   └── UpdateProfileRequest.php
│   │   │   └── Role/
│   │   │       ├── StoreRoleRequest.php
│   │   │       └── UpdateRoleRequest.php
│   │   └── Resources/
│   │       ├── PermissionResource.php
│   │       ├── ProfileResource.php
│   │       └── RoleResource.php
│   ├── Models/
│   │   ├── Permission.php
│   │   ├── Profile.php
│   │   ├── Role.php
│   │   ├── Plan.php (modificado - profiles())
│   │   └── User.php (modificado - roles() + HasPermission)
│   ├── Providers/
│   │   └── RepositoryServiceProvider.php (modificado)
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── ProfileRepositoryInterface.php
│   │   │   └── RoleRepositoryInterface.php
│   │   └── Eloquent/
│   │       ├── ProfileRepository.php
│   │       └── RoleRepository.php
│   └── Traits/
│       └── HasPermission.php
├── bootstrap/app.php (modificado - middleware permission)
├── database/
│   ├── migrations/
│   │   ├── 0001_01_02_000005_create_permissions_table.php
│   │   ├── 0001_01_02_000006_create_profiles_table.php
│   │   ├── 0001_01_02_000007_create_permission_profile_table.php
│   │   ├── 0001_01_02_000008_create_plan_profile_table.php
│   │   ├── 0001_01_02_000009_create_roles_table.php
│   │   ├── 0001_01_02_000010_create_permission_role_table.php
│   │   └── 0001_01_02_000011_create_role_user_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php (modificado)
│       ├── PermissionSeeder.php
│       ├── ProfileSeeder.php
│       └── RoleSeeder.php
└── routes/api.php (modificado - profiles, roles, acl sync, permissions middleware)

frontend/
├── src/
│   ├── app/(admin)/
│   │   ├── profiles/
│   │   │   ├── page.tsx (listagem)
│   │   │   └── [id]/page.tsx (permissoes)
│   │   └── roles/
│   │       ├── page.tsx (listagem)
│   │       └── [id]/page.tsx (permissoes)
│   ├── components/app-sidebar.tsx (modificado - itens Perfis e Papeis)
│   ├── services/
│   │   └── acl-service.ts
│   ├── types/
│   │   └── acl.ts
│   └── middleware.ts (modificado - rotas /profiles e /roles)
```

**Conceitos aprendidos:**
- ACL de dupla camada (Plan→Profile→Permission + User→Role→Permission)
- Tabelas pivot many-to-many com chave composta
- `sync()` para substituir todos os vinculos de uma relacao
- Middleware parametrizado (`permission:plans.create`)
- Middleware por acao em `apiResource` (array associativo)
- Trait reutilizavel (`HasPermission`) com logica de intersecao
- `BelongsToTenant` em Roles para escopo automatico
- Frontend: Checkbox grid para gerenciar permissoes
- Agrupamento de dados por prefixo (`plans.view` → grupo `plans`)

## Passo 4.17 - Documentacao API interativa (OpenAPI + Scramble)

Ate agora testamos a API via `curl`. Funciona, mas tem problemas:
- Precisa decorar URLs, headers e payloads
- Nao tem autocomplete nem validacao visual
- Dificil compartilhar com outros devs ou com o frontend

A solucao e **documentacao interativa**: uma pagina web que lista todos os endpoints, mostra os schemas de request/response, e permite testar direto no navegador.

### Conceito: OpenAPI (ex-Swagger)

```
┌─────────────────────────────────────────────────────────────┐
│                    OpenAPI Ecosystem                         │
│                                                             │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │  OpenAPI Spec │    │  Swagger UI  │    │  Stoplight   │  │
│  │  (o padrao)   │    │  (uma UI)    │    │  Elements    │  │
│  │              │    │              │    │  (outra UI)  │  │
│  │  JSON/YAML   │───▶│  Interface   │    │  Interface   │  │
│  │  que descreve│    │  classica    │    │  moderna     │  │
│  │  sua API     │───▶│  (verde)     │    │  (sidebar)   │  │
│  └──────────────┘    └──────────────┘    └──────────────┘  │
│         ▲                                       ▲           │
│         │                                       │           │
│  ┌──────────────┐                               │           │
│  │  Scramble    │  Gera a spec automaticamente  │           │
│  │  (Laravel)   │  e renderiza com Stoplight ───┘           │
│  └──────────────┘                                           │
└─────────────────────────────────────────────────────────────┘
```

- **OpenAPI** = o **padrao/especificacao** (JSON/YAML descrevendo endpoints, schemas, auth)
- **Swagger UI** = uma interface visual para renderizar a spec (a classica, verde/preto)
- **Stoplight Elements** = outra interface visual (mais moderna, com sidebar responsiva e tema dark)
- **Scramble** = pacote Laravel que **gera a spec automaticamente** a partir do codigo (Controllers, FormRequests, Resources) e renderiza com Stoplight Elements

**Por que Scramble e nao l5-swagger?**
- l5-swagger exige annotations manuais (`@OA\Get`, `@OA\Post`) em cada controller — verboso e propenso a ficar desatualizado
- Scramble analisa o **codigo real** (return types, FormRequests, Resources) e gera a spec sem annotations
- Menos codigo = menos manutencao = doc sempre sincronizada com a API

### 1. Publicar configuracao do Scramble

O pacote `dedoc/scramble` ja esta no `composer.json` desde o Passo 1.11. Publique o arquivo de configuracao:

```bash
docker compose exec backend php artisan vendor:publish \
  --provider="Dedoc\Scramble\ScrambleServiceProvider" \
  --tag=scramble-config
sudo chown -R $USER:$USER backend/config/
```

### 2. Configurar o Scramble

Edite `backend/config/scramble.php`:

```php
<?php

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MARKDOWN'
API REST do **Orderly** — plataforma SaaS multi-tenant para gestao de restaurantes.

## Autenticacao
Todas as rotas protegidas exigem um token JWT no header `Authorization: Bearer {token}`.
Use o endpoint **POST /api/v1/auth/login** para obter o token.

## Multi-tenancy
O `tenant_id` e extraido automaticamente do token JWT. Usuarios comuns so acessam dados do seu tenant.
Super-admins (sem tenant) acessam dados de todos os tenants.

## ACL (Controle de Acesso)
O sistema usa **dupla camada de permissoes**:
- **Plan → Profile → Permission**: define o que o plano do tenant permite
- **User → Role → Permission**: define o que o usuario pode fazer

Uma acao so e permitida se existir nas **duas camadas** (intersecao).
MARKDOWN,
    ],

    'ui' => [
        'title' => 'Orderly API',
        'theme' => 'dark',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => null,

    'middleware' => [
        'web',
        // RestrictedDocsAccess removido para permitir acesso em dev
    ],

    'extensions' => [],
];
```

**Pontos importantes:**
- `theme => 'dark'` — tema escuro para a documentacao
- `hide_try_it => false` — mantem o botao "Try It" para testar endpoints no navegador
- `layout => 'responsive'` — sidebar que colapsa em telas pequenas
- `RestrictedDocsAccess` removido — em producao, recoloque para exigir autenticacao
- A `description` usa Markdown — aparece na home da documentacao explicando autenticacao, tenancy e ACL

### 3. Configurar seguranca JWT no AppServiceProvider

O Scramble precisa saber que a API usa JWT Bearer. Edite `backend/app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT'),
            );

            // Adicionar example=1 em todos os path parameters integer
            // para evitar que o Stoplight UI gere UUIDs como exemplo
            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    foreach ($operation->parameters as $parameter) {
                        if (
                            $parameter->in === 'path'
                            && $parameter->schema
                            && $parameter->schema->type instanceof IntegerType
                        ) {
                            $parameter->example(1);
                        }
                    }
                }
            }
        });
    }
}
```

**O que isso faz?**
1. Adiciona o security scheme `Bearer` na spec OpenAPI. Na interface, isso habilita o botao "Authorize" onde voce cola o JWT token. Todos os endpoints protegidos enviarao o header `Authorization: Bearer {token}` automaticamente.
2. Forca `example: 1` em todos os path parameters do tipo integer. Sem isso, o Stoplight UI gera valores aleatorios (incluindo UUIDs) que causam `TypeError` nos controllers que esperam `int`.

### 4. Adicionar PHPDoc tags nos controllers

O Scramble le o codigo automaticamente, mas PHPDoc tags melhoram a organizacao. Adicione `@tags` na classe e descricoes nos metodos:

**Exemplo — `AuthController.php`:**
```php
/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Autentica o usuario e retorna um token JWT.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    // ...
```

**Exemplo — `PlanController.php`:**
```php
/**
 * @tags Planos
 */
class PlanController extends Controller
{
    /**
     * Listar planos
     *
     * Retorna todos os planos com paginacao. Requer permissao `plans.view`.
     */
    public function index(ListPlansAction $action): AnonymousResourceCollection
    // ...
```

**Tags usadas em cada controller:**

| Controller | Tag | Descricao |
|---|---|---|
| `AuthController` | Auth | Login, logout, refresh, me |
| `PlanController` | Planos | CRUD de planos de assinatura |
| `DetailPlanController` | Detalhes do Plano | CRUD de detalhes (nested em planos) |
| `TenantController` | Tenants | CRUD de tenants (restaurantes) |
| `ProfileController` | Perfis (Profiles) | CRUD de perfis de acesso |
| `RoleController` | Papeis (Roles) | CRUD de papeis do tenant |
| `AclSyncController` | ACL (Controle de Acesso) | Sync de permissoes/perfis/roles |

**Annotations especiais do Scramble:**
- `@tags NomeDoGrupo` — agrupa endpoints na sidebar
- `@unauthenticated` — marca endpoint como publico (sem cadeado)
- O restante (schemas, validacoes, responses) e inferido automaticamente dos `FormRequest`, `Resource` e return types

### 5. Rotear /docs no Nginx

O Scramble serve a documentacao em `/docs/api` (rota web do Laravel). O Nginx precisa rotear isso para o PHP-FPM.

Edite `docker/nginx/default.conf` e adicione apos o bloco `/api`:

```nginx
    # --- API Routes -> Laravel ---
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # --- API Documentation (Scramble/Swagger) -> Laravel ---
    location /docs {
        try_files $uri $uri/ /index.php?$query_string;
    }
```

Reinicie o Nginx:

```bash
docker compose rm -f nginx && docker compose up -d nginx
```

> **Nota WSL2:** Se `docker restart orderly-nginx` falhar com erro de bind mount, use `docker compose rm -f nginx && docker compose up -d nginx` que recria o container.

### 6. Limpar caches e testar

```bash
# Limpar caches do Laravel
docker compose exec backend php artisan route:clear
docker compose exec backend php artisan config:clear

# Testar se a documentacao esta acessivel
curl -s -o /dev/null -w "%{http_code}" http://localhost/docs/api
# 200

# Ver a spec OpenAPI em JSON
curl -s http://localhost/docs/api.json | python3 -m json.tool | head -20
```

### 7. Usar a documentacao

1. Acesse **http://localhost/docs/api** no navegador
2. Na sidebar esquerda, veja os endpoints agrupados por tags
3. Clique em **POST /v1/auth/login** → clique em **Try It**
4. Preencha o body com `{"email": "admin@orderly.com", "password": "password"}`
5. Clique **Send** — copie o `access_token` do response
6. Clique no botao **Authorize** (cadeado no topo) e cole: `Bearer {seu_token}`
7. Agora todos os endpoints protegidos enviam o token automaticamente

### Como o Scramble gera a documentacao automaticamente

```
┌──────────────────┐     ┌────────────────────┐     ┌─────────────────┐
│  FormRequest     │     │  Controller         │     │  Resource       │
│                  │     │                     │     │                 │
│  StorePlanRequest│────▶│  PlanController     │────▶│  PlanResource   │
│  - name: required│     │  - store()          │     │  - id           │
│  - price: numeric│     │  - returns 201      │     │  - name         │
│  - description   │     │  - returns 404      │     │  - price        │
└──────────────────┘     └────────────────────┘     └─────────────────┘
         │                        │                          │
         ▼                        ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Scramble (analise automatica)                     │
│                                                                     │
│  Le o codigo PHP via reflection + analise estatica e gera:         │
│  - Request body schema (dos FormRequest rules)                     │
│  - Response schema (dos Resource toArray)                          │
│  - Path parameters (dos type hints int $plan)                      │
│  - HTTP status codes (dos return response()->json(..., 201))       │
│  - Auth requirements (dos middleware auth:api)                     │
└─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     OpenAPI 3.1 JSON Spec                           │
│                     /docs/api.json                                  │
│                                                                     │
│  Renderizado pelo Stoplight Elements em /docs/api                  │
└─────────────────────────────────────────────────────────────────────┘
```

**Isso significa:** quando voce adicionar um novo controller com FormRequest e Resource, o Scramble documenta automaticamente. Zero annotations extras.

### Arquivos criados/modificados

```
backend/
├── config/scramble.php                        # (novo) Configuracao do Scramble
├── app/Providers/AppServiceProvider.php        # (modificado) Security scheme JWT
└── app/Http/Controllers/Api/V1/
    ├── Auth/AuthController.php                # (modificado) @tags Auth
    ├── PlanController.php                     # (modificado) @tags Planos
    ├── DetailPlanController.php               # (modificado) @tags Detalhes do Plano
    ├── TenantController.php                   # (modificado) @tags Tenants
    ├── ProfileController.php                  # (modificado) @tags Perfis (Profiles)
    ├── RoleController.php                     # (modificado) @tags Papeis (Roles)
    └── AclSyncController.php                  # (modificado) @tags ACL (Controle de Acesso)
docker/
└── nginx/default.conf                         # (modificado) Rota /docs -> PHP-FPM
```

**Conceitos aprendidos:**
- OpenAPI e uma **especificacao** (JSON/YAML), nao uma ferramenta
- Swagger UI e Stoplight Elements sao **interfaces visuais** que renderizam a mesma spec
- Scramble gera a spec **automaticamente** a partir do codigo Laravel (sem annotations)
- PHPDoc `@tags` organiza endpoints em grupos na sidebar
- `@unauthenticated` marca endpoints publicos
- A spec e servida como JSON em `/docs/api.json` (pode ser importada no Postman, Insomnia, etc.)

**Proximo:** Fase 5 - Catalogo: Categorias + Produtos

---


---

[Voltar ao README](../README.md)
