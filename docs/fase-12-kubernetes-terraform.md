# Fase 12 - Kubernetes + Terraform (Cloud-Native)

> Levar a aplicacao do Docker Compose local para um cluster Kubernetes na AWS, provisionado 100% via Terraform. Infraestrutura como codigo, escalavel, reproduzivel e profissional.

**Objetivo:** Provisionar toda a infraestrutura AWS com Terraform (VPC, EKS, RDS, ElastiCache, ECR) e deployar a aplicacao no Kubernetes com Kustomize, seguindo principios cloud-native.

**O que voce vai aprender:**
- Terraform: modulos reutilizaveis, state remoto, environments (dev/staging/prod)
- AWS: VPC, EKS (Kubernetes gerenciado), RDS PostgreSQL, ElastiCache Redis, ECR, ALB
- Kubernetes: Deployments, Services, Ingress, ConfigMaps, Secrets, HPA, CronJobs
- Kustomize: base + overlays para ambientes diferentes
- Principio de single-process container (separar PHP-FPM, Worker, Scheduler)
- GitOps: deploy automatizado via GitHub Actions + kubectl

**Pre-requisitos:**
- Terraform CLI instalado (`>= 1.6`)
- AWS CLI configurado com credenciais (`aws configure`)
- kubectl instalado
- Conta AWS com permissoes de administrador
- Fases 1-11 completas

---

## Passo 12.1 - Conceito: Cloud-Native e a jornada Docker → Kubernetes

### O que muda do Docker Compose para Kubernetes?

No Docker Compose, temos um unico servidor rodando todos os containers. Funciona para dev, mas em producao precisamos de:

| Necessidade | Docker Compose | Kubernetes |
|---|---|---|
| **Alta disponibilidade** | Unico ponto de falha | Pods distribuidos em multiplos nos |
| **Auto-scaling** | Manual | HPA escala automaticamente por CPU/memoria |
| **Self-healing** | `restart: always` | Kubelet reinicia, ReplicaSet recria pods |
| **Zero-downtime deploy** | Para e sobe (downtime) | Rolling update sem interromper |
| **Secrets** | Env vars em plain text | Secrets criptografados nativos |
| **Service discovery** | Rede bridge fixa | DNS interno automatico |
| **Load balancing** | Nginx manual | Ingress Controller + ALB |

### Arquitetura alvo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              AWS Cloud                                   │
│                                                                         │
│  ┌──────────────────────── VPC (10.0.0.0/16) ───────────────────────┐  │
│  │                                                                    │  │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │  │
│  │  │ Public Subnet    │  │ Public Subnet    │  │ Public Subnet    │  │  │
│  │  │   AZ-a           │  │   AZ-b           │  │   AZ-c           │  │  │
│  │  │ ┌─────────────┐  │  │ ┌─────────────┐  │  │ ┌─────────────┐  │  │  │
│  │  │ │ NAT Gateway │  │  │ │             │  │  │ │             │  │  │  │
│  │  │ └─────────────┘  │  │ └─────────────┘  │  │ └─────────────┘  │  │  │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘  │  │
│  │                                                                    │  │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │  │
│  │  │ Private Subnet   │  │ Private Subnet   │  │ Private Subnet   │  │  │
│  │  │   AZ-a           │  │   AZ-b           │  │   AZ-c           │  │  │
│  │  │ ┌─────────────┐  │  │ ┌─────────────┐  │  │ ┌─────────────┐  │  │  │
│  │  │ │ EKS Nodes   │  │  │ │ EKS Nodes   │  │  │ │ EKS Nodes   │  │  │  │
│  │  │ │ ┌─────────┐ │  │  │ │ ┌─────────┐ │  │  │ │ ┌─────────┐ │  │  │  │
│  │  │ │ │ Backend │ │  │  │ │ │Frontend │ │  │  │ │ │ Worker  │ │  │  │  │
│  │  │ │ │   API   │ │  │  │ │ │ Next.js │ │  │  │ │ │ Queue   │ │  │  │  │
│  │  │ │ └─────────┘ │  │  │ │ └─────────┘ │  │  │ │ └─────────┘ │  │  │  │
│  │  │ └─────────────┘  │  │ └─────────────┘  │  │ └─────────────┘  │  │  │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘  │  │
│  │                                                                    │  │
│  │  ┌─────────────────────┐  ┌─────────────────────┐                │  │
│  │  │ RDS PostgreSQL 16   │  │ ElastiCache Redis 7  │                │  │
│  │  │ (Multi-AZ)          │  │ (Cluster Mode)       │                │  │
│  │  └─────────────────────┘  └─────────────────────┘                │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  ┌─────────────┐  ┌──────────────────┐  ┌──────────────────────────┐  │
│  │ ECR         │  │ ALB              │  │ S3 (Terraform state)     │  │
│  │ (imagens)   │  │ (load balancer)  │  │                          │  │
│  └─────────────┘  └──────────────────┘  └──────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

### Principio: single-process container

No Docker Compose, usamos Supervisor para rodar PHP-FPM + Queue Worker + Scheduler no mesmo container. Em Kubernetes, **cada processo deve ser um workload separado**:

| Processo | Docker Compose | Kubernetes |
|---|---|---|
| PHP-FPM (API) | `supervisord` → `php-fpm` | Deployment `backend-api` |
| Queue Worker | `supervisord` → `queue:work` | Deployment `backend-worker` |
| Scheduler | `supervisord` → `schedule:run` | CronJob `backend-scheduler` |
| Next.js | Container unico | Deployment `frontend` |

> **Por que separar?** Cada workload escala independentemente. Se a fila de jobs cresce, escalamos apenas os workers. Se o trafego HTTP aumenta, escalamos apenas a API. O Scheduler roda a cada minuto via CronJob — nao precisa de um pod permanente.

---

## Passo 12.2 - Conceito: Terraform e Infraestrutura como Codigo

### Vocabulario

| Termo | O que e |
|---|---|
| **Provider** | Plugin que conecta o Terraform a um cloud provider (AWS, GCP, Azure) |
| **Resource** | Um recurso de infraestrutura (VPC, subnet, RDS, EKS...) |
| **Module** | Grupo reutilizavel de resources (ex: modulo `networking` cria VPC + subnets) |
| **State** | Arquivo que mapeia o que o Terraform gerencia vs o que existe na cloud |
| **Plan** | Preview das mudancas antes de aplicar (`terraform plan`) |
| **Apply** | Aplica as mudancas na cloud (`terraform apply`) |
| **Backend** | Onde o state e armazenado (local, S3, Terraform Cloud) |
| **Variables** | Parametros de entrada do modulo (ex: `region`, `instance_type`) |
| **Outputs** | Valores exportados pelo modulo (ex: `vpc_id`, `rds_endpoint`) |

### Estrutura de diretorios

```
terraform/
├── modules/                    # Modulos reutilizaveis (building blocks)
│   ├── networking/             # VPC, subnets, NAT, security groups
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   ├── database/               # RDS PostgreSQL
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   ├── cache/                  # ElastiCache Redis
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   ├── registry/               # ECR (container registry)
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   └── kubernetes/             # EKS cluster + node groups
│       ├── main.tf
│       ├── variables.tf
│       └── outputs.tf
└── environments/               # Composicao por ambiente
    ├── dev/
    │   ├── main.tf             # Chama os modulos com valores de dev
    │   ├── variables.tf
    │   ├── outputs.tf
    │   ├── backend.tf          # State no S3
    │   └── terraform.tfvars.example
    ├── staging/
    │   └── (mesma estrutura)
    └── prod/
        └── (mesma estrutura)
```

> **Por que modulos?** Mesma logica de "nao repita codigo" (DRY). O modulo `networking` e escrito uma vez e reutilizado em dev, staging e prod — mudando apenas os parametros (tamanho da instancia, numero de AZs, etc.).

> **Por que environments separados?** Cada ambiente tem seu proprio state file no S3. Isso significa que `terraform apply` no dev **nunca** afeta prod. Isolamento total.

---

## Passo 12.3 - Terraform: modulo networking (VPC + Subnets)

### O que esse modulo cria

O modulo `networking` e a fundacao de tudo. Sem rede, nenhum recurso AWS funciona.

```
VPC (10.0.0.0/16)
├── Public Subnets (3 AZs)    → ALB, NAT Gateway
├── Private Subnets (3 AZs)   → EKS nodes, RDS, ElastiCache
├── Internet Gateway           → Acesso a internet (public subnets)
├── NAT Gateway                → Saida de internet (private subnets)
└── Route Tables               → Roteamento entre subnets
```

### Criar o modulo

Crie `terraform/modules/networking/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto (usado como prefixo em todos os recursos)"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "vpc_cidr" {
  description = "CIDR block da VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "Lista de AZs para distribuir os recursos"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b", "us-east-1c"]
}

variable "tags" {
  description = "Tags adicionais para todos os recursos"
  type        = map(string)
  default     = {}
}
```

Crie `terraform/modules/networking/main.tf`:

