# Fase 10 - Testes (Unit, Integration, E2E)

**Objetivo:** Adicionar cobertura de testes automatizados em todas as camadas — backend (Pest), frontend (Vitest + Testing Library) e end-to-end (Playwright).

**O que voce vai aprender:**
- Piramide de testes: Unit vs Integration vs E2E
- Pest PHP: testes expressivos com syntax funcional
- `RefreshDatabase` + factories para testes de banco
- `actingAs()` e `withHeaders()` para autenticacao em testes
- Vitest: testes rapidos para TypeScript/React
- Testing Library: testar componentes pelo comportamento (nao implementacao)
- Mock de APIs com `msw` (Mock Service Worker)
- Playwright: testes E2E com browser real
- Coverage reports com `--coverage`

**Pre-requisitos:**
- Fase 9 completa (todos os features implementados)
- Docker rodando (`docker compose up -d`)

---

## Passo 10.1 - Conceito: Piramide de Testes

### A piramide

```
        /\
       /  \        E2E (Playwright)
      / E2E\       Poucos, lentos, alto valor
     /------\
    /        \     Integration (Feature tests)
   / Feature  \    API endpoints, banco real
  /------------\
 /              \  Unit (Pest / Vitest)
/   Unit Tests   \ Rapidos, isolados, muitos
------------------
```

| Camada | Ferramenta | O que testa | Quantidade |
|--------|-----------|-------------|------------|
| Unit | Pest (backend) / Vitest (frontend) | Models, Actions, Services, Stores — logica isolada | Muitos (~70%) |
| Integration | Pest Feature tests | Endpoints HTTP, banco, middleware, auth | Medio (~20%) |
| E2E | Playwright | Fluxo completo usuario → browser → API → banco | Poucos (~10%) |

### Decisoes de arquitetura

| Decisao | Motivo |
|---------|--------|
| Pest em vez de PHPUnit puro | Syntax funcional mais expressiva, menos boilerplate |
| SQLite `:memory:` para testes | Rapido, sem dependencia de Docker para rodar testes |
| Vitest em vez de Jest | Compativel com Vite, mais rapido, ESM nativo |
| `msw` para mock de API no frontend | Intercepta fetch no nivel de rede, sem alterar codigo |
| Playwright em vez de Cypress | Multi-browser, mais rapido, melhor suporte a autenticacao |
| Factories para dados de teste | Evita dados hardcoded, gera dados realistas |

---

## Passo 10.2 - Backend: configurar Pest + database testing

### Pest.php

O Pest ja esta instalado (veio com o Laravel). Crie o arquivo de configuracao `backend/tests/Pest.php`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Configura a classe base para todos os testes.
| RefreshDatabase reseta o banco antes de cada teste.
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Cria um usuario super-admin com tenant para testes.
 * Super-admin bypassa todas as verificacoes de permissao.
 */
function createAdminUser(): \App\Models\User
{
    $plan = \App\Models\Plan::factory()->create();

    $tenant = \App\Models\Tenant::factory()->create([
        'plan_id' => $plan->id,
    ]);

    return \App\Models\User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'admin@orderly.com', // super-admin email
    ]);
}

/**
 * Retorna headers de autenticacao JWT para um usuario.
 */
function authHeaders(\App\Models\User $user): array
{
    $token = auth('api')->login($user);

    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}
```

> **Por que `RefreshDatabase` apenas em Feature tests?** Unit tests nao devem tocar o banco — testam logica pura. Feature tests precisam do banco para testar endpoints HTTP completos (controller → action → repository → banco).

> **Por que `email` de super-admin?** O `createAdminUser()` usa o email `admin@orderly.com` que esta na config `orderly.super_admin_emails`. Isso faz o `isSuperAdmin()` retornar `true`, bypassing todas as verificacoes de permissao (ACL). Sem isso, o usuario de teste receberia 403 Forbidden em todos os endpoints protegidos por `CheckPermission`.

### Importante: `force="true"` no phpunit.xml

O `docker-compose.yml` define `DB_CONNECTION=pgsql` como variavel de ambiente, que **sobrescreve** o `.env` e o `phpunit.xml`. Sem `force="true"`, os testes usariam o banco PostgreSQL de desenvolvimento e o `RefreshDatabase` **apagaria todos os seus dados**!

Abra `backend/phpunit.xml` e garanta que as linhas de DB tenham `force="true"`:

```xml
<env name="DB_CONNECTION" value="sqlite" force="true"/>
<env name="DB_DATABASE" value=":memory:" force="true"/>
```

O `force="true"` diz ao PHPUnit: "ignore qualquer variavel de ambiente existente e use este valor". Assim os testes sempre usam SQLite em memoria, que e rapido e descartavel.

> **Se voce ja rodou testes sem o `force="true"`**, seus dados de desenvolvimento foram apagados. Restaure com: `docker compose exec backend php artisan db:seed`

### Factories necessarias

Verifique que as factories existem. As mais importantes:

```bash
docker compose exec backend ls database/factories/
```

Factories ja existentes: `UserFactory`, `PlanFactory`, `TenantFactory`, `CategoryFactory`, `ProductFactory`.

Crie a factory faltante — `backend/database/factories/OrderFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'status' => Order::STATUS_OPEN,
            'total' => $this->faker->randomFloat(2, 10, 500),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(['status' => Order::STATUS_DELIVERED]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => Order::STATUS_REJECTED]);
    }
}
```

Crie `backend/database/factories/ClientFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
        ];
    }
}
```

> **Nota:** Adicione o trait `HasFactory` nos models `Order` e `Client` se ainda nao tiverem:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, BelongsToTenant;
    // ...
}
```

