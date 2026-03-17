output "repository_urls" {
  description = "URLs dos repositorios ECR (map: nome -> url)"
  value       = { for name, repo in aws_ecr_repository.main : name => repo.repository_url }
}

output "registry_id" {
  description = "ID do registry (AWS account ID)"
  value       = values(aws_ecr_repository.main)[0].registry_id
}