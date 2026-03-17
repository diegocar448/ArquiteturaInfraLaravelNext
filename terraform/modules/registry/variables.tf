variable "project" {
  description = "Nome do projeto"
  type        = string
}

variable "environment" {
  description = "Ambiente: dev, staging ou prod"
  type        = string
}

variable "image_names" {
  description = "Nomes dos repositorios de imagens"
  type        = list(string)
  default     = ["backend", "frontend"]
}

variable "image_retention_count" {
  description = "Numero de imagens a manter por repositorio"
  type        = number
  default     = 10
}

variable "tags" {
  description = "Tags adicionais"
  type        = map(string)
  default     = {}
}