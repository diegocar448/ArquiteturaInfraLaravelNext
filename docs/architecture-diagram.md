# Arquitetura Orderly - Visao Completa

## 1. Arquitetura Geral (Visao Macro)

```mermaid
graph TB
    subgraph CLIENTS["🌐 Clientes"]
        BROWSER["Browser<br/>(Admin/Cliente)"]
        MOBILE["Mobile<br/>(futuro)"]
    end

    subgraph CICD["⚙️ CI/CD - GitHub Actions"]
        CI["CI Pipeline<br/>Lint + Tests"]
        E2E["E2E Pipeline<br/>Playwright"]
        CD["CD Pipeline<br/>Build + Deploy"]
    end

    subgraph CLOUD["☁️ AWS (Producao)"]
        ALB["AWS ALB<br/>Load Balancer"]

        subgraph EKS["EKS Cluster (Kubernetes)"]
            INGRESS["Ingress Controller"]

            subgraph FE_PODS["Frontend Pods (2-6)"]
                NEXTJS["Next.js 16<br/>SSR + CSR"]
            end

            subgraph BE_PODS["Backend Pods (2-15)"]
                LARAVEL["Laravel 13<br/>API REST"]
            end

            subgraph WORKER_PODS["Worker Pods (1-2)"]
                WORKER["Queue Worker<br/>php artisan queue:work"]
            end

            SCHEDULER["CronJob<br/>Scheduler"]
            HPA["HPA<br/>Auto-scaling"]
        end

        subgraph DATA["Dados Persistentes"]
            RDS["RDS PostgreSQL 16<br/>Multi-AZ"]
            ELASTICACHE["ElastiCache Redis 7<br/>2 nodes"]
        end

        ECR["ECR<br/>Container Registry"]
    end

    subgraph MESSAGING["📨 Mensageria"]
        KAFKA["Apache Kafka 4.2<br/>KRaft (sem ZooKeeper)"]
    end

    BROWSER --> ALB
    MOBILE --> ALB
    ALB --> INGRESS
    INGRESS -->|"/*"| NEXTJS
    INGRESS -->|"/api/*"| LARAVEL
    NEXTJS -->|"SSR calls"| LARAVEL
    LARAVEL --> RDS
    LARAVEL --> ELASTICACHE
    LARAVEL -->|"publish events"| KAFKA
    KAFKA -->|"consume events"| WORKER
    WORKER --> RDS
    SCHEDULER --> LARAVEL
    HPA --> BE_PODS
    HPA --> FE_PODS
    CD -->|"push images"| ECR
    CD -->|"kubectl apply"| EKS
    CI -->|"triggers"| CD
```

## 2. Ambiente Local (Docker Compose)

```mermaid
graph LR
    subgraph HOST["🖥️ Host (WSL2)"]
        BROWSER["Browser<br/>:80"]
    end

    subgraph DOCKER["🐳 Docker Compose"]
        subgraph PROXY["Nginx :80"]
            NGINX["nginx:1.27-alpine<br/>Reverse Proxy"]
        end

        subgraph APP["Aplicacao"]
            FRONTEND["Next.js 16<br/>Turbopack :3000"]
            BACKEND["Laravel 13<br/>PHP-FPM 8.4 :9000"]
        end

        subgraph INFRA["Infraestrutura"]
            POSTGRES["PostgreSQL 16<br/>:5432"]
            REDIS["Redis 7<br/>:6379"]
            KAFKA["Kafka 4.2<br/>KRaft :9092"]
        end

        subgraph MONITORING["Observabilidade (--profile monitoring)"]
            PROMETHEUS["Prometheus<br/>:9090"]
            GRAFANA["Grafana<br/>:3001"]
            LOKI["Loki<br/>:3100"]
            PROMTAIL["Promtail"]
            PG_EXP["Postgres<br/>Exporter"]
            REDIS_EXP["Redis<br/>Exporter"]
            NGINX_EXP["Nginx<br/>Exporter"]
            KAFKA_UI["Kafka UI<br/>:8080"]
        end
    end

    BROWSER --> NGINX
    NGINX -->|"/*"| FRONTEND
    NGINX -->|"/api/* /docs/*"| BACKEND
    BACKEND --> POSTGRES
    BACKEND --> REDIS
    BACKEND --> KAFKA
    FRONTEND -->|"SSR via nginx"| BACKEND

    PG_EXP --> POSTGRES
    REDIS_EXP --> REDIS
    NGINX_EXP --> NGINX
    PROMTAIL --> LOKI
    PROMETHEUS --> PG_EXP
    PROMETHEUS --> REDIS_EXP
    PROMETHEUS --> NGINX_EXP
    PROMETHEUS --> BACKEND
    GRAFANA --> PROMETHEUS
    GRAFANA --> LOKI
    KAFKA_UI --> KAFKA
```

