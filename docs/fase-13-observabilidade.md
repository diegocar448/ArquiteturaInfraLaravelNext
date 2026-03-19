# Fase 13 - Observabilidade (Prometheus + Grafana + Logging)

> Monitorar e entender o comportamento da aplicacao em tempo real. Metricas, logs estruturados, dashboards e alertas — tudo que um sistema de producao precisa.

**Objetivo:** Implementar os tres pilares da observabilidade (metricas, logs, traces) com ferramentas open-source, integradas ao Docker Compose e ao Kubernetes.

**O que voce vai aprender:**
- Prometheus: coleta e armazenamento de metricas (time-series)
- Grafana: dashboards e alertas visuais
- Logging estruturado: logs em JSON para facil busca e correlacao
- Loki + Promtail: agregacao de logs (alternativa leve ao ELK)
- Health checks e endpoints de metricas
- Alerting: regras de alerta com Alertmanager

**Pre-requisitos:**
- Fases 1-12 completas
- Docker Compose funcionando
- Conhecimento basico de HTTP e JSON

---

## Passo 13.1 - Conceito: Os Tres Pilares da Observabilidade

### Monitorar vs Observar

**Monitoramento** responde: "O sistema esta funcionando?"
**Observabilidade** responde: "Por que o sistema nao esta funcionando?"

Os tres pilares da observabilidade sao:

| Pilar | O que responde | Ferramenta |
|-------|---------------|------------|
| **Metricas** | Quanto? Quantas requests? Qual a latencia? | Prometheus + Grafana |
| **Logs** | O que aconteceu? Qual o erro exato? | Loki + Promtail |
| **Traces** | Onde o tempo foi gasto? Qual servico e lento? | (futuro: Jaeger/Tempo) |

### Arquitetura da stack de observabilidade

```
┌─────────────────────────────────────────────────────────────────┐
│                    Grafana (porta 3001)                         │
│              Dashboards + Alertas visuais                       │
│         ┌──────────────┐  ┌──────────────┐                     │
│         │  Prometheus   │  │    Loki      │                     │
│         │  (metricas)   │  │   (logs)     │                     │
│         └──────┬───────┘  └──────┬───────┘                     │
└────────────────┼─────────────────┼─────────────────────────────┘
                 │                 │
     ┌───────────┼─────────┐      │
     │           │         │      │
┌────▼────┐ ┌───▼───┐ ┌───▼───┐  │
│ Backend │ │ Nginx │ │ Redis │  │
│ /metrics│ │ stub  │ │ :9121 │  │
│ :9000   │ │ :9113 │ │       │  │
└─────────┘ └───────┘ └───────┘  │
                                  │
                           ┌──────▼──────┐
                           │  Promtail   │
                           │ (coleta de  │
                           │   logs)     │
                           └─────────────┘
```

### Por que Prometheus + Grafana (e nao Datadog/New Relic)?

| Criterio | Prometheus + Grafana | Datadog/New Relic |
|----------|---------------------|-------------------|
| Custo | Gratuito (open-source) | Pago ($$$/mes) |
| Controle | Total (self-hosted) | Vendor lock-in |
| Aprendizado | Alto valor para portfolio | "Cliquei no botao" |
| Kubernetes | Nativo (kube-state-metrics) | Agent proprietario |
| Comunidade | Enorme (CNCF graduated) | Suporte comercial |

> **Para portfolio:** Prometheus + Grafana mostra que voce sabe monitorar de verdade, nao apenas contratar um SaaS.

---

## Passo 13.2 - Endpoint de metricas no Laravel (Prometheus)

### Por que expor metricas?

O Prometheus funciona no modelo **pull**: ele acessa um endpoint `/metrics` da sua aplicacao e coleta metricas formatadas. Precisamos:

1. Instalar um pacote que exponha metricas PHP/Laravel
2. Criar o endpoint `/metrics`
3. Configurar o Prometheus para "scrapear" esse endpoint

### Instalar o pacote

```bash
docker compose exec backend composer require promphp/prometheus_client_php
```

### Criar o Service Provider

```bash
docker compose exec backend php artisan make:provider PrometheusServiceProvider
sudo chown -R $USER:$USER backend/app/Providers/
```

