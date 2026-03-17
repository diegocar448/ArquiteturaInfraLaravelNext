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