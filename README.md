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
- [ ] Autenticacao JWT (Admin + Client)
- [ ] Multi-tenancy (single-db, tenant_id, Global Scopes)
- [ ] Planos de assinatura (CRUD + detalhes)
- [ ] ACL dupla camada (Plan->Profile->Permission + User->Role->Permission)
- [ ] Catalogo: Categories + Products (CRUD, tenant-scoped)
- [ ] Mesas com QR Code
- [ ] Sistema de Pedidos com Kafka
- [ ] Autenticacao de Clientes (JWT)
- [ ] Avaliacoes de Pedidos
- [ ] Dashboard com metricas
- [ ] Landing page publica (SSR)
- [ ] Testes completos (Unit, Integration, E2E)
- [ ] Documentacao API (OpenAPI/Swagger)

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
import { SidebarProvider, SidebarInset } from "@/components/ui/sidebar";
import { AppSidebar } from "@/components/app-sidebar";
import { AppHeader } from "@/components/app-header";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
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
│   │       ├── layout.tsx
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
}
```

**Nota:** Os models `DetailPlan` e `Tenant` serao criados nos proximos passos. O Laravel nao reclama de referencias a classes que ainda nao existem — so daria erro se voce tentasse usar essas relacoes agora.

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
            'details' => DetailPlanResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

**O que e `whenLoaded`?**
Se a relacao `details` foi carregada (via `->load('details')` ou `->with('details')`), inclui os detalhes na resposta. Senao, omite. Isso evita N+1 queries — os detalhes so aparecem quando voce pede explicitamente.

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

        $plan->load('details');

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

*Projeto construido como tutorial progressivo. Cada fase adiciona novas funcionalidades e documenta os conceitos aprendidos.*