**`backend/app/Providers/PrometheusServiceProvider.php`**:
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function () {
            return new CollectorRegistry(new InMemory());
        });
    }
}
```

> **Por que InMemory?** Para desenvolvimento, metricas em memoria sao suficientes. Em producao, voce pode trocar por `Redis` ou `APC` para persistir entre requests.

### Criar o Middleware de metricas

```bash
docker compose exec backend php artisan make:middleware PrometheusMiddleware
sudo chown -R $USER:$USER backend/app/Http/Middleware/
```

**`backend/app/Http/Middleware/PrometheusMiddleware.php`**:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function __construct(
        private CollectorRegistry $registry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;
        $method = $request->method();
        $route = $request->route()?->uri() ?? 'unknown';
        $status = (string) $response->getStatusCode();

        // Contador de requests
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );
        $counter->inc([$method, $route, $status]);

        // Histograma de latencia
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0]
        );
        $histogram->observe($duration, [$method, $route]);

        return $response;
    }
}
```

### Criar o Controller de metricas

```bash
docker compose exec backend php artisan make:controller Api/V1/MetricsController
sudo chown -R $USER:$USER backend/app/Http/Controllers/
```

**`backend/app/Http/Controllers/Api/V1/MetricsController.php`**:
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function __invoke(CollectorRegistry $registry): \Illuminate\Http\Response
    {
        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return response($result, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }
}
```

### Registrar a rota

**`backend/routes/api.php`** — adicionar fora do grupo autenticado:
```php
// Metricas Prometheus (sem autenticacao — acesso interno apenas)
Route::get('/metrics', \App\Http\Controllers\Api\V1\MetricsController::class);
```

### Registrar o Middleware

**`backend/bootstrap/app.php`** — adicionar ao middleware da API:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        \App\Http\Middleware\PrometheusMiddleware::class,
    ]);
})
```

### Testar

```bash
# Endpoint de metricas (publico, sem autenticacao)
docker compose exec nginx curl -s http://localhost/api/v1/metrics

# Deve retornar algo como:
# # HELP app_http_requests_total Total HTTP requests
# # TYPE app_http_requests_total counter
# app_http_requests_total{method="GET",route="api/metrics",status="200"} 1
```

> **Dica:** Para testar endpoints protegidos (que exigem JWT), use o fluxo abaixo:

```bash
# 1. Obter token JWT via login
TOKEN=$(docker compose exec nginx curl -s http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 2. Verificar que o token foi obtido
echo $TOKEN

# 3. Usar o token para acessar endpoints protegidos
docker compose exec nginx curl -s http://localhost/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 4. Testar outros endpoints protegidos
docker compose exec nginx curl -s http://localhost/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

docker compose exec nginx curl -s http://localhost/api/v1/dashboard/metrics \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

> **Importante:** Sempre inclua o header `Accept: application/json` nas requests. Sem ele, o Laravel retorna HTML em vez de JSON nos erros (ex: 401, 500).

---

## Passo 13.3 - Health Check endpoint dedicado

### Por que um health check separado?

O Nginx ja tem `/health`, mas e um JSON hardcoded — nao verifica se o backend esta realmente funcionando. Precisamos de:

| Tipo | O que verifica | Uso |
|------|---------------|-----|
| **Liveness** | "O processo esta rodando?" | Kubernetes mata e recria o pod |
| **Readiness** | "O app consegue atender requests?" | Kubernetes remove do load balancer |
| **Startup** | "O app ja inicializou?" | Kubernetes espera antes de checar |

### Criar o Controller

**`backend/app/Http/Controllers/Api/V1/HealthController.php`**:
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function readiness(): JsonResponse
    {
        $checks = [];

        // Verificar banco de dados
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'failed: ' . $e->getMessage();
        }

        // Verificar Redis
        try {
            Cache::store('redis')->put('health_check', true, 10);
            $checks['redis'] = Cache::store('redis')->get('health_check') ? 'ok' : 'failed';
        } catch (\Exception $e) {
            $checks['redis'] = 'failed: ' . $e->getMessage();
        }

        $allHealthy = collect($checks)->every(fn ($status) => $status === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
    }
}
```

### Registrar rotas

