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
        migrate seed fresh \
        kafka-topics kafka-consumers kafka-consume-orders

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
	@echo "  Frontend: http://127.0.0.1:3000"
	@echo "  Backend:  http://127.0.0.1:8000"
	@echo "  Nginx:    http://127.0.0.1"
	@echo ""

# ==========================================
# DOCKER
# ==========================================
up: ## Sobe o ambiente de desenvolvimento
	docker compose up -d

# ── Observabilidade ─────────────────────────────────────────────
monitoring-up: ## Subir stack de monitoramento (Prometheus + Grafana + Loki)
	docker compose --profile monitoring up -d
	@echo "$(GREEN)>>> Monitoring stack UP$(NC)"
	@echo "  Prometheus: http://127.0.0.1:9090"
	@echo "  Grafana:    http://127.0.0.1:3001 (admin/orderly123)"
	@echo "  Loki:       API interna (:3100) - consulte via Grafana Explore"

monitoring-down: ## Parar stack de monitoramento
	docker compose --profile monitoring down
	@echo "$(YELLOW)>>> Monitoring stack DOWN$(NC)"

monitoring-logs: ## Ver logs da stack de monitoramento
	docker compose --profile monitoring logs -f prometheus grafana loki promtail

monitoring-status: ## Status dos servicos de monitoramento
	@docker compose --profile monitoring ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

down: ## Para todos os containers (incluindo monitoring e e2e)
	docker compose --profile monitoring --profile e2e down

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
	docker compose exec frontend npx vitest run

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


# ==========================================
# CI/CD (local simulation)
# ==========================================
# ==========================================
# KAFKA
# ==========================================
kafka-topics: ## Listar topics do Kafka
	docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server localhost:9092 --list

kafka-consumers: ## Listar consumer groups e lag
	docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh --bootstrap-server localhost:9092 --list

kafka-consume-orders: ## Iniciar consumer de eventos de pedidos
	docker compose exec backend php artisan kafka:consume-orders

ci: lint test ## Simula o pipeline CI localmente
	@echo "$(GREEN)>>> CI passed!$(NC)"

ci-e2e: ## Roda testes E2E como no CI
	docker compose --profile e2e run --rm playwright npx playwright test

ci-build: ## Simula build de producao das imagens
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build
	@echo "$(GREEN)>>> Build de producao OK$(NC)"



# ==========================================
# TERRAFORM
# ==========================================
tf-init-dev: ## Inicializa Terraform (dev)
	cd terraform/environments/dev && terraform init

tf-plan-dev: ## Preview das mudancas (dev)
	cd terraform/environments/dev && terraform plan

tf-apply-dev: ## Aplica as mudancas na AWS (dev)
	cd terraform/environments/dev && terraform apply

tf-destroy-dev: ## Destroi a infra de dev (CUIDADO!)
	@echo "$(RED)>>> ATENCAO: Isso vai destruir TODA a infraestrutura de dev$(NC)"
	@read -p "Tem certeza? [y/N] " confirm && [ "$$confirm" = "y" ] && \
		cd terraform/environments/dev && terraform destroy || echo "Cancelado."

tf-init-prod: ## Inicializa Terraform (prod)
	cd terraform/environments/prod && terraform init

tf-plan-prod: ## Preview das mudancas (prod)
	cd terraform/environments/prod && terraform plan

tf-apply-prod: ## Aplica as mudancas na AWS (prod)
	cd terraform/environments/prod && terraform apply

# ==========================================
# KUBERNETES
# ==========================================
k8s-dev: ## Deploy no cluster dev
	kubectl apply -k k8s/overlays/dev

k8s-staging: ## Deploy no cluster staging
	kubectl apply -k k8s/overlays/staging

k8s-prod: ## Deploy no cluster prod
	kubectl apply -k k8s/overlays/prod

k8s-status: ## Status dos pods
	kubectl get pods -n orderly -o wide

k8s-logs-api: ## Logs do backend API
	kubectl logs -n orderly -l app.kubernetes.io/component=backend-api -f

k8s-logs-worker: ## Logs do backend worker
	kubectl logs -n orderly -l app.kubernetes.io/component=backend-worker -f

k8s-shell: ## Shell no backend API
	kubectl exec -n orderly -it deploy/backend-api -- sh

k8s-migrate: ## Rodar migrations no cluster
	kubectl exec -n orderly deploy/backend-api -- php artisan migrate --force

k8s-seed: ## Rodar seeders no cluster
	kubectl exec -n orderly deploy/backend-api -- php artisan db:seed --force