---

## Passo 10.3 - Backend: Unit Tests (Models + Actions)

### Testar o Model Order (status transitions)

Crie `backend/tests/Unit/Models/OrderTest.php`:

```php
<?php

use App\Models\Order;

describe('Order Model', function () {
    it('has all required status constants', function () {
        expect(Order::ALL_STATUSES)->toContain(
            'open', 'accepted', 'rejected', 'preparing', 'done', 'delivered'
        );
    });

    it('defines valid transitions for each status', function () {
        expect(Order::VALID_TRANSITIONS)->toHaveKeys([
            'open', 'accepted', 'preparing', 'done',
        ]);
    });

    it('allows open → accepted transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->toContain('accepted');
    });

    it('allows open → rejected transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->toContain('rejected');
    });

    it('does not allow open → delivered transition', function () {
        expect(Order::VALID_TRANSITIONS['open'])->not->toContain('delivered');
    });

    it('allows done → delivered as final transition', function () {
        expect(Order::VALID_TRANSITIONS['done'])->toContain('delivered');
    });

    it('does not have transitions from delivered', function () {
        expect(Order::VALID_TRANSITIONS)->not->toHaveKey('delivered');
    });

    it('does not have transitions from rejected', function () {
        expect(Order::VALID_TRANSITIONS)->not->toHaveKey('rejected');
    });
});
```

### Testar a Action UpdateOrderStatus

Crie `backend/tests/Unit/Actions/Order/UpdateOrderStatusActionTest.php`:

> **Importante:** Os repositories sao `final class`, entao nao podem ser mockados com `Mockery::mock(OrderRepository::class)`. Em vez disso, mockamos a **interface** (`OrderRepositoryInterface`), que e o que a Action recebe no construtor via injecao de dependencia.

```php
<?php

use App\Actions\Order\UpdateOrderStatusAction;
use App\DTOs\Order\UpdateOrderStatusDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

describe('UpdateOrderStatusAction', function () {
    it('returns error for invalid transition', function () {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->status = 'open';
        $order->shouldReceive('canTransitionTo')
            ->with('delivered')
            ->andReturn(false);

        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(1)
            ->andReturn($order);

        $action = new UpdateOrderStatusAction($repository);
        $dto = new UpdateOrderStatusDTO(status: 'delivered');
        $result = $action->execute(1, $dto);

        expect($result)->toBeString()
            ->and($result)->toContain('nao e permitida');
    });

    it('returns updated order for valid transition', function () {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->status = 'open';
        $order->shouldReceive('canTransitionTo')
            ->with('accepted')
            ->andReturn(true);
        $order->shouldReceive('fresh')
            ->andReturn($order);

        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(1)
            ->andReturn($order);
        $repository->shouldReceive('update')
            ->with(1, ['status' => 'accepted'])
            ->once();

        $action = new UpdateOrderStatusAction($repository);
        $dto = new UpdateOrderStatusDTO(status: 'accepted');
        $result = $action->execute(1, $dto);

        expect($result)->toBeInstanceOf(Order::class);
    });

    it('returns error when order not found', function () {
        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with(999)
            ->andReturnNull();

        $action = new UpdateOrderStatusAction($repository);
        $dto = new UpdateOrderStatusDTO(status: 'accepted');
        $result = $action->execute(999, $dto);

        expect($result)->toBeString()
            ->and($result)->toContain('nao encontrado');
    });
});
```

### Testar a Action CreateEvaluation

Crie `backend/tests/Unit/Actions/Evaluation/CreateEvaluationActionTest.php`:

```php
<?php

use App\Actions\Evaluation\CreateEvaluationAction;
use App\DTOs\Evaluation\CreateEvaluationDTO;
use App\Models\Order;
use App\Repositories\Contracts\EvaluationRepositoryInterface;

describe('CreateEvaluationAction', function () {
    it('returns error if order does not exist', function () {
        $repository = Mockery::mock(EvaluationRepositoryInterface::class);

        $dto = new CreateEvaluationDTO(
            orderId: 999,
            stars: 5,
            comment: 'Otimo!',
        );

        $action = new CreateEvaluationAction($repository);
        $result = $action->execute($dto, 1);

        expect($result)->toBeString()
            ->and($result)->toContain('Pedido nao encontrado');
    });
});
```

### Testar GetDashboardMetricsAction (parcial — cards)

Crie `backend/tests/Unit/Actions/Dashboard/GetDashboardMetricsActionTest.php`:

```php
<?php

use App\Actions\Dashboard\GetDashboardMetricsAction;

describe('GetDashboardMetricsAction', function () {
    it('returns expected structure', function () {
        $action = new GetDashboardMetricsAction();
        $result = $action->execute(null);

        expect($result)->toHaveKeys([
            'cards',
            'orders_per_day',
            'orders_by_status',
            'latest_evaluations',
        ]);
    });

    it('returns 7 days in orders_per_day', function () {
        $action = new GetDashboardMetricsAction();
        $result = $action->execute(null);

        expect($result['orders_per_day'])->toHaveCount(7);
    });

    it('has correct card keys', function () {
        $action = new GetDashboardMetricsAction();
        $result = $action->execute(null);

        expect($result['cards'])->toHaveKeys([
            'orders_today',
            'revenue_today',
            'total_clients',
            'total_products',
        ]);
    });
});
```

