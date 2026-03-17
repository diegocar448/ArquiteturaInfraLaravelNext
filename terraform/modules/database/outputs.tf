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