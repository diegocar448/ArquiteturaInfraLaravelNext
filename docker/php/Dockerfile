# ============================================
# STAGE 1: Base image with PHP extensions
# ============================================
# Usamos php:8.3-fpm-alpine para imagem minima (~50MB vs ~400MB debian)
# Alpine Linux e uma distro minimalista ideal para containers
FROM php:8.3-fpm-alpine AS base

# Metadata seguindo OCI Image Spec
LABEL maintainer="Diego <cardoso.benko@gmail.com>"
LABEL description="Orderly Backend - Laravel 12 API"

# Variaveis de build reutilizaveis
ARG PHP_MEMORY_LIMIT=256M
ARG PHP_UPLOAD_MAX_FILESIZE=10M
ARG PHP_POST_MAX_SIZE=10M

# Instalar dependencias do sistema necessarias para as extensoes PHP
# --no-cache evita cache do apk, mantendo a imagem menor
RUN apk add --no-cache \
    # Para extensao pgsql
    postgresql-dev \
    # Para extensao gd (manipulacao de imagens)
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # Para extensao intl (internacionalizacao)
    icu-dev \
    # Para extensao zip
    libzip-dev \
    # Para healthcheck
    curl \
    # Supervisor para gerenciar processos
    supervisor \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    # Instalar extensoes PHP necessarias para Laravel
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        bcmath \
        opcache \
        pcntl \
    # Instalar Redis via PECL
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    # Limpar caches
    && rm -rf /var/cache/apk/* /tmp/*

# Copiar configuracao PHP customizada
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Criar usuario nao-root para seguranca
# Nunca rodar containers como root em producao!
RUN addgroup -g 1000 -S orderly \
    && adduser -u 1000 -S orderly -G orderly

# Diretorio de trabalho
WORKDIR /var/www/html

# ============================================
# STAGE 2: Composer dependencies
# ============================================
# Estagio separado para cache de dependencias
# Se o codigo mudar mas composer.json nao, este cache e reutilizado
FROM base AS dependencies

# Instalar Composer (copiando do image oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar APENAS arquivos de dependencia primeiro (cache layer)
COPY backend/composer.json backend/composer.lock* ./

# Instalar dependencias sem scripts (o codigo ainda nao esta la)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# ============================================
# STAGE 3: Development
# ============================================
# Imagem de desenvolvimento com ferramentas extras
FROM base AS development

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar Xdebug para debugging (APENAS em dev!)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -rf /tmp/*

# Configuracao do Xdebug
RUN echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Git necessario para composer em dev
RUN apk add --no-cache git

# O codigo sera montado via volume no docker-compose
# Nao copiamos nada aqui - isso permite hot-reload

# Manter como root em dev para facilitar (volumes compartilhados)
USER orderly

EXPOSE 9000

CMD ["php-fpm"]

# ============================================
# STAGE 4: Production
# ============================================
# Imagem de producao otimizada e segura
FROM base AS production

# Copiar dependencias do estagio de dependencies
COPY --from=dependencies /var/www/html/vendor ./vendor

# Copiar codigo da aplicacao
COPY backend/ .

# Instalar Composer apenas para gerar autoload otimizado
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Gerar autoload otimizado e rodar post-install scripts
RUN composer dump-autoload --optimize --classmap-authoritative \
    && rm /usr/bin/composer

# Criar diretorios necessarios com permissoes corretas
RUN mkdir -p storage/framework/{cache/data,sessions,views} \
    storage/logs \
    bootstrap/cache \
    && chown -R orderly:orderly /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copiar configuracao do supervisor
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Trocar para usuario nao-root
USER orderly

# Health check - verifica se PHP-FPM esta respondendo
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s \
    CMD curl -f http://localhost:9000/health || exit 1

EXPOSE 9000

# Supervisor gerencia PHP-FPM + queue workers
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]