## 3. Fluxo de Request (Nginx Routing)

```mermaid
flowchart LR
    CLIENT["Cliente HTTP<br/>:80"] --> NGINX{"Nginx<br/>Reverse Proxy"}

    NGINX -->|"/api/*"| FASTCGI["FastCGI :9000"]
    NGINX -->|"/docs/*"| FASTCGI
    NGINX -->|"/storage/*"| STATIC["Arquivos Estaticos<br/>Laravel Storage"]
    NGINX -->|"/_next/static/*"| CACHE["Assets Cacheados<br/>Cache 1 ano"]
    NGINX -->|"/health"| HEALTH["200 OK JSON"]
    NGINX -->|"/stub_status"| METRICS["Nginx Metrics<br/>(Prometheus)"]
    NGINX -->|"/* (default)"| PROXY["Proxy HTTP/WS<br/>:3000"]

    FASTCGI --> LARAVEL["Laravel 13<br/>PHP-FPM 8.4"]
    PROXY --> NEXTJS["Next.js 16<br/>Turbopack"]
    CACHE --> NEXTJS
```

## 4. Arquitetura Backend (Clean Architecture)

```mermaid
graph TB
    subgraph HTTP["Camada HTTP"]
        ROUTES["routes/api.php<br/>/v1/auth, /v1/plans, ..."]
        CONTROLLERS["Controllers<br/>AuthController, PlanController, ..."]
        REQUESTS["Form Requests<br/>Validacao de entrada"]
        RESOURCES["API Resources<br/>Serializacao JSON"]
        MIDDLEWARE["Middleware<br/>JWT Auth, CORS, Tenant"]
    end

    subgraph DOMAIN["Camada de Dominio"]
        ACTIONS["Actions (Use Cases)<br/>LoginAction, CreateOrderAction, ..."]
        DTOS["DTOs<br/>LoginDTO, CreatePlanDTO, ..."]
        EVENTS["Kafka Events<br/>OrderCreatedEvent, ..."]
    end

    subgraph DATA["Camada de Dados"]
        REPOS_INT["Repository Interfaces<br/>UserRepositoryInterface, ..."]
        REPOS_IMPL["Eloquent Repositories<br/>UserRepository, ..."]
        MODELS["Models + Scopes<br/>User, Plan, Order, ..."]
        OBSERVERS["Observers<br/>Lifecycle hooks"]
    end

    subgraph EXTERNAL["Servicos Externos"]
        POSTGRES[("PostgreSQL 16")]
        REDIS[("Redis 7")]
        KAFKA["Kafka Producer"]
    end

    ROUTES --> MIDDLEWARE --> CONTROLLERS
    CONTROLLERS --> REQUESTS
    CONTROLLERS --> ACTIONS
    CONTROLLERS --> RESOURCES
    ACTIONS --> DTOS
    ACTIONS --> REPOS_INT
    ACTIONS --> EVENTS --> KAFKA
    REPOS_INT -.->|"implementa"| REPOS_IMPL
    REPOS_IMPL --> MODELS
    MODELS --> POSTGRES
    MODELS --> OBSERVERS
    ACTIONS --> REDIS
```

## 5. Arquitetura Frontend (Next.js 16)

```mermaid
graph TB
    subgraph NEXTJS["Next.js 16 (App Router)"]
        subgraph PAGES["Pages (src/app/)"]
            LOGIN["/login"]
            DASHBOARD["/(admin)/dashboard"]
            PRODUCTS["/(admin)/products"]
            ORDERS["/(admin)/orders"]
            TABLES["/(admin)/tables"]
            CLIENT_LOGIN["/client/login"]
            CLIENT_ORDERS["/client/pedidos"]
        end

        PROXY["proxy.ts<br/>Auth Guard"]
        LAYOUT["layout.tsx<br/>Root Layout"]
    end

    subgraph COMPONENTS["Componentes"]
        UI["shadcn/ui<br/>Button, Input, Card, ..."]
        SIDEBAR["App Sidebar<br/>Navegacao admin"]
        FORMS["Form Dialogs<br/>CRUD components"]
    end

    subgraph STATE["Estado"]
        ZUSTAND["Zustand Stores<br/>auth-store"]
        HOOKS["Custom Hooks<br/>usePagination, ..."]
    end

    subgraph SERVICES["Servicos"]
        API_CLIENT["API Client<br/>fetch wrapper + JWT"]
        SVC["Service Layer<br/>plan-service, order-service, ..."]
    end

    PROXY -->|"protege rotas"| PAGES
    PAGES --> COMPONENTS
    PAGES --> STATE
    PAGES --> SERVICES
    FORMS --> UI
    SVC --> API_CLIENT
    API_CLIENT -->|"CSR: /api"| NGINX["Nginx :80"]
    API_CLIENT -->|"SSR: nginx:80/api"| NGINX
    ZUSTAND -->|"JWT token"| API_CLIENT
```