> **Nota:** O teste do `GetDashboardMetricsAction` toca o banco (usa Eloquent), mas como retorna dados vazios (banco limpo), funciona como unit test verificando a estrutura. Para testes com dados, usaremos Feature tests.

---

## Passo 10.4 - Backend: Feature Tests (API endpoints)

### Testar Auth API

Crie `backend/tests/Feature/Api/AuthTest.php`:

```php
<?php

use App\Models\User;

describe('Auth API', function () {
    it('can login with valid credentials', function () {
        $user = createAdminUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    });

    it('returns 401 for invalid credentials', function () {
        $user = createAdminUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    });

    it('can get authenticated user', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    });

    it('can logout', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Logout realizado com sucesso.']);
    });
});
```

### Testar Plans CRUD

Crie `backend/tests/Feature/Api/PlanTest.php`:

```php
<?php

use App\Models\Plan;

describe('Plans API', function () {
    it('can list plans', function () {
        $user = createAdminUser();
        Plan::factory()->count(3)->create();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'description'],
                ],
            ]);
    });

    it('can create a plan', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/plans', [
                'name' => 'Plano Premium',
                'price' => 99.90,
                'description' => 'Plano com recursos premium',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Plano Premium']);
    });

    it('validates required fields on create', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/plans', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price']);
    });

    it('can show a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson("/api/v1/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => $plan->name]);
    });

    it('can update a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/plans/{$plan->id}", [
                'name' => 'Plano Atualizado',
                'price' => 149.90,
                'description' => $plan->description,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Plano Atualizado']);
    });

    it('can delete a plan', function () {
        $user = createAdminUser();
        $plan = Plan::factory()->create();

        $response = $this->withHeaders(authHeaders($user))
            ->deleteJson("/api/v1/plans/{$plan->id}");

        $response->assertOk();
    });
});
```

### Testar Orders API

Crie `backend/tests/Feature/Api/OrderTest.php`:

```php
<?php

use App\Models\Order;
use App\Models\Product;

describe('Orders API', function () {
    it('can list orders', function () {
        $user = createAdminUser();

        Order::factory()->count(3)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'total'],
                ],
            ]);
    });

    it('can create an order with products', function () {
        $user = createAdminUser();

        $products = Product::factory()->count(2)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->postJson('/api/v1/orders', [
                'products' => [
                    ['product_id' => $products[0]->id, 'qty' => 2],
                    ['product_id' => $products[1]->id, 'qty' => 1],
                ],
                'comment' => 'Sem cebola',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['status' => 'open']);
    });

    it('can update order status with valid transition', function () {
        $user = createAdminUser();

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'accepted',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['status' => 'accepted']);
    });

    it('rejects invalid status transition', function () {
        $user = createAdminUser();

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'delivered',
            ]);

        $response->assertUnprocessable();
    });

    it('can filter orders by status', function () {
        $user = createAdminUser();

        Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status' => 'open',
        ]);
        Order::factory()->delivered()->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/orders?status=open');

        $response->assertOk();

        $orders = $response->json('data');
        foreach ($orders as $order) {
            expect($order['status'])->toBe('open');
        }
    });
});
```

### Testar Dashboard Metrics API

Crie `backend/tests/Feature/Api/DashboardTest.php`:

```php
<?php

use App\Models\Order;

describe('Dashboard API', function () {
    it('returns metrics structure', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'cards' => [
                        'orders_today',
                        'revenue_today',
                        'total_clients',
                        'total_products',
                    ],
                    'orders_per_day',
                    'orders_by_status',
                    'latest_evaluations',
                ],
            ]);
    });

    it('counts orders created today', function () {
        $user = createAdminUser();

        Order::factory()->count(3)->create([
            'tenant_id' => $user->tenant_id,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertOk();
        expect($response->json('data.cards.orders_today'))->toBe(3);
    });

    it('excludes rejected orders from revenue', function () {
        $user = createAdminUser();

        Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'total' => 100.00,
            'status' => 'open',
        ]);
        Order::factory()->rejected()->create([
            'tenant_id' => $user->tenant_id,
            'total' => 50.00,
        ]);

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        expect($response->json('data.cards.revenue_today'))->toBe('100.00');
    });

    it('returns 7 days in orders_per_day', function () {
        $user = createAdminUser();

        $response = $this->withHeaders(authHeaders($user))
            ->getJson('/api/v1/dashboard/metrics');

        expect($response->json('data.orders_per_day'))->toHaveCount(7);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/dashboard/metrics');

        $response->assertUnauthorized();
    });
});
```

### Testar Client Auth API

Crie `backend/tests/Feature/Api/ClientAuthTest.php`:

```php
<?php

use App\Models\Client;

describe('Client Auth API', function () {
    it('can register a new client', function () {
        $response = $this->postJson('/api/v1/client/auth/register', [
            'name' => 'Joao Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type']);
    });

    it('validates unique email on register', function () {
        Client::factory()->create(['email' => 'joao@example.com']);

        $response = $this->postJson('/api/v1/client/auth/register', [
            'name' => 'Joao Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('can login as client', function () {
        Client::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/client/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token']);
    });

    it('returns 401 for wrong client password', function () {
        Client::factory()->create([
            'email' => 'joao@example.com',
        ]);

        $response = $this->postJson('/api/v1/client/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized();
    });
});
```

