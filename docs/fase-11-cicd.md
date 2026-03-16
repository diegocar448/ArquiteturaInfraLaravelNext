## Fase 11 - CI/CD com GitHub Actions

> Automatizar a qualidade: cada push e pull request roda lint, testes e build automaticamente. O deploy so acontece se tudo passar.

### O que vamos construir

```
┌─────────────────────────────────────────────────────────────────┐
│                    GitHub Actions Pipeline                       │
│                                                                 │
│  Push/PR → Lint ──→ Test Backend ──→ Test Frontend ──→ Build    │
│              │          │                 │               │      │
│          Pint/ESLint  Pest+SQLite     Vitest          Docker    │
│                         │                               │      │
│                    E2E (Playwright)              Push to GHCR   │
│                                                  (main only)    │
└─────────────────────────────────────────────────────────────────┘
```

**Conceito:** CI (Continuous Integration) roda em cada push/PR para garantir que nada quebrou. CD (Continuous Delivery) publica imagens Docker quando o codigo chega na `main`. Separamos em **3 workflows**:

1. **`ci.yml`** — Lint + testes backend + testes frontend (roda em todo push/PR)
2. **`e2e.yml`** — Testes E2E com Playwright (roda em PRs para main)
3. **`cd.yml`** — Build e push de imagens Docker para GitHub Container Registry (roda no merge para main)

---

## Passo 11.1 - Conceito: GitHub Actions

### Vocabulario

| Termo | O que e |
|---|---|
| **Workflow** | Arquivo YAML em `.github/workflows/` que define um pipeline |
| **Trigger** | Evento que inicia o workflow (`push`, `pull_request`, `workflow_dispatch`) |
| **Job** | Grupo de steps que rodam na mesma maquina virtual |
| **Step** | Uma unica acao (rodar comando, usar action, etc.) |
| **Action** | Bloco reutilizavel da comunidade (ex: `actions/checkout`, `actions/setup-node`) |
| **Matrix** | Roda o mesmo job com diferentes configuracoes (ex: PHP 8.3 + 8.4) |
| **Artifact** | Arquivo gerado pelo job que pode ser baixado ou passado entre jobs |
| **Secret** | Variavel sensivel (token, senha) armazenada de forma segura no GitHub |
| **GHCR** | GitHub Container Registry — registro de imagens Docker do GitHub |

### Estrutura de diretorios

```
.github/
└── workflows/
    ├── ci.yml        # Lint + testes (push + PR)
    ├── e2e.yml       # Testes E2E com Playwright (PR para main)
    └── cd.yml        # Build + push Docker images (merge em main)
```

---

## Passo 11.2 - Workflow CI: Lint + Testes

### Criar o workflow

Crie `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [main, master, develop]
  pull_request:
    branches: [main, master]

concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  # ==========================================
  # LINT
  # ==========================================
  lint-backend:
    name: Lint Backend (Pint)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          tools: composer:v2

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: backend/vendor
          key: composer-${{ hashFiles('backend/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Pint
        run: ./vendor/bin/pint --test

  lint-frontend:
    name: Lint Frontend (ESLint + TypeScript)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: frontend
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: "npm"
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Run ESLint
        run: npm run lint

      - name: Run TypeScript check
        run: npm run type-check

  # ==========================================
  # BACKEND TESTS
  # ==========================================
  test-backend:
    name: Test Backend (Pest)
    runs-on: ubuntu-latest
    needs: lint-backend
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pdo_sqlite, mbstring, xml, bcmath
          coverage: pcov
          tools: composer:v2

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: backend/vendor
          key: composer-${{ hashFiles('backend/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate app key
        run: php artisan key:generate

      - name: Generate JWT secret
        run: php artisan jwt:secret --force

      - name: Run tests
        run: php artisan test --coverage --min=50
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: ":memory:"

  # ==========================================
  # FRONTEND TESTS
  # ==========================================
  test-frontend:
    name: Test Frontend (Vitest)
    runs-on: ubuntu-latest
    needs: lint-frontend
    defaults:
      run:
        working-directory: frontend
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: "npm"
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Run tests
        run: npx vitest run --coverage

      - name: Upload coverage
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: frontend-coverage
          path: frontend/coverage/
          retention-days: 7
```

