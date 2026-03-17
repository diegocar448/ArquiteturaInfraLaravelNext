# ============================================
# SECURITY GROUP
# ============================================
# Permite acesso ao PostgreSQL (5432) apenas dos security groups informados
resource "aws_security_group" "rds" {
  name_prefix = "${var.project}-${var.environment}-rds-"
  description = "Security group for RDS PostgreSQL"
  vpc_id      = var.vpc_id

  ingress {
    description     = "PostgreSQL from EKS"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = var.allowed_security_group_ids
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-rds-sg"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# SUBNET GROUP
# ============================================
# O RDS precisa de subnets em pelo menos 2 AZs
resource "aws_db_subnet_group" "main" {
  name       = "${var.project}-${var.environment}-rds"
  subnet_ids = var.subnet_ids

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-rds-subnet-group"
  })
}

# ============================================
# PARAMETER GROUP
# ============================================
# Configuracoes customizadas do PostgreSQL
resource "aws_db_parameter_group" "main" {
  name_prefix = "${var.project}-${var.environment}-pg16-"
  family      = "postgres16"
  description = "Custom parameter group for Orderly"

  # Log queries lentas (> 1 segundo)
  parameter {
    name  = "log_min_duration_statement"
    value = "1000"
  }

  # Encoding UTF-8
  parameter {
    name  = "client_encoding"
    value = "UTF8"
  }

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-pg-params"
  })

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================
# RDS INSTANCE
# ============================================
resource "aws_db_instance" "main" {
  identifier = "${var.project}-${var.environment}-pg"

  # Engine
  engine               = "postgres"
  engine_version       = "16.4"
  instance_class       = var.instance_class
  parameter_group_name = aws_db_parameter_group.main.name

  # Storage
  allocated_storage     = var.allocated_storage
  max_allocated_storage = var.allocated_storage * 2
  storage_type          = "gp3"
  storage_encrypted     = true

  # Database
  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  # Network
  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false

  # High availability
  multi_az = var.multi_az

  # Backup
  backup_retention_period = var.environment == "prod" ? 14 : 3
  backup_window           = "03:00-04:00"
  maintenance_window      = "sun:04:00-sun:05:00"

  # Protection
  deletion_protection = var.environment == "prod" ? true : false
  skip_final_snapshot = var.environment == "prod" ? false : true
  final_snapshot_identifier = var.environment == "prod" ? "${var.project}-${var.environment}-final-snapshot" : null

  tags = merge(var.tags, {
    Name = "${var.project}-${var.environment}-postgres"
  })
}