```hcl
# ============================================
# VPC
# ============================================
resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-vpc"
  })
}

# ============================================
# SUBNETS
# ============================================
# Public subnets — usadas pelo ALB e NAT Gateway
resource "aws_subnet" "public" {
  count                   = length(var.availability_zones)
  vpc_id                  = aws_vpc.main.id
  cidr_block              = cidrsubnet(var.vpc_cidr, 8, count.index)
  availability_zone       = var.availability_zones[count.index]
  map_public_ip_on_launch = true

  tags = merge(var.tags, {
    Name                                          = "${var.project}-${var.environment}-public-${var.availability_zones[count.index]}"
    "kubernetes.io/role/elb"                       = "1"
    "kubernetes.io/cluster/${var.project}-${var.environment}" = "shared"
  })
}

# Private subnets — usadas pelo EKS, RDS, ElastiCache
resource "aws_subnet" "private" {
  count             = length(var.availability_zones)
  vpc_id            = aws_vpc.main.id
  cidr_block        = cidrsubnet(var.vpc_cidr, 8, count.index + 100)
  availability_zone = var.availability_zones[count.index]

  tags = merge(var.tags, {
    Name                                          = "${var.project}-${var.environment}-private-${var.availability_zones[count.index]}"
    "kubernetes.io/role/internal-elb"              = "1"
    "kubernetes.io/cluster/${var.project}-${var.environment}" = "shared"
  })
}

# ============================================
# INTERNET GATEWAY
# ============================================
# Permite que recursos em public subnets acessem a internet
resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-igw"
  })
}

# ============================================
# NAT GATEWAY
# ============================================
# Permite que recursos em private subnets acessem a internet
# (para baixar imagens Docker, atualizacoes, etc.)
# Em dev usamos 1 NAT; em prod usamos 1 por AZ para alta disponibilidade
resource "aws_eip" "nat" {
  domain = "vpc"

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-nat-eip"
  })
}

resource "aws_nat_gateway" "main" {
  allocation_id = aws_eip.nat.id
  subnet_id     = aws_subnet.public[0].id

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-nat"
  })

  depends_on = [aws_internet_gateway.main]
}

# ============================================
# ROUTE TABLES
# ============================================
# Public: trafego vai direto para o Internet Gateway
resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main.id
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-public-rt"
  })
}

resource "aws_route_table_association" "public" {
  count          = length(var.availability_zones)
  subnet_id      = aws_subnet.public[count.index].id
  route_table_id = aws_route_table.public.id
}

# Private: trafego sai pelo NAT Gateway
resource "aws_route_table" "private" {
  vpc_id = aws_vpc.main.id

  route {
    cidr_block     = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.main.id
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-private-rt"
  })
}

resource "aws_route_table_association" "private" {
  count          = length(var.availability_zones)
  subnet_id      = aws_subnet.private[count.index].id
  route_table_id = aws_route_table.private.id
}
```

Crie `terraform/modules/networking/outputs.tf`:

```hcl
output "vpc_id" {
  description = "ID da VPC criada"
  value       = aws_vpc.main.id
}

output "vpc_cidr" {
  description = "CIDR block da VPC"
  value       = aws_vpc.main.cidr_block
}

output "public_subnet_ids" {
  description = "IDs das subnets publicas"
  value       = aws_subnet.public[*].id
}

output "private_subnet_ids" {
  description = "IDs das subnets privadas"
  value       = aws_subnet.private[*].id
}

output "nat_gateway_ip" {
  description = "IP publico do NAT Gateway"
  value       = aws_eip.nat.public_ip
}
```

> **Por que 3 AZs?** A AWS recomenda no minimo 2 AZs para alta disponibilidade. Com 3, aguentamos a falha de uma AZ inteira sem impacto. O EKS distribui pods automaticamente entre as AZs.

> **Por que `cidrsubnet`?** Calcula automaticamente os blocos CIDR. `cidrsubnet("10.0.0.0/16", 8, 0)` gera `10.0.0.0/24`, `cidrsubnet("10.0.0.0/16", 8, 1)` gera `10.0.1.0/24`, etc. As private subnets comecam no index 100 para evitar colisao: `10.0.100.0/24`, `10.0.101.0/24`, `10.0.102.0/24`.

> **Por que tags com `kubernetes.io/role/elb`?** O AWS Load Balancer Controller do EKS usa essas tags para saber em quais subnets criar os ALBs. Sem elas, o Ingress nao funciona.

---

## Passo 12.4 - Terraform: modulo database (RDS PostgreSQL)

### O que esse modulo cria

O banco de dados PostgreSQL gerenciado pela AWS, com backups automaticos, Multi-AZ em producao, e security group dedicado.

### Criar o modulo

Crie `terraform/modules/database/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "vpc_id" {
  description = "ID da VPC"
  type        = string
}

variable "subnet_ids" {
  description = "IDs das subnets privadas para o RDS"
  type        = list(string)
}

variable "instance_class" {
  description = "Tipo da instancia RDS"
  type        = string
  default     = "db.t3.micro"
}

variable "allocated_storage" {
  description = "Armazenamento em GB"
  type        = number
  default     = 20
}

variable "db_name" {
  description = "Nome do banco de dados"
  type        = string
  default     = "orderly"
}

variable "db_username" {
  description = "Usuario master do banco"
  type        = string
  default     = "orderly"
}

variable "db_password" {
  description = "Senha do usuario master (usar tfvars ou secrets manager)"
  type        = string
  sensitive   = true
}

variable "multi_az" {
  description = "Habilitar Multi-AZ (recomendado para prod)"
  type        = bool
  default     = false
}

variable "allowed_security_group_ids" {
  description = "Security groups que podem acessar o RDS (ex: EKS nodes)"
  type        = list(string)
  default     = []
}

variable "tags" {
  description = "Tags adicionais"
  type        = map(string)
  default     = {}
}
```

Crie `terraform/modules/database/main.tf`:

```hcl
# ============================================
# SECURITY GROUP
# ============================================
# Permite acesso ao PostgreSQL (5432) apenas dos security groups informados
resource "aws_security_group" "rds" {
  name_prefix = "${var.project}-${var.environment}-rds-"
  description = "Security group for RDS PostgreSQL"
  vpc_id      = var.vpc_id

  ingress {
    description     = "PostgreSQL from EKS"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = var.allowed_security_group_ids
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-rds-sg"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# SUBNET GROUP
# ============================================
# O RDS precisa de subnets em pelo menos 2 AZs
resource "aws_db_subnet_group" "main" {
  name       = "${var.project}-${var.environment}-rds"
  subnet_ids = var.subnet_ids

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-rds-subnet-group"
  })
}

# ============================================
# PARAMETER GROUP
# ============================================
# Configuracoes customizadas do PostgreSQL
resource "aws_db_parameter_group" "main" {
  name_prefix = "${var.project}-${var.environment}-pg16-"
  family      = "postgres16"
  description = "Custom parameter group for Orderly"

  # Log queries lentas (> 1 segundo)
  parameter {
    name  = "log_min_duration_statement"
    value = "1000"
  }

  # Encoding UTF-8
  parameter {
    name  = "client_encoding"
    value = "UTF8"
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-pg-params"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# RDS INSTANCE
# ============================================
resource "aws_db_instance" "main" {
  identifier = "${var.project}-${var.environment}-pg"

  # Engine
  engine               = "postgres"
  engine_version       = "16.4"
  instance_class       = var.instance_class
  parameter_group_name = aws_db_parameter_group.main.name

  # Storage
  allocated_storage     = var.allocated_storage
  max_allocated_storage = var.allocated_storage * 2
  storage_type          = "gp3"
  storage_encrypted     = true

  # Database
  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  # Network
  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false

  # High availability
  multi_az = var.multi_az

  # Backup
  backup_retention_period = var.environment == "prod" ? 14 : 3
  backup_window           = "03:00-04:00"
  maintenance_window      = "sun:04:00-sun:05:00"

  # Protection
  deletion_protection = var.environment == "prod" ? true : false
  skip_final_snapshot = var.environment == "prod" ? false : true
  final_snapshot_identifier = var.environment == "prod" ? "${var.project}-${var.environment}-final-snapshot" : null

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-postgres"
  })
}
```

Crie `terraform/modules/database/outputs.tf`:

```hcl
output "endpoint" {
  description = "Endpoint de conexao do RDS (host:port)"
  value       = aws_db_instance.main.endpoint
}

output "host" {
  description = "Hostname do RDS"
  value       = aws_db_instance.main.address
}

output "port" {
  description = "Porta do RDS"
  value       = aws_db_instance.main.port
}

output "db_name" {
  description = "Nome do banco de dados"
  value       = aws_db_instance.main.db_name
}

output "security_group_id" {
  description = "ID do security group do RDS"
  value       = aws_security_group.rds.id
}
```

> **Por que `sensitive = true` na senha?** O Terraform oculta valores marcados como sensitive nos logs de plan/apply. A senha nunca aparece no terminal.

> **Por que `max_allocated_storage = allocated_storage * 2`?** Habilita auto-scaling de storage. Se o disco encher, o RDS aumenta automaticamente ate o dobro sem downtime.

> **Por que `gp3` em vez de `gp2`?** O gp3 e 20% mais barato que gp2 e permite configurar IOPS e throughput independentemente.

---

## Passo 12.5 - Terraform: modulo cache (ElastiCache Redis)

### Criar o modulo

