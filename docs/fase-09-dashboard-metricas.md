# Fase 9 - Dashboard com Metricas

**Objetivo:** Transformar o dashboard placeholder em um painel real com metricas do negocio, consumindo dados agregados da API.

**O que voce vai aprender:**
- Queries de agregacao no Eloquent (`count`, `sum`, `whereDate`, `groupBy`)
- Endpoint dedicado para metricas (sem CRUD — apenas leitura agregada)
- Componentes de Card com icones e valores dinamicos
- Grafico de barras com Recharts
- Tratamento de tenant vs super-admin em metricas

**Pre-requisitos:**
- Fase 8 completa (Orders, Clients, Evaluations)
- Seeders rodados (pedidos e avaliacoes de exemplo)

---

## Passo 9.1 - Conceito: Dashboard de Metricas

### O que vamos construir

O dashboard mostra uma visao geral do negocio para o admin/gerente:

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Pedidos Hoje │ │  Faturamento │ │   Clientes   │ │  Produtos    │
│      12      │ │  R$ 1.250,00 │ │      45      │ │      28      │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  Pedidos por dia (ultimos 7 dias)            Grafico de barras  │
│  ███ ████ ██ █████ ███████ ████ ██                              │
└──────────────────────────────────────────────────────────────────┘

┌────────────────────────────────┐ ┌────────────────────────────────┐
│  Pedidos por status            │ │  Avaliacoes recentes           │
│  Aberto: 3  Preparando: 5     │ │  ★★★★★ Joao - "Otimo!"        │
│  Pronto: 2  Entregue: 15      │ │  ★★★★☆ Maria - "Bom"          │
└────────────────────────────────┘ └────────────────────────────────┘
```

### Decisoes de arquitetura

| Decisao | Motivo |
|---------|--------|
| Endpoint unico `/dashboard/metrics` | Evita N+1 de requests — frontend faz um unico fetch |
| Queries diretas no Action (sem Repository) | Metricas sao read-only e cross-model, nao faz sentido um repositorio dedicado |
| `Carbon::today()` para filtro "hoje" | Timezone do servidor — em producao usar timezone do tenant |
| Super-admin ve metricas globais | Sem `tenant_id` no filtro, agrega tudo |

---

## Passo 9.2 - Backend: Action de Metricas

Crie `backend/app/Actions/Dashboard/GetDashboardMetricsAction.php`:

```php
<?php

namespace App\Actions\Dashboard;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderEvaluation;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardMetricsAction
{
    /**
     * @param  int|null  $tenantId  null = super-admin (metricas globais)
     */
    public function execute(?int $tenantId): array
    {
        return [
            'cards' => $this->getCards($tenantId),
            'orders_per_day' => $this->getOrdersPerDay($tenantId),
            'orders_by_status' => $this->getOrdersByStatus($tenantId),
            'latest_evaluations' => $this->getLatestEvaluations($tenantId),
        ];
    }

    private function getCards(?int $tenantId): array
    {
        $orderQuery = Order::query()->withoutGlobalScopes();
        $productQuery = Product::query()->withoutGlobalScopes();

        if ($tenantId) {
            $orderQuery->where('tenant_id', $tenantId);
            $productQuery->where('tenant_id', $tenantId);
        }

        $ordersToday = (clone $orderQuery)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $revenueToday = (clone $orderQuery)
            ->whereDate('created_at', Carbon::today())
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->sum('total');

        $totalClients = Client::count();

        $totalProducts = (clone $productQuery)->count();

        return [
            'orders_today' => $ordersToday,
            'revenue_today' => number_format($revenueToday, 2, '.', ''),
            'total_clients' => $totalClients,
            'total_products' => $totalProducts,
        ];
    }

    private function getOrdersPerDay(?int $tenantId): array
    {
        $query = Order::query()->withoutGlobalScopes()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->groupBy('date')
            ->orderBy('date');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $results = $query->get();

        // Preencher dias sem pedidos com 0
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $found = $results->firstWhere('date', $date);
            $days[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => $found ? (int) $found->total : 0,
            ];
        }

