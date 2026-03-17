# ============================================
# ECR REPOSITORIES
# ============================================
# Um repositorio para cada imagem (backend, frontend)
resource "aws_ecr_repository" "main" {
  for_each = toset(var.image_names)

  name                 = "${var.project}/${each.value}"
  image_tag_mutability = "MUTABLE"
  force_delete         = var.environment != "prod"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${each.value}"
  })
}

# ============================================
# LIFECYCLE POLICY
# ============================================
# Limpa imagens antigas automaticamente (evita custo infinito)
resource "aws_ecr_lifecycle_policy" "main" {
  for_each   = toset(var.image_names)
  repository = aws_ecr_repository.main[each.value].name

  policy = jsonencode({
    rules = [
      {
        rulePriority = 1
        description  = "Keep last ${var.image_retention_count} images"
        selection = {
          tagStatus   = "any"
          countType   = "imageCountMoreThan"
          countNumber = var.image_retention_count
        }
        action = {
          type = "expire"
        }
      }
    ]
  })
}