Crie `terraform/modules/cache/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "vpc_id" {
  description = "ID da VPC"
  type        = string
}

variable "subnet_ids" {
  description = "IDs das subnets privadas"
  type        = list(string)
}

variable "node_type" {
  description = "Tipo do node ElastiCache"
  type        = string
  default     = "cache.t3.micro"
}

variable "num_cache_nodes" {
  description = "Numero de nodes no cluster"
  type        = number
  default     = 1
}

variable "allowed_security_group_ids" {
  description = "Security groups que podem acessar o Redis"
  type        = list(string)
  default     = []
}

variable "tags" {
  description = "Tags adicionais"
  type        = map(string)
  default     = {}
}
```

Crie `terraform/modules/cache/main.tf`:

```hcl
# ============================================
# SECURITY GROUP
# ============================================
resource "aws_security_group" "redis" {
  name_prefix = "${var.project}-${var.environment}-redis-"
  description = "Security group for ElastiCache Redis"
  vpc_id      = var.vpc_id

  ingress {
    description     = "Redis from EKS"
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = var.allowed_security_group_ids
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-redis-sg"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# SUBNET GROUP
# ============================================
resource "aws_elasticache_subnet_group" "main" {
  name       = "${var.project}-${var.environment}-redis"
  subnet_ids = var.subnet_ids

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-redis-subnet-group"
  })
}

# ============================================
# PARAMETER GROUP
# ============================================
resource "aws_elasticache_parameter_group" "main" {
  name   = "${var.project}-${var.environment}-redis7"
  family = "redis7"

  # Politica de evicao: remove chaves menos usadas quando a memoria enche
  parameter {
    name  = "maxmemory-policy"
    value = "allkeys-lru"
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-redis-params"
  })
}

# ============================================
# ELASTICACHE CLUSTER
# ============================================
resource "aws_elasticache_cluster" "main" {
  cluster_id           = "${var.project}-${var.environment}-redis"
  engine               = "redis"
  engine_version       = "7.1"
  node_type            = var.node_type
  num_cache_nodes      = var.num_cache_nodes
  parameter_group_name = aws_elasticache_parameter_group.main.name
  subnet_group_name    = aws_elasticache_subnet_group.main.name
  security_group_ids   = [aws_security_group.redis.id]

  port = 6379

  # Snapshot (backup) — apenas em prod
  snapshot_retention_limit = var.environment == "prod" ? 7 : 0

  maintenance_window = "sun:05:00-sun:06:00"

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-redis"
  })
}
```

Crie `terraform/modules/cache/outputs.tf`:

```hcl
output "endpoint" {
  description = "Endpoint de conexao do Redis"
  value       = aws_elasticache_cluster.main.cache_nodes[0].address
}

output "port" {
  description = "Porta do Redis"
  value       = aws_elasticache_cluster.main.port
}

output "security_group_id" {
  description = "ID do security group do Redis"
  value       = aws_security_group.redis.id
}
```

> **Por que ElastiCache em vez de Redis no Kubernetes?** O Redis e stateful (dados em memoria). Gerenciar failover, persistencia e backup de um Redis dentro do K8s e complexo. O ElastiCache faz isso automaticamente — menos infra para manter.

---

## Passo 12.6 - Terraform: modulo registry (ECR)

### O que esse modulo cria

O ECR (Elastic Container Registry) armazena as imagens Docker do backend e frontend. E o "Docker Hub privado" da AWS, integrado nativamente com o EKS.

### Criar o modulo

Crie `terraform/modules/registry/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "image_names" {
  description = "Nomes dos repositorios de imagens"
  type        = list(string)
  default     = ["backend", "frontend"]
}

variable "image_retention_count" {
  description = "Numero de imagens a manter por repositorio"
  type        = number
  default     = 10
}

variable "tags" {
  description = "Tags adicionais"
  type        = map(string)
  default     = {}
}
```

Crie `terraform/modules/registry/main.tf`:

```hcl
# ============================================
# ECR REPOSITORIES
# ============================================
# Um repositorio para cada imagem (backend, frontend)
resource "aws_ecr_repository" "main" {
  for_each = toset(var.image_names)

  name                 = "${var.project}/${each.value}"
  image_tag_mutability = "MUTABLE"
  force_delete         = var.environment != "prod"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${each.value}"
  })
}

# ============================================
# LIFECYCLE POLICY
# ============================================
# Limpa imagens antigas automaticamente (evita custo infinito)
resource "aws_ecr_lifecycle_policy" "main" {
  for_each   = toset(var.image_names)
  repository = aws_ecr_repository.main[each.value].name

  policy = jsonencode({
    rules = [
      {
        rulePriority = 1
        description  = "Keep last ${var.image_retention_count} images"
        selection = {
          tagStatus   = "any"
          countType   = "imageCountMoreThan"
          countNumber = var.image_retention_count
        }
        action = {
          type = "expire"
        }
      }
    ]
  })
}
```

Crie `terraform/modules/registry/outputs.tf`:

```hcl
output "repository_urls" {
  description = "URLs dos repositorios ECR (map: nome -> url)"
  value       = { for name, repo in aws_ecr_repository.main : name => repo.repository_url }
}

output "registry_id" {
  description = "ID do registry (AWS account ID)"
  value       = values(aws_ecr_repository.main)[0].registry_id
}
```

> **Por que `scan_on_push = true`?** O ECR escaneia cada imagem por vulnerabilidades (CVEs) automaticamente. Voce ve o resultado no console da AWS e pode bloquear deploys de imagens vulneraveis.

> **Por que lifecycle policy?** Sem ela, o ECR acumula centenas de imagens ao longo do tempo. Com a policy, mantemos apenas as ultimas 10 e as antigas sao removidas automaticamente.

---

## Passo 12.7 - Terraform: modulo kubernetes (EKS)

### O que esse modulo cria

O EKS (Elastic Kubernetes Service) e o Kubernetes gerenciado da AWS. A AWS cuida do control plane (API server, etcd, scheduler); nos so gerenciamos os worker nodes.

### Criar o modulo

Crie `terraform/modules/kubernetes/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "vpc_id" {
  description = "ID da VPC"
  type        = string
}

variable "public_subnet_ids" {
  description = "IDs das subnets publicas (para o ALB)"
  type        = list(string)
}

variable "private_subnet_ids" {
  description = "IDs das subnets privadas (para os nodes)"
  type        = list(string)
}

variable "kubernetes_version" {
  description = "Versao do Kubernetes"
  type        = string
  default     = "1.31"
}

variable "node_instance_types" {
  description = "Tipos de instancia para os worker nodes"
  type        = list(string)
  default     = ["t3.medium"]
}

variable "node_desired_size" {
  description = "Numero desejado de nodes"
  type        = number
  default     = 2
}

variable "node_min_size" {
  description = "Numero minimo de nodes"
  type        = number
  default     = 1
}

variable "node_max_size" {
  description = "Numero maximo de nodes"
  type        = number
  default     = 4
}

variable "tags" {
  description = "Tags adicionais"
  type        = map(string)
  default     = {}
}
```

Crie `terraform/modules/kubernetes/main.tf`:

```hcl
# ============================================
# IAM ROLE - EKS CLUSTER
# ============================================
# O EKS precisa de uma IAM Role para gerenciar recursos AWS
resource "aws_iam_role" "eks_cluster" {
  name = "${var.project}-${var.environment}-eks-cluster-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "eks.amazonaws.com"
      }
    }]
  })

  tags = var.tags
}

resource "aws_iam_role_policy_attachment" "eks_cluster_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSClusterPolicy"
  role       = aws_iam_role.eks_cluster.name
}

resource "aws_iam_role_policy_attachment" "eks_vpc_resource_controller" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSVPCResourceController"
  role       = aws_iam_role.eks_cluster.name
}

# ============================================
# IAM ROLE - NODE GROUP
# ============================================
# Os worker nodes precisam de suas proprias permissoes
resource "aws_iam_role" "eks_nodes" {
  name = "${var.project}-${var.environment}-eks-node-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "ec2.amazonaws.com"
      }
    }]
  })

  tags = var.tags
}

resource "aws_iam_role_policy_attachment" "eks_worker_node_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSWorkerNodePolicy"
  role       = aws_iam_role.eks_nodes.name
}

resource "aws_iam_role_policy_attachment" "eks_cni_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKS_CNI_Policy"
  role       = aws_iam_role.eks_nodes.name
}

resource "aws_iam_role_policy_attachment" "ecr_read_only" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
  role       = aws_iam_role.eks_nodes.name
}

# ============================================
# SECURITY GROUP - EKS CLUSTER
# ============================================
resource "aws_security_group" "eks" {
  name_prefix = "${var.project}-${var.environment}-eks-"
  description = "Security group for EKS cluster"
  vpc_id      = var.vpc_id

  ingress {
    description = "HTTPS from VPC"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-eks-sg"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# EKS CLUSTER
# ============================================
resource "aws_eks_cluster" "main" {
  name     = "${var.project}-${var.environment}"
  version  = var.kubernetes_version
  role_arn = aws_iam_role.eks_cluster.arn

  vpc_config {
    subnet_ids              = concat(var.public_subnet_ids, var.private_subnet_ids)
    security_group_ids      = [aws_security_group.eks.id]
    endpoint_private_access = true
    endpoint_public_access  = true
  }

  # Habilitar logs do control plane
  enabled_cluster_log_types = ["api", "audit", "authenticator"]

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-eks"
  })

  depends_on = [
    aws_iam_role_policy_attachment.eks_cluster_policy,
    aws_iam_role_policy_attachment.eks_vpc_resource_controller,
  ]
}

# ============================================
# EKS NODE GROUP (Managed)
# ============================================
resource "aws_eks_node_group" "main" {
  cluster_name    = aws_eks_cluster.main.name
  node_group_name = "${var.project}-${var.environment}-nodes"
  node_role_arn   = aws_iam_role.eks_nodes.arn
  subnet_ids      = var.private_subnet_ids
  instance_types  = var.node_instance_types

  scaling_config {
    desired_size = var.node_desired_size
    min_size     = var.node_min_size
    max_size     = var.node_max_size
  }

  update_config {
    max_unavailable = 1
  }

  # AMI otimizada para EKS (gerenciada pela AWS)
  ami_type = "AL2023_x86_64_STANDARD"

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-eks-nodes"
  })

  depends_on = [
    aws_iam_role_policy_attachment.eks_worker_node_policy,
    aws_iam_role_policy_attachment.eks_cni_policy,
    aws_iam_role_policy_attachment.ecr_read_only,
  ]
}
```

