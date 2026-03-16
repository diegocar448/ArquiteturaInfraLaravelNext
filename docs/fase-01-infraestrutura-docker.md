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


---

[Voltar ao README](../README.md)