**`backend/routes/api.php`**:
```php
// Health checks (sem autenticacao)
Route::get('/health/live', [\App\Http\Controllers\Api\V1\HealthController::class, 'liveness']);
Route::get('/health/ready', [\App\Http\Controllers\Api\V1\HealthController::class, 'readiness']);
```

### Testar

```bash
# Liveness (deve retornar 200)
docker compose exec nginx curl -s http://localhost/api/v1/health/live
# {"status":"ok"}

# Readiness (verifica DB + Redis)
docker compose exec nginx curl -s http://localhost/api/v1/health/ready
# {"status":"ok","checks":{"database":"ok","redis":"ok"},"timestamp":"2026-03-17T..."}
```

---

## Passo 13.4 - Logging estruturado (JSON)

### Por que logs em JSON?

Logs tradicionais:
```
[2026-03-17 10:30:45] local.ERROR: User not found {"user_id":123}
```

Logs estruturados (JSON):
```json
{"timestamp":"2026-03-17T10:30:45Z","level":"error","message":"User not found","user_id":123,"request_id":"abc-123","duration_ms":45}
```

**Vantagens:**
- Parseavel por maquina (Loki, Elasticsearch, CloudWatch)
- Filtravel por campos (`user_id`, `request_id`, `level`)
- Correlacionavel entre servicos (via `request_id`)

### Configurar o Laravel para logs JSON

**`backend/config/logging.php`** — adicionar canal `json`:
```php
'channels' => [
    // ... canais existentes ...

    'json' => [
        'driver' => 'monolog',
        'handler' => \Monolog\Handler\StreamHandler::class,
        'with' => [
            'stream' => 'php://stderr',
        ],
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'formatter_with' => [
            'includeStacktraces' => true,
        ],
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

### Criar Middleware de Request ID

Cada request recebe um ID unico que viaja pelos logs — essencial para debugar problemas em producao.

**`backend/app/Http/Middleware/RequestIdMiddleware.php`**:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());

        // Disponibilizar para toda a aplicacao
        app()->instance('request_id', $requestId);

        // Adicionar ao contexto de log
        \Illuminate\Support\Facades\Log::shareContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        // Retornar o request ID no header da resposta
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
```

### Registrar o Middleware

**`backend/bootstrap/app.php`**:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\RequestIdMiddleware::class,
    ]);
    $middleware->api(append: [
        \App\Http\Middleware\PrometheusMiddleware::class,
    ]);
})
```

### Configurar o .env

```env
# Desenvolvimento: log legivel no terminal
LOG_CHANNEL=stack

# Producao: JSON para Loki/CloudWatch
# LOG_CHANNEL=json
```

### Testar

```bash
# 1. Testar endpoint publico e verificar o header X-Request-ID na resposta
docker compose exec nginx curl -s -i http://localhost/api/v1/health/live
# Deve conter: X-Request-ID: <uuid>

# 2. Testar endpoint de metricas (publico)
docker compose exec nginx curl -s http://localhost/api/v1/metrics