Crie `terraform/modules/kubernetes/outputs.tf`:

```hcl
output "cluster_name" {
  description = "Nome do cluster EKS"
  value       = aws_eks_cluster.main.name
}

output "cluster_endpoint" {
  description = "Endpoint da API do cluster"
  value       = aws_eks_cluster.main.endpoint
}

output "cluster_certificate_authority" {
  description = "Certificado CA do cluster (base64)"
  value       = aws_eks_cluster.main.certificate_authority[0].data
}

output "node_security_group_id" {
  description = "Security group dos worker nodes"
  value       = aws_security_group.eks.id
}

output "cluster_oidc_issuer" {
  description = "OIDC issuer do cluster (para IRSA)"
  value       = aws_eks_cluster.main.identity[0].oidc[0].issuer
}
```

> **Por que managed node groups?** A AWS gerencia o lifecycle dos EC2 (patching, draining, replacement). Voce so define min/max/desired e o tipo de instancia. Menos ops manual.

> **Por que `t3.medium`?** Para dev e bom custo-beneficio (2 vCPU, 4GB RAM). Em prod, considere `t3.large` ou `m5.large` dependendo da carga.

> **Por que logs `api`, `audit`, `authenticator`?** Sao os logs do control plane do EKS. O `audit` registra quem fez o que no cluster — essencial para seguranca e compliance.

---

## Passo 12.8 - Terraform: environment dev (composicao dos modulos)

### Conceito: composicao

Os modules sao building blocks. O environment **compoe** eles com valores especificos. Dev usa instancias pequenas e baratas; prod usa instancias maiores com Multi-AZ.

### Criar o environment dev

Crie `terraform/environments/dev/backend.tf`:

```hcl
# ============================================
# TERRAFORM BACKEND - S3
# ============================================
# O state do Terraform e armazenado no S3 (nao localmente)
# Isso permite que a equipe inteira use o mesmo state
terraform {
  required_version = ">= 1.6"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Descomente apos criar o bucket S3 manualmente:
  # backend "s3" {
  #   bucket         = "orderly-terraform-state"
  #   key            = "dev/terraform.tfstate"
  #   region         = "us-east-1"
  #   encrypt        = true
  #   dynamodb_table = "orderly-terraform-locks"
  # }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Project     = var.project
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}
```

Crie `terraform/environments/dev/variables.tf`:

```hcl
variable "project" {
  description = "Nome do projeto"
  type        = string
  default     = "orderly"
}

variable "environment" {
  description = "Ambiente"
  type        = string
  default     = "dev"
}

variable "region" {
  description = "Regiao AWS"
  type        = string
  default     = "us-east-1"
}

variable "db_password" {
  description = "Senha do banco de dados"
  type        = string
  sensitive   = true
}
```

Crie `terraform/environments/dev/main.tf`:

```hcl
# ============================================
# COMPOSICAO DE MODULOS - AMBIENTE DEV
# ============================================
# Aqui chamamos cada modulo com os valores de dev.
# Dev usa instancias pequenas, single-AZ, sem Multi-AZ.

locals {
  tags = {
    Project     = var.project
    Environment = var.environment
  }
}

# --- Networking ---
module "networking" {
  source = "../../modules/networking"

  project            = var.project
  environment        = var.environment
  vpc_cidr           = "10.0.0.0/16"
  availability_zones = ["us-east-1a", "us-east-1b", "us-east-1c"]
  tags               = local.tags
}

# --- Database (RDS PostgreSQL) ---
module "database" {
  source = "../../modules/database"

  project                    = var.project
  environment                = var.environment
  vpc_id                     = module.networking.vpc_id
  subnet_ids                 = module.networking.private_subnet_ids
  instance_class             = "db.t3.micro"
  allocated_storage          = 20
  db_password                = var.db_password
  multi_az                   = false
  allowed_security_group_ids = [module.kubernetes.node_security_group_id]
  tags                       = local.tags
}

# --- Cache (ElastiCache Redis) ---
module "cache" {
  source = "../../modules/cache"

  project                    = var.project
  environment                = var.environment
  vpc_id                     = module.networking.vpc_id
  subnet_ids                 = module.networking.private_subnet_ids
  node_type                  = "cache.t3.micro"
  num_cache_nodes            = 1
  allowed_security_group_ids = [module.kubernetes.node_security_group_id]
  tags                       = local.tags
}

# --- Registry (ECR) ---
module "registry" {
  source = "../../modules/registry"

  project               = var.project
  environment           = var.environment
  image_names           = ["backend", "frontend"]
  image_retention_count = 5
  tags                  = local.tags
}

# --- Kubernetes (EKS) ---
module "kubernetes" {
  source = "../../modules/kubernetes"

  project             = var.project
  environment         = var.environment
  vpc_id              = module.networking.vpc_id
  public_subnet_ids   = module.networking.public_subnet_ids
  private_subnet_ids  = module.networking.private_subnet_ids
  kubernetes_version  = "1.31"
  node_instance_types = ["t3.medium"]
  node_desired_size   = 2
  node_min_size       = 1
  node_max_size       = 3
  tags                = local.tags
}
```

Crie `terraform/environments/dev/outputs.tf`:

```hcl
output "vpc_id" {
  value = module.networking.vpc_id
}

output "eks_cluster_name" {
  value = module.kubernetes.cluster_name
}

output "eks_cluster_endpoint" {
  value = module.kubernetes.cluster_endpoint
}

output "rds_endpoint" {
  value = module.database.endpoint
}

output "redis_endpoint" {
  value = module.cache.endpoint
}

output "ecr_urls" {
  value = module.registry.repository_urls
}
```

Crie `terraform/environments/dev/terraform.tfvars.example`:

```hcl
# Copie este arquivo para terraform.tfvars e preencha:
# cp terraform.tfvars.example terraform.tfvars

project     = "orderly"
environment = "dev"
region      = "us-east-1"
db_password = "CHANGE_ME_use_a_strong_password"
```

### Como usar

```bash
cd terraform/environments/dev

# 1. Copiar variaveis
cp terraform.tfvars.example terraform.tfvars
# Edite terraform.tfvars com sua senha do banco

# 2. Inicializar (baixa os providers)
terraform init

# 3. Planejar (preview das mudancas)
terraform plan

# 4. Aplicar (cria os recursos na AWS)
terraform apply

# 5. Configurar kubectl para o cluster EKS
aws eks update-kubeconfig --region us-east-1 --name orderly-dev
```

> **Por que `terraform plan` antes de `apply`?** SEMPRE revise o plan antes de aplicar. Ele mostra exatamente o que sera criado, modificado ou destruido. Em producao, um `apply` sem `plan` pode derrubar tudo.

---

## Passo 12.9 - Terraform: environment prod (composicao dos modulos)

### Diferencas do dev

O environment prod reutiliza os mesmos modulos, mas com valores maiores e mais resiliencia.

Crie `terraform/environments/prod/backend.tf`:

```hcl
terraform {
  required_version = ">= 1.6"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Descomente apos criar o bucket S3:
  # backend "s3" {
  #   bucket         = "orderly-terraform-state"
  #   key            = "prod/terraform.tfstate"
  #   region         = "us-east-1"
  #   encrypt        = true
  #   dynamodb_table = "orderly-terraform-locks"
  # }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Project     = var.project
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}
```

Crie `terraform/environments/prod/variables.tf`:

```hcl
variable "project" {
  type    = string
  default = "orderly"
}

variable "environment" {
  type    = string
  default = "prod"
}

variable "region" {
  type    = string
  default = "us-east-1"
}

variable "db_password" {
  type      = string
  sensitive = true
}
```

Crie `terraform/environments/prod/main.tf`:

```hcl
# ============================================
# COMPOSICAO DE MODULOS - AMBIENTE PROD
# ============================================
# Prod usa instancias maiores, Multi-AZ, mais replicas e protecoes extras.

locals {
  tags = {
    Project     = var.project
    Environment = var.environment
  }
}

module "networking" {
  source = "../../modules/networking"

  project            = var.project
  environment        = var.environment
  vpc_cidr           = "10.1.0.0/16"
  availability_zones = ["us-east-1a", "us-east-1b", "us-east-1c"]
  tags               = local.tags
}

module "database" {
  source = "../../modules/database"

  project                    = var.project
  environment                = var.environment
  vpc_id                     = module.networking.vpc_id
  subnet_ids                 = module.networking.private_subnet_ids
  instance_class             = "db.t3.medium"
  allocated_storage          = 50
  db_password                = var.db_password
  multi_az                   = true
  allowed_security_group_ids = [module.kubernetes.node_security_group_id]
  tags                       = local.tags
}

module "cache" {
  source = "../../modules/cache"

  project                    = var.project
  environment                = var.environment
  vpc_id                     = module.networking.vpc_id
  subnet_ids                 = module.networking.private_subnet_ids
  node_type                  = "cache.t3.small"
  num_cache_nodes            = 2
  allowed_security_group_ids = [module.kubernetes.node_security_group_id]
  tags                       = local.tags
}

module "registry" {
  source = "../../modules/registry"

  project               = var.project
  environment           = var.environment
  image_names           = ["backend", "frontend"]
  image_retention_count = 20
  tags                  = local.tags
}

module "kubernetes" {
  source = "../../modules/kubernetes"

  project             = var.project
  environment         = var.environment
  vpc_id              = module.networking.vpc_id
  public_subnet_ids   = module.networking.public_subnet_ids
  private_subnet_ids  = module.networking.private_subnet_ids
  kubernetes_version  = "1.31"
  node_instance_types = ["t3.large"]
  node_desired_size   = 3
  node_min_size       = 2
  node_max_size       = 6
  tags                = local.tags
}
```

Crie `terraform/environments/prod/outputs.tf`:

```hcl
output "vpc_id" {
  value = module.networking.vpc_id
}

output "eks_cluster_name" {
  value = module.kubernetes.cluster_name
}

output "eks_cluster_endpoint" {
  value = module.kubernetes.cluster_endpoint
}

output "rds_endpoint" {
  value = module.database.endpoint
}

output "redis_endpoint" {
  value = module.cache.endpoint
}

output "ecr_urls" {
  value = module.registry.repository_urls
}
```

Crie `terraform/environments/prod/terraform.tfvars.example`:

```hcl
project     = "orderly"
environment = "prod"
region      = "us-east-1"
db_password = "CHANGE_ME_use_a_very_strong_password"
```

### Comparativo dev vs prod

| Recurso | Dev | Prod |
|---|---|---|
| VPC CIDR | `10.0.0.0/16` | `10.1.0.0/16` |
| RDS instance | `db.t3.micro` | `db.t3.medium` |
| RDS Multi-AZ | `false` | `true` |
| RDS storage | 20 GB | 50 GB |
| RDS backup retention | 3 dias | 14 dias |
| RDS deletion protection | `false` | `true` |
| Redis node | `cache.t3.micro` | `cache.t3.small` |
| Redis nodes | 1 | 2 |
| EKS instance | `t3.medium` | `t3.large` |
| EKS nodes | 2 (min 1, max 3) | 3 (min 2, max 6) |
| ECR retention | 5 imagens | 20 imagens |

> **Por que VPC CIDR diferente?** Se algum dia precisar de VPC peering entre dev e prod, os CIDRs nao podem colidir.

> **Por que `deletion_protection = true` em prod?** Impede que um `terraform destroy` acidental delete o banco de producao. Precisa desabilitar manualmente antes.

---

## Passo 12.10 - Conceito: Kubernetes e Kustomize

### Vocabulario Kubernetes

| Termo | O que e |
|---|---|
| **Pod** | Menor unidade — um ou mais containers que compartilham rede e storage |
| **Deployment** | Gerencia pods com ReplicaSet — garante N replicas rodando e faz rolling updates |
| **Service** | Endpoint estavel para acessar pods (ClusterIP, NodePort, LoadBalancer) |
| **Ingress** | Regras de roteamento HTTP/HTTPS (ex: `/api` → backend, `/` → frontend) |
| **ConfigMap** | Configuracoes nao-sensiveis (variaveis de ambiente) |
| **Secret** | Configuracoes sensiveis (senhas, tokens) — base64 encoded |
| **HPA** | HorizontalPodAutoscaler — escala pods por CPU/memoria |
| **CronJob** | Job que roda em schedule (ex: scheduler do Laravel a cada minuto) |
| **Namespace** | Isolamento logico dentro do cluster (como "pastas" para recursos) |

### Kustomize: base + overlays

Kustomize e um gerenciador de configuracao nativo do `kubectl`. Em vez de templates (como Helm), ele usa **patches** sobre uma base:

```
k8s/
├── base/                          # Manifests "genericos"
│   ├── kustomization.yaml         # Lista todos os resources
│   ├── namespace.yaml
│   ├── configmap.yaml
│   ├── secret.yaml
│   ├── backend-api-deployment.yaml
│   ├── backend-api-service.yaml
│   ├── backend-worker-deployment.yaml
│   ├── backend-scheduler-cronjob.yaml
│   ├── frontend-deployment.yaml
│   ├── frontend-service.yaml
│   ├── ingress.yaml
│   └── hpa.yaml
└── overlays/                      # Patches por ambiente
    ├── dev/
    │   ├── kustomization.yaml     # Aplica patches sobre a base
    │   └── patches/
    │       └── replicas.yaml      # 1 replica em dev
    ├── staging/
    │   ├── kustomization.yaml
    │   └── patches/
    │       └── replicas.yaml      # 2 replicas em staging
    └── prod/
        ├── kustomization.yaml
        └── patches/
            ├── replicas.yaml      # 3 replicas em prod
            └── resources.yaml     # Mais CPU/memoria
```

> **Por que Kustomize em vez de Helm?** Para um projeto proprio, Kustomize e mais simples — sem templates Go, sem chart dependencies, sem Tiller. Helm brilha para software distribuido (ex: instalar Prometheus). Para nosso app, Kustomize e a escolha certa.

---

## Passo 12.11 - Separar container PHP em 3 workloads

### Conceito

Atualmente o Dockerfile de producao usa Supervisor para rodar 3 processos:
1. `php-fpm` (API)
2. `queue:work` (Worker)
3. `schedule:run` (Scheduler)

No Kubernetes, cada processo sera um workload separado. Para isso, precisamos que o **mesmo Dockerfile** suporte diferentes entrypoints via argumento.

### Atualizar o Dockerfile do PHP

No arquivo `docker/php/Dockerfile`, **substitua** o stage de producao para suportar multiplos entrypoints:

```dockerfile
# ============================================
# STAGE 4: Production
# ============================================
# Imagem de producao otimizada e segura
FROM base AS production

# Argumento para definir o papel do container
# Valores: "api" (default), "worker", "scheduler"
ARG CONTAINER_ROLE=api
ENV CONTAINER_ROLE=${CONTAINER_ROLE}

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

# Copiar script de entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Trocar para usuario nao-root
USER orderly

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
```

### Criar o script de entrypoint

Crie `docker/php/entrypoint.sh`:

```bash
#!/bin/sh
set -e

# ============================================
# Entrypoint multi-role para Kubernetes
# ============================================
# O mesmo Dockerfile gera a imagem, mas o comportamento
# muda conforme a variavel CONTAINER_ROLE:
#   - api:       roda PHP-FPM (serve a API)
#   - worker:    roda queue:work (processa jobs)
#   - scheduler: roda schedule:run a cada 60s

echo "Starting container with role: ${CONTAINER_ROLE}"

case "${CONTAINER_ROLE}" in
  api)
    # Rodar migrations automaticamente (apenas o primeiro pod)
    # Em producao, considere usar um Job de migration separado
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    exec php-fpm
    ;;
  worker)
    echo "Starting queue worker..."
    exec php artisan queue:work redis \
      --sleep=3 \
      --tries=3 \
      --max-time=3600 \
      --max-jobs=500
    ;;
  scheduler)
    echo "Running scheduler..."
    while true; do
      php artisan schedule:run --verbose --no-interaction
      sleep 60
    done
    ;;
  *)
    echo "ERROR: Unknown CONTAINER_ROLE '${CONTAINER_ROLE}'"
    echo "Valid roles: api, worker, scheduler"
    exit 1
    ;;
esac
```

> **Por que um unico Dockerfile com roles?** Uma unica imagem Docker para os 3 workloads. Isso garante que API, Worker e Scheduler rodam EXATAMENTE o mesmo codigo. Sem risco de versoes divergentes.

> **Por que `exec` antes dos comandos?** O `exec` substitui o processo shell pelo processo PHP. Isso garante que sinais (SIGTERM, SIGKILL) chegam diretamente ao PHP-FPM/Worker, permitindo graceful shutdown no Kubernetes.

> **Por que `config:cache`, `route:cache`, `view:cache` so no api?** Esses comandos geram caches que aceleram o boot do Laravel. O Worker e Scheduler nao precisam — eles nao servem HTTP.

---

## Passo 12.12 - K8s base: Namespace + ConfigMap + Secrets

### Namespace

Crie `k8s/base/namespace.yaml`:

```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/managed-by: kustomize
```

### ConfigMap

Crie `k8s/base/configmap.yaml`:

```yaml
# ============================================
# ConfigMap - Configuracoes nao-sensiveis
# ============================================
# Valores que mudam entre ambientes (dev/staging/prod)
# sao sobrescritos pelos overlays via Kustomize.
apiVersion: v1
kind: ConfigMap
metadata:
  name: orderly-config
  namespace: orderly
data:
  APP_ENV: "production"
  APP_DEBUG: "false"
  APP_URL: "https://orderly.example.com"
  DB_CONNECTION: "pgsql"
  DB_PORT: "5432"
  DB_DATABASE: "orderly"
  REDIS_PORT: "6379"
  CACHE_DRIVER: "redis"
  QUEUE_CONNECTION: "redis"
  SESSION_DRIVER: "redis"
  LOG_CHANNEL: "stderr"
  NEXT_PUBLIC_API_URL: "/api"
```

