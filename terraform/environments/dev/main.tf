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