# 3. Obter token JWT para testar endpoints protegidos
TOKEN=$(docker compose exec nginx curl -s http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 4. Testar endpoint protegido (gera log com request_id)
docker compose exec nginx curl -s http://localhost/api/v1/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 5. Ver logs do backend — devem conter request_id, method, uri, ip
docker compose logs backend --tail 10

# 6. Testar com LOG_CHANNEL=json (producao)
# Edite backend/.env: LOG_CHANNEL=json
# Reinicie: docker compose restart backend
# Faca requests e veja os logs em formato JSON estruturado:
# docker compose logs backend --tail 5
```

> **Dica:** O `X-Request-ID` e propagado em cada resposta HTTP. Voce pode usar esse ID para rastrear uma request especifica em todos os logs (backend, Nginx, Loki). Se o cliente enviar o header `X-Request-ID`, o backend reutiliza — util para correlacionar requests entre frontend e backend.

---

## Passo 13.5 - Prometheus + Grafana no Docker Compose

### Criar configuracao do Prometheus

```bash
mkdir -p docker/prometheus
```

**`docker/prometheus/prometheus.yml`**:
```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  # Metricas do Laravel (via endpoint /api/v1/metrics)
  - job_name: 'laravel'
    metrics_path: '/api/v1/metrics'
    static_configs:
      - targets: ['nginx:80']
        labels:
          app: 'orderly'
          service: 'backend'

  # Metricas do Redis
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']
        labels:
          app: 'orderly'
          service: 'redis'

  # Metricas do PostgreSQL
  - job_name: 'postgres'
    static_configs:
      - targets: ['postgres-exporter:9187']
        labels:
          app: 'orderly'
          service: 'postgres'

  # Metricas do Nginx
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx-exporter:9113']
        labels:
          app: 'orderly'
          service: 'nginx'

  # Metricas do proprio Prometheus
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
```

### Criar configuracao do Grafana

```bash
mkdir -p docker/grafana/provisioning/datasources
mkdir -p docker/grafana/provisioning/dashboards
```

**`docker/grafana/provisioning/datasources/datasources.yml`**:
```yaml
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://prometheus:9090
    isDefault: true
    editable: true

  - name: Loki
    type: loki
    access: proxy
    url: http://loki:3100
    editable: true
```

**`docker/grafana/provisioning/dashboards/dashboards.yml`**:
```yaml
apiVersion: 1

providers:
  - name: 'default'
    orgId: 1
    folder: 'Orderly'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 30
    options:
      path: /var/lib/grafana/dashboards
      foldersFromFilesStructure: false
```

### Adicionar servicos ao docker-compose.yml

Adicionar ao `docker-compose.yml`, no bloco `services`:

```yaml
  # ── Observabilidade ────────────────────────────────────
  prometheus:
    image: prom/prometheus:v3.4.0
    container_name: orderly-prometheus
    volumes:
      - ./docker/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.retention.time=7d'
      - '--web.enable-lifecycle'
    ports:
      - "9090:9090"
    depends_on:
      - nginx
    profiles:
      - monitoring
    networks:
      - orderly-network

  grafana:
    image: grafana/grafana:11.6.0
    container_name: orderly-grafana
    volumes:
      - grafana_data:/var/lib/grafana
      - ./docker/grafana/provisioning:/etc/grafana/provisioning:ro
    environment:
      GF_SECURITY_ADMIN_USER: admin
      GF_SECURITY_ADMIN_PASSWORD: orderly123
      GF_USERS_ALLOW_SIGN_UP: "false"
    ports:
      - "3001:3000"
    depends_on:
      - prometheus
    profiles:
      - monitoring
    networks:
      - orderly-network

  redis-exporter:
    image: oliver006/redis_exporter:v1.73.0
    container_name: orderly-redis-exporter
    environment:
      REDIS_ADDR: redis://redis:6379
    depends_on:
      - redis
    profiles:
      - monitoring
    networks:
      - orderly-network

  postgres-exporter:
    image: prometheuscommunity/postgres-exporter:v0.17.1
    container_name: orderly-postgres-exporter
    environment:
      DATA_SOURCE_NAME: "postgresql://orderly:orderly@postgres:5432/orderly?sslmode=disable"
    depends_on:
      - postgres
    profiles:
      - monitoring
    networks:
      - orderly-network

  nginx-exporter:
    image: nginx/nginx-prometheus-exporter:1.4.2
    container_name: orderly-nginx-exporter
    command:
      - '--nginx.scrape-uri=http://nginx:80/stub_status'
    depends_on:
      - nginx
    profiles:
      - monitoring
    networks:
      - orderly-network
```

Adicionar volumes ao bloco `volumes`:
```yaml
volumes:
  # ... volumes existentes ...
  prometheus_data:
  grafana_data:
```

### Habilitar stub_status no Nginx

**`docker/nginx/default.conf`** — adicionar dentro do bloco `server`:
```nginx
    # Nginx metrics (para Prometheus)
    location /stub_status {
        stub_status;
        allow 172.16.0.0/12;  # Docker network
        deny all;
    }
```

### Subir a stack de monitoramento

```bash
docker compose --profile monitoring up -d
```

### Verificar

```bash
# Prometheus (targets devem estar UP)
# Abrir: http://localhost:9090/targets

# Grafana (login: admin / orderly123)
# Abrir: http://localhost:3001
```

---

## Passo 13.6 - Loki + Promtail (Agregacao de logs)

### O que e Loki?

Loki e como "Prometheus para logs" — criado pela Grafana Labs. Diferente do Elasticsearch (ELK), Loki **nao indexa o conteudo** dos logs, apenas os labels. Isso torna muito mais leve e barato.

| Componente | Funcao |
|-----------|--------|
| **Loki** | Armazena e indexa logs por labels |
| **Promtail** | Agente que coleta logs dos containers e envia ao Loki |
| **Grafana** | Interface para consultar logs (LogQL) |

### Criar configuracao do Loki

```bash
mkdir -p docker/loki
```

**`docker/loki/loki-config.yml`**:
```yaml
auth_enabled: false

server:
  http_listen_port: 3100

common:
  path_prefix: /loki
  storage:
    filesystem:
      chunks_directory: /loki/chunks
      rules_directory: /loki/rules
  replication_factor: 1
  ring:
    kvstore:
      store: inmemory

schema_config:
  configs:
    - from: "2024-01-01"
      store: tsdb
      object_store: filesystem
      schema: v13
      index:
        prefix: index_
        period: 24h

limits_config:
  reject_old_samples: true
  reject_old_samples_max_age: 168h

analytics:
  reporting_enabled: false
```

### Criar configuracao do Promtail

```bash
mkdir -p docker/promtail
```

**`docker/promtail/promtail-config.yml`**:
```yaml
server:
  http_listen_port: 9080

positions:
  filename: /tmp/positions.yaml

clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: docker
    docker_sd_configs:
      - host: unix:///var/run/docker.sock
        refresh_interval: 5s
        filters:
          - name: label
            values: ["com.docker.compose.project=laravelnextts"]
    relabel_configs:
      # Extrair nome do container como label
      - source_labels: ['__meta_docker_container_name']
        regex: '/(.*)'
        target_label: 'container'
      # Extrair nome do servico como label
      - source_labels: ['__meta_docker_container_label_com_docker_compose_service']
        target_label: 'service'
```

### Adicionar ao docker-compose.yml

```yaml
  loki:
    image: grafana/loki:3.5.0
    container_name: orderly-loki
    volumes:
      - ./docker/loki/loki-config.yml:/etc/loki/local-config.yaml:ro
      - loki_data:/loki
    command: -config.file=/etc/loki/local-config.yaml
    ports:
      - "3100:3100"
    profiles:
      - monitoring
    networks:
      - orderly-network

  promtail:
    image: grafana/promtail:3.5.0
    container_name: orderly-promtail
    volumes:
      - ./docker/promtail/promtail-config.yml:/etc/promtail/config.yml:ro
      - /var/run/docker.sock:/var/run/docker.sock:ro
    command: -config.file=/etc/promtail/config.yml
    depends_on:
      - loki
    profiles:
      - monitoring
    networks:
      - orderly-network
```

Adicionar ao bloco `volumes`:
```yaml
  loki_data:
```

### Verificar

```bash
# Subir tudo
docker compose --profile monitoring up -d

# Verificar Loki
curl -s http://localhost:3100/ready
# ready

# No Grafana (http://localhost:3001):
# 1. Ir em Explore
# 2. Selecionar datasource "Loki"
# 3. Consultar: {service="backend"}
# 4. Ver os logs do Laravel em tempo real
```

---

## Passo 13.7 - Dashboard do Grafana: Orderly Overview

### Criar o dashboard JSON

```bash
mkdir -p docker/grafana/dashboards
```

**`docker/grafana/dashboards/orderly-overview.json`**:
```json
{
  "dashboard": {
    "title": "Orderly - Overview",
    "tags": ["orderly"],
    "timezone": "browser",
    "panels": [
      {
        "title": "HTTP Requests/sec",
        "type": "timeseries",
        "gridPos": { "h": 8, "w": 12, "x": 0, "y": 0 },
        "targets": [
          {
            "expr": "rate(app_http_requests_total[5m])",
            "legendFormat": "{{method}} {{route}} {{status}}"
          }
        ]
      },
      {
        "title": "Request Latency (p95)",
        "type": "timeseries",
        "gridPos": { "h": 8, "w": 12, "x": 12, "y": 0 },
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(app_http_request_duration_seconds_bucket[5m]))",
            "legendFormat": "p95 {{route}}"
          }
        ]
      },
      {
        "title": "Error Rate (%)",
        "type": "stat",
        "gridPos": { "h": 4, "w": 6, "x": 0, "y": 8 },
        "targets": [
          {
            "expr": "sum(rate(app_http_requests_total{status=~\"5..\"}[5m])) / sum(rate(app_http_requests_total[5m])) * 100",
            "legendFormat": "Error Rate"
          }
        ]
      },
      {
        "title": "Total Requests",
        "type": "stat",
        "gridPos": { "h": 4, "w": 6, "x": 6, "y": 8 },
        "targets": [
          {
            "expr": "sum(app_http_requests_total)",
            "legendFormat": "Total"
          }
        ]
      },
      {
        "title": "PostgreSQL Active Connections",
        "type": "gauge",
        "gridPos": { "h": 4, "w": 6, "x": 12, "y": 8 },
        "targets": [
          {
            "expr": "pg_stat_activity_count{datname=\"orderly\"}",
            "legendFormat": "Active"
          }
        ]
      },
      {
        "title": "Redis Memory Usage",
        "type": "gauge",
        "gridPos": { "h": 4, "w": 6, "x": 18, "y": 8 },
        "targets": [
          {
            "expr": "redis_memory_used_bytes / 1024 / 1024",
            "legendFormat": "MB"
          }
        ]
      },
      {
        "title": "Backend Logs",
        "type": "logs",
        "gridPos": { "h": 8, "w": 24, "x": 0, "y": 12 },
        "targets": [
          {
            "expr": "{service=\"backend\"}",
            "datasource": "Loki"
          }
        ]
      }
    ],
    "schemaVersion": 39,
    "version": 1
  }
}
```

### Atualizar provisionamento

**`docker/grafana/provisioning/dashboards/dashboards.yml`** — atualizar path:
```yaml
apiVersion: 1