> **Por que `needs: lint-backend`?** O job de testes so roda se o lint passar. Isso economiza minutos do GitHub Actions — nao faz sentido rodar testes se o codigo nem compila/formata corretamente.

> **Por que `concurrency` com `cancel-in-progress`?** Se voce fizer 3 pushes seguidos, so o ultimo roda. Evita desperdicio de minutos.

> **Por que `--min=50`?** Garante um minimo de 50% de cobertura de testes no backend. Conforme o projeto cresce, aumente esse valor.

---

## Passo 11.3 - Workflow E2E: Playwright

### Criar o workflow

Crie `.github/workflows/e2e.yml`:

```yaml
name: E2E Tests

on:
  pull_request:
    branches: [main, master]

concurrency:
  group: e2e-${{ github.ref }}
  cancel-in-progress: true

jobs:
  e2e:
    name: E2E (Playwright)
    runs-on: ubuntu-latest
    timeout-minutes: 15

    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: orderly
          POSTGRES_USER: orderly
          POSTGRES_PASSWORD: orderly
        ports:
          - 5432:5432
        options: >-
          --health-cmd="pg_isready -U orderly"
          --health-interval=5s
          --health-timeout=5s
          --health-retries=5

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=5s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      # ---- Backend setup ----
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pdo_pgsql, mbstring, xml, bcmath
          tools: composer:v2

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: backend/vendor
          key: composer-${{ hashFiles('backend/composer.lock') }}
          restore-keys: composer-

      - name: Install backend dependencies
        working-directory: backend
        run: composer install --no-interaction --prefer-dist

      - name: Setup backend
        working-directory: backend
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan jwt:secret --force
          php artisan migrate --force
          php artisan db:seed --force
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: orderly
          DB_USERNAME: orderly
          DB_PASSWORD: orderly
          REDIS_HOST: localhost

      - name: Start backend server
        working-directory: backend
        run: php artisan serve --host=0.0.0.0 --port=8000 &
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: orderly
          DB_USERNAME: orderly
          DB_PASSWORD: orderly
          REDIS_HOST: localhost

      # ---- Frontend setup ----
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: "npm"
          cache-dependency-path: frontend/package-lock.json

      - name: Install frontend dependencies
        working-directory: frontend
        run: npm ci

      - name: Install Playwright browsers
        working-directory: frontend
        run: npx playwright install --with-deps chromium

      - name: Build frontend
        working-directory: frontend
        run: npm run build
        env:
          NEXT_PUBLIC_API_URL: http://localhost:8000/api
          INTERNAL_API_URL: http://localhost:8000/api

      - name: Start frontend server
        working-directory: frontend
        run: npm start -- -p 3000 &
        env:
          NEXT_PUBLIC_API_URL: http://localhost:8000/api
          INTERNAL_API_URL: http://localhost:8000/api

      - name: Wait for servers
        run: |
          echo "Waiting for backend..."
          timeout 30 bash -c 'until curl -s http://localhost:8000/api/v1/auth/login > /dev/null 2>&1; do sleep 1; done' || true
          echo "Waiting for frontend..."
          timeout 30 bash -c 'until curl -s http://localhost:3000 > /dev/null 2>&1; do sleep 1; done' || true
          echo "Servers ready!"

      # ---- Run E2E tests ----
      - name: Run Playwright tests
        working-directory: frontend
        run: npx playwright test
        env:
          BASE_URL: http://localhost:3000

      - name: Upload Playwright report
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-report
          path: frontend/playwright-report/
          retention-days: 7

      - name: Upload test screenshots
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-screenshots
          path: frontend/test-results/
          retention-days: 7
```