---

## Passo 10.5 - Backend: rodar testes e coverage

### Rodar todos os testes

```bash
docker compose exec backend php artisan test
```

Saida esperada:

```
   PASS  Tests\Unit\Models\OrderTest
  ✓ it has all required status constants
  ✓ it defines valid transitions for each status
  ✓ it allows open → accepted transition
  ✓ it allows open → rejected transition
  ✓ it does not allow open → delivered transition
  ✓ it allows done → delivered as final transition
  ✓ it does not have transitions from delivered
  ✓ it does not have transitions from rejected

   PASS  Tests\Unit\Actions\Order\UpdateOrderStatusActionTest
  ✓ it returns error for invalid transition
  ✓ it returns updated order for valid transition
  ✓ it returns error when order not found

   PASS  Tests\Unit\Actions\Evaluation\CreateEvaluationActionTest
  ✓ it returns error if order does not exist

   PASS  Tests\Unit\Actions\Dashboard\GetDashboardMetricsActionTest
  ✓ it returns expected structure
  ✓ it returns 7 days in orders_per_day
  ✓ it has correct card keys

   PASS  Tests\Feature\Api\AuthTest
  ✓ it can login with valid credentials
  ✓ it returns 401 for invalid credentials
  ✓ it can get authenticated user
  ✓ it can logout

   PASS  Tests\Feature\Api\PlanTest
  ✓ it can list plans
  ✓ it can create a plan
  ✓ it validates required fields on create
  ✓ it can show a plan
  ✓ it can update a plan
  ✓ it can delete a plan

   PASS  Tests\Feature\Api\OrderTest
  ✓ it can list orders
  ✓ it can create an order with products
  ✓ it can update order status with valid transition
  ✓ it rejects invalid status transition
  ✓ it can filter orders by status

   PASS  Tests\Feature\Api\DashboardTest
  ✓ it returns metrics structure
  ✓ it counts orders created today
  ✓ it excludes rejected orders from revenue
  ✓ it returns 7 days in orders_per_day
  ✓ it requires authentication

   PASS  Tests\Feature\Api\ClientAuthTest
  ✓ it can register a new client
  ✓ it validates unique email on register
  ✓ it can login as client
  ✓ it returns 401 for wrong client password

  Tests:    41 passed (117 assertions)
  Duration: ~6s
```

### Rodar com coverage

Instale o Xdebug ou PCOV (mais rapido) se ainda nao tiver:

```bash
# Instalar dependencias de compilacao (Alpine) + PCOV
docker compose exec backend sh -c "apk add --no-cache autoconf gcc g++ make && pecl install pcov && echo 'extension=pcov.so' > /usr/local/etc/php/conf.d/pcov.ini"
```

> **Nota:** O container usa Alpine Linux, entao usamos `apk` em vez de `apt-get`. O `autoconf`, `gcc`, `g++` e `make` sao necessarios para compilar a extensao PCOV.

Rode com coverage:

```bash
docker compose exec backend php artisan test --coverage
```

> **Meta:** Manter cobertura acima de 60% no inicio, subindo conforme novas features sao adicionadas.

### Filtrar testes por grupo

```bash
# Apenas unit tests
docker compose exec backend php artisan test --testsuite=Unit

# Apenas feature tests
docker compose exec backend php artisan test --testsuite=Feature

# Apenas um arquivo
docker compose exec backend php artisan test --filter=OrderTest
```

---

## Passo 10.6 - Frontend: configurar Vitest + Testing Library

### Instalar dependencias

```bash
docker compose exec frontend npm install -D vitest @vitejs/plugin-react \
  @testing-library/react @testing-library/jest-dom @testing-library/user-event \
  jsdom msw
```

| Pacote | Funcao |
|--------|--------|
| `vitest` | Test runner (rapido, ESM nativo) |
| `@vitejs/plugin-react` | Plugin React para Vitest |
| `@testing-library/react` | Renderizar componentes para teste |
| `@testing-library/jest-dom` | Matchers como `toBeInTheDocument()` |
| `@testing-library/user-event` | Simular interacoes do usuario |
| `jsdom` | DOM virtual para testes |
| `msw` | Mock Service Worker — intercepta fetch |

### Criar vitest.config.ts

Crie `frontend/vitest.config.ts`:

```ts
import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [react()],
    test: {
        environment: "jsdom",
        globals: true,
        setupFiles: ["./src/test/setup.ts"],
        include: ["src/**/*.{test,spec}.{ts,tsx}"],
        coverage: {
            provider: "v8",
            reporter: ["text", "html"],
            include: ["src/**/*.{ts,tsx}"],
            exclude: [
                "src/test/**",
                "src/types/**",
                "src/**/*.d.ts",
            ],
        },
    },
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./src"),
        },
    },
});
```

### Setup file

Crie `frontend/src/test/setup.ts`:

```ts
import "@testing-library/jest-dom/vitest";
```

### MSW handlers (mock de API)

Crie `frontend/src/test/mocks/handlers.ts`:

```ts
import { http, HttpResponse } from "msw";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api";

export const handlers = [
    // Auth
    http.post(`${API_URL}/v1/auth/login`, () => {
        return HttpResponse.json({
            access_token: "fake-jwt-token",
            token_type: "bearer",
            expires_in: 3600,
        });
    }),

    http.get(`${API_URL}/v1/auth/me`, () => {
        return HttpResponse.json({
            id: 1,
            name: "Admin",
            email: "admin@orderly.com",
            tenant_id: 1,
        });
    }),

    // Dashboard metrics
    http.get(`${API_URL}/v1/dashboard/metrics`, () => {
        return HttpResponse.json({
            data: {
                cards: {
                    orders_today: 5,
                    revenue_today: "375.90",
                    total_clients: 4,
                    total_products: 8,
                },
                orders_per_day: Array.from({ length: 7 }, (_, i) => ({
                    date: `2026-03-${String(6 + i).padStart(2, "0")}`,
                    label: `${String(6 + i).padStart(2, "0")}/03`,
                    total: Math.floor(Math.random() * 10),
                })),
                orders_by_status: {
                    open: 2,
                    delivered: 3,
                    preparing: 1,
                },
                latest_evaluations: [
                    {
                        id: 1,
                        stars: 5,
                        comment: "Otimo!",
                        client_name: "Joao",
                        order_identify: "ORD-001",
                        created_at: "2026-03-12T10:00:00Z",
                    },
                ],
            },
        });
    }),

    // Plans
    http.get(`${API_URL}/v1/plans`, () => {
        return HttpResponse.json({
            data: [
                {
                    id: 1,
                    uuid: "abc-123",
                    name: "Plano Basic",
                    price: "49.90",
                    description: "Plano basico",
                },
            ],
            meta: { current_page: 1, last_page: 1, total: 1 },
        });
    }),
];
```

Crie `frontend/src/test/mocks/server.ts`:

```ts
import { setupServer } from "msw/node";
import { handlers } from "./handlers";

export const server = setupServer(...handlers);
```

Atualize `frontend/src/test/setup.ts` para iniciar o MSW:

```ts
import "@testing-library/jest-dom/vitest";
import { server } from "./mocks/server";
import { beforeAll, afterAll, afterEach } from "vitest";

beforeAll(() => server.listen({ onUnhandledRequest: "warn" }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
```

---

## Passo 10.7 - Frontend: Unit Tests (services + stores)

### Testar dashboard-service

Crie `frontend/src/services/__tests__/dashboard-service.test.ts`:

```ts
import { describe, it, expect } from "vitest";
import { getDashboardMetrics } from "@/services/dashboard-service";

describe("getDashboardMetrics", () => {
    it("returns metrics with correct structure", async () => {
        const metrics = await getDashboardMetrics();

        expect(metrics).toHaveProperty("cards");
        expect(metrics).toHaveProperty("orders_per_day");
        expect(metrics).toHaveProperty("orders_by_status");
        expect(metrics).toHaveProperty("latest_evaluations");
    });

    it("returns cards with numeric values", async () => {
        const metrics = await getDashboardMetrics();

        expect(metrics.cards.orders_today).toBe(5);
        expect(metrics.cards.revenue_today).toBe("375.90");
        expect(metrics.cards.total_clients).toBe(4);
        expect(metrics.cards.total_products).toBe(8);
    });

    it("returns 7 days of orders_per_day", async () => {
        const metrics = await getDashboardMetrics();

        expect(metrics.orders_per_day).toHaveLength(7);
        expect(metrics.orders_per_day[0]).toHaveProperty("date");
        expect(metrics.orders_per_day[0]).toHaveProperty("label");
        expect(metrics.orders_per_day[0]).toHaveProperty("total");
    });
});
```

### Testar auth-store

Crie `frontend/src/stores/__tests__/auth-store.test.ts`:

```ts
import { describe, it, expect, beforeEach } from "vitest";
import { useAuthStore } from "@/stores/auth-store";

describe("useAuthStore", () => {
    beforeEach(() => {
        const store = useAuthStore.getState();
        store.logout();
    });

    it("starts with null user and token", () => {
        const { user, token } = useAuthStore.getState();

        expect(user).toBeNull();
        expect(token).toBeNull();
    });

    it("sets token on setToken", () => {
        useAuthStore.getState().setToken("my-token");

        expect(useAuthStore.getState().token).toBe("my-token");
    });

    it("sets user on setUser", () => {
        const mockUser = {
            id: 1,
            name: "Admin",
            email: "admin@test.com",
            tenant_id: 1,
        };

        useAuthStore.getState().setUser(mockUser);

        expect(useAuthStore.getState().user).toEqual(mockUser);
    });

    it("clears state on logout", () => {
        useAuthStore.getState().setToken("my-token");
        useAuthStore.getState().setUser({
            id: 1,
            name: "Admin",
            email: "admin@test.com",
            tenant_id: 1,
        });

        useAuthStore.getState().logout();

        expect(useAuthStore.getState().user).toBeNull();
        expect(useAuthStore.getState().token).toBeNull();
    });
});
```

---

## Passo 10.8 - Frontend: Component Tests

### Testar StarRating

Crie `frontend/src/components/__tests__/star-rating.test.tsx`:

