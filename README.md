![CI](https://github.com/diegocar448/laravelnextts/actions/workflows/ci.yml/badge.svg)
![E2E](https://github.com/diegocar448/laravelnextts/actions/workflows/e2e.yml/badge.svg)
![CD](https://github.com/diegocar448/laravelnextts/actions/workflows/cd.yml/badge.svg)

# Orderly - Tutorial de Construcao Passo a Passo

> Construindo uma plataforma SaaS multi-tenant de delivery com **Laravel 12** + **Next.js 16** + **Docker** do zero, como um arquiteto senior faria.

**Orderly** — porque todo grande restaurante precisa de ordem. Nos pedidos, no cardapio, na operacao.

Este repositorio e um tutorial progressivo. Cada fase documenta exatamente o que foi feito, por que foi feito, e como reproduzir. Apague tudo e reconstrua seguindo cada passo.

---

## Sobre o Projeto

Reescrita do [larafood_reescrito](https://github.com/diegocar448/larafood_reescrito) (Laravel 7 + Blade) com arquitetura moderna.

### Stack

| Camada | Tecnologia | Versao |
|---|---|---|
| Backend | Laravel (API-only) | 12.x |
| Frontend | Next.js + TypeScript | 16.x |
| UI | shadcn/ui + Tailwind CSS | latest |
| Banco | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Mensageria | Apache Kafka (KRaft) | 4.2 |
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
- [ ] Kubernetes manifests + Kustomize (Fase 12)
- [ ] Terraform modules (Fase 12)
- [x] CI/CD com GitHub Actions
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
- [ ] Observabilidade (Prometheus + Grafana + Loki) (Fase 13)
- [ ] Landing page publica (SSR)
- [x] Testes completos (Unit, Integration, E2E)
- [x] Documentacao API (OpenAPI/Swagger via Scramble)

---

## Tutorial - Fases

Cada fase esta documentada em um arquivo separado na pasta `docs/`:

| Fase | Titulo | Descricao |
|------|--------|-----------|
| [Fase 1](docs/fase-01-infraestrutura-docker.md) | Infraestrutura Docker | Dockerfiles, Nginx, docker-compose dev/prod, Makefile |
| [Fase 2](docs/fase-02-bootstrap-laravel-nextjs.md) | Bootstrap Laravel + Next.js | Laravel skeleton, JWT, shadcn/ui, Auth Store, Login page |
| [Fase 3](docs/fase-03-multi-tenancy-planos.md) | Multi-tenancy + Planos | Plans CRUD, Tenants, Global Scopes, frontend de Planos |
| [Fase 4](docs/fase-04-acl-permissoes.md) | ACL: Permissoes e Perfis | Permissions, Profiles, Roles, Middleware, Scramble docs |
| [Fase 5](docs/fase-05-catalogo.md) | Catalogo | Categories + Products CRUD, pivot, frontend completo |
| [Fase 6](docs/fase-06-mesas-qrcode.md) | Mesas com QR Code | Tables CRUD, geracao de QR Code, frontend |
| [Fase 7](docs/fase-07-sistema-pedidos.md) | Sistema de Pedidos | Orders, status flow, order_product, frontend |
| [Fase 8](docs/fase-08-clientes-avaliacoes.md) | Clientes + Avaliacoes | Client auth, order evaluations, frontend |
| [Fase 9](docs/fase-09-dashboard-metricas.md) | Dashboard com Metricas | Metrics API, Recharts, cards e graficos |
| [Fase 10](docs/fase-10-testes.md) | Testes | Pest, Vitest, Playwright, coverage |
| [Fase 11](docs/fase-11-cicd.md) | CI/CD GitHub Actions | Workflows CI/E2E/CD, branch protection, badges |
| [Fase 12](docs/fase-12-kubernetes-terraform.md) | Kubernetes + Terraform | EKS, Kustomize, Terraform modules, GitOps |
| [Fase 13](docs/fase-13-observabilidade.md) | Observabilidade | Prometheus, Grafana, Loki, logs estruturados, alertas |

---

## Quick Start

```bash
# 1. Clonar
git clone https://github.com/diegocar448/laravelnextts.git
cd laravelnextts

# 2. Copiar env
cp backend/.env.example backend/.env

# 3. Subir containers
make up

# 4. Setup inicial
make setup

# 5. Acessar
# Frontend: http://localhost
# Backend API: http://localhost/api
# Docs API: http://localhost/docs/api
```

### Credenciais padrao

| Tipo | Email | Senha |
|------|-------|-------|
| Admin | admin@orderly.com | password |
| Client | client@orderly.com | password |

### Comandos uteis

```bash
make help          # Lista todos os comandos
make up            # Subir containers
make down          # Parar containers
make logs          # Ver logs
make test          # Rodar todos os testes
make lint          # Rodar linters
make ci            # Simular pipeline CI
make ci-e2e        # Rodar testes E2E
```

---

*Projeto construido como tutorial progressivo. Cada fase adiciona novas funcionalidades e documenta os conceitos aprendidos.*
