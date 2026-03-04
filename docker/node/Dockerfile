# ============================================
# STAGE 1: Base
# ============================================
# Node 22 Alpine - versao LTS mais recente
FROM node:22-alpine AS base

LABEL maintainer="Diego <cardoso.benko@gmail.com>"
LABEL description="Orderly Frontend - Next.js 15"

# Instalar libc6-compat necessario para algumas deps nativas
RUN apk add --no-cache libc6-compat curl

WORKDIR /app

# ============================================
# STAGE 2: Dependencies
# ============================================
# Estagio separado para instalar dependencias (cache layer)
FROM base AS dependencies

# Copiar APENAS arquivos de dependencia primeiro
# Se package.json nao mudar, este layer e cacheado
COPY frontend/package.json frontend/package-lock.json* ./

# Instalar dependencias
# Usa npm ci se package-lock.json existir, senao npm install
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# ============================================
# STAGE 3: Development
# ============================================
# Imagem de desenvolvimento com hot-reload
FROM base AS development

# Copiar node_modules do estagio de dependencies
COPY --from=dependencies /app/node_modules ./node_modules

# O codigo sera montado via volume no docker-compose
# Nao copiamos o codigo aqui - isso permite hot-reload via next dev

# Em dev, rodamos como root porque o volume montado
# (./frontend:/app) herda as permissoes do host.
# O usuario nao-root e aplicado apenas em producao.

EXPOSE 3000

# Variavel de ambiente para Next.js saber que esta em dev
ENV NODE_ENV=development
ENV NEXT_TELEMETRY_DISABLED=1

# next dev com turbopack para hot-reload ultra-rapido
CMD ["npx", "next", "dev", "--turbopack", "--hostname", "0.0.0.0"]

# ============================================
# STAGE 4: Builder (para producao)
# ============================================
FROM base AS builder

COPY --from=dependencies /app/node_modules ./node_modules
COPY frontend/ .

# Variaveis de build (nao ficam na imagem final)
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
ENV NEXT_TELEMETRY_DISABLED=1

# Build otimizado do Next.js
# standalone output gera um servidor Node minimo sem node_modules
RUN npm run build

# ============================================
# STAGE 5: Production
# ============================================
# Imagem final de producao - minima possivel
FROM base AS production

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs

# Next.js standalone output:
# - .next/standalone/ contem server.js + node_modules minimos
# - .next/static/ contem assets estaticos
# - public/ contem arquivos publicos
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/public ./public

USER nextjs

EXPOSE 3000

ENV PORT=3000
ENV HOSTNAME="0.0.0.0"

# Health check
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=15s \
    CMD curl -f http://localhost:3000/ || exit 1

# Servidor standalone do Next.js - minimo e otimizado
CMD ["node", "server.js"]