        return $days;
    }

    private function getOrdersByStatus(?int $tenantId): array
    {
        $query = Order::query()->withoutGlobalScopes()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    private function getLatestEvaluations(?int $tenantId): array
    {
        $query = OrderEvaluation::with(['client', 'order'])
            ->latest()
            ->limit(5);

        if ($tenantId) {
            $query->whereHas('order', fn ($q) => $q->where('tenant_id', $tenantId));
        }

        return $query->get()->map(fn ($eval) => [
            'id' => $eval->id,
            'stars' => $eval->stars,
            'comment' => $eval->comment,
            'client_name' => $eval->client->name,
            'order_identify' => $eval->order->identify,
            'created_at' => $eval->created_at->toISOString(),
        ])->toArray();
    }
}
```

> **Por que `withoutGlobalScopes()`?** O model `Order` usa o trait `BelongsToTenant` que aplica um Global Scope filtrando por `tenant_id` automaticamente. Na Action de metricas, precisamos controlar o filtro manualmente — o super-admin deve ver todos os tenants (`tenantId = null`), enquanto o gerente ve apenas o seu. Sem `withoutGlobalScopes()`, o Global Scope aplicaria o filtro de tenant duplicado ou impediria o super-admin de ver dados globais.

> **Por que queries diretas no Action (sem Repository)?** Metricas agregam dados de multiplos models (`Order`, `Product`, `Client`, `OrderEvaluation`). Criar um "DashboardRepository" seria um anti-pattern — repositorios devem ser por entidade. A Action e o lugar certo para orquestrar queries cross-model.

---

## Passo 9.3 - Backend: Dashboard Controller + Rota

Crie `backend/app/Http/Controllers/Api/V1/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Actions\Dashboard\GetDashboardMetricsAction;
use Illuminate\Http\JsonResponse;

/**
 * @tags Dashboard
 */
class DashboardController extends Controller
{
    /**
     * Metricas do dashboard
     *
     * Retorna metricas agregadas: pedidos do dia, faturamento, pedidos por dia
     * (ultimos 7 dias), pedidos por status e avaliacoes recentes.
     * Para super-admin, retorna metricas globais de todos os tenants.
     */
    public function metrics(GetDashboardMetricsAction $action): JsonResponse
    {
        $user = auth('api')->user();
        $tenantId = $user->tenant_id;

        // Super-admin ve metricas globais
        if ($user->isSuperAdmin()) {
            $tenantId = null;
        }

        return response()->json([
            'data' => $action->execute($tenantId),
        ]);
    }
}
```

### Rota

Adicione o import no topo de `backend/routes/api.php`:

```php
use App\Http\Controllers\Api\V1\DashboardController;
```

Adicione a rota dentro do grupo `auth:api`, `tenant` (antes do grupo `tenant:required`):

```php
        // Dashboard Metrics
        Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);
```

O trecho completo fica assim:

```php
    // Rotas protegidas (requer JWT + tenant)
    Route::middleware('auth:api', 'tenant')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard Metrics
        Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);

        // Plans CRUD (protegido por permissao)
        // ... restante das rotas
    });
```

> **Nota:** A rota fica no grupo `auth:api` + `tenant` (modo optional), nao no `tenant:required`. Assim o super-admin consegue acessar sem ter `tenant_id`.

---

## Passo 9.4 - Teste da API de Metricas

Rode o seeder para garantir que existem dados:

```bash
docker compose exec backend php artisan db:seed
```

Teste o endpoint (substitua `TOKEN` pelo token obtido no login):

```bash
# Login
curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}' | jq '.access_token'

# Metricas
curl -s http://127.0.0.1/api/v1/dashboard/metrics \
  -H "Authorization: Bearer TOKEN" | jq '.'
```

Resposta esperada:

```json
{
  "data": {
    "cards": {
      "orders_today": 0,
      "revenue_today": "0.00",
      "total_clients": 3,
      "total_products": 5
    },
    "orders_per_day": [
      { "date": "2026-03-06", "label": "06/03", "total": 0 },
      { "date": "2026-03-07", "label": "07/03", "total": 0 },
      { "date": "2026-03-08", "label": "08/03", "total": 2 },
      { "date": "2026-03-09", "label": "09/03", "total": 0 },
      { "date": "2026-03-10", "label": "10/03", "total": 1 },
      { "date": "2026-03-11", "label": "11/03", "total": 0 },
      { "date": "2026-03-12", "label": "12/03", "total": 0 }
    ],
    "orders_by_status": {
      "open": 2,
      "delivered": 3,
      "preparing": 1
    },
    "latest_evaluations": [
      {
        "id": 1,
        "stars": 5,
        "comment": "Otimo atendimento!",
        "client_name": "Joao Silva",
        "order_identify": "ORD-0001",
        "created_at": "2026-03-08T15:30:00.000000Z"
      }
    ]
  }
}
```

> Verifique tambem no Swagger: `http://127.0.0.1/docs/api` — a tag **Dashboard** deve aparecer com o endpoint `GET /v1/dashboard/metrics`.

---

## Passo 9.5 - Frontend: tipos TypeScript + servico

### Tipos

Crie `frontend/src/types/dashboard.ts`:

```ts
export interface DashboardCards {
    orders_today: number;
    revenue_today: string;
    total_clients: number;
    total_products: number;
}

export interface OrdersPerDay {
    date: string;
    label: string;
    total: number;
}

export interface LatestEvaluation {
    id: number;
    stars: number;
    comment: string | null;
    client_name: string;
    order_identify: string;
    created_at: string;
}

export interface DashboardMetrics {
    cards: DashboardCards;
    orders_per_day: OrdersPerDay[];
    orders_by_status: Record<string, number>;
    latest_evaluations: LatestEvaluation[];
}
```