> **Por que nao usar Docker Compose no CI?** No GitHub Actions, e mais rapido usar os services nativos (PostgreSQL, Redis) e rodar diretamente no runner. Isso evita build de imagens Docker e reduz o tempo de CI de ~10min para ~5min.

> **Por que so em PRs para main?** Testes E2E sao lentos (~3min). Rodar em cada push desperdicaria minutos. Eles servem como "gate" final antes do merge.

> **Por que `timeout-minutes: 15`?** Protege contra testes travados. Se o Playwright ficar pendurado, o job e cancelado apos 15 minutos.

---

## Passo 11.4 - Workflow CD: Build + Push Docker Images

### Criar o workflow

Crie `.github/workflows/cd.yml`:

```yaml
name: CD

on:
  push:
    branches: [main]

concurrency:
  group: cd-${{ github.ref }}
  cancel-in-progress: false

env:
  REGISTRY: ghcr.io
  BACKEND_IMAGE: ghcr.io/${{ github.repository }}/backend
  FRONTEND_IMAGE: ghcr.io/${{ github.repository }}/frontend

jobs:
  build-and-push:
    name: Build & Push Images
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - uses: actions/checkout@v4

      - name: Login to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Extract metadata
        id: meta
        run: |
          SHA_SHORT=$(git rev-parse --short HEAD)
          echo "sha_short=$SHA_SHORT" >> $GITHUB_OUTPUT
          echo "date=$(date -u +%Y%m%d)" >> $GITHUB_OUTPUT

      # ---- Backend image ----
      - name: Build & Push Backend
        uses: docker/build-push-action@v6
        with:
          context: .
          file: docker/php/Dockerfile
          target: production
          push: true
          tags: |
            ${{ env.BACKEND_IMAGE }}:latest
            ${{ env.BACKEND_IMAGE }}:${{ steps.meta.outputs.sha_short }}
            ${{ env.BACKEND_IMAGE }}:${{ steps.meta.outputs.date }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

      # ---- Frontend image ----
      - name: Build & Push Frontend
        uses: docker/build-push-action@v6
        with:
          context: .
          file: docker/node/Dockerfile
          target: production
          push: true
          tags: |
            ${{ env.FRONTEND_IMAGE }}:latest
            ${{ env.FRONTEND_IMAGE }}:${{ steps.meta.outputs.sha_short }}
            ${{ env.FRONTEND_IMAGE }}:${{ steps.meta.outputs.date }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          build-args: |
            NEXT_PUBLIC_API_URL=/api
```

> **Por que GHCR (GitHub Container Registry)?** E gratuito para repos publicos, integrado com o GitHub, e nao precisa de conta no Docker Hub. As imagens ficam em `ghcr.io/seu-usuario/orderly/backend:latest`.

> **Por que 3 tags?** `latest` para sempre ter a versao mais recente, `sha_short` para rastrear qual commit gerou a imagem, `date` para referencia temporal. Em producao, nunca use `latest` — use a tag do commit.

> **Por que `cancel-in-progress: false`?** Diferente do CI, nao queremos cancelar um deploy em andamento. Se dois merges acontecem rapidamente, ambos devem completar.

> **Por que `cache-from/to: type=gha`?** Usa o cache nativo do GitHub Actions para layers Docker. Reduz o tempo de build de ~5min para ~1min nas builds subsequentes.

---

## Passo 11.5 - Badges no README

### Adicionar status badges

No topo do README (antes do titulo), adicione os badges:

```markdown
![CI](https://github.com/diegocar448/laravelnextts/actions/workflows/ci.yml/badge.svg)
![E2E](https://github.com/diegocar448/laravelnextts/actions/workflows/e2e.yml/badge.svg)
![CD](https://github.com/diegocar448/laravelnextts/actions/workflows/cd.yml/badge.svg)
```

