# Orderly - Tutorial de Construcao Passo a Passo

> Construindo uma plataforma SaaS multi-tenant de delivery com **Laravel 12** + **Next.js 15** + **Docker** do zero, como um arquiteto senior faria.

**Orderly** — porque todo grande restaurante precisa de ordem. Nos pedidos, no cardapio, na operacao.

Este README e um tutorial progressivo. Cada fase documenta exatamente o que foi feito, por que foi feito, e como reproduzir. Apague tudo e reconstrua seguindo cada passo.

---

## Indice

- [Fase 1 - Infraestrutura Docker](#fase-1---infraestrutura-docker)
  - [Passo 1.1 - Inicializar o projeto](#passo-11---inicializar-o-projeto)
  - [Passo 1.2 - Estrutura de diretorios](#passo-12---estrutura-de-diretorios)
  - [Passo 1.3 - Gitignore](#passo-13---gitignore)
  - [Passo 1.4 - Dockerignore](#passo-14---dockerignore)
  - [Passo 1.5 - Variaveis de ambiente](#passo-15---variaveis-de-ambiente)
  - [Passo 1.6 - Dockerfile do Backend (PHP)](#passo-16---dockerfile-do-backend-php)
  - [Passo 1.7 - Configuracao do PHP](#passo-17---configuracao-do-php)
  - [Passo 1.8 - Supervisor (gerenciador de processos)](#passo-18---supervisor-gerenciador-de-processos)
  - [Passo 1.9 - Dockerfile do Frontend (Node)](#passo-19---dockerfile-do-frontend-node)
  - [Passo 1.10 - Configuracao do Nginx](#passo-110---configuracao-do-nginx)
  - [Passo 1.11 - Scaffold do Backend (Laravel)](#passo-111---scaffold-do-backend-laravel)
  - [Passo 1.12 - Scaffold do Frontend (Next.js)](#passo-112---scaffold-do-frontend-nextjs)
  - [Passo 1.13 - Docker Compose (desenvolvimento)](#passo-113---docker-compose-desenvolvimento)
  - [Passo 1.14 - Docker Compose (producao)](#passo-114---docker-compose-producao)
  - [Passo 1.15 - Makefile (automacao)](#passo-115---makefile-automacao)
  - [Passo 1.16 - Subindo o ambiente](#passo-116---subindo-o-ambiente)
- [Fase 2 - Bootstrap Laravel 12 + Next.js 15 com shadcn/ui](#fase-2---bootstrap-laravel-12--nextjs-15-com-shadcnui)
  - [Passo 2.1 - Instalar Laravel skeleton](#passo-21---instalar-laravel-skeleton)
  - [Passo 2.2 - Testar conexao com PostgreSQL e Redis](#passo-22---testar-conexao-com-postgresql-e-redis)
  - [Passo 2.3 - Configurar CORS e rotas API](#passo-23---configurar-cors-e-rotas-api)
  - [Passo 2.4 - Configurar JWT Auth (tymon/jwt-auth)](#passo-24---configurar-jwt-auth-tymonjwt-auth)
  - [Passo 2.5 - Clean Architecture - Padroes base](#passo-25---clean-architecture---padroes-base)
  - [Passo 2.6 - Controller de autenticacao + rotas](#passo-26---controller-de-autenticacao--rotas)
  - [Passo 2.7 - Seeder de admin e teste da API](#passo-27---seeder-de-admin-e-teste-da-api)
  - [Passo 2.8 - Configurar Tailwind CSS v4](#passo-28---configurar-tailwind-css-v4)
  - [Passo 2.9 - Inicializar shadcn/ui](#passo-29---inicializar-shadcnui)
  - [Passo 2.10 - Instalar dependencias do frontend](#passo-210---instalar-dependencias-do-frontend)
  - [Passo 2.11 - API Client](#passo-211---api-client)
  - [Passo 2.12 - Auth Store (Zustand)](#passo-212---auth-store-zustand)
  - [Passo 2.13 - Pagina de Login](#passo-213---pagina-de-login)
  - [Passo 2.14 - Layout admin com sidebar](#passo-214---layout-admin-com-sidebar)
  - [Passo 2.15 - Middleware de autenticacao (Next.js)](#passo-215---middleware-de-autenticacao-nextjs)
  - [Passo 2.16 - Verificacao end-to-end](#passo-216---verificacao-end-to-end)
- [Fase 3 - Multi-tenancy + Planos de Assinatura](#fase-3---multi-tenancy--planos-de-assinatura)
  - [Passo 3.1 - Migration: tabela plans](#passo-31---migration-tabela-plans)
  - [Passo 3.2 - Model Plan + Observer + Factory](#passo-32---model-plan--observer--factory)
  - [Passo 3.3 - Plan Repository + DTO + Actions (CRUD)](#passo-33---plan-repository--dto--actions-crud)
  - [Passo 3.4 - Plan Controller + Routes + FormRequests + Resource](#passo-34---plan-controller--routes--formrequests--resource)
  - [Passo 3.5 - Plan Seeder + teste da API](#passo-35---plan-seeder--teste-da-api)
  - [Passo 3.6 - DetailPlan: migration + Model + CRUD completo](#passo-36---detailplan-migration--model--crud-completo)
  - [Passo 3.7 - Migration: tabela tenants + Model Tenant](#passo-37---migration-tabela-tenants--model-tenant)
  - [Passo 3.8 - Tenant Repository + CRUD (Backend API)](#passo-38---tenant-repository--crud-backend-api)
  - [Passo 3.9 - User-Tenant relationship + Migration](#passo-39---user-tenant-relationship--migration)
  - [Passo 3.10 - Seeders: Plans + Tenant + Usuario tenant](#passo-310---seeders-plans--tenant--usuario-tenant)
  - [Passo 3.11 - Multi-tenancy: Global Scope + Trait](#passo-311---multi-tenancy-global-scope--trait)
  - [Passo 3.12 - Middleware IdentifyTenant](#passo-312---middleware-identifytenant)
  - [Passo 3.13 - Frontend: pagina de listagem de Planos](#passo-313---frontend-pagina-de-listagem-de-planos)
  - [Passo 3.14 - Frontend: formularios de criar/editar Plano](#passo-314---frontend-formularios-de-criareditarplano)
  - [Passo 3.15 - Frontend: gerenciamento de detalhes do Plano](#passo-315---frontend-gerenciamento-de-detalhes-do-plano)
  - [Passo 3.16 - Verificacao end-to-end da Fase 3](#passo-316---verificacao-end-to-end-da-fase-3)
- [Fase 4 - ACL: Permissoes, Perfis e Papeis](#fase-4---acl-permissoes-perfis-e-papeis)
  - [Passo 4.1 - Conceito: ACL de dupla camada](#passo-41---conceito-acl-de-dupla-camada)
  - [Passo 4.2 - Migration: tabela permissions + Model](#passo-42---migration-tabela-permissions--model)
  - [Passo 4.3 - Migration: tabela profiles + pivots + Model](#passo-43---migration-tabela-profiles--pivots--model)
  - [Passo 4.4 - Migration: tabela roles + pivots + Model](#passo-44---migration-tabela-roles--pivots--model)
  - [Passo 4.5 - Atualizar Models existentes (Plan, User)](#passo-45---atualizar-models-existentes-plan-user)
  - [Passo 4.6 - Permission Seeder](#passo-46---permission-seeder)
  - [Passo 4.7 - Profile Repository + CRUD completo](#passo-47---profile-repository--crud-completo)
  - [Passo 4.8 - Role Repository + CRUD completo](#passo-48---role-repository--crud-completo)
  - [Passo 4.9 - Endpoints de Sync (vincular permissoes e perfis)](#passo-49---endpoints-de-sync-vincular-permissoes-e-perfis)
  - [Passo 4.10 - Profile Seeder + vinculos com permissoes e planos](#passo-410---profile-seeder--vinculos-com-permissoes-e-planos)
  - [Passo 4.11 - Role Seeder + vinculos com permissoes e usuarios](#passo-411---role-seeder--vinculos-com-permissoes-e-usuarios)
  - [Passo 4.12 - Trait HasPermission no User](#passo-412---trait-haspermission-no-user)
  - [Passo 4.13 - Middleware CheckPermission + proteger rotas](#passo-413---middleware-checkpermission--proteger-rotas)
  - [Passo 4.14 - Frontend: pagina de Perfis (Profiles)](#passo-414---frontend-pagina-de-perfis-profiles)
  - [Passo 4.15 - Frontend: pagina de Papeis (Roles)](#passo-415---frontend-pagina-de-papeis-roles)
  - [Passo 4.16 - Verificacao end-to-end da Fase 4](#passo-416---verificacao-end-to-end-da-fase-4)
  - [Passo 4.17 - Documentacao API interativa (OpenAPI + Scramble)](#passo-417---documentacao-api-interativa-openapi--scramble)
- [Fase 5 - Catalogo: Categorias + Produtos](#fase-5---catalogo-categorias--produtos)
  - [Passo 5.1 - Conceito: Catalogo multi-tenant](#passo-51---conceito-catalogo-multi-tenant)
  - [Passo 5.2 - Migration: tabela categories + Model + Observer](#passo-52---migration-tabela-categories--model--observer)
  - [Passo 5.3 - Category Repository + CRUD completo](#passo-53---category-repository--crud-completo)
  - [Passo 5.4 - Category Controller + Routes + FormRequests + Resource](#passo-54---category-controller--routes--formrequests--resource)
  - [Passo 5.5 - Category Seeder + teste da API](#passo-55---category-seeder--teste-da-api)
  - [Passo 5.6 - Migration: tabela products + Model + Observer](#passo-56---migration-tabela-products--model--observer)
  - [Passo 5.7 - Product Repository + CRUD completo](#passo-57---product-repository--crud-completo)
  - [Passo 5.8 - Product Controller + Routes + FormRequests + Resource](#passo-58---product-controller--routes--formrequests--resource)
  - [Passo 5.9 - Pivot category_product + relacionamentos](#passo-59---pivot-category_product--relacionamentos)
  - [Passo 5.10 - Product Seeder + teste da API](#passo-510---product-seeder--teste-da-api)
  - [Passo 5.11 - Frontend: tipos TypeScript + servicos do Catalogo](#passo-511---frontend-tipos-typescript--servicos-do-catalogo)
  - [Passo 5.12 - Frontend: pagina de Categorias (CRUD)](#passo-512---frontend-pagina-de-categorias-crud)
  - [Passo 5.13 - Frontend: pagina de Produtos (CRUD)](#passo-513---frontend-pagina-de-produtos-crud)
  - [Passo 5.14 - Verificacao end-to-end da Fase 5](#passo-514---verificacao-end-to-end-da-fase-5)
- [Fase 6 - Mesas com QR Code](#fase-6---mesas-com-qr-code)
  - [Passo 6.1 - Conceito: Mesas e QR Codes](#passo-61---conceito-mesas-e-qr-codes)
  - [Passo 6.2 - Migration: tabela tables + Model + Observer](#passo-62---migration-tabela-tables--model--observer)
  - [Passo 6.3 - Table Repository + CRUD completo](#passo-63---table-repository--crud-completo)
  - [Passo 6.4 - Table Controller + Routes + FormRequests + Resource](#passo-64---table-controller--routes--formrequests--resource)
  - [Passo 6.5 - Table Seeder + teste da API](#passo-65---table-seeder--teste-da-api)
  - [Passo 6.6 - QR Code: geracao e endpoint](#passo-66---qr-code-geracao-e-endpoint)
  - [Passo 6.7 - Frontend: tipos TypeScript + servico de Mesas](#passo-67---frontend-tipos-typescript--servico-de-mesas)
  - [Passo 6.8 - Frontend: pagina de Mesas (CRUD + QR Code)](#passo-68---frontend-pagina-de-mesas-crud--qr-code)
  - [Passo 6.9 - Verificacao end-to-end da Fase 6](#passo-69---verificacao-end-to-end-da-fase-6)
- [Fase 7 - Sistema de Pedidos](#fase-7---sistema-de-pedidos)
  - [Passo 7.1 - Conceito: Pedidos e fluxo de status](#passo-71---conceito-pedidos-e-fluxo-de-status)
  - [Passo 7.2 - Migration: tabela orders + order_product](#passo-72---migration-tabela-orders--order_product)
  - [Passo 7.3 - Order Model + Observer + relacionamentos](#passo-73---order-model--observer--relacionamentos)
  - [Passo 7.4 - Order Repository + CRUD completo](#passo-74---order-repository--crud-completo)
  - [Passo 7.5 - Order Controller + Routes + FormRequests + Resource](#passo-75---order-controller--routes--formrequests--resource)
  - [Passo 7.6 - Order Seeder + teste da API](#passo-76---order-seeder--teste-da-api)
  - [Passo 7.7 - Frontend: tipos TypeScript + servico de Pedidos](#passo-77---frontend-tipos-typescript--servico-de-pedidos)
  - [Passo 7.8 - Frontend: pagina de Pedidos (listagem + status)](#passo-78---frontend-pagina-de-pedidos-listagem--status)
  - [Passo 7.9 - Frontend: dialog de criacao de pedido](#passo-79---frontend-dialog-de-criacao-de-pedido)
  - [Passo 7.10 - Verificacao end-to-end da Fase 7](#passo-710---verificacao-end-to-end-da-fase-7)
- [Fase 8 - Autenticacao de Clientes + Avaliacoes](#fase-8---autenticacao-de-clientes--avaliacoes)
  - [Passo 8.1 - Conceito: Clientes vs Usuarios e Avaliacoes](#passo-81---conceito-clientes-vs-usuarios-e-avaliacoes)
  - [Passo 8.2 - Migration: tabela clients + guard JWT](#passo-82---migration-tabela-clients--guard-jwt)
  - [Passo 8.3 - Client Model + Observer + JWTSubject](#passo-83---client-model--observer--jwtsubject)
  - [Passo 8.4 - Client Auth Controller + Routes](#passo-84---client-auth-controller--routes)
  - [Passo 8.5 - Migration: FK orders.client_id + tabela order_evaluations](#passo-85---migration-fk-ordersclient_id--tabela-order_evaluations)
  - [Passo 8.6 - Evaluation Model + Repository + Actions](#passo-86---evaluation-model--repository--actions)
  - [Passo 8.7 - Evaluation Controller + Routes + FormRequests + Resource](#passo-87---evaluation-controller--routes--formrequests--resource)
  - [Passo 8.8 - Seeders + teste da API](#passo-88---seeders--teste-da-api)
  - [Passo 8.9 - Frontend: tipos TypeScript + servicos](#passo-89---frontend-tipos-typescript--servicos)
  - [Passo 8.10 - Frontend: pagina de Avaliacoes (admin)](#passo-810---frontend-pagina-de-avaliacoes-admin)
  - [Passo 8.11 - Frontend: Client Auth Store + paginas de login/cadastro/pedidos](#passo-811---frontend-client-auth-store--paginas-de-logincadastropedidos)
  - [Passo 8.12 - Verificacao end-to-end da Fase 8](#passo-812---verificacao-end-to-end-da-fase-8)
- **[Fase 9 - Dashboard com Metricas](#fase-9---dashboard-com-metricas)**
  - [Passo 9.1 - Conceito: Dashboard de Metricas](#passo-91---conceito-dashboard-de-metricas)
  - [Passo 9.2 - Backend: Action de Metricas](#passo-92---backend-action-de-metricas)
  - [Passo 9.3 - Backend: Dashboard Controller + Rota](#passo-93---backend-dashboard-controller--rota)
  - [Passo 9.4 - Teste da API de Metricas](#passo-94---teste-da-api-de-metricas)
  - [Passo 9.5 - Frontend: tipos TypeScript + servico](#passo-95---frontend-tipos-typescript--servico)
  - [Passo 9.6 - Frontend: instalar Recharts](#passo-96---frontend-instalar-recharts)
  - [Passo 9.7 - Frontend: pagina do Dashboard](#passo-97---frontend-pagina-do-dashboard)
  - [Passo 9.8 - Verificacao end-to-end da Fase 9](#passo-98---verificacao-end-to-end-da-fase-9)

---

## Sobre o Projeto

Reescrita do [larafood_reescrito](https://github.com/diegocar448/larafood_reescrito) (Laravel 7 + Blade) com arquitetura moderna.

### Stack

| Camada | Tecnologia | Versao |
|---|---|---|
| Backend | Laravel (API-only) | 12.x |
| Frontend | Next.js + TypeScript | 15.x |
| UI | shadcn/ui + Tailwind CSS | latest |
| Banco | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Mensageria | Apache Kafka (KRaft) | 4.0 |
| Auth | JWT (tymon/jwt-auth) | latest |
| Containers | Docker + Docker Compose | latest |
| Orquestracao | Kubernetes + Kustomize | latest |
| IaC | Terraform | latest |
| CI/CD | GitHub Actions | - |
| Testes BE | Pest (PHPUnit) | latest |
| Testes FE | Vitest + Testing Library | latest |
| Testes E2E | Playwright | latest |

### Arquitetura: Clean Architecture Pragmatica

```
┌─────────────────────────────────────────────────┐
│              Frameworks & Drivers                │
│   Laravel, Next.js, PostgreSQL, Redis, Kafka    │
├─────────────────────────────────────────────────┤
│              Interface Adapters                  │
│   Controllers, Requests, Resources, Repos Impl  │
├─────────────────────────────────────────────────┤
│               Use Cases (Actions)                │
│   CreateOrder, RegisterTenant, AttachRole...    │
├─────────────────────────────────────────────────┤
│                  Entities                        │
│   Tenant, Plan, Product, Order, User            │
└─────────────────────────────────────────────────┘
         Dependencias apontam PARA DENTRO
```

### Checklist de Funcionalidades

- [x] Infraestrutura Docker (dev + prod)
- [ ] Kubernetes manifests + Kustomize
- [ ] Terraform modules
- [ ] CI/CD com GitHub Actions
- [x] Autenticacao JWT (Admin + Client)
- [x] Multi-tenancy (single-db, tenant_id, Global Scopes)
- [x] Planos de assinatura (CRUD + detalhes)
- [x] ACL dupla camada (Plan->Profile->Permission + User->Role->Permission)
- [x] Catalogo: Categories + Products (CRUD, tenant-scoped)
- [x] Mesas com QR Code
- [x] Sistema de Pedidos
- [x] Autenticacao de Clientes (JWT)
- [x] Avaliacoes de Pedidos
- [x] Dashboard com metricas
- [ ] Landing page publica (SSR)
- [ ] Testes completos (Unit, Integration, E2E)
- [x] Documentacao API (OpenAPI/Swagger via Scramble)

---

# Fase 1 - Infraestrutura Docker

**Objetivo:** Montar um ambiente de desenvolvimento profissional com Docker, preparado para cloud-native.

**O que voce vai aprender:**
- Multi-stage Docker builds (dev vs prod na mesma imagem)
- Docker Compose com multiplos servicos
- Nginx como reverse proxy
- Health checks em containers
- PHP-FPM tunado para producao
- Supervisor para gerenciar multiplos processos
- Boas praticas de seguranca em containers

**Pre-requisitos:**
- Docker e Docker Compose instalados
- Git instalado
- Um terminal (bash/zsh)

---

## Passo 1.1 - Inicializar o projeto

Crie a pasta do projeto e inicialize o Git:

```bash
mkdir laravelnextts
cd laravelnextts
git init
```

**Por que Git logo no inicio?**
Porque queremos rastrear CADA mudanca desde o primeiro arquivo. Em projetos profissionais, o historico do Git conta a historia da evolucao do projeto.

---

## Passo 1.2 - Estrutura de diretorios

Crie toda a estrutura de pastas do projeto:

```bash
# Docker configs
mkdir -p docker/nginx docker/php docker/node

# Kubernetes (futuro - Fase 2)
mkdir -p k8s/base k8s/overlays/dev k8s/overlays/staging k8s/overlays/prod

# Terraform (futuro - Fase 3)
mkdir -p terraform/modules/networking terraform/modules/kubernetes \
         terraform/modules/database terraform/modules/cache \
         terraform/modules/registry
mkdir -p terraform/environments/dev terraform/environments/staging \
         terraform/environments/prod

# CI/CD (futuro - Fase 4)
mkdir -p .github/workflows

# Documentacao
mkdir -p docs/api docs/architecture

# Aplicacoes (serao populadas nos passos seguintes)
mkdir -p backend/public
mkdir -p frontend/src/app frontend/public
```

**Por que criar as pastas do K8s e Terraform agora?**
Porque definimos a estrutura macro do projeto desde o inicio. Isso comunica para qualquer dev que entrar no projeto: "aqui tem K8s, Terraform, CI/CD". E uma decisao arquitetural documentada na propria estrutura de pastas.

**Estrutura resultante:**

```
laravelnextts/
├── .github/workflows/     # CI/CD pipelines
├── backend/               # Laravel 12 API
│   └── public/
├── docker/                # Configuracoes Docker
│   ├── nginx/
│   ├── node/
│   └── php/
├── docs/                  # Documentacao
│   ├── api/
│   └── architecture/
├── frontend/              # Next.js 15
│   ├── public/
│   └── src/app/
├── k8s/                   # Kubernetes manifests
│   ├── base/
│   └── overlays/
│       ├── dev/
│       ├── staging/
│       └── prod/
└── terraform/             # Infraestrutura como codigo
    ├── environments/
    └── modules/
```

---

## Passo 1.3 - Gitignore

Crie o arquivo `.gitignore` na raiz:

```bash
cat > .gitignore << 'EOF'
# ==========================
# Environment
# ==========================
.env
.env.local
.env.*.local

# ==========================
# Backend (Laravel)
# ==========================
backend/vendor/
backend/.env
backend/storage/*.key
backend/storage/framework/cache/data/*
backend/storage/framework/sessions/*
backend/storage/framework/views/*
backend/storage/logs/*
backend/bootstrap/cache/*
backend/.phpunit.result.cache
backend/.php-cs-fixer.cache

# ==========================
# Frontend (Next.js)
# ==========================
frontend/node_modules/
frontend/.next/
frontend/out/
frontend/.env.local
frontend/.env.*.local
frontend/coverage/
frontend/test-results/
frontend/playwright-report/

# ==========================
# Docker
# ==========================
docker/volumes/

# ==========================
# IDE
# ==========================
.idea/
.vscode/
*.swp
*.swo
*~
.DS_Store
Thumbs.db

# ==========================
# Terraform
# ==========================
terraform/**/.terraform/
terraform/**/*.tfstate
terraform/**/*.tfstate.backup
terraform/**/*.tfvars
!terraform/**/*.tfvars.example
terraform/**/.terraform.lock.hcl

# ==========================
# OS
# ==========================
*.log
*.pid
*.seed
EOF
```

**Entendendo cada bloco:**

- **Environment:** `.env` contem senhas e secrets. NUNCA vai pro Git.
- **Backend:** `vendor/` sao as dependencias PHP (equivalente ao `node_modules`). Geradas pelo `composer install`.
- **Frontend:** `node_modules/` e `.next/` sao gerados. Nao versionamos codigo gerado.
- **Terraform:** `.tfstate` contem o estado da infraestrutura. Contem dados sensiveis e fica em remote backend (S3).
- **IDE:** Configs pessoais de cada dev. Cada um usa o que prefere.

---

## Passo 1.4 - Dockerignore

O `.dockerignore` diz ao Docker quais arquivos NAO copiar para dentro da imagem durante o build. Menos arquivos = build mais rapido + imagem menor.

Crie o arquivo `.dockerignore` na raiz:

```bash
cat > .dockerignore << 'EOF'
# Git
.git
.gitignore

# Docker
docker-compose*.yml
Dockerfile*

# Documentation
README.md
docs/

# IDE
.idea/
.vscode/

# Terraform
terraform/

# Kubernetes
k8s/

# CI/CD
.github/

# Backend
backend/vendor/
backend/node_modules/
backend/storage/logs/*
backend/.phpunit.result.cache
backend/tests/

# Frontend
frontend/node_modules/
frontend/.next/
frontend/coverage/
frontend/test-results/
frontend/playwright-report/

# Docker build artifacts (BuildKit temp files)
sha256:*
extracting
reading
resolve
transferring
CANCELED
ERROR
=
]
EOF
```

**Dica profissional:** Sem o `.dockerignore`, o Docker copiaria o `.git` (que pode ter centenas de MB) para dentro do build context. Isso deixaria o build MUITO lento.

---

## Passo 1.5 - Variaveis de ambiente

Crie o `.env.example` (template que vai pro Git) e o `.env` (valores reais que NAO vai pro Git):

```bash
cat > .env.example << 'EOF'
# ==========================
# Application
# ==========================
APP_NAME=Orderly
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

# ==========================
# Database (PostgreSQL)
# ==========================
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=orderly
DB_USERNAME=orderly
DB_PASSWORD=orderly_secret

# ==========================
# Redis
# ==========================
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# ==========================
# JWT
# ==========================
JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160

# ==========================
# Kafka
# ==========================
KAFKA_BROKER=kafka:9092
KAFKA_GROUP_ID=orderly-group

# ==========================
# Docker
# ==========================
COMPOSE_PROJECT_NAME=orderly

# ==========================
# PHP
# ==========================
PHP_MEMORY_LIMIT=256M
PHP_UPLOAD_MAX_FILESIZE=10M
PHP_POST_MAX_SIZE=10M
EOF

# Copiar para .env (este nao vai pro Git)
cp .env.example .env
```

**Por que DB_HOST=postgres e nao localhost?**
Dentro do Docker, cada container e isolado. O container do Laravel nao "ve" localhost do PostgreSQL. Ele precisa do nome do servico definido no docker-compose (`postgres`). O Docker DNS resolve esse nome para o IP interno do container.

**Por que JWT_TTL=60 e JWT_REFRESH_TTL=20160?**
- `JWT_TTL=60` = access token expira em 60 minutos
- `JWT_REFRESH_TTL=20160` = refresh token expira em 14 dias (20160 minutos)
- Padrao de seguranca: access token curto (limita dano se vazado), refresh token longo (conforto do usuario)

---

## Passo 1.6 - Dockerfile do Backend (PHP)

Este e o arquivo mais importante da infraestrutura. Ele define como a imagem do backend e construida.

**Conceito-chave: Multi-stage builds**

Um Dockerfile pode ter multiplos estagios (`FROM ... AS nome`). Cada estagio e uma imagem independente. O estagio final e o que vira a imagem real. Estagios intermediarios sao descartados (nao ocupam espaco na imagem final).

```
Estagio 1 (base)         → Extensoes PHP comuns
Estagio 2 (dependencies) → Instala Composer + dependencias
Estagio 3 (development)  → Xdebug + ferramentas dev
Estagio 4 (production)   → Imagem minima e segura
```

Crie o arquivo `docker/php/Dockerfile`:

```dockerfile
# ============================================
# STAGE 1: Base image with PHP extensions
# ============================================
# Usamos php:8.3-fpm-alpine para imagem minima (~50MB vs ~400MB debian)
# Alpine Linux e uma distro minimalista ideal para containers
FROM php:8.3-fpm-alpine AS base

# Metadata seguindo OCI Image Spec
LABEL maintainer="Diego <cardoso.benko@gmail.com>"
LABEL description="Orderly Backend - Laravel 12 API"

# Variaveis de build reutilizaveis
ARG PHP_MEMORY_LIMIT=256M
ARG PHP_UPLOAD_MAX_FILESIZE=10M
ARG PHP_POST_MAX_SIZE=10M

# Instalar dependencias do sistema necessarias para as extensoes PHP
# --no-cache evita cache do apk, mantendo a imagem menor
RUN apk add --no-cache \
    # Para extensao pgsql
    postgresql-dev \
    # Para extensao gd (manipulacao de imagens)
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # Para extensao intl (internacionalizacao)
    icu-dev \
    # Para extensao zip
    libzip-dev \
    # Para healthcheck
    curl \
    # Supervisor para gerenciar processos
    supervisor \
    # Kafka (librdkafka para ext-rdkafka)
    librdkafka-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    # Instalar extensoes PHP necessarias para Laravel
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        bcmath \
        opcache \
        pcntl \
    # Instalar Redis via PECL
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis rdkafka \
    && docker-php-ext-enable redis rdkafka \
    && apk del .build-deps \
    # Limpar caches
    && rm -rf /var/cache/apk/* /tmp/*

# Copiar configuracao PHP customizada
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Criar usuario nao-root para seguranca
# Nunca rodar containers como root em producao!
RUN addgroup -g 1000 -S orderly \
    && adduser -u 1000 -S orderly -G orderly

# Diretorio de trabalho
WORKDIR /var/www/html

# ============================================
# STAGE 2: Composer dependencies
# ============================================
# Estagio separado para cache de dependencias
# Se o codigo mudar mas composer.json nao, este cache e reutilizado
FROM base AS dependencies

# Instalar Composer (copiando do image oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar APENAS arquivos de dependencia primeiro (cache layer)
COPY backend/composer.json backend/composer.lock* ./

# Instalar dependencias sem scripts (o codigo ainda nao esta la)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# ============================================
# STAGE 3: Development
# ============================================
# Imagem de desenvolvimento com ferramentas extras
FROM base AS development

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar Xdebug para debugging (APENAS em dev!)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -rf /tmp/*

# Configuracao do Xdebug
RUN echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Git necessario para composer em dev
RUN apk add --no-cache git

# O codigo sera montado via volume no docker-compose
# Nao copiamos nada aqui - isso permite hot-reload

# Em dev, rodamos como root porque o volume montado
# (./backend:/var/www/html) herda as permissoes do host.
# O usuario nao-root e aplicado apenas em producao.

EXPOSE 9000

CMD ["php-fpm"]

# ============================================
# STAGE 4: Production
# ============================================
# Imagem de producao otimizada e segura
FROM base AS production

# Copiar dependencias do estagio de dependencies
COPY --from=dependencies /var/www/html/vendor ./vendor

# Copiar codigo da aplicacao
COPY backend/ .

# Instalar Composer apenas para gerar autoload otimizado
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Gerar autoload otimizado e rodar post-install scripts
RUN composer dump-autoload --optimize --classmap-authoritative \
    && rm /usr/bin/composer

# Criar diretorios necessarios com permissoes corretas
RUN mkdir -p storage/framework/{cache/data,sessions,views} \
    storage/logs \
    bootstrap/cache \
    && chown -R orderly:orderly /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copiar configuracao do supervisor
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Trocar para usuario nao-root
USER orderly

# Health check - verifica se PHP-FPM esta respondendo
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s \
    CMD curl -f http://localhost:9000/health || exit 1

EXPOSE 9000

# Supervisor gerencia PHP-FPM + queue workers
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

**Explicacao detalhada dos conceitos:**

**Por que Alpine e nao Debian/Ubuntu?**
- Alpine: ~5MB base. Debian: ~120MB base.
- Menos software = menos vulnerabilidades = mais seguro.
- Em K8s com dezenas de pods, a diferenca de tamanho importa MUITO (tempo de pull).

**Por que um usuario nao-root?**
- Se alguem explorar uma vulnerabilidade no PHP, nao tera acesso root no container.
- K8s pode forcar `runAsNonRoot: true` via PodSecurityPolicy.
- E uma best practice obrigatoria em ambientes regulados (PCI-DSS, SOC2).

**Por que COPY --from=composer:2?**
- Em vez de instalar o Composer dentro da imagem (o que adicionaria ~60MB), copiamos apenas o binario da imagem oficial. Mais limpo.

**Por que copiar composer.json ANTES do codigo?**
- Docker faz cache por camadas. Se o `composer.json` nao mudou, o `RUN composer install` nao roda novamente.
- Isso economiza MINUTOS em cada build (instalar dependencias e demorado).
- O codigo muda a cada commit, mas as dependencias raramente mudam.

**Por que --no-dev no stage de dependencies?**
- Em producao nao precisamos de PHPUnit, Pest, Faker, etc.
- Menos pacotes = imagem menor = mais segura.

---

## Passo 1.7 - Configuracao do PHP

Crie o arquivo `docker/php/php.ini`:

```ini
; ============================================
; PHP Custom Configuration for Orderly
; ============================================
; Este arquivo sobrescreve as configs padrao do PHP
; Otimizado para uma API Laravel em producao

; --- Memory ---
memory_limit = 256M

; --- Upload ---
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 5

; --- Execution ---
max_execution_time = 30
max_input_time = 30

; --- OPcache (compilacao de bytecode) ---
; OPcache pre-compila o PHP, evitando recompilar a cada request
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
; Em producao: revalidate_freq alto (nao verifica mudancas a cada request)
opcache.revalidate_freq = 60
; preload do Laravel (descomente em producao)
; opcache.preload = /var/www/html/preload.php
; opcache.preload_user = orderly

; --- Error Handling ---
; Em producao, NUNCA mostrar erros ao usuario
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; --- Session ---
session.save_handler = redis
; Sera configurado via variavel de ambiente
; session.save_path = "tcp://redis:6379"

; --- Security ---
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; --- Date ---
date.timezone = America/Sao_Paulo

; --- Realpath cache ---
; Melhora performance de autoload
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

**Explicacao das configs mais importantes:**

**OPcache:** PHP e interpretado - a cada request, ele le o arquivo .php, compila para bytecode, e executa. OPcache guarda o bytecode compilado na memoria. Resultado: 2-3x mais rapido.

**expose_php = Off:** Por padrao, o PHP adiciona um header `X-Powered-By: PHP/8.3`. Isso diz para hackers exatamente qual versao voce usa. Desligamos.

**allow_url_fopen = Off:** Impede que o PHP abra URLs remotas via `fopen()`. Previne ataques de SSRF (Server-Side Request Forgery).

**session.save_handler = redis:** Em vez de salvar sessoes em arquivos (default), salvamos no Redis. Isso permite que multiplas instancias da API compartilhem sessoes (essencial para escalar horizontalmente).

---

## Passo 1.8 - Supervisor (gerenciador de processos)

Em producao, precisamos de 3 processos rodando dentro do container do backend:
1. **PHP-FPM** - serve as requests HTTP
2. **Queue Worker** - processa jobs em background
3. **Scheduler** - executa tarefas agendadas (cron)

Docker foi feito para "1 container = 1 processo", mas no mundo real e pragmatico usar Supervisor para gerenciar poucos processos relacionados no mesmo container.

Crie o arquivo `docker/php/supervisord.conf`:

```ini
; ============================================
; Supervisor Configuration for Orderly
; ============================================
; Supervisor gerencia multiplos processos dentro de um unico container
; Em producao, precisamos de PHP-FPM + Queue Worker rodando juntos

[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
logfile_maxbytes=5MB
logfile_backups=3

; --- PHP-FPM ---
; Processo principal que serve a API
[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
priority=5
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

; --- Laravel Queue Worker ---
; Processa jobs em background (emails, notificacoes, etc.)
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
; 2 workers em paralelo (ajustar conforme necessidade)
numprocs=2
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stopwaitsecs=3600

; --- Laravel Scheduler ---
; Executa tarefas agendadas (cron jobs do Laravel)
[program:laravel-scheduler]
command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --verbose --no-interaction; sleep 60; done"
autostart=true
autorestart=true
priority=15
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

**Por que stdout_logfile=/dev/stdout?**
No Docker, logs devem ir para stdout/stderr (nao para arquivos). O Docker captura esses streams e os disponibiliza via `docker logs`. Em K8s, ferramentas como Fluentd/Loki coletam logs de stdout automaticamente.

**Por que numprocs=2 no queue worker?**
2 workers processam jobs em paralelo. Se um esta processando um email lento, o outro continua trabalhando. Em producao, ajuste conforme a carga.

**Por que --max-time=3600?**
O worker reinicia a cada 1 hora. Isso previne memory leaks - o PHP nao foi feito para processos de longa duracao como Node.js.

---

## Passo 1.9 - Dockerfile do Frontend (Node)

Crie o arquivo `docker/node/Dockerfile`:

```dockerfile
# ============================================
# STAGE 1: Base
# ============================================
# Node 22 Alpine - versao LTS mais recente
FROM node:22-alpine AS base

LABEL maintainer="Diego <cardoso.benko@gmail.com>"
LABEL description="Orderly Frontend - Next.js 15"

# Instalar libc6-compat necessario para algumas deps nativas
RUN apk add --no-cache libc6-compat curl

WORKDIR /app

# ============================================
# STAGE 2: Dependencies
# ============================================
# Estagio separado para instalar dependencias (cache layer)
FROM base AS dependencies

# Copiar APENAS arquivos de dependencia primeiro
# Se package.json nao mudar, este layer e cacheado
COPY frontend/package.json frontend/package-lock.json* ./

# Instalar dependencias
# Usa npm ci se package-lock.json existir, senao npm install
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# ============================================
# STAGE 3: Development
# ============================================
# Imagem de desenvolvimento com hot-reload
FROM base AS development

# Copiar node_modules do estagio de dependencies
COPY --from=dependencies /app/node_modules ./node_modules

# O codigo sera montado via volume no docker-compose
# Nao copiamos o codigo aqui - isso permite hot-reload via next dev

# Em dev, rodamos como root porque o volume montado
# (./frontend:/app) herda as permissoes do host.
# O usuario nao-root e aplicado apenas em producao.

EXPOSE 3000

# Variavel de ambiente para Next.js saber que esta em dev
ENV NODE_ENV=development
ENV NEXT_TELEMETRY_DISABLED=1

# next dev com turbopack para hot-reload ultra-rapido
CMD ["npx", "next", "dev", "--turbopack", "--hostname", "0.0.0.0"]

# ============================================
# STAGE 4: Builder (para producao)
# ============================================
FROM base AS builder

COPY --from=dependencies /app/node_modules ./node_modules
COPY frontend/ .

# Variaveis de build (nao ficam na imagem final)
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
ENV NEXT_TELEMETRY_DISABLED=1

# Build otimizado do Next.js
# standalone output gera um servidor Node minimo sem node_modules
RUN npm run build

# ============================================
# STAGE 5: Production
# ============================================
# Imagem final de producao - minima possivel
FROM base AS production

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs

# Next.js standalone output:
# - .next/standalone/ contem server.js + node_modules minimos
# - .next/static/ contem assets estaticos
# - public/ contem arquivos publicos
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/public ./public

USER nextjs

EXPOSE 3000

ENV PORT=3000
ENV HOSTNAME="0.0.0.0"

# Health check
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=15s \
    CMD curl -f http://localhost:3000/ || exit 1

# Servidor standalone do Next.js - minimo e otimizado
CMD ["node", "server.js"]
```

**Conceitos importantes:**

**npm ci vs npm install:**
- `npm install` pode atualizar o `package-lock.json`
- `npm ci` instala EXATAMENTE o que esta no lock file (reproducivel)
- Em CI/CD e Docker, SEMPRE use `npm ci` quando houver lock file
- No Dockerfile usamos um fallback inteligente: se o `package-lock.json` existir, usa `npm ci`; senao, usa `npm install` (primeira vez)

**Turbopack (--turbopack):**
O Turbopack e o substituto do Webpack feito pela Vercel, escrito em Rust. Em dev, ele faz hot-reload em <50ms (vs 1-3 segundos do Webpack).

**Next.js standalone output:**
Normalmente, o Next.js precisa do `node_modules` inteiro para rodar em producao. Com `output: "standalone"` no `next.config.ts`, ele gera um `server.js` auto-contido com APENAS as dependencias necessarias. Resultado: imagem de producao de ~100MB vs ~800MB.

**Por que 5 stages e nao 4?**
O "builder" e separado da "production" porque o build gera MUITO lixo (cache, source maps, etc). Copiando apenas o output para uma imagem limpa, a imagem final fica minima.

---

## Passo 1.10 - Configuracao do Nginx

O Nginx e nosso reverse proxy. Ele recebe TODAS as requests e roteia:
- `/api/*` → Laravel (backend via PHP-FPM)
- `/storage/*` → Arquivos estaticos do Laravel
- `/*` → Next.js (frontend)

Crie o arquivo `docker/nginx/default.conf`:

```nginx
# ============================================
# Nginx Configuration for Orderly
# ============================================
# Nginx atua como reverse proxy unificando frontend e backend
# em um unico ponto de entrada. Isso simula um ambiente real
# de producao onde temos um unico dominio.
#
# Roteamento:
#   /api/*      -> Laravel (PHP-FPM na porta 9000)
#   /storage/*  -> Arquivos estaticos do Laravel
#   /*          -> Next.js (porta 3000)

# --- Upstream definitions ---
# Upstreams definem os backends para load balancing
# Em K8s, isso sera substituido por Services
upstream backend {
    server backend:9000;
}

upstream frontend {
    server frontend:3000;
}

# --- Mapa para WebSocket condicional ---
# So envia "upgrade" quando o cliente pede (WebSocket).
# Requisicoes HTTP normais usam "" (vazio).
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      '';
}

# --- Main server block ---
server {
    listen 80;
    server_name localhost;

    # Tamanho maximo de upload (deve bater com php.ini)
    client_max_body_size 10M;

    # Root para arquivos estaticos do Laravel
    root /var/www/html/public;
    index index.php;

    # --- Security Headers ---
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # --- Gzip Compression ---
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml
        application/rss+xml
        image/svg+xml;

    # --- API Routes -> Laravel ---
    # Tudo que comecar com /api vai para o PHP-FPM
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # --- PHP-FPM Handler ---
    location ~ \.php$ {
        fastcgi_pass backend;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;

        # Buffer settings
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    # --- Storage (uploaded files) -> Laravel ---
    location /storage {
        alias /var/www/html/storage/app/public;
        access_log off;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # --- Health check endpoint ---
    location /health {
        access_log off;
        return 200 '{"status":"ok"}';
        add_header Content-Type application/json;
    }

    # --- Everything else -> Next.js ---
    location / {
        proxy_pass http://frontend;
        proxy_http_version 1.1;

        # WebSocket condicional (so ativa quando o cliente pede upgrade)
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;

        # Headers necessarios para o Next.js
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts (longos para dev - Turbopack pode demorar na primeira compilacao)
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;
    }

    # --- Next.js static assets ---
    location /_next/static {
        proxy_pass http://frontend;
        proxy_cache_valid 60m;
        add_header Cache-Control "public, immutable, max-age=31536000";
    }

    # --- Deny access to hidden files ---
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

**Explicacao dos conceitos:**

**Por que reverse proxy e nao acessar backend/frontend diretamente?**
1. **Unico ponto de entrada** - Em producao, temos UM dominio (orderly.com)
2. **Sem CORS** - Frontend e API estao no mesmo dominio, elimina problemas de cross-origin
3. **SSL terminado no Nginx** - Um unico certificado SSL
4. **Seguranca** - Backend e frontend nao sao acessiveis diretamente

**Security Headers explicados:**
- `X-Frame-Options: SAMEORIGIN` - Impede que seu site seja carregado em iframes de outros dominios (previne clickjacking)
- `X-Content-Type-Options: nosniff` - Impede o browser de "adivinhar" o tipo do arquivo (previne ataques XSS via MIME sniffing)
- `Referrer-Policy` - Controla quanta informacao de URL e enviada em requests (privacidade)

**Por que gzip?**
JSON de APIs e HTML/CSS/JS sao texto puro. Gzip comprime ~70% do tamanho. Uma resposta de 100KB vira 30KB. Menos banda = mais rapido.

**Por que proxy_set_header Upgrade?**
Para WebSocket funcionar atraves do proxy. Sem isso, hot-reload do Next.js em dev nao funciona, e WebSockets em producao (real-time de pedidos) tambem nao.

---

## Passo 1.11 - Scaffold do Backend (Laravel)

Precisamos dos arquivos minimos para o Docker conseguir montar o container. O Laravel real sera instalado via `composer install` dentro do container.

Crie o `backend/composer.json`:

```bash
cat > backend/composer.json << 'COMPOSEREOF'
{
    "name": "orderly/backend",
    "type": "project",
    "description": "Orderly Backend API - Laravel 12",
    "keywords": ["laravel", "api", "saas", "multi-tenant"],
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10",
        "tymon/jwt-auth": "^2.1",
        "mateusjunges/laravel-kafka": "^2.4"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2",
        "laravel/pint": "^1.18",
        "laravel/sail": "^1.38",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "pestphp/pest": "^3.7",
        "pestphp/pest-plugin-laravel": "^3.1",
        "phpstan/phpstan": "^2.1",
        "dedoc/scramble": "^0.12"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
COMPOSEREOF
```

**Entendendo as dependencias:**

**Producao (require):**
- `laravel/framework` - O framework em si
- `tymon/jwt-auth` - Autenticacao JWT (stateless, ideal para cloud)
- `mateusjunges/laravel-kafka` - Integracao com Apache Kafka

**Desenvolvimento (require-dev):**
- `pestphp/pest` - Framework de testes moderno (syntax limpa)
- `phpstan/phpstan` - Analise estatica de tipos (encontra bugs sem rodar o codigo)
- `laravel/pint` - Code formatter (equivalente ao Prettier para PHP)
- `dedoc/scramble` - Gera documentacao OpenAPI/Swagger automaticamente

Crie o placeholder do `backend/public/index.php`:

```bash
cat > backend/public/index.php << 'PHPEOF'
<?php

// Placeholder - sera substituido pelo Laravel real
// Este arquivo existe apenas para o Nginx ter algo para servir
// durante o build inicial do Docker

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'Orderly API - Placeholder. Run composer install to setup Laravel.',
    'version' => '0.0.1',
]);
PHPEOF
```

---

## Passo 1.12 - Scaffold do Frontend (Next.js)

Crie o `frontend/package.json`:

```bash
cat > frontend/package.json << 'PKGEOF'
{
  "name": "orderly-frontend",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev --turbopack --hostname 0.0.0.0",
    "build": "next build",
    "start": "next start",
    "lint": "next lint",
    "test": "vitest",
    "test:e2e": "playwright test",
    "type-check": "tsc --noEmit"
  },
  "dependencies": {
    "next": "^15.2.0",
    "react": "^19.0.0",
    "react-dom": "^19.0.0"
  },
  "devDependencies": {
    "@types/node": "^22.0.0",
    "@types/react": "^19.0.0",
    "@types/react-dom": "^19.0.0",
    "typescript": "^5.7.0",
    "eslint": "^9.0.0",
    "eslint-config-next": "^15.2.0",
    "tailwindcss": "^4.0.0",
    "@tailwindcss/postcss": "^4.0.0"
  }
}
PKGEOF
```

**Por que --hostname 0.0.0.0 no script dev?**
Por padrao, Next.js escuta apenas em `localhost` (127.0.0.1). Dentro do Docker, o container tem seu proprio network namespace. O Nginx precisa acessar o Next.js pelo IP interno do container. Com `0.0.0.0`, o Next.js escuta em TODAS as interfaces de rede.

Crie o `frontend/next.config.ts`:

```bash
cat > frontend/next.config.ts << 'NEXTEOF'
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Standalone output para producao (imagem Docker minima)
  output: "standalone",
};

export default nextConfig;
NEXTEOF
```

Crie o `frontend/tsconfig.json`:

```bash
cat > frontend/tsconfig.json << 'TSEOF'
{
  "compilerOptions": {
    "target": "ES2017",
    "lib": ["dom", "dom.iterable", "esnext"],
    "allowJs": true,
    "skipLibCheck": true,
    "strict": true,
    "noEmit": true,
    "esModuleInterop": true,
    "module": "esnext",
    "moduleResolution": "bundler",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "jsx": "preserve",
    "incremental": true,
    "plugins": [
      {
        "name": "next"
      }
    ],
    "paths": {
      "@/*": ["./src/*"]
    }
  },
  "include": ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"],
  "exclude": ["node_modules"]
}
TSEOF
```

**Por que strict: true?**
Ativa TODAS as verificacoes de tipo do TypeScript. Sim, da mais trabalho no inicio, mas pega bugs ANTES de rodar o codigo. Em projetos grandes, isso economiza horas de debug.

**Por que paths @/*?**
Permite importar assim: `import { Button } from "@/components/ui/button"` em vez de `import { Button } from "../../../components/ui/button"`. Muito mais limpo.

Crie o layout raiz `frontend/src/app/layout.tsx`:

```bash
cat > frontend/src/app/layout.tsx << 'LAYOUTEOF'
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Orderly - Plataforma SaaS de Delivery",
  description:
    "Plataforma multi-tenant para delivery de comida. Gerencie seu restaurante, cardapio, pedidos e muito mais.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR" className="dark">
      <body>{children}</body>
    </html>
  );
}
LAYOUTEOF
```

**Por que lang="pt-BR"?**
Acessibilidade e SEO. Screen readers usam esse atributo para saber em que idioma pronunciar o texto. Google usa para indexacao regional.

Crie a pagina inicial `frontend/src/app/page.tsx`:

```bash
cat > frontend/src/app/page.tsx << 'PAGEEOF'
export default function Home() {
  return (
    <main
      style={{
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        minHeight: "100vh",
        fontFamily: "system-ui, sans-serif",
        backgroundColor: "#0a0a0a",
        color: "#ededed",
      }}
    >
      <h1 style={{ fontSize: "3rem", marginBottom: "0.5rem" }}>Orderly</h1>
      <p style={{ fontSize: "1.25rem", color: "#888" }}>
        Plataforma SaaS Multi-Tenant de Delivery
      </p>
      <div
        style={{
          marginTop: "2rem",
          padding: "1rem 2rem",
          border: "1px solid #333",
          borderRadius: "8px",
          fontSize: "0.9rem",
          color: "#666",
        }}
      >
        <p>Stack: Next.js 15 + Laravel 12 + PostgreSQL + Redis + Kafka</p>
        <p style={{ marginTop: "0.5rem" }}>
          Ambiente Docker funcionando com sucesso!
        </p>
      </div>
    </main>
  );
}
PAGEEOF
```

**Por que inline styles e nao Tailwind aqui?**
Porque o Tailwind ainda nao esta configurado (precisa de `npm install`). Esta pagina e apenas um placeholder para validar que o container funciona. Na Fase 6 vamos configurar Tailwind + shadcn/ui corretamente.

---

## Passo 1.13 - Docker Compose (desenvolvimento)

Este e o coracao do ambiente. Crie o `docker-compose.yml`:

```bash
cat > docker-compose.yml << 'DCEOF'
# ============================================
# Docker Compose - Development Environment
# ============================================
# Este arquivo define todos os servicos necessarios para
# rodar o Orderly em ambiente de desenvolvimento.
#
# Uso:
#   docker compose up -d                    # Sobe tudo
#   docker compose --profile monitoring up  # Inclui Kafka UI
#   docker compose logs -f backend          # Ver logs
#   docker compose exec backend bash        # Entrar no container
#
# Arquitetura:
#   Nginx (:80) -> Frontend (:3000) + Backend (:9000)
#   Backend -> PostgreSQL (:5432) + Redis (:6379) + Kafka (:9092)

services:
  # ==========================================
  # NGINX - Reverse Proxy
  # ==========================================
  # Ponto de entrada unico que roteia requests
  # /api/* -> backend, /* -> frontend
  nginx:
    image: nginx:1.27-alpine
    container_name: orderly-nginx
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      # Volume compartilhado para servir arquivos do Laravel
      - backend-storage:/var/www/html/storage/app/public:ro
      - ./backend/public:/var/www/html/public:ro
    depends_on:
      backend:
        condition: service_started
      frontend:
        condition: service_started
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # BACKEND - Laravel 12 (PHP-FPM)
  # ==========================================
  # API REST em Laravel, roda via PHP-FPM
  # Em dev: codigo montado via volume (hot-reload ao salvar)
  backend:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      target: development
    container_name: orderly-backend
    # PHP-FPM fala FastCGI (nao HTTP), acesso via Nginx em /api
    # Porta 9000 exposta apenas internamente na rede Docker
    expose:
      - "9000"
    volumes:
      # Montar codigo fonte - mudancas refletem instantaneamente
      - ./backend:/var/www/html
      # Volume nomeado para vendor (performance no Linux/WSL)
      - backend-vendor:/var/www/html/vendor
      # Persistir storage entre restarts
      - backend-storage:/var/www/html/storage
    environment:
      # Variaveis do .env sao carregadas automaticamente
      # Estas sobrescrevem para garantir conexao entre containers
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: http://localhost
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: orderly
      DB_USERNAME: orderly
      DB_PASSWORD: orderly_secret
      REDIS_HOST: redis
      REDIS_PORT: 6379
      CACHE_DRIVER: redis
      QUEUE_CONNECTION: redis
      SESSION_DRIVER: redis
      KAFKA_BROKER: kafka:9092
      # Xdebug config
      XDEBUG_MODE: debug,coverage
      PHP_IDE_CONFIG: serverName=orderly
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # FRONTEND - Next.js 15
  # ==========================================
  # Frontend React com SSR, roda via next dev com Turbopack
  # Em dev: hot-reload automatico (Fast Refresh)
  frontend:
    build:
      context: .
      dockerfile: docker/node/Dockerfile
      target: development
    container_name: orderly-frontend
    ports:
      - "3000:3000"
    volumes:
      # Montar codigo fonte
      - ./frontend:/app
      # Volume anonimo para node_modules (evita conflito host/container)
      - /app/node_modules
    environment:
      NODE_ENV: development
      # URL relativa - o browser usa a mesma origem (127.0.0.1 ou localhost)
      # Evita problemas de IPv6 no WSL2
      NEXT_PUBLIC_API_URL: /api
      # Watchpack polling para WSL2 (file watching)
      WATCHPACK_POLLING: "true"
      CHOKIDAR_USEPOLLING: "true"
      # Next.js Server Components rodam DENTRO do container Docker para SSR
      INTERNAL_API_URL: http://nginx:80/api
    depends_on:
      - backend
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # POSTGRESQL 16
  # ==========================================
  # Banco de dados principal
  # Escolhemos PostgreSQL por: UUID nativo, JSONB, performance,
  # full-text search, e melhor suporte em cloud (RDS, CloudSQL)
  postgres:
    image: postgres:16-alpine
    container_name: orderly-postgres
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: orderly
      POSTGRES_USER: orderly
      POSTGRES_PASSWORD: orderly_secret
      # Otimizacoes para dev
      POSTGRES_INITDB_ARGS: "--encoding=UTF-8 --lc-collate=C --lc-ctype=C"
    volumes:
      - postgres-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U orderly -d orderly"]
      interval: 5s
      timeout: 5s
      retries: 10
      start_period: 30s
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # REDIS 7
  # ==========================================
  # Cache, sessions, filas (queues), e broadcast
  # Redis e in-memory, extremamente rapido para esses use cases
  redis:
    image: redis:7-alpine
    container_name: orderly-redis
    ports:
      - "6379:6379"
    command: redis-server --appendonly yes --maxmemory 128mb --maxmemory-policy allkeys-lru
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # KAFKA (KRaft mode - sem ZooKeeper)
  # ==========================================
  # Message broker para eventos asincronos
  # KRaft mode = Kafka gerencia seu proprio consenso,
  # eliminando a necessidade do ZooKeeper (mais simples)
  kafka:
    image: apache/kafka:4.0.0
    container_name: orderly-kafka
    ports:
      - "9092:9092"
    environment:
      # KRaft mode configuration (imagem oficial Apache Kafka)
      KAFKA_NODE_ID: 0
      KAFKA_PROCESS_ROLES: controller,broker
      KAFKA_CONTROLLER_QUORUM_VOTERS: 0@kafka:9093
      # Listeners
      KAFKA_LISTENERS: PLAINTEXT://:9092,CONTROLLER://:9093,EXTERNAL://:9094
      KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://kafka:9092,EXTERNAL://localhost:9094
      KAFKA_LISTENER_SECURITY_PROTOCOL_MAP: CONTROLLER:PLAINTEXT,PLAINTEXT:PLAINTEXT,EXTERNAL:PLAINTEXT
      KAFKA_CONTROLLER_LISTENER_NAMES: CONTROLLER
      KAFKA_INTER_BROKER_LISTENER_NAME: PLAINTEXT
      # Auto-create topics em dev (desabilitar em prod!)
      KAFKA_AUTO_CREATE_TOPICS_ENABLE: "true"
      # Retencao de mensagens (7 dias)
      KAFKA_LOG_RETENTION_HOURS: 168
      # Cluster ID obrigatorio para KRaft
      CLUSTER_ID: orderly-kafka-cluster-001
    volumes:
      - kafka-data:/tmp/kafka-logs
    # appuser (uid 1000) precisa de permissao no volume
    user: root
    healthcheck:
      test: ["CMD-SHELL", "nc -z localhost 9092"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 30s
    networks:
      - orderly-network
    restart: unless-stopped

  # ==========================================
  # KAFKA UI (monitoring profile)
  # ==========================================
  # Interface web para visualizar topics, mensagens, consumers
  # So sobe com: docker compose --profile monitoring up
  kafka-ui:
    image: provectuslabs/kafka-ui:latest
    container_name: orderly-kafka-ui
    ports:
      - "8080:8080"
    environment:
      KAFKA_CLUSTERS_0_NAME: orderly-local
      KAFKA_CLUSTERS_0_BOOTSTRAPSERVERS: kafka:9092
    depends_on:
      kafka:
        condition: service_healthy
    profiles:
      - monitoring
    networks:
      - orderly-network
    restart: unless-stopped

# ==========================================
# NETWORKS
# ==========================================
# Rede bridge customizada - todos os containers se comunicam
# pelo nome do servico (ex: postgres, redis, kafka)
networks:
  orderly-network:
    driver: bridge
    name: orderly-network

# ==========================================
# VOLUMES
# ==========================================
# Volumes nomeados persistem dados entre restarts
# docker compose down -v remove TUDO (cuidado!)
volumes:
  postgres-data:
    name: orderly-postgres-data
  redis-data:
    name: orderly-redis-data
  kafka-data:
    name: orderly-kafka-data
  backend-vendor:
    name: orderly-backend-vendor
  backend-storage:
    name: orderly-backend-storage
DCEOF
```

**Conceitos importantes do Docker Compose:**

**depends_on + condition: service_healthy:**
O backend so inicia DEPOIS que o PostgreSQL esta realmente pronto (nao apenas "rodando", mas respondendo queries). Sem isso, o Laravel tentaria conectar ao banco antes dele estar pronto e falharia.

**Volume anonimo `- /app/node_modules`:**
Truque essencial! Montamos `./frontend:/app` (codigo local no container), mas o `node_modules` do container (Linux) e diferente do host (Windows/Mac). O volume anonimo "protege" o `node_modules` interno do container de ser sobrescrito pelo bind mount.

**WATCHPACK_POLLING para WSL2:**
No WSL2, o file system notification entre Windows e Linux nao funciona bem. O polling (verificar mudancas periodicamente) resolve o hot-reload.

**Redis --appendonly yes:**
Redis salva dados em disco (AOF - Append Only File). Se o container reiniciar, os dados de cache/sessao sao restaurados.

**Redis --maxmemory-policy allkeys-lru:**
Quando o Redis atingir 128MB, ele remove as chaves menos recentemente usadas (LRU = Least Recently Used). Isso previne o Redis de consumir toda a memoria.

**Kafka KRaft mode:**
Kafka antigamente precisava do ZooKeeper (um servico separado) para coordenacao. KRaft (Kafka Raft) elimina essa dependencia - o Kafka gerencia seu proprio consenso. Resultado: menos um container para manter.

**profiles: monitoring:**
O Kafka UI so sobe quando voce pede explicitamente: `docker compose --profile monitoring up`. Em dev do dia-a-dia, ele nao consome recursos.

---

## Passo 1.14 - Docker Compose (producao)

O Docker Compose suporta "override files". O `docker-compose.prod.yml` SOBRESCREVE valores do `docker-compose.yml` para producao.

Crie o `docker-compose.prod.yml`:

```bash
cat > docker-compose.prod.yml << 'DCPEOF'
# ============================================
# Docker Compose - Production Override
# ============================================
# Este arquivo SOBRESCREVE o docker-compose.yml para producao.
#
# Uso:
#   docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
#
# Diferencas de producao:
#   - Builds usam target "production" (imagens otimizadas)
#   - Sem volumes de codigo (codigo esta DENTRO da imagem)
#   - Sem portas expostas desnecessarias
#   - Sem ferramentas de debug (Xdebug)
#   - Resources limits (CPU/memoria)
#   - Restart always (em vez de unless-stopped)

services:
  nginx:
    restart: always
    deploy:
      resources:
        limits:
          cpus: "0.5"
          memory: 128M

  backend:
    build:
      target: production
    # Sem portas expostas - acesso apenas via Nginx
    ports: []
    # Sem volumes de codigo - tudo esta na imagem
    volumes:
      - backend-storage:/var/www/html/storage
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      # Desabilitar Xdebug
      XDEBUG_MODE: "off"
    restart: always
    deploy:
      resources:
        limits:
          cpus: "1"
          memory: 512M
      # Em producao, podemos ter multiplas replicas
      # (em K8s isso e feito via Deployment replicas)
      replicas: 1

  frontend:
    build:
      target: production
    ports: []
    # Sem volumes - codigo na imagem
    volumes: []
    environment:
      NODE_ENV: production
    restart: always
    deploy:
      resources:
        limits:
          cpus: "0.5"
          memory: 256M

  postgres:
    # Sem porta exposta para o host
    ports: []
    environment:
      # Em producao, use secrets do Docker/K8s em vez de env vars
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    restart: always
    deploy:
      resources:
        limits:
          cpus: "1"
          memory: 1G

  redis:
    ports: []
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru --requirepass ${REDIS_PASSWORD}
    restart: always
    deploy:
      resources:
        limits:
          cpus: "0.5"
          memory: 256M

  kafka:
    ports: []
    environment:
      # Desabilitar auto-create em producao
      KAFKA_CFG_AUTO_CREATE_TOPICS_ENABLE: "false"
    restart: always
    deploy:
      resources:
        limits:
          cpus: "1"
          memory: 512M
DCPEOF
```

**Diferencas criticas dev vs prod:**

| Aspecto | Dev | Prod |
|---|---|---|
| Codigo | Montado via volume (hot-reload) | Copiado DENTRO da imagem (imutavel) |
| Portas | Expostas para debug | Apenas Nginx na porta 80 |
| Xdebug | Habilitado | Desabilitado |
| Resources | Sem limites | CPU e memoria limitados |
| Restart | unless-stopped | always |
| Kafka auto-create | Habilitado | Desabilitado |
| Redis password | Sem | Com |

**Por que ports: [] em producao?**
Nenhum servico interno deve ser acessivel diretamente. Todo trafego passa pelo Nginx (que em producao tera SSL). Isso e o principio de "defense in depth".

**Por que resource limits?**
Sem limites, um container com memory leak pode consumir TODA a RAM do servidor e derrubar tudo. Com limits, o Docker mata apenas o container problematico.

---

## Passo 1.15 - Makefile (automacao)

O Makefile cria atalhos para comandos longos. Em vez de digitar `docker compose exec backend php artisan migrate`, voce digita `make migrate`.

Crie o `Makefile` na raiz:

```makefile
# ============================================
# Orderly - Makefile
# ============================================
# Atalhos para comandos comuns do projeto.
# Use: make <comando>
#
# Exemplos:
#   make setup    - Configura o projeto do zero
#   make up       - Sobe o ambiente de desenvolvimento
#   make down     - Para todos os containers
#   make logs     - Ver logs em tempo real
#   make test     - Roda todos os testes

.PHONY: help setup up down build logs restart clean \
        backend-shell frontend-shell db-shell \
        test test-backend test-frontend test-e2e \
        lint lint-backend lint-frontend \
        migrate seed fresh

# Cores para output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RED    := \033[0;31m
NC     := \033[0m

# ==========================================
# HELP
# ==========================================
help: ## Mostra esta ajuda
	@echo ""
	@echo "$(GREEN)Orderly - Comandos Disponiveis$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ==========================================
# SETUP
# ==========================================
setup: ## Configura o projeto do zero (primeira vez)
	@echo "$(GREEN)>>> Configurando Orderly...$(NC)"
	@cp -n .env.example .env 2>/dev/null || true
	@echo "$(YELLOW)>>> Building Docker images...$(NC)"
	@docker compose build
	@echo "$(YELLOW)>>> Starting services...$(NC)"
	@docker compose up -d
	@echo "$(YELLOW)>>> Installing backend dependencies...$(NC)"
	@docker compose exec backend composer install
	@echo "$(YELLOW)>>> Installing frontend dependencies...$(NC)"
	@docker compose exec frontend npm install
	@echo "$(GREEN)>>> Setup completo!$(NC)"
	@echo ""
	@echo "  Frontend: http://localhost:3000"
	@echo "  Backend:  http://localhost:8000"
	@echo "  Nginx:    http://localhost"
	@echo ""

# ==========================================
# DOCKER
# ==========================================
up: ## Sobe o ambiente de desenvolvimento
	docker compose up -d

up-monitoring: ## Sobe com Kafka UI
	docker compose --profile monitoring up -d

down: ## Para todos os containers
	docker compose down

build: ## Rebuild das imagens Docker
	docker compose build --no-cache

restart: ## Reinicia todos os containers
	docker compose restart

logs: ## Ver logs de todos os servicos (tempo real)
	docker compose logs -f

logs-backend: ## Ver logs do backend
	docker compose logs -f backend

logs-frontend: ## Ver logs do frontend
	docker compose logs -f frontend

ps: ## Status dos containers
	docker compose ps

clean: ## Para tudo e remove volumes (CUIDADO: apaga dados!)
	@echo "$(RED)>>> ATENCAO: Isso vai apagar TODOS os dados (banco, cache, etc.)$(NC)"
	@read -p "Tem certeza? [y/N] " confirm && [ "$$confirm" = "y" ] && \
		docker compose down -v --remove-orphans || echo "Cancelado."

# ==========================================
# SHELL ACCESS
# ==========================================
backend-shell: ## Entra no container do backend (bash)
	docker compose exec backend sh

frontend-shell: ## Entra no container do frontend (sh)
	docker compose exec frontend sh

db-shell: ## Entra no PostgreSQL (psql)
	docker compose exec postgres psql -U orderly -d orderly

redis-shell: ## Entra no Redis (redis-cli)
	docker compose exec redis redis-cli

# ==========================================
# LARAVEL
# ==========================================
artisan: ## Roda comando artisan (ex: make artisan CMD="migrate")
	docker compose exec backend php artisan $(CMD)

migrate: ## Roda as migrations
	docker compose exec backend php artisan migrate

seed: ## Roda os seeders
	docker compose exec backend php artisan db:seed

fresh: ## Drop + migrate + seed (CUIDADO!)
	docker compose exec backend php artisan migrate:fresh --seed

# ==========================================
# TESTES
# ==========================================
test: test-backend test-frontend ## Roda todos os testes

test-backend: ## Roda testes do backend (Pest)
	docker compose exec backend php artisan test

test-frontend: ## Roda testes do frontend (Vitest)
	docker compose exec frontend npm test

test-e2e: ## Roda testes E2E (Playwright)
	docker compose exec frontend npm run test:e2e

# ==========================================
# LINT / QUALITY
# ==========================================
lint: lint-backend lint-frontend ## Roda lint em tudo

lint-backend: ## Roda PHP Pint (code style)
	docker compose exec backend ./vendor/bin/pint

lint-frontend: ## Roda ESLint
	docker compose exec frontend npm run lint

type-check: ## Verifica tipos TypeScript
	docker compose exec frontend npm run type-check

# ==========================================
# PRODUCTION
# ==========================================
prod-build: ## Build de producao
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build

prod-up: ## Sobe ambiente de producao
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

**IMPORTANTE sobre Makefile:** As indentacoes nos comandos DEVEM ser TABs, nao espacos. Se voce copiar e o editor converter para espacos, o make vai dar erro. Verifique no seu editor.

**Por que Makefile e nao shell scripts?**
- `make help` lista TODOS os comandos disponiveis automaticamente (parseando os `##` comments)
- Make tem dependencias nativas (um target pode depender de outro)
- E universal - qualquer dev Linux/Mac conhece `make`
- `.PHONY` garante que os comandos rodem mesmo se existir um arquivo com o mesmo nome

---

## Passo 1.16 - Subindo o ambiente

### Pre-requisito: Docker Desktop + WSL2 (Windows)

Se voce esta no Windows com WSL2, precisa configurar o Docker Desktop para se comunicar com sua distro Linux. Sem isso, o comando `docker` no terminal WSL nao consegue se conectar ao daemon.

**1. Corrigir permissao do config.json:**

O Docker Desktop precisa de permissao de escrita no arquivo de configuracao. Rode no terminal WSL:

```bash
chmod 644 ~/.docker/config.json
```

**Por que isso acontece?** O Docker Desktop no Windows precisa escrever configuracoes dentro do WSL via o arquivo `~/.docker/config.json`. Se ele estiver read-only (`-r--r--r--`), a integracao falha silenciosamente.

**2. Habilitar a integracao WSL no Docker Desktop:**

1. Abra o **Docker Desktop** no Windows (menu Iniciar → Docker Desktop)
2. Clique no icone de **engrenagem** (Settings) no canto superior direito
3. Va em **Resources** → **WSL Integration**
4. Marque **"Enable integration with my default WSL distro"**
5. Habilite o toggle da sua distro (ex: **Ubuntu**)
6. Clique **Apply & Restart**
7. Aguarde o Docker Desktop reiniciar completamente

**3. Verificar se funcionou:**

Volte ao terminal WSL e rode:

```bash
docker info
```

Voce deve ver tanto a secao **Client** quanto a secao **Server** com informacoes completas (versao, OS, containers, etc). Se a secao Server ainda mostrar erro, repita os passos acima.

**O que esta acontecendo por baixo?** O Docker Desktop cria um socket em `/var/run/docker.sock` dentro da sua distro WSL. O CLI do Docker no WSL usa esse socket para se comunicar com o daemon que roda no Windows. Sem a integracao habilitada, o socket nao e criado e o CLI nao encontra o daemon.

---

### Subindo os containers

Agora sim, rode:

```bash
# Setup completo (primeira vez)
make setup

# OU passo a passo:
cp .env.example .env
docker compose up -d --build
docker compose exec backend composer install
docker compose exec frontend npm install
```

Verifique se tudo esta rodando:

```bash
# Ver status dos containers
docker compose ps

# Deve mostrar algo como:
# orderly-nginx      running   0.0.0.0:80->80/tcp
# orderly-backend    running   0.0.0.0:8000->9000/tcp
# orderly-frontend   running   0.0.0.0:3000->3000/tcp
# orderly-postgres   running (healthy)   0.0.0.0:5432->5432/tcp
# orderly-redis      running (healthy)   0.0.0.0:6379->6379/tcp
# orderly-kafka      running (healthy)   0.0.0.0:9092->9092/tcp
```

Teste os endpoints:

```bash
# Frontend (Next.js)
curl http://localhost:3000

# Backend API (placeholder)
curl http://localhost:8000

# Nginx (reverse proxy)
curl http://localhost        # -> Next.js
curl http://localhost/api    # -> Laravel
curl http://localhost/health # -> {"status":"ok"}

# Testar conexao do banco
docker compose exec postgres psql -U orderly -d orderly -c "SELECT 1;"

# Testar Redis
docker compose exec redis redis-cli ping
# Resposta: PONG
```

### Comandos uteis para debug

```bash
# Ver logs em tempo real
docker compose logs -f

# Logs de um servico especifico
docker compose logs -f backend

# Entrar no container do backend
make backend-shell

# Entrar no PostgreSQL
make db-shell

# Reconstruir tudo do zero
docker compose down -v
docker compose up -d --build

# Ver todos os comandos disponiveis
make help
```

### Troubleshooting

**Pagina nao carrega no browser (loading infinito):**

O Chrome no Windows resolve `localhost` para `::1` (IPv6), mas o Docker no WSL2 so escuta em IPv4. Solucao: use `127.0.0.1` ao inves de `localhost`:

```
# Ao inves de http://localhost/login use:
http://127.0.0.1/login

# Ao inves de http://localhost:3000/login use:
http://127.0.0.1:3000/login
```

> **Dica:** Por isso o `NEXT_PUBLIC_API_URL` usa URL relativa (`/api`) no docker-compose — assim o browser faz o fetch usando a mesma origem que voce acessou (127.0.0.1).

**Porta 80 ja em uso:**
```bash
# Descobrir quem esta usando a porta 80
sudo lsof -i :80
# Parar o servico ou mudar a porta no docker-compose.yml
```

**Container reiniciando em loop:**
```bash
# Ver os logs do container problemático
docker compose logs backend
# Geralmente e erro de permissao ou dependencia faltando
```

**Hot-reload nao funciona no WSL2:**
Verifique se as variaveis WATCHPACK_POLLING e CHOKIDAR_USEPOLLING estao "true" no docker-compose.yml.

---

## Resumo da Fase 1

**Arquivos criados:** 15

```
laravelnextts/
├── .dockerignore
├── .env.example
├── .gitignore
├── Makefile
├── README.md
├── docker-compose.yml
├── docker-compose.prod.yml
├── docker/
│   ├── nginx/default.conf
│   ├── node/Dockerfile
│   └── php/
│       ├── Dockerfile
│       ├── php.ini
│       └── supervisord.conf
├── backend/
│   ├── composer.json
│   └── public/index.php
└── frontend/
    ├── next.config.ts
    ├── package.json
    ├── tsconfig.json
    └── src/app/
        ├── layout.tsx
        └── page.tsx
```

**Servicos rodando:** 6 containers

| Container | Porta | Funcao |
|---|---|---|
| orderly-nginx | 80 | Reverse proxy |
| orderly-backend | 9000 (interno) | PHP-FPM (Laravel) via Nginx |
| orderly-frontend | 3000 | Next.js dev server |
| orderly-postgres | 5432 | Banco de dados |
| orderly-redis | 6379 | Cache / Queue / Session |
| orderly-kafka | 9092 | Message broker |

**Conceitos aprendidos:**
- Multi-stage Docker builds
- Docker Compose com health checks e depends_on
- Nginx como reverse proxy
- PHP-FPM tunado com OPcache
- Supervisor para multiplos processos
- Seguranca em containers (usuario nao-root, headers, expose_php=Off)
- Volumes nomeados vs bind mounts vs anonimos
- Kafka KRaft mode (sem ZooKeeper)
- Makefile para automacao de comandos

**Proximo:** Fase 2 - Bootstrap Laravel 12 + Next.js 15 com shadcn/ui

---

# Fase 2 - Bootstrap Laravel 12 + Next.js 15 com shadcn/ui

> **Objetivo:** Instalar o Laravel real, configurar JWT auth, e montar o frontend com shadcn/ui.
> Ao final desta fase, teremos login funcional e dashboard admin com sidebar.

---

## Passo 2.1 - Instalar Laravel skeleton

O `backend/` tem apenas um `composer.json` e um placeholder. Precisamos instalar o Laravel completo.

**Por que nao usamos `composer create-project`?**
Porque o diretorio ja contem nosso `composer.json` customizado (com jwt-auth, kafka, pest, etc.). O `create-project` exige um diretorio vazio.

**Estrategia:** Criar um projeto Laravel temporario e copiar os arquivos skeleton para nosso diretorio.

```bash
# Entrar no container do backend
make backend-shell

# Dentro do container:
# 1. Criar Laravel em diretorio temporario
composer create-project laravel/laravel /tmp/laravel-skeleton --prefer-dist --no-interaction

# 2. Copiar arquivos skeleton (sem sobrescrever nosso composer.json)
cp /tmp/laravel-skeleton/artisan /var/www/html/
cp -r /tmp/laravel-skeleton/app /var/www/html/
cp -r /tmp/laravel-skeleton/bootstrap /var/www/html/
cp -r /tmp/laravel-skeleton/config /var/www/html/
cp -r /tmp/laravel-skeleton/database /var/www/html/
cp -r /tmp/laravel-skeleton/resources /var/www/html/
cp -r /tmp/laravel-skeleton/routes /var/www/html/
cp -r /tmp/laravel-skeleton/storage /var/www/html/
cp -r /tmp/laravel-skeleton/tests /var/www/html/
cp /tmp/laravel-skeleton/.env.example /var/www/html/
cp /tmp/laravel-skeleton/phpunit.xml /var/www/html/

# IMPORTANTE: sobrescrever o public/index.php placeholder com o real do Laravel
# Na Fase 1 criamos um index.php simples que retorna JSON fixo.
# Agora precisamos do index.php real que carrega o bootstrap do Laravel.
cp -f /tmp/laravel-skeleton/public/index.php /var/www/html/public/index.php

# Copiar .env.example para .env
cp /var/www/html/.env.example /var/www/html/.env

# 3. Limpar temporario
rm -rf /tmp/laravel-skeleton

# 4. Instalar NOSSAS dependencias (composer.json customizado)
composer install

# 5. Gerar chave da aplicacao
php artisan key:generate

# 6. Dar permissoes ao storage
chmod -R 777 storage bootstrap/cache

# 7. Sair do container
exit
```

**O que aconteceu:**
- O `composer create-project` baixou a versao mais recente do Laravel 12
- Copiamos toda a estrutura (models, config, routes, migrations, etc.)
- O `composer install` usou NOSSO `composer.json` que ja inclui `tymon/jwt-auth`, `laravel-kafka`, `pestphp/pest`, etc.
- O `key:generate` criou o `APP_KEY` no `.env`

**Conceito importante - Volumes no Docker:**
- `./backend:/var/www/html` = bind mount (codigo aparece no host)
- `backend-vendor:/var/www/html/vendor` = volume nomeado (fica SÓ no Docker, performance melhor)
- `backend-storage:/var/www/html/storage` = volume nomeado (persistente entre restarts)

Os arquivos do Laravel vao aparecer na pasta `backend/` do seu computador, mas `vendor/` e `storage/` ficam nos volumes Docker (por performance e persistencia).

---

## Passo 2.2 - Testar conexao com PostgreSQL e Redis

O `.env` ja foi criado no passo anterior. O docker-compose.yml ja passa as variaveis de ambiente (DB_HOST, REDIS_HOST, etc.) para o container, sobrescrevendo o `.env`.

Agora vamos testar a conexao com o PostgreSQL:

```bash
# Rodar as migrations padrao do Laravel
docker compose exec backend php artisan migrate
```

Deve exibir:
```
Migration table created successfully.
Running migrations...
   INFO  Running migrations.

  2024_... create_users_table .......... DONE
  2024_... create_password_reset_tokens_table .. DONE
  2024_... create_sessions_table ....... DONE
  2024_... create_cache_table .......... DONE
  2024_... create_jobs_table ........... DONE
```

**Verificar Redis:**
```bash
docker compose exec backend php artisan tinker
>>> Cache::put('test', 'Orderly funciona!', 60);
>>> Cache::get('test');
# Deve retornar: "Orderly funciona!"
>>> exit
```

**Conceito - Por que PostgreSQL e nao MySQL?**
- UUID nativo (gen_random_uuid())
- JSONB para dados semi-estruturados
- Full-text search embutido
- Melhor suporte em cloud (RDS, Cloud SQL, Supabase)
- Sequences e CTEs mais robustos

---

## Passo 2.3 - Configurar CORS e rotas API

O CORS (Cross-Origin Resource Sharing) permite que o frontend (`localhost:3000`) faca requests para o backend (`localhost/api`).

No Laravel 12, nao existe mais `config/cors.php`. O CORS e as rotas API sao configurados no `bootstrap/app.php`.

Primeiro, crie o arquivo de rotas API. Crie `backend/routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Orderly API v1',
        'version' => '1.0.0',
    ]);
});
```

Agora edite `backend/bootstrap/app.php` para registrar as rotas API e configurar o CORS:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**O que mudou no Laravel 12?**
- Sem `config/cors.php` — o middleware `HandleCors` e adicionado automaticamente
- Sem `routes/api.php` por padrao — precisa criar e registrar no `bootstrap/app.php`
- A linha `api: __DIR__.'/../routes/api.php'` registra as rotas com prefixo `/api`
- O `trustProxies(at: '*')` e necessario porque o Nginx faz proxy para o PHP-FPM

Teste:
```bash
curl http://localhost/api
# Esperado: {"status":"ok","message":"Orderly API v1","version":"1.0.0"}
```

**Nota:** Na pratica, quando o frontend acessa `http://localhost/api` via Nginx, nao e cross-origin (mesma origem). Mas durante SSR, o Next.js chama o backend internamente pela rede Docker, e ai o CORS pode ser necessario.

---

## Corrigir permissoes dos arquivos do backend

Antes de continuar, precisamos corrigir as permissoes dos arquivos do backend. Como os arquivos foram criados dentro do container Docker (que roda como root), eles ficam com owner `root` no host, impedindo a edicao no VSCode ou outro editor.

```bash
# Corrigir permissoes via container Docker (nao precisa de sudo)
docker compose exec backend chown -R 1000:1000 /var/www/html
```

> **Por que isso acontece?** Quando voce roda comandos como `composer install` ou `php artisan` dentro do container, os arquivos sao criados pelo usuario root do container. Como usamos bind mount (`./backend:/var/www/html`), esses arquivos aparecem no host com owner root. O comando acima muda o owner para UID 1000 (seu usuario WSL).

> **Dica:** Sempre que rodar comandos dentro do container que criem ou modifiquem arquivos, rode o `chown` novamente para manter as permissoes corretas.

---

## Passo 2.4 - Configurar JWT Auth (tymon/jwt-auth)

JWT (JSON Web Token) e nosso metodo de autenticacao. Diferente do Sanctum (session-based), JWT e stateless e perfeito para APIs e microsservicos.

```bash
# Publicar a configuracao do JWT
docker compose exec backend php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Gerar o JWT secret (adiciona JWT_SECRET no .env)
docker compose exec backend php artisan jwt:secret
```

Agora configure o guard de autenticacao. Edite `backend/config/auth.php`:

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
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
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

Agora modifique o model `User` para implementar `JWTSubject`. Edite `backend/app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
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

    // JWT: claims customizados (ex: role, tenant_id)
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
```

**Conceito - JWT vs Sanctum vs Passport:**

| Feature | JWT | Sanctum | Passport |
|---|---|---|---|
| Stateless | Sim | Nao (session) | Sim (OAuth2) |
| Cloud-native | Perfeito | Limitado | Complexo |
| Simplicidade | Alta | Muito alta | Baixa |
| Refresh Token | Manual | N/A | Built-in |
| Microsservicos | Ideal | Nao adequado | Possivel |

Escolhemos JWT porque e stateless (nao precisa de sessao no Redis), funciona com qualquer frontend, e ideal para Kubernetes (horizontal scaling sem session affinity).

---

## Passo 2.5 - Clean Architecture - Padroes base

Antes de criar os controllers, vamos estabelecer os padroes arquiteturais que usaremos em TODO o projeto.

**Estrutura de diretorios:**

```
backend/app/
├── Actions/           # Use Cases (logica de negocio pura)
│   └── Auth/
│       └── LoginAction.php
├── DTOs/              # Data Transfer Objects
│   └── Auth/
│       └── LoginDTO.php
├── Repositories/      # Acesso a dados
│   ├── Contracts/     # Interfaces
│   │   └── UserRepositoryInterface.php
│   └── Eloquent/      # Implementacoes Eloquent
│       └── UserRepository.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/    # Versionamento de API
│   │           └── Auth/
│   │               └── AuthController.php
│   ├── Requests/
│   │   └── Auth/
│   │       └── LoginRequest.php
│   └── Resources/
│       └── UserResource.php
└── Providers/
    └── RepositoryServiceProvider.php
```

**Por que essa estrutura?**
- **Actions:** Cada classe = 1 caso de uso. Sem dependencias do framework. Testavel isoladamente.
- **DTOs:** Objetos imutaveis para transferir dados entre camadas. Sem logica.
- **Repositories:** Abstrai o acesso ao banco. Se trocar Eloquent por outro ORM, so muda a implementacao.
- **Versionamento (V1):** Permite evoluir a API sem quebrar clientes antigos.

### LoginDTO

Crie o arquivo `backend/app/DTOs/Auth/LoginDTO.php`:

```php
<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\LoginRequest;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
```

**Conceito - `readonly class` (PHP 8.2+):**
Todas as propriedades sao automaticamente readonly. Impossivel modificar apos criacao. Perfeito para DTOs.

### LoginAction

Crie o arquivo `backend/app/Actions/Auth/LoginAction.php`:

```php
<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use Illuminate\Auth\AuthenticationException;

final class LoginAction
{
    public function execute(LoginDTO $dto): string
    {
        $token = auth('api')->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        if (!$token) {
            throw new AuthenticationException('Credenciais invalidas.');
        }

        return $token;
    }
}
```

**Conceito - Action Pattern:**
- Recebe um DTO (dados validados)
- Executa a logica de negocio
- Retorna resultado ou lanca excecao
- Sem dependencia do HTTP (pode ser chamado por CLI, Queue, etc.)

### UserRepositoryInterface

Crie o arquivo `backend/app/Repositories/Contracts/UserRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function create(array $data): User;
}
```

### UserRepository

Crie o arquivo `backend/app/Repositories/Eloquent/UserRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }
}
```

### RepositoryServiceProvider

Crie o arquivo `backend/app/Providers/RepositoryServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
```

Registre o provider em `backend/bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

**Conceito - Dependency Inversion (SOLID):**
O controller depende da INTERFACE `UserRepositoryInterface`, nao da implementacao `UserRepository`. Se amanha trocarmos Eloquent por Doctrine, so mudamos o binding no ServiceProvider. Zero mudanca nos controllers e actions.

---

## Passo 2.6 - Controller de autenticacao + rotas

### LoginRequest

Crie `backend/app/Http/Requests/Auth/LoginRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email e obrigatorio.',
            'email.email' => 'Informe um email valido.',
            'password.required' => 'A senha e obrigatoria.',
            'password.min' => 'A senha deve ter no minimo 6 caracteres.',
        ];
    }
}
```

### UserResource

Crie `backend/app/Http/Resources/UserResource.php`:

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
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### AuthController

Crie `backend/app/Http/Controllers/Api/V1/Auth/AuthController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\DTOs\Auth\LoginDTO;
use App\Actions\Auth\LoginAction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            $token = $action->execute(LoginDTO::fromRequest($request));

            return $this->respondWithToken($token);
        } catch (AuthenticationException) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], 401);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();

        return $this->respondWithToken($token);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
```

### Rotas API

Edite `backend/routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rotas publicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rotas protegidas (requer JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });
});
```

**Conceito - Versionamento de API:**
Prefixamos com `v1` para poder criar `v2` no futuro sem quebrar clientes existentes. URLs ficam: `GET /api/v1/auth/me`, `POST /api/v1/auth/login`, etc.

---

## Passo 2.7 - Seeder de admin e teste da API

Crie `backend/database/seeders/AdminUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@orderly.com'],
            [
                'name' => 'Admin Orderly',
                'email' => 'admin@orderly.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
```

Registre no `backend/database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
```

Execute:

```bash
# Rodar seed
docker compose exec backend php artisan db:seed

# Testar login via curl
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}'
```

> **Importante:** O header `-H "Accept: application/json"` e essencial em APIs Laravel. Sem ele, erros sao retornados como paginas HTML ao inves de JSON legivel.

Resposta esperada:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

Teste a rota protegida:
```bash
# Copie o access_token da resposta anterior
curl http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

Resposta esperada:
```json
{
  "data": {
    "id": 1,
    "name": "Admin Orderly",
    "email": "admin@orderly.com",
    "created_at": "2026-03-04T..."
  }
}
```

Se chegou aqui, o backend esta 100% funcional com JWT auth!

---

## Passo 2.8 - Configurar Tailwind CSS v4

O Tailwind CSS v4 tem uma configuracao muito mais simples que o v3. Nao precisa de `tailwind.config.js`. Tudo e feito via CSS.

Crie `frontend/postcss.config.mjs`:

```js
/** @type {import('postcss-load-config').Config} */
const config = {
  plugins: {
    "@tailwindcss/postcss": {},
  },
};

export default config;
```

Crie `frontend/src/app/globals.css`:

```css
@import "tailwindcss";
```

Atualize `frontend/src/app/layout.tsx`:

```tsx
import type { Metadata } from "next";
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
        {children}
      </body>
    </html>
  );
}
```

**Conceito - Tailwind CSS v4 vs v3:**
- v3: Configuracao via `tailwind.config.js` (JS)
- v4: Configuracao via `@theme` no CSS (CSS-first)
- v4: Nao precisa de `content` paths (auto-detection)
- v4: Import simples com `@import "tailwindcss"`
- v4: 10x mais rapido que v3 (engine reescrita em Rust)

---

## Passo 2.9 - Inicializar shadcn/ui

O shadcn/ui nao e uma biblioteca de componentes tradicional. Ele COPIA os componentes para o seu projeto. Voce tem controle total sobre o codigo.

```bash
# Dentro do container frontend
docker compose exec frontend sh

# Inicializar shadcn/ui
npx shadcn@latest init

# Selecione:
# Base color: Zinc
```

O comando vai:
- Criar `components.json` (configuracao do shadcn)
- Atualizar `globals.css` com variaveis CSS para temas
- Criar `src/lib/utils.ts` com a funcao `cn()` (class merge)
- Instalar dependencias: `class-variance-authority`, `clsx`, `tailwind-merge`, `lucide-react`

Agora instale os componentes que vamos usar:

```bash
# Componentes de formulario
npx shadcn@latest add button input label card

# Componentes de layout
npx shadcn@latest add sidebar separator skeleton tooltip avatar dropdown-menu

# Sair do container
exit
```

**Conceito - shadcn/ui vs Material UI vs Chakra UI:**

| Feature | shadcn/ui | MUI | Chakra |
|---|---|---|---|
| Filosofia | Copy & own | Install & use | Install & use |
| Bundle size | Zero (so o que usa) | Grande | Medio |
| Customizacao | Total (e seu codigo) | Temas | Temas |
| Tailwind | Nativo | Nao | Nao |
| Server Components | Sim | Parcial | Nao |

shadcn/ui e ideal para Next.js 15 porque funciona perfeitamente com Server Components e Tailwind CSS.

---

## Corrigir permissoes dos arquivos do frontend

Assim como no backend, os arquivos criados dentro do container Docker ficam com owner `root`. Corrija antes de criar/editar arquivos do frontend:

```bash
# Corrigir permissoes via container Docker (nao precisa de sudo)
docker compose exec frontend chown -R 1000:1000 /app
```

> **Dica:** Sempre que rodar `npm install` ou `npx shadcn` dentro do container, rode o `chown` novamente.

---

## Passo 2.10 - Instalar dependencias do frontend

```bash
# Dentro do container frontend
docker compose exec frontend npm install zustand react-hook-form @hookform/resolvers zod
```

| Pacote | Funcao |
|---|---|
| `zustand` | Estado global (alternativa leve ao Redux) |
| `react-hook-form` | Formularios performaticos |
| `@hookform/resolvers` | Integra Zod com React Hook Form |
| `zod` | Validacao de schemas TypeScript-first |

---

## Passo 2.11 - API Client

Crie `frontend/src/lib/api.ts`:

```typescript
type RequestOptions = RequestInit & {
  isServer?: boolean;
};

// Client-side: vai pelo Nginx (mesma origem)
const PUBLIC_API_URL = process.env.NEXT_PUBLIC_API_URL || "/api";

// Server-side: vai pela rede Docker interna
const INTERNAL_API_URL =
  process.env.INTERNAL_API_URL || "http://nginx:80/api";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public data?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestOptions = {},
): Promise<T> {
  const { isServer = false, headers: customHeaders, ...fetchOptions } = options;
  const baseUrl = isServer ? INTERNAL_API_URL : PUBLIC_API_URL;

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(customHeaders as Record<string, string>),
  };

  // Adicionar token JWT se disponivel (client-side)
  if (typeof window !== "undefined") {
    const token = localStorage.getItem("token");
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${baseUrl}${endpoint}`, {
    ...fetchOptions,
    headers,
  });

  if (!response.ok) {
    const data = await response.json().catch(() => null);
    throw new ApiError(response.status, data?.message || "Erro na requisicao", data);
  }

  return response.json();
}
```

**Conceito - Server-side vs Client-side API calls:**

```
Browser (Client-side):
  fetch("/api/v1/auth/me")  →  Nginx (:80)  →  PHP-FPM (:9000)

Next.js Container (Server-side / SSR):
  fetch("http://nginx:80/api/v1/auth/me")  →  Nginx (:80)  →  PHP-FPM (:9000)
```

O Next.js Server Components rodam DENTRO do container Docker. Eles nao podem acessar `localhost` (que aponta para o container do frontend). Por isso usamos `http://nginx:80/api` para SSR.

Adicione a variavel de ambiente no `docker-compose.yml`, na secao do frontend:

```yaml
  frontend:
    environment:
      INTERNAL_API_URL: http://nginx:80/api
```

---

## Passo 2.12 - Auth Store (Zustand)

Crie `frontend/src/stores/auth-store.ts`:

```typescript
import { create } from "zustand";
import { persist } from "zustand/middleware";
import { apiClient, ApiError } from "@/lib/api";

// Sync token com cookie para o middleware (server-side) conseguir ler
function setTokenCookie(token: string) {
  document.cookie = `token=${token}; path=/; max-age=${60 * 60}; SameSite=Lax`;
}

function removeTokenCookie() {
  document.cookie = "token=; path=/; max-age=0";
}

interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setToken: (token: string) => void;
  clear: () => void;
}

interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

interface UserResponse {
  data: User;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,
      isLoading: false,

      setToken: (token: string) => {
        localStorage.setItem("token", token);
        setTokenCookie(token);
        set({ token, isAuthenticated: true });
      },

      login: async (email: string, password: string) => {
        set({ isLoading: true });
        try {
          const response = await apiClient<LoginResponse>("/v1/auth/login", {
            method: "POST",
            body: JSON.stringify({ email, password }),
          });

          localStorage.setItem("token", response.access_token);
          setTokenCookie(response.access_token);
          set({
            token: response.access_token,
            isAuthenticated: true,
            isLoading: false,
          });

          // Buscar dados do usuario
          await get().fetchUser();
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchUser: async () => {
        try {
          const response = await apiClient<UserResponse>("/v1/auth/me");
          set({ user: response.data });
        } catch {
          get().clear();
        }
      },

      logout: async () => {
        try {
          await apiClient("/v1/auth/logout", { method: "POST" });
        } catch {
          // Limpar mesmo se der erro no backend
        }
        get().clear();
      },

      clear: () => {
        localStorage.removeItem("token");
        removeTokenCookie();
        set({ token: null, user: null, isAuthenticated: false });
      },
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({ token: state.token }),
    },
  ),
);
```

> **Por que o cookie?** O middleware do Next.js roda no servidor (Edge Runtime) e nao tem acesso ao `localStorage`. Para que ele saiba se o usuario esta autenticado, sincronizamos o token JWT como cookie. O `localStorage` continua sendo a fonte primaria para o client-side.

**Conceito - Zustand vs Redux:**
- Zustand: ~1KB, zero boilerplate, hooks nativos
- Redux: ~7KB, actions/reducers/store, Redux Toolkit necessario
- Para nosso caso (auth + theme), Zustand e mais que suficiente

---

## Passo 2.13 - Pagina de Login

Crie `frontend/src/app/login/page.tsx`:

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useAuthStore } from "@/stores/auth-store";
import { ApiError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const loginSchema = z.object({
  email: z.string().email("Informe um email valido"),
  password: z.string().min(6, "A senha deve ter no minimo 6 caracteres"),
});

type LoginForm = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const router = useRouter();
  const { login, isLoading } = useAuthStore();
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
      router.push("/dashboard");
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
          <CardTitle className="text-2xl font-bold">Orderly</CardTitle>
          <CardDescription>
            Acesse sua conta para gerenciar seu restaurante
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
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
                placeholder="admin@orderly.com"
                {...register("email")}
              />
              {errors.email && (
                <p className="text-sm text-destructive">{errors.email.message}</p>
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

            <Button type="submit" className="w-full" disabled={isLoading}>
              {isLoading ? "Entrando..." : "Entrar"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
```

**Conceito - `"use client"` no Next.js 15:**
Por padrao, componentes no App Router sao Server Components (renderizados no servidor). Quando precisamos de interatividade (useState, useEffect, event handlers), marcamos com `"use client"`. A pagina de login precisa de client-side porque tem formulario com estado.

---

## Passo 2.14 - Layout admin com sidebar

Crie o route group `(admin)` para agrupar paginas que compartilham o layout com sidebar.

Crie `frontend/src/app/(admin)/layout.tsx`:

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

> **Por que `"use client"` e `fetchUser()`?** O Zustand so persiste o `token` no localStorage (via `partialize`). Ao dar F5, o `user` e `null` ate que `fetchUser()` recarregue os dados via API. Sem isso, componentes como a sidebar condicional (Passo 5.12) nao renderizam os grupos corretos.

Crie `frontend/src/app/(admin)/dashboard/page.tsx`:

```tsx
export default function DashboardPage() {
  return (
    <div>
      <h1 className="text-3xl font-bold">Dashboard</h1>
      <p className="mt-2 text-muted-foreground">
        Bem-vindo ao painel administrativo do Orderly.
      </p>

      <div className="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[
          { title: "Pedidos Hoje", value: "0" },
          { title: "Faturamento", value: "R$ 0,00" },
          { title: "Clientes", value: "0" },
          { title: "Produtos", value: "0" },
        ].map((card) => (
          <div
            key={card.title}
            className="rounded-xl border bg-card p-6 shadow-sm"
          >
            <p className="text-sm text-muted-foreground">{card.title}</p>
            <p className="mt-1 text-2xl font-bold">{card.value}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

Crie `frontend/src/components/app-sidebar.tsx`:

```tsx
"use client";

import {
  LayoutDashboard,
  ShoppingBag,
  Users,
  UtensilsCrossed,
  QrCode,
  Star,
  Settings,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
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
} from "@/components/ui/sidebar";

const menuItems = [
  { title: "Dashboard", url: "/dashboard", icon: LayoutDashboard },
  { title: "Pedidos", url: "/orders", icon: ShoppingBag },
  { title: "Cardapio", url: "/products", icon: UtensilsCrossed },
  { title: "Clientes", url: "/customers", icon: Users },
  { title: "Mesas", url: "/tables", icon: QrCode },
  { title: "Avaliacoes", url: "/reviews", icon: Star },
  { title: "Configuracoes", url: "/settings", icon: Settings },
];

export function AppSidebar() {
  const pathname = usePathname();

  return (
    <Sidebar>
      <SidebarHeader className="border-b px-6 py-4">
        <h2 className="text-lg font-bold">Orderly</h2>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>Menu</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {menuItems.map((item) => (
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
      </SidebarContent>
    </Sidebar>
  );
}
```

Crie `frontend/src/components/app-header.tsx`:

```tsx
"use client";

import { useAuthStore } from "@/stores/auth-store";
import { useRouter } from "next/navigation";
import { SidebarTrigger } from "@/components/ui/sidebar";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";
import { LogOut } from "lucide-react";

export function AppHeader() {
  const { user, logout } = useAuthStore();
  const router = useRouter();

  const handleLogout = async () => {
    await logout();
    router.push("/login");
  };

  return (
    <header className="flex h-14 items-center gap-4 border-b px-6">
      <SidebarTrigger />
      <Separator orientation="vertical" className="h-6" />
      <div className="flex flex-1 items-center justify-between">
        <h1 className="text-sm font-medium">Painel Administrativo</h1>
        <div className="flex items-center gap-4">
          <span className="text-sm text-muted-foreground">
            {user?.name || "Carregando..."}
          </span>
          <Button variant="ghost" size="icon" onClick={handleLogout}>
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </header>
  );
}
```

**Conceito - Route Groups no Next.js 15:**
Pastas com parenteses `(admin)` agrupam rotas que compartilham layout SEM afetar a URL. A URL fica `/dashboard`, nao `/(admin)/dashboard`. Isso permite ter layouts diferentes para admin vs public.

---

## Passo 2.15 - Middleware de autenticacao (Next.js)

Crie `frontend/src/middleware.ts`:

```typescript
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const token = request.cookies.get("token")?.value;
  const { pathname } = request.nextUrl;

  const isLoginPage = pathname === "/login";
  const isProtectedRoute = pathname.startsWith("/dashboard") ||
    pathname.startsWith("/orders") ||
    pathname.startsWith("/products") ||
    pathname.startsWith("/customers") ||
    pathname.startsWith("/tables") ||
    pathname.startsWith("/reviews") ||
    pathname.startsWith("/settings");

  // Redirecionar para login se nao autenticado
  if (isProtectedRoute && !token) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  // Redirecionar para dashboard se ja autenticado
  if (isLoginPage && token) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/dashboard/:path*",
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

Atualize a home page `frontend/src/app/page.tsx` para redirecionar ao dashboard:

```tsx
import { redirect } from "next/navigation";

export default function HomePage() {
  redirect("/login");
}
```

---

## Passo 2.16 - Verificacao end-to-end

Agora vamos verificar tudo funciona de ponta a ponta.

### Reiniciar os servicos

```bash
docker compose down
docker compose up -d --build
```

### Testar backend

```bash
# 1. Health check
curl http://localhost/api/v1/auth/login -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@orderly.com","password":"password"}'

# Esperado: {"access_token":"eyJ...","token_type":"bearer","expires_in":3600}

# 2. Rota protegida (substitua o token)
curl http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"

# Esperado: {"data":{"id":1,"name":"Admin Orderly",...}}
```

### Testar frontend

1. Acesse `http://127.0.0.1` no navegador (use `127.0.0.1`, nao `localhost` - veja Troubleshooting)
2. Deve redirecionar para `/login`
3. Faca login com `admin@orderly.com` / `password`
4. Deve redirecionar para `/dashboard` com sidebar
5. Clique no icone de logout no header
6. Deve voltar para `/login`

---

## Resumo da Fase 2

**Arquivos criados/modificados no backend:**

```
backend/
├── app/
│   ├── Actions/Auth/LoginAction.php
│   ├── DTOs/Auth/LoginDTO.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/Auth/AuthController.php
│   │   ├── Requests/Auth/LoginRequest.php
│   │   └── Resources/UserResource.php
│   ├── Models/User.php (modificado - JWTSubject)
│   ├── Providers/RepositoryServiceProvider.php
│   └── Repositories/
│       ├── Contracts/UserRepositoryInterface.php
│       └── Eloquent/UserRepository.php
├── config/auth.php (modificado - JWT guard)
├── config/cors.php (modificado - frontend origin)
├── database/seeders/AdminUserSeeder.php
├── routes/api.php (modificado - rotas v1)
└── bootstrap/providers.php (modificado - RepositoryServiceProvider)
```

**Arquivos criados/modificados no frontend:**

```
frontend/
├── postcss.config.mjs
├── components.json (gerado pelo shadcn)
├── src/
│   ├── app/
│   │   ├── globals.css (Tailwind + shadcn theme)
│   │   ├── layout.tsx (modificado - imports CSS)
│   │   ├── page.tsx (modificado - redirect)
│   │   ├── login/page.tsx
│   │   └── (admin)/
│   │       ├── layout.tsx (client component — fetchUser on hydration)
│   │       └── dashboard/page.tsx
│   ├── components/
│   │   ├── ui/ (gerado pelo shadcn)
│   │   ├── app-sidebar.tsx
│   │   └── app-header.tsx
│   ├── lib/
│   │   ├── api.ts
│   │   └── utils.ts (gerado pelo shadcn)
│   ├── stores/
│   │   └── auth-store.ts
│   └── middleware.ts
```

**Conceitos aprendidos:**
- JWT Authentication (stateless, cloud-native)
- Clean Architecture Pragmatica (Actions, DTOs, Repositories)
- Dependency Inversion (interfaces + ServiceProvider)
- API versioning (v1 prefix)
- Tailwind CSS v4 (CSS-first config)
- shadcn/ui (copy & own components)
- Zustand (lightweight state management)
- React Hook Form + Zod (type-safe forms)
- Next.js Route Groups (admin layout)
- Next.js Middleware (auth protection)
- Server-side vs Client-side API calls

**Proximo:** Fase 3 - Multi-tenancy + Planos de Assinatura

---

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
- `#[ObservedBy]` — attribute do PHP 8.1+ que registra o Observer sem precisar de `AppServiceProvider`. Laravel 12 suporta nativamente.
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

Adicione `/plans` nas rotas protegidas do middleware `frontend/src/middleware.ts`:

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
import { zodResolver } from "@hookform/resolvers/zod";
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
    resolver: zodResolver(planSchema),
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
│   └── middleware.ts (modificado - rota /plans)
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
                                fill="hsl(var(--primary))"
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
- **`hsl(var(--primary))`** — reutiliza a cor primaria do tema shadcn/ui no grafico, mantendo consistencia visual

**Proximo:** Fase 10 - Testes (Unit, Integration, E2E)

---

*Projeto construido como tutorial progressivo. Cada fase adiciona novas funcionalidades e documenta os conceitos aprendidos.*
