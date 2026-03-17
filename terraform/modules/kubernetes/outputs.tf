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