### Secret

Crie `k8s/base/secret.yaml`:

```yaml
# ============================================
# Secret - Configuracoes sensiveis
# ============================================
# ATENCAO: Em producao, use Sealed Secrets ou External Secrets
# para nao commitar valores reais no Git.
# Os valores abaixo sao placeholders (base64 de "CHANGE_ME").
apiVersion: v1
kind: Secret
metadata:
  name: orderly-secret
  namespace: orderly
type: Opaque
data:
  # echo -n "CHANGE_ME" | base64
  APP_KEY: Q0hBTkdFX01F
  DB_HOST: Q0hBTkdFX01F
  DB_USERNAME: Q0hBTkdFX01F
  DB_PASSWORD: Q0hBTkdFX01F
  REDIS_HOST: Q0hBTkdFX01F
  JWT_SECRET: Q0hBTkdFX01F
```

> **Por que `LOG_CHANNEL: stderr`?** No Kubernetes, logs devem ir para stdout/stderr. O kubelet coleta automaticamente e voce ve com `kubectl logs`. Nunca escreva logs em arquivo dentro do container.

> **Por que Secret separado do ConfigMap?** ConfigMaps sao visiveis para qualquer um com acesso ao namespace. Secrets sao base64 (nao criptografados por padrao), mas podem ser integrados com AWS Secrets Manager ou Sealed Secrets para criptografia real.

---

## Passo 12.13 - K8s base: Backend API Deployment + Service

### Deployment

Crie `k8s/base/backend-api-deployment.yaml`:

```yaml
# ============================================
# Backend API - Deployment
# ============================================
# Roda PHP-FPM servindo a API Laravel.
# Escalavel horizontalmente via HPA.
apiVersion: apps/v1
kind: Deployment
metadata:
  name: backend-api
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-api
spec:
  replicas: 2
  selector:
    matchLabels:
      app.kubernetes.io/name: orderly
      app.kubernetes.io/component: backend-api
  template:
    metadata:
      labels:
        app.kubernetes.io/name: orderly
        app.kubernetes.io/component: backend-api
    spec:
      containers:
        - name: backend-api
          image: orderly/backend:latest
          ports:
            - containerPort: 9000
              protocol: TCP
          env:
            - name: CONTAINER_ROLE
              value: "api"
          envFrom:
            - configMapRef:
                name: orderly-config
            - secretRef:
                name: orderly-secret
          resources:
            requests:
              cpu: 100m
              memory: 128Mi
            limits:
              cpu: 500m
              memory: 512Mi
          # Readiness: o pod so recebe trafego quando esta pronto
          readinessProbe:
            tcpSocket:
              port: 9000
            initialDelaySeconds: 10
            periodSeconds: 5
          # Liveness: reinicia o pod se travar
          livenessProbe:
            tcpSocket:
              port: 9000
            initialDelaySeconds: 30
            periodSeconds: 10
          # Startup: da tempo para o primeiro boot (cache warmup)
          startupProbe:
            tcpSocket:
              port: 9000
            initialDelaySeconds: 5
            periodSeconds: 5
            failureThreshold: 12
      # Distribuir pods entre AZs
      topologySpreadConstraints:
        - maxSkew: 1
          topologyKey: topology.kubernetes.io/zone
          whenUnsatisfiable: ScheduleAnyway
          labelSelector:
            matchLabels:
              app.kubernetes.io/component: backend-api
```

### Service

Crie `k8s/base/backend-api-service.yaml`:

```yaml
# ============================================
# Backend API - Service (ClusterIP)
# ============================================
# Endpoint interno estavel para o Ingress rotear trafego /api
apiVersion: v1
kind: Service
metadata:
  name: backend-api
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-api
spec:
  type: ClusterIP
  selector:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-api
  ports:
    - port: 9000
      targetPort: 9000
      protocol: TCP
```

> **Por que 3 probes (readiness, liveness, startup)?** `readinessProbe` garante que o pod so recebe trafego quando esta funcional. `livenessProbe` reinicia o pod se ele travar. `startupProbe` da tempo extra no primeiro boot (Laravel precisa carregar configs, caches, etc.).

> **Por que `topologySpreadConstraints`?** Distribui os pods entre diferentes AZs. Se uma AZ cair, os pods nas outras AZs continuam servindo. E anti-fragil por design.

> **Por que `requests` e `limits`?** `requests` e o minimo garantido (o scheduler usa para alocar). `limits` e o maximo permitido (o kernel mata o processo se ultrapassar). Sem isso, um pod pode consumir todos os recursos do node.

---

## Passo 12.14 - K8s base: Backend Worker Deployment

Crie `k8s/base/backend-worker-deployment.yaml`:

```yaml
# ============================================
# Backend Worker - Deployment
# ============================================
# Processa jobs da fila Redis (emails, notificacoes, etc.)
# Nao expoe porta — nao recebe trafego HTTP.
apiVersion: apps/v1
kind: Deployment
metadata:
  name: backend-worker
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-worker
spec:
  replicas: 1
  selector:
    matchLabels:
      app.kubernetes.io/name: orderly
      app.kubernetes.io/component: backend-worker
  template:
    metadata:
      labels:
        app.kubernetes.io/name: orderly
        app.kubernetes.io/component: backend-worker
    spec:
      containers:
        - name: backend-worker
          image: orderly/backend:latest
          env:
            - name: CONTAINER_ROLE
              value: "worker"
          envFrom:
            - configMapRef:
                name: orderly-config
            - secretRef:
                name: orderly-secret
          resources:
            requests:
              cpu: 50m
              memory: 128Mi
            limits:
              cpu: 300m
              memory: 256Mi
          # Liveness: verifica se o processo do worker esta vivo
          livenessProbe:
            exec:
              command:
                - sh
                - -c
                - "ps aux | grep 'queue:work' | grep -v grep"
            initialDelaySeconds: 10
            periodSeconds: 30
      # Graceful shutdown: espera o job atual terminar antes de matar
      terminationGracePeriodSeconds: 120
```

> **Por que `terminationGracePeriodSeconds: 120`?** Quando o K8s precisa parar o Worker (scale down, deploy), ele envia SIGTERM e espera 120s. Isso da tempo para o job atual terminar antes de matar o processo. Sem isso, jobs seriam interrompidos no meio.

---

## Passo 12.15 - K8s base: Backend Scheduler CronJob

Crie `k8s/base/backend-scheduler-cronjob.yaml`:

```yaml
# ============================================
# Backend Scheduler - CronJob
# ============================================
# Roda `php artisan schedule:run` a cada minuto.
# Diferente de um Deployment, o CronJob cria um Pod temporario,
# executa o comando, e o Pod morre. Eficiente e sem desperdicio.
apiVersion: batch/v1
kind: CronJob
metadata:
  name: backend-scheduler
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-scheduler
spec:
  schedule: "* * * * *"
  concurrencyPolicy: Forbid
  successfulJobsHistoryLimit: 3
  failedJobsHistoryLimit: 5
  jobTemplate:
    spec:
      activeDeadlineSeconds: 120
      template:
        metadata:
          labels:
            app.kubernetes.io/name: orderly
            app.kubernetes.io/component: backend-scheduler
        spec:
          restartPolicy: OnFailure
          containers:
            - name: scheduler
              image: orderly/backend:latest
              command: ["php", "artisan", "schedule:run", "--verbose", "--no-interaction"]
              envFrom:
                - configMapRef:
                    name: orderly-config
                - secretRef:
                    name: orderly-secret
              resources:
                requests:
                  cpu: 50m
                  memory: 64Mi
                limits:
                  cpu: 200m
                  memory: 128Mi
```

> **Por que CronJob em vez de Deployment?** O Scheduler so precisa rodar por alguns segundos a cada minuto. Um Deployment manteria um Pod rodando 24/7 desperdicando recursos. O CronJob cria um Pod, roda, e destroi — pagando apenas pelo tempo de uso.

> **Por que `concurrencyPolicy: Forbid`?** Se a execucao anterior ainda nao terminou, pula a proxima. Evita execucoes sobrepostas que poderiam causar duplicacao de tarefas agendadas.

---

## Passo 12.16 - K8s base: Frontend Deployment + Service

### Deployment

Crie `k8s/base/frontend-deployment.yaml`:

```yaml
# ============================================
# Frontend - Deployment
# ============================================
# Next.js em modo standalone (server.js).
# Escalavel horizontalmente via HPA.
apiVersion: apps/v1
kind: Deployment
metadata:
  name: frontend
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: frontend
spec:
  replicas: 2
  selector:
    matchLabels:
      app.kubernetes.io/name: orderly
      app.kubernetes.io/component: frontend
  template:
    metadata:
      labels:
        app.kubernetes.io/name: orderly
        app.kubernetes.io/component: frontend
    spec:
      containers:
        - name: frontend
          image: orderly/frontend:latest
          ports:
            - containerPort: 3000
              protocol: TCP
          env:
            - name: INTERNAL_API_URL
              value: "http://backend-api:9000/api"
          envFrom:
            - configMapRef:
                name: orderly-config
          resources:
            requests:
              cpu: 100m
              memory: 128Mi
            limits:
              cpu: 300m
              memory: 256Mi
          readinessProbe:
            httpGet:
              path: /
              port: 3000
            initialDelaySeconds: 10
            periodSeconds: 5
          livenessProbe:
            httpGet:
              path: /
              port: 3000
            initialDelaySeconds: 30
            periodSeconds: 10
      topologySpreadConstraints:
        - maxSkew: 1
          topologyKey: topology.kubernetes.io/zone
          whenUnsatisfiable: ScheduleAnyway
          labelSelector:
            matchLabels:
              app.kubernetes.io/component: frontend
```

