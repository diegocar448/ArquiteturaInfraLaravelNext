# ============================================
# TERRAFORM BACKEND - S3
# ============================================
# O state do Terraform e armazenado no S3 (nao localmente)
# Isso permite que a equipe inteira use o mesmo state
terraform {
  required_version = ">= 1.6"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Descomente apos criar o bucket S3 manualmente:
  # backend "s3" {
  #   bucket         = "orderly-terraform-state"
  #   key            = "dev/terraform.tfstate"
  #   region         = "us-east-1"
  #   encrypt        = true
  #   dynamodb_table = "orderly-terraform-locks"
  # }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Project     = var.project
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}