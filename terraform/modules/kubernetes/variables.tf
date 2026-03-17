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