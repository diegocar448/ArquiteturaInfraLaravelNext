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