### Service

Crie `k8s/base/frontend-service.yaml`:

```yaml
# ============================================
# Frontend - Service (ClusterIP)
# ============================================
apiVersion: v1
kind: Service
metadata:
  name: frontend
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: frontend
spec:
  type: ClusterIP
  selector:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: frontend
  ports:
    - port: 3000
      targetPort: 3000
      protocol: TCP
```

> **Por que `INTERNAL_API_URL: http://backend-api:9000/api`?** O Next.js Server Components fazem fetch no server-side. Dentro do cluster, o frontend acessa o backend pelo nome do Service (`backend-api`). O DNS do Kubernetes resolve automaticamente.

---

## Passo 12.17 - K8s base: Ingress (ALB)

Crie `k8s/base/ingress.yaml`:

```yaml
# ============================================
# Ingress - AWS ALB
# ============================================
# Substitui o Nginx do Docker Compose.
# O AWS Load Balancer Controller cria um ALB automaticamente
# baseado nessas annotations.
#
# Roteamento:
#   /api/*  → backend-api:9000
#   /*      → frontend:3000
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: orderly-ingress
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: ingress
  annotations:
    # Usar o AWS ALB Ingress Controller
    kubernetes.io/ingress.class: alb
    alb.ingress.kubernetes.io/scheme: internet-facing
    alb.ingress.kubernetes.io/target-type: ip
    # Health check
    alb.ingress.kubernetes.io/healthcheck-path: /
    alb.ingress.kubernetes.io/healthcheck-interval-seconds: "15"
    # Subnets publicas (o ALB precisa estar nas public subnets)
    alb.ingress.kubernetes.io/subnets: ""
    # SSL (descomente apos configurar certificado no ACM)
    # alb.ingress.kubernetes.io/listen-ports: '[{"HTTPS":443}]'
    # alb.ingress.kubernetes.io/certificate-arn: "arn:aws:acm:..."
    # alb.ingress.kubernetes.io/ssl-redirect: "443"
spec:
  rules:
    - http:
        paths:
          # API routes → Backend (PHP-FPM)
          - path: /api
            pathType: Prefix
            backend:
              service:
                name: backend-api
                port:
                  number: 9000
          # Everything else → Frontend (Next.js)
          - path: /
            pathType: Prefix
            backend:
              service:
                name: frontend
                port:
                  number: 3000
```

> **Por que ALB em vez do Nginx?** No Kubernetes/AWS, o ALB e nativo: auto-scaling, SSL termination, WAF integration, health checks, access logs para S3. O Nginx precisaria de um Deployment + Service + carga operacional extra.

> **Por que `target-type: ip`?** Com IP mode, o ALB envia trafego diretamente para os pods (sem passar pelo NodePort). Menor latencia e melhor performance.

---

## Passo 12.18 - K8s base: HorizontalPodAutoscaler

Crie `k8s/base/hpa.yaml`:

```yaml
# ============================================
# HPA - Backend API
# ============================================
# Escala automaticamente os pods do backend baseado em CPU.
# Se a media de CPU passar de 70%, cria mais pods.
# Se cair abaixo, remove pods (respeitando o minimo).
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: backend-api-hpa
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: backend-api
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: backend-api
  minReplicas: 2
  maxReplicas: 10
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: 80
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
        - type: Pods
          value: 1
          periodSeconds: 60
    scaleUp:
      stabilizationWindowSeconds: 30
      policies:
        - type: Pods
          value: 2
          periodSeconds: 60
---
# ============================================
# HPA - Frontend
# ============================================
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: frontend-hpa
  namespace: orderly
  labels:
    app.kubernetes.io/name: orderly
    app.kubernetes.io/component: frontend
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: frontend
  minReplicas: 2
  maxReplicas: 6
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
```

> **Por que `stabilizationWindowSeconds: 300` no scale down?** Evita "flapping" — escalar para cima e para baixo rapidamente. O HPA espera 5 minutos com CPU baixa antes de remover pods.

> **Por que scale up mais rapido (30s)?** Escalar para cima e urgente (trafego alto). Escalar para baixo pode esperar (economia nao e urgente).

---

## Passo 12.19 - K8s base: Kustomization

Crie `k8s/base/kustomization.yaml`:

```yaml
# ============================================
# Kustomize - Base
# ============================================
# Lista todos os resources que compoem a aplicacao.
# Os overlays (dev, staging, prod) aplicam patches sobre esta base.
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

namespace: orderly

commonLabels:
  app.kubernetes.io/part-of: orderly

resources:
  - namespace.yaml
  - configmap.yaml
  - secret.yaml
  - backend-api-deployment.yaml
  - backend-api-service.yaml
  - backend-worker-deployment.yaml
  - backend-scheduler-cronjob.yaml
  - frontend-deployment.yaml
  - frontend-service.yaml
  - ingress.yaml
  - hpa.yaml
```

---

## Passo 12.20 - K8s overlays: dev

### Kustomization

Crie `k8s/overlays/dev/kustomization.yaml`:

```yaml
# ============================================
# Kustomize Overlay - Dev
# ============================================
# Herda tudo da base e aplica customizacoes para dev:
# - Menos replicas
# - Imagens do ECR de dev
# - ConfigMap com valores de dev
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

resources:
  - ../../base

# Prefixo para nao colidir com outros ambientes no mesmo cluster
namePrefix: dev-

# Sobrescrever imagens com as do ECR
images:
  - name: orderly/backend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/backend
    newTag: latest
  - name: orderly/frontend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/frontend
    newTag: latest

# Patches para dev
patches:
  # Reduzir replicas em dev
  - target:
      kind: Deployment
      name: backend-api
    patch: |
      - op: replace
        path: /spec/replicas
        value: 1
  - target:
      kind: Deployment
      name: frontend
    patch: |
      - op: replace
        path: /spec/replicas
        value: 1
  # Ajustar HPA para dev
  - target:
      kind: HorizontalPodAutoscaler
      name: backend-api-hpa
    patch: |
      - op: replace
        path: /spec/minReplicas
        value: 1
      - op: replace
        path: /spec/maxReplicas
        value: 3
  - target:
      kind: HorizontalPodAutoscaler
      name: frontend-hpa
    patch: |
      - op: replace
        path: /spec/minReplicas
        value: 1
      - op: replace
        path: /spec/maxReplicas
        value: 2
```

### Como aplicar

```bash
# Preview do que sera aplicado
kubectl apply -k k8s/overlays/dev --dry-run=client -o yaml

# Aplicar de verdade
kubectl apply -k k8s/overlays/dev

# Verificar
kubectl get all -n orderly
```

---

## Passo 12.21 - K8s overlays: staging

Crie `k8s/overlays/staging/kustomization.yaml`:

```yaml
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

resources:
  - ../../base

namePrefix: staging-

images:
  - name: orderly/backend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/backend
    newTag: latest
  - name: orderly/frontend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/frontend
    newTag: latest

# Staging usa a base sem patches — 2 replicas, mesmo HPA
```

---

## Passo 12.22 - K8s overlays: prod

Crie `k8s/overlays/prod/kustomization.yaml`:

```yaml
# ============================================
# Kustomize Overlay - Prod
# ============================================
# Mais replicas, mais recursos, imagens com tag de commit (nunca latest)
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

resources:
  - ../../base

namePrefix: prod-

images:
  - name: orderly/backend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/backend
    newTag: abc1234
  - name: orderly/frontend
    newName: 123456789012.dkr.ecr.us-east-1.amazonaws.com/orderly/frontend
    newTag: abc1234

patches:
  # Mais replicas em prod
  - target:
      kind: Deployment
      name: backend-api
    patch: |
      - op: replace
        path: /spec/replicas
        value: 3
  - target:
      kind: Deployment
      name: backend-worker
    patch: |
      - op: replace
        path: /spec/replicas
        value: 2
  - target:
      kind: Deployment
      name: frontend
    patch: |
      - op: replace
        path: /spec/replicas
        value: 3
  # Mais recursos em prod
  - target:
      kind: Deployment
      name: backend-api
    patch: |
      - op: replace
        path: /spec/template/spec/containers/0/resources/requests/cpu
        value: 250m
      - op: replace
        path: /spec/template/spec/containers/0/resources/limits/cpu
        value: "1"
      - op: replace
        path: /spec/template/spec/containers/0/resources/limits/memory
        value: 1Gi
  # HPA mais agressivo em prod
  - target:
      kind: HorizontalPodAutoscaler
      name: backend-api-hpa
    patch: |
      - op: replace
        path: /spec/minReplicas
        value: 3
      - op: replace
        path: /spec/maxReplicas
        value: 15
```

