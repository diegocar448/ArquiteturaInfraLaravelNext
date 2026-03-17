variable "project" {
  description = "orderly"
  type        = string
}

variable "environment" {
  description = "dev"
  type        = string
}

variable "vpc_cidr" {
  description = "CIDR block da VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "Lista de AZs para distribuir os recursos"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b", "us-east-1c"]
}

variable "tags" {
  description = "Tags adicionais para todos os recursos"
  type        = map(string)
  default     = {}
}