> Os badges so renderizam apos os workflows serem commitados e pushados. Ate la, aparecem como imagem quebrada — e normal.

> Os badges atualizam automaticamente conforme os workflows passam ou falham. E a primeira coisa que recrutadores e contribuidores olham num repositorio.

---

## Passo 11.6 - Protecao de branch (Branch Protection Rules)

### Configurar no GitHub

Va em **Settings → Branches → Add branch protection rule** para a branch `main`:

1. **Branch name pattern:** `main`
2. Marque:
   - [x] **Require a pull request before merging**
     - [x] Require approvals: 1 (ou 0 para projetos solo)
   - [x] **Require status checks to pass before merging**
     - Busque e adicione:
       - `Lint Backend (Pint)`
       - `Lint Frontend (ESLint + TypeScript)`
       - `Test Backend (Pest)`
       - `Test Frontend (Vitest)`
       - `E2E (Playwright)`
   - [x] **Require branches to be up to date before merging**
   - [x] **Do not allow bypassing the above settings**
3. Clique **Create**

> **O que isso faz?** Impede merge direto na `main`. Todo codigo precisa:
> 1. Estar em uma branch separada
> 2. Ter um PR aberto
> 3. Lint + testes passando
> 4. Branch atualizada com a `main`
>
> Isso garante que a `main` esta **sempre em estado deployavel**.

### Fluxo de trabalho recomendado

```
main (protegida)
  │
  ├── feature/nova-funcionalidade
  │     └── PR → CI ✅ → E2E ✅ → Review → Merge → CD (build images)
  │
  ├── fix/corrigir-bug
  │     └── PR → CI ✅ → E2E ✅ → Review → Merge → CD (build images)
  │
  └── develop (opcional, para staging)
```

### Quando cada workflow dispara

Cada workflow tem um **trigger** diferente. E fundamental entender quando cada um roda:

| Workflow | Trigger | Quando roda |
|---|---|---|
| `ci.yml` | `push` em `[main, develop]` + `pull_request` para `[main]` | Sempre que voce faz push nessas branches OU abre/atualiza um PR |
| `e2e.yml` | `pull_request` para `[main]` | Apenas quando existe um PR aberto para a main |
| `cd.yml` | `push` em `[main]` | Apenas quando codigo chega na main (merge de PR) |

### Exemplo pratico: ciclo completo de uma feature

Imagine que voce esta na branch `feature/minha-feature` e quer levar seu codigo ate producao. Aqui esta o fluxo passo a passo:

```
┌─────────────────────────────────────────────────────────────────────┐
│                     CICLO COMPLETO CI/CD                            │
│                                                                     │
│  1. Desenvolve na branch                                           │
│     git checkout -b feature/minha-feature                           │
│     (... codifica, testa localmente ...)                            │
│                                                                     │
│  2. Commit + Push                                                   │
│     git add .                                                       │
│     git commit -m "feat: minha feature"                             │
│     git push -u origin feature/minha-feature                        │
│     (nenhum workflow dispara — branch nao esta na lista do CI)      │
│                                                                     │
│  3. Abre PR para main                                               │
│     gh pr create --title "Minha Feature" --body "..."               │
│     ┌──────────────────────────────────────────┐                    │
│     │  CI roda automaticamente:                │                    │
│     │  ├── lint-backend (Pint)                 │                    │
│     │  ├── lint-frontend (ESLint + TypeScript) │                    │
│     │  ├── test-backend (Pest)                 │                    │
│     │  └── test-frontend (Vitest)              │                    │
│     │                                          │                    │
│     │  E2E roda automaticamente:               │                    │
│     │  └── Playwright (PostgreSQL + Redis)     │                    │
│     └──────────────────────────────────────────┘                    │
│                                                                     │
│  4. CI ✅ + E2E ✅ → Merge o PR                                     │
│     gh pr merge --squash                                            │
│     ┌──────────────────────────────────────────┐                    │
│     │  CD roda automaticamente:                │                    │
│     │  ├── Build imagem backend (production)   │                    │
│     │  ├── Build imagem frontend (production)  │                    │
│     │  └── Push para GHCR (3 tags cada)        │                    │
│     └──────────────────────────────────────────┘                    │
│                                                                     │
│  5. Volta para main atualizada                                      │
│     git checkout main                                               │
│     git pull origin main                                            │
│     git branch -d feature/minha-feature  (limpa branch local)      │
│                                                                     │
│  6. Imagens publicadas em:                                          │
│     ghcr.io/seu-usuario/repo/backend:latest                        │
│     ghcr.io/seu-usuario/repo/frontend:latest                       │
└─────────────────────────────────────────────────────────────────────┘
```