```tsx
import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";

// O componente StarRating esta inline no dashboard, vamos extrair para teste
// Se voce quiser testar inline, importe o DashboardPage completo
function StarRating({ stars }: { stars: number }) {
    return (
        <div data-testid="star-rating">
            {Array.from({ length: 5 }).map((_, i) => (
                <span
                    key={i}
                    data-testid={`star-${i}`}
                    className={i < stars ? "filled" : "empty"}
                >
                    ★
                </span>
            ))}
        </div>
    );
}

describe("StarRating", () => {
    it("renders 5 stars", () => {
        render(<StarRating stars={3} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        expect(stars).toHaveLength(5);
    });

    it("fills correct number of stars", () => {
        render(<StarRating stars={3} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const filled = stars.filter((s) => s.className.includes("filled"));
        const empty = stars.filter((s) => s.className.includes("empty"));

        expect(filled).toHaveLength(3);
        expect(empty).toHaveLength(2);
    });

    it("fills all stars for rating 5", () => {
        render(<StarRating stars={5} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const filled = stars.filter((s) => s.className.includes("filled"));

        expect(filled).toHaveLength(5);
    });

    it("fills no stars for rating 0", () => {
        render(<StarRating stars={0} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const empty = stars.filter((s) => s.className.includes("empty"));

        expect(empty).toHaveLength(5);
    });
});
```

> **Dica:** Se quiser testar componentes que usam `next/navigation` ou `next/router`, adicione um mock:

```ts
// No arquivo de teste ou no setup
vi.mock("next/navigation", () => ({
    useRouter: () => ({ push: vi.fn(), back: vi.fn() }),
    usePathname: () => "/dashboard",
}));
```

---

## Passo 10.9 - Frontend: rodar testes e coverage

### Rodar todos os testes

```bash
docker compose exec frontend npx vitest run
```

Saida esperada:

```
 ✓ src/services/__tests__/dashboard-service.test.ts (3 tests) 45ms
 ✓ src/stores/__tests__/auth-store.test.ts (4 tests) 12ms
 ✓ src/components/__tests__/star-rating.test.tsx (4 tests) 38ms

 Test Files  3 passed (3)
      Tests  11 passed (11)
   Start at  10:30:00
   Duration  1.23s
```

### Rodar com watch mode (desenvolvimento)

```bash
docker compose exec frontend npx vitest
```

### Rodar com coverage

```bash
docker compose exec frontend npx vitest run --coverage
```

### Atualizar scripts no package.json

Verifique que os scripts ja estao no `frontend/package.json`:

```json
{
  "scripts": {
    "test": "vitest",
    "test:run": "vitest run",
    "test:coverage": "vitest run --coverage",
    "test:e2e": "playwright test"
  }
}
```

---

## Passo 10.10 - E2E: configurar Playwright

### Por que um container dedicado?

> **Importante:** Playwright nao roda dentro de containers Alpine (nosso frontend usa `node:22-alpine`). Ele precisa de dependencias de sistema (glibc, bibliotecas graficas) que so existem em distros como Ubuntu/Debian. Alem disso, o `node_modules` dentro do container pertence ao root, o que impede rodar `npx playwright` diretamente na maquina host.
>
> A solucao ideal e criar um **servico Docker dedicado** com a imagem oficial do Playwright (`mcr.microsoft.com/playwright`), que ja vem com Ubuntu + Chromium pré-instalados. Esse servico usa `profiles: [e2e]` para nao subir junto com o ambiente normal.

### Adicionar servico Playwright ao docker-compose.yml

Adicione ao final dos `services` no `docker-compose.yml`:

```yaml
  playwright:
    image: mcr.microsoft.com/playwright:v1.58.2-noble
    container_name: orderly-playwright
    working_dir: /app
    volumes:
      - ./frontend:/app
      - playwright_node_modules:/app/node_modules
    environment:
      BASE_URL: http://nginx:80
    entrypoint: ["sh", "-c", "npm install --save-dev @playwright/test@1.58.2 && npm install && exec \"$@\"", "--"]
    depends_on:
      - nginx
    profiles:
      - e2e
    networks:
      - orderly-network
```

Adicione tambem o volume nomeado na secao `volumes:` do docker-compose:

```yaml
volumes:
  # ... volumes existentes ...
  playwright_node_modules:
    name: orderly-playwright-node-modules
```

> O `profiles: [e2e]` faz com que esse container so suba quando voce usar `--profile e2e`. No dia a dia, `docker compose up -d` nao sobe o Playwright.
>
> O `entrypoint` instala as dependencias automaticamente antes de rodar o comando. Usamos um volume nomeado (`playwright_node_modules`) para que o `npm install` seja instantaneo a partir da segunda execucao (cache). A versao do `@playwright/test` e fixada em `1.58.2` para combinar com a imagem Docker.
>
> **Importante:** A versao do `@playwright/test` **deve** ser igual a versao da imagem Docker. Mantenha ambas alinhadas ao atualizar.

### Instalar Playwright no projeto

```bash
# Instalar a dependencia no package.json (via container frontend normal)
docker compose exec frontend npm install -D @playwright/test

# Nao precisa rodar "npx playwright install" — a imagem oficial ja vem com os browsers
```

### Criar playwright.config.ts

Crie `frontend/playwright.config.ts`:

```ts
import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
    testDir: "./e2e",
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: "html",
    use: {
        baseURL: process.env.BASE_URL || "http://127.0.0.1",
        trace: "on-first-retry",
        screenshot: "only-on-failure",
    },
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
    ],
});
```