### Servico

Crie `frontend/src/services/dashboard-service.ts`:

```ts
import { apiClient } from "@/lib/api";
import type { DashboardMetrics } from "@/types/dashboard";

export async function getDashboardMetrics(): Promise<DashboardMetrics> {
    const response = await apiClient<{ data: DashboardMetrics }>(
        "/v1/dashboard/metrics",
    );
    return response.data;
}
```

---

## Passo 9.6 - Frontend: instalar Recharts

Recharts e a biblioteca de graficos mais popular para React. Instale dentro do container:

```bash
docker compose exec frontend npm install recharts
```

> **Por que Recharts?** E declarativo (componentes React), leve (~40kb gzipped), e compativel com SSR do Next.js. Alternativas como Chart.js precisam de wrappers extras para React.

---

## Passo 9.7 - Frontend: pagina do Dashboard

Substitua o conteudo de `frontend/src/app/(admin)/dashboard/page.tsx`:

```tsx
"use client";

import { useEffect, useState } from "react";
import { getDashboardMetrics } from "@/services/dashboard-service";
import type { DashboardMetrics } from "@/types/dashboard";
import { Skeleton } from "@/components/ui/skeleton";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    ShoppingBag,
    DollarSign,
    Users,
    Package,
    Star,
} from "lucide-react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from "recharts";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

const ORDER_STATUS_LABELS: Record<string, string> = {
    open: "Aberto",
    accepted: "Aceito",
    rejected: "Rejeitado",
    preparing: "Preparando",
    done: "Pronto",
    delivered: "Entregue",
};

function StarRating({ stars }: { stars: number }) {
    return (
        <div className="flex gap-0.5">
            {Array.from({ length: 5 }).map((_, i) => (
                <Star
                    key={i}
                    className={`h-3.5 w-3.5 ${
                        i < stars
                            ? "fill-yellow-400 text-yellow-400"
                            : "text-gray-300"
                    }`}
                />
            ))}
        </div>
    );
}

export default function DashboardPage() {
    const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getDashboardMetrics()
            .then(setMetrics)
            .catch((err) => console.error("Erro ao carregar metricas:", err))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <Skeleton key={i} className="h-28" />
                    ))}
                </div>
                <Skeleton className="h-72" />
            </div>
        );
    }

    if (!metrics) {
        return (
            <p className="text-muted-foreground">
                Erro ao carregar metricas.
            </p>
        );
    }

    const cards = [
        {
            title: "Pedidos Hoje",
            value: String(metrics.cards.orders_today),
            icon: ShoppingBag,
            description: "pedidos realizados hoje",
        },
        {
            title: "Faturamento Hoje",
            value: `R$ ${Number(metrics.cards.revenue_today).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
            icon: DollarSign,
            description: "receita do dia (excluindo rejeitados)",
        },
        {
            title: "Clientes",
            value: String(metrics.cards.total_clients),
            icon: Users,
            description: "clientes cadastrados",
        },
        {
            title: "Produtos",
            value: String(metrics.cards.total_products),
            icon: Package,
            description: "produtos no catalogo",
        },
    ];

    return (
        <div className="space-y-6">
            <TenantRequiredAlert resource="metricas" />

            <h1 className="text-2xl font-bold">Dashboard</h1>

            {/* Cards de metricas */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.title}>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {card.title}
                            </CardTitle>
                            <card.icon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {card.value}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {card.description}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Grafico de pedidos por dia */}
            <Card>
                <CardHeader>
                    <CardTitle>Pedidos por dia</CardTitle>
                    <CardDescription>Ultimos 7 dias</CardDescription>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart data={metrics.orders_per_day}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="label" />
                            <YAxis allowDecimals={false} />
                            <Tooltip
                                formatter={(value: number) => [
                                    value,
                                    "Pedidos",
                                ]}
                                labelFormatter={(label: string) =>
                                    `Dia ${label}`
                                }
                            />
                            <Bar
                                dataKey="total"
                                fill="#3b82f6"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2">
                {/* Pedidos por status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Pedidos por status</CardTitle>
                        <CardDescription>
                            Distribuicao de todos os pedidos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {Object.keys(metrics.orders_by_status).length ===
                        0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhum pedido encontrado.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {Object.entries(
                                    metrics.orders_by_status,
                                ).map(([status, count]) => (
                                    <div
                                        key={status}
                                        className="flex items-center justify-between"
                                    >
                                        <span className="text-sm">
                                            {ORDER_STATUS_LABELS[status] ||
                                                status}
                                        </span>
                                        <span className="text-sm font-bold">
                                            {count}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Avaliacoes recentes */}
                <Card>
                    <CardHeader>
                        <CardTitle>Avaliacoes recentes</CardTitle>
                        <CardDescription>
                            Ultimas 5 avaliacoes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {metrics.latest_evaluations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhuma avaliacao encontrada.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {metrics.latest_evaluations.map((eval_) => (
                                    <div
                                        key={eval_.id}
                                        className="flex items-start justify-between gap-2"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {eval_.client_name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {eval_.order_identify}
                                                </span>
                                            </div>
                                            {eval_.comment && (
                                                <p className="text-xs text-muted-foreground">
                                                    &ldquo;{eval_.comment}
                                                    &rdquo;
                                                </p>
                                            )}
                                        </div>
                                        <StarRating
                                            stars={eval_.stars}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
```

> **Nota sobre `"use client"`:** O dashboard usa `useState`, `useEffect` e Recharts — todos requerem client-side rendering. Em producao, poderiamos usar Server Components para os cards estaticos e Client Components apenas para o grafico.

---

## Passo 9.8 - Verificacao end-to-end da Fase 9

### Checklist de verificacao

**Backend:**

- [ ] Action `GetDashboardMetricsAction` retorna cards, orders_per_day, orders_by_status, latest_evaluations
- [ ] `withoutGlobalScopes()` usado para evitar conflito com TenantScope
- [ ] Queries filtram por `tenant_id` quando usuario tem tenant
- [ ] Super-admin ve metricas globais (`tenantId = null`)
- [ ] Faturamento exclui pedidos rejeitados
- [ ] `orders_per_day` preenche dias sem pedidos com `total: 0`
- [ ] Endpoint `GET /v1/dashboard/metrics` retorna 200 com JSON
- [ ] Swagger mostra tag "Dashboard" com o endpoint

**Frontend:**

- [ ] Tipos `DashboardMetrics` em `dashboard.ts`
- [ ] Servico `dashboard-service.ts` consome endpoint
- [ ] Recharts instalado (`npm install recharts`)
- [ ] Dashboard exibe 4 cards com icones (Pedidos Hoje, Faturamento, Clientes, Produtos)
- [ ] Grafico de barras (Recharts) mostra pedidos dos ultimos 7 dias
- [ ] Card "Pedidos por status" lista contagem por status
- [ ] Card "Avaliacoes recentes" mostra ultimas 5 com estrelas
- [ ] Loading skeleton durante o fetch
- [ ] `TenantRequiredAlert` aparece para super-admin

### Fluxo de teste

1. **Criar pedidos** (se nao existem): via frontend `/orders` > Novo Pedido, ou via Swagger
2. **Logar como gerente** (`gerente@demo.com` / `password`) e acessar `/dashboard`
3. **Verificar cards** com valores reais (pedidos hoje, faturamento, etc.)
4. **Verificar grafico** com barras dos ultimos 7 dias
5. **Logar como super-admin** (`admin@orderly.com` / `password`) e verificar metricas globais
6. **Swagger**: `http://127.0.0.1/docs/api` → tag Dashboard → testar `GET /v1/dashboard/metrics`

### Resumo dos arquivos da Fase 9

**Backend:**

```
backend/app/
├── Actions/Dashboard/GetDashboardMetricsAction.php
├── Http/Controllers/Api/V1/DashboardController.php
└── routes/api.php (modificado — rota dashboard/metrics)
```

**Frontend:**

```
frontend/
├── src/types/dashboard.ts
├── src/services/dashboard-service.ts
├── src/app/(admin)/dashboard/page.tsx (substituido)
└── package.json (modificado — recharts)
```

**Conceitos aprendidos:**
- **Queries de agregacao** — `count()`, `sum()`, `groupBy()`, `whereDate()` para metricas eficientes
- **`withoutGlobalScopes()`** — necessario quando a Action precisa controlar o filtro de tenant manualmente (super-admin vs gerente)
- **Action cross-model** — quando a logica agrega multiplos models, a Action e o lugar certo (nao o Repository)
- **Preenchimento de gaps temporais** — dias sem dados precisam aparecer como `0` no grafico, nao simplesmente omitidos
- **`DB::raw()` em selects** — necessario para `DATE(created_at)` e `COUNT(*)`, funcoes SQL puras
- **Recharts** — biblioteca declarativa de graficos para React com `ResponsiveContainer` para responsividade
- **`fill="#3b82f6"`** — cor fixa (Tailwind `blue-500`) porque SVG inline do Recharts nao resolve variaveis CSS como `hsl(var(--primary))`
- **Cast `(int)` em agregacoes** — `COUNT(*)` do PostgreSQL pode retornar string/float; o `->map(fn ($value) => (int) $value)` garante inteiros no JSON

**Proximo:** Fase 10 - Testes (Unit, Integration, E2E)

---


---

[Voltar ao README](../README.md)
