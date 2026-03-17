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