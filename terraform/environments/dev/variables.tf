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