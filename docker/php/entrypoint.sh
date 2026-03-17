#!/bin/sh
set -e

# ============================================
# Entrypoint multi-role para Kubernetes
# ============================================
# O mesmo Dockerfile gera a imagem, mas o comportamento
# muda conforme a variavel CONTAINER_ROLE:
#   - api:       roda PHP-FPM (serve a API)
#   - worker:    roda queue:work (processa jobs)
#   - scheduler: roda schedule:run a cada 60s

echo "Starting container with role: ${CONTAINER_ROLE}"

case "${CONTAINER_ROLE}" in
  api)
    # Rodar migrations automaticamente (apenas o primeiro pod)
    # Em producao, considere usar um Job de migration separado
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    exec php-fpm
    ;;
  worker)
    echo "Starting queue worker..."
    exec php artisan queue:work redis \
      --sleep=3 \
      --tries=3 \
      --max-time=3600 \
      --max-jobs=500
    ;;
  scheduler)
    echo "Running scheduler..."
    while true; do
      php artisan schedule:run --verbose --no-interaction
      sleep 60
    done
    ;;
  *)
    echo "ERROR: Unknown CONTAINER_ROLE '${CONTAINER_ROLE}'"
    echo "Valid roles: api, worker, scheduler"
    exit 1
    ;;
esac