### Comandos Git na pratica

```bash
# === FASE 1: Desenvolver ===
git checkout -b feature/minha-feature
# (... faz as mudancas ...)

# === FASE 2: Commitar e enviar ===
git add arquivo1.php arquivo2.tsx          # Adicionar arquivos especificos
git commit -m "feat: descricao da mudanca" # Commitar com mensagem clara
git push -u origin feature/minha-feature   # Push (-u vincula branch ao remote)

# === FASE 3: Abrir PR ===
gh pr create \
  --title "feat: Minha Feature" \
  --body "## O que muda
- Adicionei X
- Corrigi Y

## Como testar
1. Faca login
2. Acesse /dashboard"

# === FASE 4: Acompanhar CI ===
gh run list                                # Ver workflows rodando
gh run watch                               # Acompanhar em tempo real
gh pr checks                               # Ver status dos checks no PR

# === FASE 5: Merge (apos CI + E2E verdes) ===
gh pr merge --squash --delete-branch       # Merge + limpa branch remota

# === FASE 6: Atualizar local ===
git checkout main                          # Voltar para main
git pull origin main                       # Puxar o merge
git branch -d feature/minha-feature        # Limpar branch local
```

> **Por que `--squash`?** Squash merge combina todos os commits da branch em um unico commit na main. Isso mantem o historico limpo — cada feature e um unico commit na main, facil de reverter se necessario.

> **Por que `--delete-branch`?** Limpa a branch remota apos o merge. Branches orfas poluem o repositorio. A branch local voce limpa com `git branch -d`.

> **Por que `git push -u` no primeiro push?** O `-u` (upstream) vincula a branch local ao remote. Depois disso, basta `git push` sem argumentos.

### E se o CI falhar?

```bash
# 1. Ver qual check falhou
gh pr checks

# 2. Ver logs do workflow que falhou
gh run list
gh run view <run-id> --log-failed

# 3. Corrigir localmente, commitar e push
# (o CI roda de novo automaticamente no PR)
git add .
git commit -m "fix: corrigir lint/testes"
git push
```

> O CI roda novamente em cada push na branch que tem PR aberto. Nao precisa fechar e reabrir o PR.

---

## Passo 11.7 - Secrets e variaveis de ambiente

### Secrets necessarios

Va em **Settings → Secrets and variables → Actions** no GitHub:

| Secret | Valor | Uso |
|---|---|---|
| `GITHUB_TOKEN` | (automatico) | Login no GHCR — ja disponivel em todo workflow |

> Para o escopo atual, **nao precisamos de secrets adicionais**. O `GITHUB_TOKEN` e gerado automaticamente pelo GitHub Actions e tem permissao para push no GHCR.

### Quando adicionar secrets futuros

| Cenario | Secret |
|---|---|
| Deploy em servidor (VPS/AWS) | `SSH_PRIVATE_KEY`, `DEPLOY_HOST` |
| Notificacao Slack | `SLACK_WEBHOOK_URL` |
| Sentry (error tracking) | `SENTRY_DSN` |
| AWS ECR (em vez de GHCR) | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` |

---

## Passo 11.8 - Atualizar Makefile

Adicione os comandos de CI/CD ao `Makefile`:

```makefile
# ==========================================
# CI/CD (local simulation)
# ==========================================
ci: lint test ## Simula o pipeline CI localmente
	@echo "$(GREEN)>>> CI passed!$(NC)"