## 6. Pipeline CI/CD

```mermaid
flowchart LR
    subgraph TRIGGER["Trigger"]
        PR["Pull Request"]
        PUSH["Push to main"]
    end

    subgraph CI["CI Pipeline"]
        LINT_BE["Lint Backend<br/>Pint (PSR-12)"]
        LINT_FE["Lint Frontend<br/>ESLint + TypeScript"]
        TEST_BE["Test Backend<br/>Pest (coverage 30%)"]
        TEST_FE["Test Frontend<br/>Vitest"]
    end

    subgraph E2E_PIPE["E2E Pipeline"]
        E2E["Playwright<br/>Chromium"]
    end

    subgraph CD["CD Pipeline"]
        BUILD["Docker Build<br/>(production target)"]
        PUSH_IMG["Push to GHCR<br/>:latest :sha :date"]
        DEPLOY["kubectl apply<br/>Kustomize prod"]
        VERIFY["Rollout Status<br/>+ Verification"]
    end

    PR --> CI
    PR --> E2E_PIPE
    PUSH --> CI --> CD

    LINT_BE --> TEST_BE
    LINT_FE --> TEST_FE
    BUILD --> PUSH_IMG --> DEPLOY --> VERIFY
```

## 7. Infraestrutura AWS (Terraform)

```mermaid
graph TB
    subgraph AWS["☁️ AWS (us-east-1)"]
        subgraph VPC["VPC 10.1.0.0/16"]
            subgraph PUBLIC["Subnets Publicas (3 AZs)"]
                ALB["ALB<br/>Internet-facing"]
                NAT["NAT Gateway"]
            end

            subgraph PRIVATE["Subnets Privadas (3 AZs)"]
                subgraph EKS["EKS Cluster v1.31"]
                    NODES["Node Group<br/>2-6 x t3.large"]
                end
                RDS["RDS PostgreSQL 16<br/>db.t3.medium<br/>Multi-AZ, 50GB"]
                ELASTICACHE["ElastiCache Redis 7<br/>cache.t3.small<br/>2 nodes"]
            end
        end

        IGW["Internet Gateway"]
        ECR["ECR Registry<br/>orderly/backend<br/>orderly/frontend"]
    end

    INTERNET["Internet"] --> IGW --> ALB
    ALB --> NODES
    NODES --> RDS
    NODES --> ELASTICACHE
    PRIVATE --> NAT --> IGW
    ECR -.->|"pull images"| NODES
```

## 8. Mensageria Kafka

```mermaid
flowchart LR
    subgraph PRODUCER["Producers (Laravel)"]
        CREATE["CreateOrderAction"]
        UPDATE["UpdateOrderStatusAction"]
    end

    subgraph KAFKA["Kafka 4.2 (KRaft)"]
        TOPIC_ORDERS["Topic:<br/>order-events"]
        TOPIC_DLQ["Topic:<br/>order-events-dlq"]
    end

    subgraph CONSUMER["Consumers (Worker)"]
        HANDLER["OrderEventsHandler"]
        RETRY["RetryableHandler<br/>3 retries + backoff"]
    end

    CREATE -->|"OrderCreatedEvent"| TOPIC_ORDERS
    UPDATE -->|"OrderStatusChangedEvent"| TOPIC_ORDERS
    TOPIC_ORDERS --> RETRY
    RETRY -->|"sucesso"| HANDLER
    RETRY -->|"falha apos 3 tentativas"| TOPIC_DLQ
```

## Stack Completa

| Camada | Tecnologia | Versao |
|--------|-----------|--------|
| Frontend | Next.js + TypeScript | 16.x |
| UI | shadcn/ui + Tailwind CSS | latest |
| Backend | Laravel (API-only) | 13.x |
| Banco | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Mensageria | Apache Kafka (KRaft) | 4.2 |
| Auth | JWT (tymon/jwt-auth) | latest |
| Containers | Docker + Docker Compose | latest |
| Orquestracao | Kubernetes + Kustomize | latest |
| IaC | Terraform | latest |
| CI/CD | GitHub Actions | - |
| Testes BE | Pest (PHPUnit) | 4.x |
| Testes FE | Vitest + Testing Library | latest |
| Testes E2E | Playwright | latest |
| Observabilidade | Prometheus + Grafana + Loki | latest |
| API Docs | Scramble (OpenAPI/Swagger) | 0.13.x |