> **Nota:** Nao temos bloco `webServer` porque os servidores ja estao rodando via Docker Compose. A `baseURL` usa a variavel `BASE_URL` definida no docker-compose (`http://nginx:80`), que permite o container Playwright acessar a aplicacao pela rede interna do Docker.

---

## Passo 10.11 - E2E: testes de fluxo completo

### Teste: Login admin

Crie `frontend/e2e/auth.spec.ts`:

```ts
import { test, expect } from "@playwright/test";

test.describe("Admin Authentication", () => {
    test("should show login page", async ({ page }) => {
        await page.goto("/login");

        await expect(page.getByText("Entrar")).toBeVisible();
        await expect(page.getByLabel("Email")).toBeVisible();
        await expect(page.getByLabel("Senha")).toBeVisible();
    });

    test("should login with valid credentials", async ({ page }) => {
        await page.goto("/login");

        await page.getByLabel("Email").fill("admin@orderly.com");
        await page.getByLabel("Senha").fill("password");
        await page.getByRole("button", { name: "Entrar" }).click();

        // Aguardar o redirect para o dashboard (timeout maior para SSR)
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
        // Usar heading para evitar conflito com link da sidebar
        await expect(
            page.getByRole("heading", { name: "Dashboard" }),
        ).toBeVisible();
    });

    test("should show error for invalid credentials", async ({ page }) => {
        await page.goto("/login");

        await page.getByLabel("Email").fill("admin@orderly.com");
        await page.getByLabel("Senha").fill("wrong-password");
        await page.getByRole("button", { name: "Entrar" }).click();

        await expect(
            page.getByText(/credenciais|invalido|erro/i),
        ).toBeVisible({ timeout: 10000 });
    });
});
```

### Teste: Dashboard com metricas

Crie `frontend/e2e/dashboard.spec.ts`:

```ts
import { test, expect } from "@playwright/test";

// Helper: login antes dos testes
async function loginAsAdmin(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.getByLabel("Email").fill("admin@orderly.com");
    await page.getByLabel("Senha").fill("password");
    await page.getByRole("button", { name: "Entrar" }).click();
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
}

test.describe("Dashboard", () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test("should display metric cards", async ({ page }) => {
        await expect(page.getByText("Pedidos Hoje")).toBeVisible();
        await expect(page.getByText("Faturamento Hoje")).toBeVisible();
        // Usar locator mais especifico para evitar conflito com sidebar
        await expect(
            page.locator("[data-slot='card-title']", { hasText: "Clientes" }),
        ).toBeVisible();
        await expect(
            page.locator("[data-slot='card-title']", { hasText: "Produtos" }),
        ).toBeVisible();
    });

    test("should display orders chart", async ({ page }) => {
        await expect(page.getByText("Pedidos por dia")).toBeVisible();
    });

    test("should display orders by status", async ({ page }) => {
        await expect(page.getByText("Pedidos por status")).toBeVisible();
    });

    test("should display recent evaluations", async ({ page }) => {
        await expect(page.getByText("Avaliacoes recentes")).toBeVisible();
    });
});
```

> **Nota:** Usamos `page.locator("[data-slot='card-title']", { hasText: "Clientes" })` em vez de `page.getByText("Clientes")` porque "Clientes" aparece tanto no card de metricas quanto no link da sidebar. O `getByText` com texto duplicado causa `strict mode violation` no Playwright.

### Teste: Fluxo de pedido completo

Crie `frontend/e2e/order-flow.spec.ts`:

```ts
import { test, expect } from "@playwright/test";

async function loginAsAdmin(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.getByLabel("Email").fill("admin@orderly.com");
    await page.getByLabel("Senha").fill("password");
    await page.getByRole("button", { name: "Entrar" }).click();
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
}

test.describe("Order Flow", () => {
    test("should navigate to orders page", async ({ page }) => {
        await loginAsAdmin(page);

        await page.getByRole("link", { name: "Pedidos" }).click();

        await expect(page).toHaveURL(/.*orders/);
        // Usar heading para evitar conflito com link da sidebar
        await expect(
            page.getByRole("heading", { name: "Pedidos" }),
        ).toBeVisible();
    });

    test("should display orders page content", async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto("/orders");

        await expect(page).toHaveURL(/.*orders/);
        await expect(
            page.getByRole("heading", { name: "Pedidos" }),
        ).toBeVisible();
    });
});
```

> **Nota:** Usamos `page.getByRole("heading", { name: "Pedidos" })` em vez de `page.getByText("Pedidos")` porque "Pedidos" aparece no heading e no link da sidebar. Selecionar por role evita o `strict mode violation`.

### Rodar testes E2E

```bash
# Certifique-se de que o ambiente esta rodando
docker compose up -d

# Rodar testes E2E via container dedicado
docker compose --profile e2e run --rm playwright npx playwright test

# Rodar apenas um arquivo de teste
docker compose --profile e2e run --rm playwright npx playwright test e2e/auth.spec.ts

# Ver relatorio HTML (copiar para a maquina host)
docker compose --profile e2e run --rm playwright npx playwright test --reporter=html
# O relatorio fica em frontend/playwright-report/index.html
```

> **Importante:** Testes E2E dependem do ambiente completo (backend + frontend + banco). Certifique-se de que `docker compose up -d` esta rodando e que os seeders foram executados (`docker compose exec backend php artisan db:seed`). O container Playwright acessa a aplicacao via `http://nginx:80` pela rede interna do Docker.

---

## Passo 10.12 - Verificacao end-to-end da Fase 10