providers:
  - name: 'default'
    orgId: 1
    folder: 'Orderly'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 30
    options:
      path: /var/lib/grafana/dashboards
      foldersFromFilesStructure: false
```

Adicionar volume no `docker-compose.yml` do Grafana:
```yaml
  grafana:
    # ... config existente ...
    volumes:
      - grafana_data:/var/lib/grafana
      - ./docker/grafana/provisioning:/etc/grafana/provisioning:ro
      - ./docker/grafana/dashboards:/var/lib/grafana/dashboards:ro  # NOVO
```

### Verificar

```bash
# Reiniciar Grafana
docker compose --profile monitoring restart grafana

# Acessar http://localhost:3001
# Login: admin / orderly123
# Ir em Dashboards > Orderly > Orderly - Overview
```

---

## Passo 13.8 - Alertas com Alertmanager

### Criar regras de alerta do Prometheus

```bash
mkdir -p docker/prometheus/rules
```

**`docker/prometheus/rules/orderly-alerts.yml`**:
```yaml
groups:
  - name: orderly
    rules:
      # Alerta: muitos erros 5xx
      - alert: HighErrorRate
        expr: sum(rate(app_http_requests_total{status=~"5.."}[5m])) / sum(rate(app_http_requests_total[5m])) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Taxa de erros acima de 5%"
          description: "{{ $value | humanizePercentage }} das requests estao retornando erro 5xx."

      # Alerta: latencia alta
      - alert: HighLatency
        expr: histogram_quantile(0.95, rate(app_http_request_duration_seconds_bucket[5m])) > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Latencia p95 acima de 2s"
          description: "A latencia p95 esta em {{ $value }}s."

      # Alerta: banco de dados indisponivel
      - alert: PostgreSQLDown
        expr: pg_up == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "PostgreSQL esta fora do ar"
          description: "O exporter do PostgreSQL nao consegue conectar ao banco."

      # Alerta: Redis indisponivel
      - alert: RedisDown
        expr: redis_up == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Redis esta fora do ar"
          description: "O exporter do Redis nao consegue conectar."

      # Alerta: Redis usando muita memoria
      - alert: RedisHighMemory
        expr: redis_memory_used_bytes / redis_memory_max_bytes > 0.8
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Redis usando mais de 80% da memoria"
          description: "Redis esta usando {{ $value | humanizePercentage }} da memoria maxima."