ci-e2e: ## Roda testes E2E como no CI
	docker compose --profile e2e run --rm playwright npx playwright test

ci-build: ## Simula build de producao das imagens
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build
	@echo "$(GREEN)>>> Build de producao OK$(NC)"
```

> Rodar `make ci` antes de abrir um PR garante que o pipeline vai passar no GitHub Actions. E mais rapido testar localmente do que esperar o CI remoto.

---

## Passo 11.9 - Verificacao end-to-end da Fase 11

### Checklist de verificacao

**Workflows:**

- [ ] `.github/workflows/ci.yml` criado com jobs: lint-backend, lint-frontend, test-backend, test-frontend
- [ ] `.github/workflows/e2e.yml` criado com job: e2e (Playwright + PostgreSQL + Redis)
- [ ] `.github/workflows/cd.yml` criado com job: build-and-push (GHCR)
- [ ] `concurrency` configurado em todos os workflows
- [ ] Cache de Composer e npm configurado

**Validacao local:**

- [ ] `make lint` passa sem erros
- [ ] `make test` passa sem erros
- [ ] `make ci-e2e` passa sem erros
- [ ] `make ci` simula o pipeline completo

**GitHub:**

- [ ] Branch protection rules configuradas para `main`
- [ ] Status checks obrigatorios vinculados aos jobs
- [ ] Badges de status adicionados ao README

### Testar o pipeline localmente

```bash
# 1. Lint
make lint

# 2. Testes backend + frontend
make test

# 3. E2E
make ci-e2e

# 4. Simulacao completa do CI
make ci
```

### Testar no GitHub (apos push)

```bash
# Criar branch, commitar e abrir PR
git checkout -b feature/setup-ci-cd
git add .github/
git commit -m "feat: add CI/CD workflows with GitHub Actions"
git push -u origin feature/setup-ci-cd

# Abrir PR via CLI
gh pr create --title "Setup CI/CD with GitHub Actions" --body "Add CI, E2E and CD workflows"

# Acompanhar o status
gh run list
gh run watch
```

### Resumo dos arquivos da Fase 11

```
.github/
└── workflows/
    ├── ci.yml          # Lint + testes (push + PR)
    ├── e2e.yml         # Testes E2E com Playwright (PR para main)
    └── cd.yml          # Build + push Docker images (merge em main)
```

**Conceitos aprendidos:**
- **GitHub Actions** — CI/CD nativo do GitHub: workflows declarativos em YAML, triggered por eventos (push, PR, merge)
- **Job dependencies (`needs`)** — testes so rodam se lint passar, economizando minutos e dando feedback rapido
- **`concurrency` + `cancel-in-progress`** — evita execucoes duplicadas: multiplos pushes cancelam o anterior (CI) ou enfileiram (CD)
- **Services (PostgreSQL, Redis)** — containers efemeros no runner para testes de integracao, sem precisar de Docker Compose
- **Cache (Composer, npm, Docker layers)** — `actions/cache` e `cache-from: type=gha` reduzem tempo de build de minutos para segundos
- **GHCR (GitHub Container Registry)** — registro de imagens integrado ao GitHub, gratuito para repos publicos, sem vendor lock-in
- **Triple tagging (`latest`, `sha`, `date`)** — rastreabilidade completa: saber qual commit gerou qual imagem em producao
- **Branch protection** — impede merge sem CI verde, garantindo que `main` esta sempre deployavel
- **Artifacts** — relatorios de coverage e screenshots de falhas ficam disponiveis para download apos cada execucao

**Proximo:** Fase 12 - Kubernetes + Terraform (Cloud-Native)

---


---

[Voltar ao README](../README.md)