> **Por que `newTag: abc1234` em vez de `latest`?** Em producao, NUNCA use `latest`. Use a tag do commit SHA. Isso garante rastreabilidade: voce sabe exatamente qual codigo esta rodando. Para rollback, basta mudar a tag para o commit anterior.

---

## Passo 12.23 - Workflow CD: deploy no EKS

### Atualizar o CD workflow

Adicione um job de deploy ao `.github/workflows/cd.yml` (apos o job `build-and-push`):

```yaml
  # ---- Deploy to EKS ----
  deploy:
    name: Deploy to EKS
    runs-on: ubuntu-latest
    needs: build-and-push
    # Apenas se o build passou
    if: success()
    environment: production

    steps:
      - uses: actions/checkout@v4

      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v4
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: us-east-1

      - name: Update kubeconfig
        run: |
          aws eks update-kubeconfig --region us-east-1 --name orderly-prod

      - name: Set image tags
        run: |
          SHA_SHORT=$(git rev-parse --short HEAD)
          cd k8s/overlays/prod
          kustomize edit set image \
            orderly/backend=${{ env.BACKEND_IMAGE }}:${SHA_SHORT} \
            orderly/frontend=${{ env.FRONTEND_IMAGE }}:${SHA_SHORT}

      - name: Deploy to Kubernetes
        run: |
          kubectl apply -k k8s/overlays/prod
          kubectl rollout status deployment/prod-backend-api -n orderly --timeout=300s
          kubectl rollout status deployment/prod-frontend -n orderly --timeout=300s

      - name: Verify deployment
        run: |
          echo "=== Pods ==="
          kubectl get pods -n orderly
          echo "=== Services ==="
          kubectl get svc -n orderly
          echo "=== Ingress ==="
          kubectl get ingress -n orderly
```

### Secrets necessarios no GitHub

Va em **Settings → Secrets and variables → Actions** e adicione:

| Secret | Valor |
|---|---|
| `AWS_ACCESS_KEY_ID` | Access key da IAM com permissao EKS |
| `AWS_SECRET_ACCESS_KEY` | Secret key correspondente |

> **Por que `environment: production`?** O GitHub permite configurar "environments" com protecoes adicionais (aprovacao manual, restricao de branch). Isso adiciona um gate antes do deploy em prod.

> **Por que `rollout status --timeout=300s`?** Espera ate 5 minutos para o rolling update completar. Se os novos pods nao ficarem Ready nesse tempo, o deploy falha e o Kubernetes faz rollback automatico.

---

## Passo 12.24 - Atualizar Makefile com comandos K8s e Terraform

Adicione ao `Makefile`:

```makefile
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
```

---

## Passo 12.25 - Verificacao end-to-end da Fase 12

### Checklist de verificacao

**Terraform (modulos):**

- [ ] `terraform/modules/networking/` — VPC, subnets, IGW, NAT, route tables
- [ ] `terraform/modules/database/` — RDS PostgreSQL com security group
- [ ] `terraform/modules/cache/` — ElastiCache Redis com security group
- [ ] `terraform/modules/registry/` — ECR com lifecycle policy
- [ ] `terraform/modules/kubernetes/` — EKS com managed node group + IAM roles

**Terraform (environments):**

- [ ] `terraform/environments/dev/` — composicao com instancias pequenas
- [ ] `terraform/environments/prod/` — composicao com Multi-AZ e mais recursos
- [ ] `terraform.tfvars.example` em cada environment (nunca commitar `.tfvars`)

**Kubernetes (base):**

- [ ] `k8s/base/namespace.yaml` — namespace `orderly`
- [ ] `k8s/base/configmap.yaml` — variaveis nao-sensiveis
- [ ] `k8s/base/secret.yaml` — placeholders para secrets
- [ ] `k8s/base/backend-api-deployment.yaml` — PHP-FPM com probes
- [ ] `k8s/base/backend-api-service.yaml` — ClusterIP :9000
- [ ] `k8s/base/backend-worker-deployment.yaml` — queue:work
- [ ] `k8s/base/backend-scheduler-cronjob.yaml` — schedule:run cada minuto
- [ ] `k8s/base/frontend-deployment.yaml` — Next.js standalone
- [ ] `k8s/base/frontend-service.yaml` — ClusterIP :3000
- [ ] `k8s/base/ingress.yaml` — ALB com /api → backend, / → frontend
- [ ] `k8s/base/hpa.yaml` — auto-scaling por CPU/memoria
- [ ] `k8s/base/kustomization.yaml` — lista todos os resources

**Kubernetes (overlays):**

- [ ] `k8s/overlays/dev/kustomization.yaml` — 1 replica, imagens dev
- [ ] `k8s/overlays/staging/kustomization.yaml` — 2 replicas, imagens staging
- [ ] `k8s/overlays/prod/kustomization.yaml` — 3 replicas, imagens com SHA tag

**Docker:**

- [ ] `docker/php/entrypoint.sh` — multi-role (api/worker/scheduler)
- [ ] `docker/php/Dockerfile` — stage production com ENTRYPOINT

**CI/CD:**

- [ ] `.github/workflows/cd.yml` atualizado com job `deploy`
- [ ] Secrets `AWS_ACCESS_KEY_ID` e `AWS_SECRET_ACCESS_KEY` no GitHub

**Makefile:**

- [ ] Comandos `tf-*` para Terraform
- [ ] Comandos `k8s-*` para Kubernetes

### Validacao local (sem AWS)

```bash
# 1. Verificar syntax do Terraform
cd terraform/environments/dev
terraform init -backend=false
terraform validate

# 2. Verificar manifests Kubernetes
kubectl apply -k k8s/overlays/dev --dry-run=client

# 3. Verificar cada overlay
kubectl kustomize k8s/overlays/dev
kubectl kustomize k8s/overlays/staging
kubectl kustomize k8s/overlays/prod
```

### Validacao com AWS (apos `terraform apply`)

```bash
# 1. Provisionar infra de dev
make tf-init-dev
make tf-plan-dev
make tf-apply-dev

# 2. Configurar kubectl
aws eks update-kubeconfig --region us-east-1 --name orderly-dev

# 3. Deploy no cluster
make k8s-dev

# 4. Verificar
make k8s-status

# 5. Testar acesso
kubectl get ingress -n orderly
# Copie o ADDRESS do ALB e acesse no navegador
```

### Resumo dos arquivos da Fase 12

```
terraform/
├── modules/
│   ├── networking/     main.tf  variables.tf  outputs.tf
│   ├── database/       main.tf  variables.tf  outputs.tf
│   ├── cache/          main.tf  variables.tf  outputs.tf
│   ├── registry/       main.tf  variables.tf  outputs.tf
│   └── kubernetes/     main.tf  variables.tf  outputs.tf
└── environments/
    ├── dev/            backend.tf  main.tf  variables.tf  outputs.tf  terraform.tfvars.example
    └── prod/           backend.tf  main.tf  variables.tf  outputs.tf  terraform.tfvars.example

k8s/
├── base/
│   ├── kustomization.yaml
│   ├── namespace.yaml
│   ├── configmap.yaml
│   ├── secret.yaml
│   ├── backend-api-deployment.yaml
│   ├── backend-api-service.yaml
│   ├── backend-worker-deployment.yaml
│   ├── backend-scheduler-cronjob.yaml
│   ├── frontend-deployment.yaml
│   ├── frontend-service.yaml
│   ├── ingress.yaml
│   └── hpa.yaml
└── overlays/
    ├── dev/            kustomization.yaml
    ├── staging/        kustomization.yaml
    └── prod/           kustomization.yaml

docker/php/
├── entrypoint.sh       (NOVO)
└── Dockerfile          (ATUALIZADO - stage production)
```

**Conceitos aprendidos:**
- **Terraform modules** — blocos reutilizaveis de infraestrutura: escreva uma vez, use em N ambientes com valores diferentes
- **State remoto (S3)** — o state do Terraform armazenado no S3 permite que a equipe inteira gerencie a mesma infraestrutura sem conflitos
- **VPC com 3 AZs** — alta disponibilidade por design: se uma AZ cair, as outras 2 continuam servindo
- **RDS Multi-AZ** — failover automatico do banco de dados em producao: a AWS promove a replica para master em ~60 segundos
- **EKS managed node groups** — AWS gerencia o lifecycle dos EC2: patching, draining e replacement automaticos
- **Single-process container** — cada workload K8s roda um unico processo: API, Worker e Scheduler escalam independentemente
- **Kustomize base + overlays** — mesmos manifests para todos os ambientes, com patches para valores especificos (replicas, recursos, imagens)
- **HPA (HorizontalPodAutoscaler)** — escala automatica baseada em CPU/memoria: paga apenas pelo que usa
- **Rolling updates** — zero-downtime deploy: novos pods sobem antes dos antigos descerem
- **Probes (readiness, liveness, startup)** — Kubernetes sabe quando o pod esta pronto, travado ou iniciando — e age automaticamente
- **CronJob vs Deployment** — use CronJob para tarefas periodicas (scheduler) e Deployment para processos permanentes (API, worker)
- **GitOps** — o estado desejado da infraestrutura esta no Git: `kubectl apply -k` e o deploy e reproduzivel

**Proximo:** Fase 13 - Observabilidade (Prometheus + Grafana + Logging)

---

*Projeto construido como tutorial progressivo. Cada fase adiciona novas funcionalidades e documenta os conceitos aprendidos.*

---

[Voltar ao README](../README.md)