```

### Atualizar configuracao do Prometheus

**`docker/prometheus/prometheus.yml`** — adicionar `rule_files`:
```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - /etc/prometheus/rules/*.yml

scrape_configs:
  # ... scrape_configs existentes ...
```

Atualizar volume do Prometheus no `docker-compose.yml`:
```yaml
  prometheus:
    # ... config existente ...
    volumes:
      - ./docker/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - ./docker/prometheus/rules:/etc/prometheus/rules:ro  # NOVO
      - prometheus_data:/prometheus
```

### Verificar alertas

```bash
# Recarregar config do Prometheus (sem reiniciar)
curl -X POST http://localhost:9090/-/reload

# Ver alertas configurados
# Abrir: http://localhost:9090/alerts
```

---

## Passo 13.9 - Makefile: comandos de observabilidade

**Adicionar ao `Makefile`**:
```makefile
# ── Observabilidade ─────────────────────────────────────────────
monitoring-up: ## Subir stack de monitoramento (Prometheus + Grafana + Loki)
	docker compose --profile monitoring up -d
	@echo "$(GREEN)>>> Monitoring stack UP$(NC)"
	@echo "  Prometheus: http://localhost:9090"
	@echo "  Grafana:    http://localhost:3001 (admin/orderly123)"
	@echo "  Loki:       http://localhost:3100"

monitoring-down: ## Parar stack de monitoramento
	docker compose --profile monitoring down
	@echo "$(YELLOW)>>> Monitoring stack DOWN$(NC)"

monitoring-logs: ## Ver logs da stack de monitoramento
	docker compose --profile monitoring logs -f prometheus grafana loki promtail

monitoring-status: ## Status dos servicos de monitoramento
	@docker compose --profile monitoring ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
```

### Testar

```bash
make monitoring-up
# Prometheus: http://localhost:9090
# Grafana:    http://localhost:3001
# Loki:       http://localhost:3100
```

---

## Passo 13.10 - Resumo e proximos passos

### O que implementamos

| Componente | Funcao | Acesso |
|-----------|--------|--------|
| **Prometheus** | Coleta metricas (scrape) | http://localhost:9090 |
| **Grafana** | Dashboards e alertas | http://localhost:3001 |
| **Loki** | Armazenamento de logs | http://localhost:3100 |
| **Promtail** | Coleta logs dos containers | (agente interno) |
| **Redis Exporter** | Metricas do Redis | :9121 |
| **Postgres Exporter** | Metricas do PostgreSQL | :9187 |
| **Nginx Exporter** | Metricas do Nginx | :9113 |
| **Laravel /metrics** | Metricas da aplicacao | /api/v1/metrics |
| **Laravel /health** | Health checks (live + ready) | /api/v1/health/live, /api/v1/health/ready |
| **Request ID** | Correlacao de logs | Header X-Request-ID |
| **Alertas** | Regras de alerta Prometheus | Prometheus Alerts UI |

### Arquivos criados/modificados nesta fase

```
docker/prometheus/prometheus.yml           # Configuracao do Prometheus
docker/prometheus/rules/orderly-alerts.yml # Regras de alerta
docker/grafana/provisioning/datasources/   # Datasources (Prometheus + Loki)
docker/grafana/provisioning/dashboards/    # Provisioning de dashboards
docker/grafana/dashboards/                 # Dashboard JSON
docker/loki/loki-config.yml               # Configuracao do Loki
docker/promtail/promtail-config.yml        # Configuracao do Promtail
docker/nginx/default.conf                  # Adicionado stub_status

backend/app/Providers/PrometheusServiceProvider.php
backend/app/Http/Middleware/PrometheusMiddleware.php
backend/app/Http/Middleware/RequestIdMiddleware.php
backend/app/Http/Controllers/Api/V1/MetricsController.php
backend/app/Http/Controllers/Api/V1/HealthController.php
backend/config/logging.php                 # Canal JSON adicionado
backend/routes/api.php                     # Rotas /metrics, /health/*
backend/bootstrap/app.php                  # Middlewares registrados

docker-compose.yml    # Servicos: prometheus, grafana, loki, promtail, exporters
Makefile              # Targets: monitoring-up, monitoring-down, monitoring-logs
```

### Proximos passos sugeridos

- **Fase 14:** Mensageria com Kafka (eventos async, consumers, producers)
- Adicionar traces distribuidos com Jaeger ou Grafana Tempo
- Dashboard de SLOs (Service Level Objectives)
- Integrar alertas com Slack/Discord via Alertmanager