### Checklist de verificacao

**Backend (Pest):**

- [ ] `tests/Pest.php` configurado com `RefreshDatabase` para Feature tests
- [ ] Helper `createAdminUser()` cria usuario com tenant para testes
- [ ] Helper `authHeaders()` retorna headers JWT
- [ ] `OrderFactory` e `ClientFactory` criadas
- [ ] Unit tests: Order model (status transitions) — 8 testes
- [ ] Unit tests: UpdateOrderStatusAction — 3 testes
- [ ] Unit tests: GetDashboardMetricsAction (estrutura) — 3 testes
- [ ] Feature tests: Auth API (login, logout, me) — 4 testes
- [ ] Feature tests: Plans CRUD — 6 testes
- [ ] Feature tests: Orders API (CRUD + status + filtro) — 5 testes
- [ ] Feature tests: Dashboard metrics — 5 testes
- [ ] Feature tests: Client Auth — 4 testes
- [ ] `php artisan test` roda sem erros
- [ ] Coverage report funcional com `--coverage`

**Frontend (Vitest):**

- [ ] `vitest.config.ts` configurado com jsdom + path alias
- [ ] `src/test/setup.ts` com Testing Library + MSW
- [ ] MSW handlers mockam os endpoints da API
- [ ] Unit test: `dashboard-service` — 3 testes
- [ ] Unit test: `auth-store` — 4 testes
- [ ] Component test: `StarRating` — 4 testes
- [ ] `npx vitest run` roda sem erros
- [ ] Coverage report funcional com `--coverage`

**E2E (Playwright):**

- [ ] Servico `playwright` adicionado ao docker-compose.yml com `profiles: [e2e]`
- [ ] `playwright.config.ts` configurado com `BASE_URL` e sem `webServer`
- [ ] Teste: Login admin (sucesso + falha) — 3 testes
- [ ] Teste: Dashboard metricas — 4 testes
- [ ] Teste: Order flow (navegacao + lista) — 2 testes
- [ ] `docker compose --profile e2e run --rm playwright npx playwright test` roda sem erros

### Comandos rapidos

```bash
# Backend: rodar todos os testes
docker compose exec backend php artisan test

# Backend: apenas unit
docker compose exec backend php artisan test --testsuite=Unit

# Backend: apenas feature
docker compose exec backend php artisan test --testsuite=Feature

# Backend: com coverage
docker compose exec backend php artisan test --coverage

# Frontend: rodar testes
docker compose exec frontend npx vitest run

# Frontend: watch mode
docker compose exec frontend npx vitest

# Frontend: com coverage
docker compose exec frontend npx vitest run --coverage

# E2E: rodar testes (via container dedicado)
docker compose --profile e2e run --rm playwright npx playwright test

# E2E: com relatorio HTML
docker compose --profile e2e run --rm playwright npx playwright test --reporter=html
```

### Resumo dos arquivos da Fase 10

**Backend:**

```
backend/
├── tests/
│   ├── Pest.php (configuracao global)
│   ├── Unit/
│   │   ├── Models/OrderTest.php
│   │   └── Actions/
│   │       ├── Order/UpdateOrderStatusActionTest.php
│   │       ├── Evaluation/CreateEvaluationActionTest.php
│   │       └── Dashboard/GetDashboardMetricsActionTest.php
│   └── Feature/Api/
│       ├── AuthTest.php
│       ├── PlanTest.php
│       ├── OrderTest.php
│       ├── DashboardTest.php
│       └── ClientAuthTest.php
├── database/factories/
│   ├── OrderFactory.php (novo)
│   └── ClientFactory.php (novo)
```

**Frontend:**

```
frontend/
├── vitest.config.ts (novo)
├── playwright.config.ts (novo)
├── src/test/
│   ├── setup.ts
│   └── mocks/
│       ├── handlers.ts
│       └── server.ts
├── src/services/__tests__/
│   └── dashboard-service.test.ts
├── src/stores/__tests__/
│   └── auth-store.test.ts
├── src/components/__tests__/
│   └── star-rating.test.tsx
└── e2e/
    ├── auth.spec.ts
    ├── dashboard.spec.ts
    └── order-flow.spec.ts
```

**Conceitos aprendidos:**
- **Piramide de testes** — muitos unit (rapidos), menos feature (banco), poucos E2E (lentos) — otimiza feedback loop
- **Pest `describe` + `it`** — syntax BDD expressiva: `it('can create a plan')` le como especificacao
- **`RefreshDatabase`** — migra e limpa o banco antes de cada Feature test, garantindo isolamento
- **`createAdminUser()` helper** — encapsula a criacao de user + tenant + plan, evitando boilerplate em cada teste
- **Factory states** — `Order::factory()->delivered()->create()` usa state methods para cenarios especificos
- **MSW (Mock Service Worker)** — intercepta `fetch` no nivel de rede: o codigo de producao nao sabe que esta sendo mockado
- **`@testing-library/react`** — testa pelo comportamento visivel (texto, roles, labels), nao pela implementacao interna
- **Playwright `beforeEach` com login** — reutiliza autenticacao em cada teste E2E sem duplicar codigo
- **`--coverage`** — gera relatorio de cobertura para identificar codigo nao testado e guiar proximos testes

**Proximo:** Fase 11 - CI/CD com GitHub Actions

---


---

[Voltar ao README](../README.md)
