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