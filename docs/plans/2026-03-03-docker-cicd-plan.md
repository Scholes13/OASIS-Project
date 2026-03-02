# Docker + CI/CD Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Containerize the WNS Purchase Request System with Docker and update CI/CD for automated deployment to staging/production on a single VPS.

**Architecture:** Hybrid Docker — Laravel app, queue worker, scheduler, Redis, Chrome (Browsershot) run in Docker containers. MySQL stays on the host VPS. One shared Nginx proxy routes `oasis.werkudara.com` (production) and `staging.oasis.werkudara.com` (staging) to their respective container stacks.

**Tech Stack:** Docker, Docker Compose, GitHub Actions, GitHub Container Registry (GHCR), Nginx, Redis 7, PHP 8.2 FPM, Node 20, browserless/chromium

---

## Task 1: Create .dockerignore

**Files:**
- Create: `.dockerignore`

**Step 1: Create the file**

```
# .dockerignore
node_modules
vendor
.git
.github
.claude
.cursor
.kiro
.vscode
.worktrees
.phpunit.result.cache
.env
.env.*
!.env.example
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests
docs
stubs
*.md
docker-compose*.yml
```

**Step 2: Verify file is created**

Run: `cat .dockerignore | head -5`
Expected: First 5 lines of the file

**Step 3: Commit**

```bash
git add .dockerignore
git commit -m "chore: add .dockerignore for Docker build context"
```

---

## Task 2: Create PHP production config

**Files:**
- Create: `docker/php/php.ini`
- Create: `docker/php/www.conf`

**Step 1: Create docker/php/php.ini**

```ini
; docker/php/php.ini
[PHP]
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log

memory_limit = 256M
max_execution_time = 120
max_input_time = 60
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

date.timezone = Asia/Jakarta

[opcache]
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.save_comments = 1

[Session]
session.save_handler = redis
```

**Step 2: Create docker/php/www.conf**

```ini
; docker/php/www.conf
[www]
user = www-data
group = www-data
listen = 9000
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500

clear_env = no
catch_workers_output = yes
decorate_workers_output = no
```

**Step 3: Verify files exist**

Run: `ls -la docker/php/`
Expected: `php.ini` and `www.conf` listed

**Step 4: Commit**

```bash
git add docker/php/
git commit -m "chore: add PHP-FPM production config for Docker"
```

---

## Task 3: Create Nginx configs

**Files:**
- Create: `docker/nginx/default.conf` (per-stack app nginx)
- Create: `docker/nginx/proxy.conf` (shared reverse proxy)

**Step 1: Create docker/nginx/default.conf**

This is the Nginx config inside each stack (prod/staging) that talks to PHP-FPM.

```nginx
# docker/nginx/default.conf
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    charset utf-8;
    client_max_body_size 50M;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 1000;

    # Static assets with long cache
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Build assets (Vite)
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Step 2: Create docker/nginx/proxy.conf**

This is the shared Nginx proxy on the VPS host that routes by domain. This file will be deployed to the VPS, not built into Docker.

```nginx
# docker/nginx/proxy.conf
# Deployed to /opt/numbering/proxy/nginx.conf on VPS

# Production
upstream production_app {
    server 127.0.0.1:8080;
}

# Staging
upstream staging_app {
    server 127.0.0.1:8081;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name oasis.werkudara.com staging.oasis.werkudara.com;
    return 301 https://$host$request_uri;
}

# Production
server {
    listen 443 ssl http2;
    server_name oasis.werkudara.com;

    ssl_certificate /etc/letsencrypt/live/oasis.werkudara.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/oasis.werkudara.com/privkey.pem;

    client_max_body_size 50M;

    location / {
        proxy_pass http://production_app;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120;
    }
}

# Staging
server {
    listen 443 ssl http2;
    server_name staging.oasis.werkudara.com;

    ssl_certificate /etc/letsencrypt/live/staging.oasis.werkudara.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/staging.oasis.werkudara.com/privkey.pem;

    client_max_body_size 50M;

    location / {
        proxy_pass http://staging_app;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120;
    }
}
```

**Step 3: Commit**

```bash
git add docker/nginx/
git commit -m "chore: add Nginx configs for Docker stack and shared proxy"
```

---

## Task 4: Create Dockerfile (Multi-stage)

**Files:**
- Create: `Dockerfile`

**Step 1: Create the Dockerfile**

```dockerfile
# Dockerfile
# ==============================================
# Stage 1: Build frontend assets
# ==============================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ resources/

RUN npm run build

# ==============================================
# Stage 2: Install PHP dependencies
# ==============================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY database/ database/

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ==============================================
# Stage 3: Production image
# ==============================================
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        pcntl \
        mbstring \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Cleanup build deps
RUN apk del $PHPIZE_DEPS linux-headers

# Create log directory
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY --chown=www-data:www-data . .

# Copy vendor from stage 2
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Copy built frontend from stage 1
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# Create storage directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate optimized autoload
RUN php artisan package:discover --ansi || true

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php artisan --version || exit 1

EXPOSE 9000

USER www-data

CMD ["php-fpm"]
```

**Step 2: Test the build locally (optional, skip if no Docker locally)**

Run: `docker build -t numbering-app:test .`
Expected: Build completes without errors

**Step 3: Commit**

```bash
git add Dockerfile
git commit -m "feat: add multi-stage Dockerfile for production"
```

---

## Task 5: Create docker-compose.yml

**Files:**
- Create: `docker-compose.yml`
- Create: `docker-compose.dev.yml` (local dev override)

**Step 1: Create docker-compose.yml (production template)**

```yaml
# docker-compose.yml
# Used for both production and staging (different .env per environment)

services:
  app:
    image: ${DOCKER_IMAGE:-ghcr.io/your-org/numbering}:${IMAGE_TAG:-latest}
    restart: unless-stopped
    env_file: .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
    volumes:
      - app-storage:/var/www/html/storage/app
      - app-logs:/var/www/html/storage/logs
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - app-network
    healthcheck:
      test: ["CMD-SHELL", "php-fpm-healthcheck || php artisan --version"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 15s

  nginx:
    image: nginx:1.27-alpine
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - app-storage:/var/www/html/storage/app:ro
    depends_on:
      app:
        condition: service_healthy
    networks:
      - app-network

  queue:
    image: ${DOCKER_IMAGE:-ghcr.io/your-org/numbering}:${IMAGE_TAG:-latest}
    restart: unless-stopped
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
    env_file: .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
    volumes:
      - app-storage:/var/www/html/storage/app
      - app-logs:/var/www/html/storage/logs
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - app-network

  scheduler:
    image: ${DOCKER_IMAGE:-ghcr.io/your-org/numbering}:${IMAGE_TAG:-latest}
    restart: unless-stopped
    command: php artisan schedule:work
    env_file: .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
    volumes:
      - app-storage:/var/www/html/storage/app
      - app-logs:/var/www/html/storage/logs
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - app-network

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --appendonly yes --maxmemory 128mb --maxmemory-policy allkeys-lru
    volumes:
      - redis-data:/data
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

  chrome:
    image: browserless/chromium
    restart: unless-stopped
    environment:
      - MAX_CONCURRENT_SESSIONS=5
      - CONNECTION_TIMEOUT=120000
      - TIMEOUT=120000
    networks:
      - app-network
    deploy:
      resources:
        limits:
          memory: 512M

volumes:
  app-storage:
  app-logs:
  redis-data:

networks:
  app-network:
    driver: bridge
```

**Step 2: Create docker-compose.dev.yml (local development)**

```yaml
# docker-compose.dev.yml
# For local development: docker compose -f docker-compose.dev.yml up

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    env_file: .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
      - /var/www/html/node_modules
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - app-network

  nginx:
    image: nginx:1.27-alpine
    ports:
      - "8080:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - .:/var/www/html:ro
    depends_on:
      - app
    networks:
      - app-network

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan queue:work redis --sleep=3 --tries=3 --timeout=90
    env_file: .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - app-network

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

  chrome:
    image: browserless/chromium
    ports:
      - "3000:3000"
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

**Step 3: Commit**

```bash
git add docker-compose.yml docker-compose.dev.yml
git commit -m "feat: add docker-compose for production and local development"
```

---

## Task 6: Update Browsershot to use remote Chrome

**Files:**
- Modify: `config/pdf.php`
- Modify: `app/Http/Controllers/Modules/Purchasing/PurchaseRequest/PurchaseRequestController.php` (Browsershot calls)

**Step 1: Add remote Chrome URL to config/pdf.php**

In `config/pdf.php`, add `remote_url` to the browsershot config:

```php
'browsershot' => [
    'timeout' => 180,
    'format' => 'A4',
    'orientation' => 'landscape',
    'margins' => [
        'top' => 10,
        'right' => 10,
        'bottom' => 10,
        'left' => 10,
    ],
    'wait_until_network_idle' => false,
    'enable_javascript' => false,
    'no_sandbox' => true,
    'disable_web_security' => true,
    'ignore_https_errors' => true,
    'remote_url' => env('BROWSERSHOT_CHROME_URL', null),
],
```

**Step 2: Update Browsershot usage in PurchaseRequestController**

Find all `Browsershot::html(...)` calls and add remote URL support:

```php
$browsershot = Browsershot::html($html)
    ->format('A4')
    ->landscape()
    ->margins(10, 10, 10, 10)
    ->timeout(120)
    ->noSandbox()
    ->disableWebSecurity()
    ->setDelay(2000);

// Use remote Chrome if configured (Docker)
$remoteUrl = config('pdf.browsershot.remote_url');
if ($remoteUrl) {
    $browsershot->setRemoteInstance($remoteUrl);
}

$pdfContent = $browsershot->pdf();
```

Do the same for any other controller that uses Browsershot (search for all occurrences).

**Step 3: Add env variable to .env.example**

```env
# Browsershot Remote Chrome (for Docker deployments)
# BROWSERSHOT_CHROME_URL=http://chrome:3000
```

**Step 4: Run existing tests**

Run: `php artisan test --filter=PurchaseRequest`
Expected: All existing tests pass (no regression)

**Step 5: Commit**

```bash
git add config/pdf.php app/Http/Controllers/ .env.example
git commit -m "feat: support remote Chrome for Browsershot in Docker"
```

---

## Task 7: Update .env.production template for Docker

**Files:**
- Modify: `.env.production`

**Step 1: Add Docker-specific variables to .env.production**

Add these sections to `.env.production`:

```env
# ============================================
# DOCKER DEPLOYMENT SETTINGS
# ============================================
DOCKER_IMAGE=ghcr.io/your-org/numbering
IMAGE_TAG=latest
APP_PORT=8080

# ============================================
# REDIS (Docker container)
# ============================================
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ============================================
# OVERRIDE: Use Redis instead of database
# ============================================
CACHE_STORE=redis
CACHE_PREFIX=numbering_
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# ============================================
# BROWSERSHOT (Remote Chrome container)
# ============================================
BROWSERSHOT_CHROME_URL=http://chrome:3000

# ============================================
# DATABASE (Host MySQL, not in Docker)
# ============================================
DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
```

**Step 2: Commit**

```bash
git add .env.production
git commit -m "chore: update .env.production template for Docker deployment"
```

---

## Task 8: Create GitHub Actions Docker Deploy workflow

**Files:**
- Create: `.github/workflows/docker-deploy.yml`

**Step 1: Create the workflow file**

```yaml
# .github/workflows/docker-deploy.yml
name: Docker Build & Deploy

on:
  push:
    branches:
      - main
      - staging
  workflow_dispatch:
    inputs:
      environment:
        description: 'Deploy environment'
        required: true
        default: 'staging'
        type: choice
        options:
          - staging
          - production

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    name: Build & Push Docker Image
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    outputs:
      image_tag: ${{ steps.meta.outputs.tags }}
      short_sha: ${{ steps.vars.outputs.short_sha }}

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set variables
        id: vars
        run: echo "short_sha=$(git rev-parse --short HEAD)" >> "$GITHUB_OUTPUT"

      - name: Log in to GHCR
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=sha,prefix={{branch}}-

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push
        uses: docker/build-push-action@v6
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  deploy:
    name: Deploy to VPS
    needs: build-and-push
    runs-on: ubuntu-latest
    concurrency:
      group: deploy-${{ github.ref_name }}
      cancel-in-progress: false

    steps:
      - name: Determine environment
        id: env
        run: |
          if [ "${{ github.event_name }}" = "workflow_dispatch" ]; then
            ENV="${{ github.event.inputs.environment }}"
          elif [ "${{ github.ref_name }}" = "main" ]; then
            ENV="production"
          else
            ENV="staging"
          fi
          echo "environment=$ENV" >> "$GITHUB_OUTPUT"

          if [ "$ENV" = "production" ]; then
            echo "app_path=/opt/numbering/production" >> "$GITHUB_OUTPUT"
            echo "domain=oasis.werkudara.com" >> "$GITHUB_OUTPUT"
          else
            echo "app_path=/opt/numbering/staging" >> "$GITHUB_OUTPUT"
            echo "domain=staging.oasis.werkudara.com" >> "$GITHUB_OUTPUT"
          fi

      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.2.2
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script_stop: true
          script: |
            set -e
            APP_PATH="${{ steps.env.outputs.app_path }}"
            IMAGE_TAG="${{ github.ref_name }}-${{ needs.build-and-push.outputs.short_sha }}"

            cd "$APP_PATH"

            # Save current image tag for rollback
            PREV_TAG=$(grep IMAGE_TAG .env.docker 2>/dev/null | cut -d= -f2 || echo "latest")
            echo "IMAGE_TAG=$IMAGE_TAG" > .env.docker

            # Login to GHCR
            echo "${{ secrets.GITHUB_TOKEN }}" | docker login ghcr.io -u ${{ github.actor }} --password-stdin

            # Pull new image
            IMAGE="${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:$IMAGE_TAG"
            docker compose pull

            # Deploy with zero-downtime (restart containers one by one)
            docker compose up -d --remove-orphans

            # Wait for app to be healthy
            sleep 5

            # Run migrations
            docker compose exec -T app php artisan migrate --force

            # Clear and rebuild caches
            docker compose exec -T app php artisan optimize

            # Health check
            HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://${{ steps.env.outputs.domain }}/up" || echo "000")

            if [ "$HTTP_CODE" != "200" ]; then
              echo "::error::Health check failed (HTTP $HTTP_CODE). Rolling back..."
              echo "IMAGE_TAG=$PREV_TAG" > .env.docker
              docker compose pull
              docker compose up -d
              exit 1
            fi

            echo "Deploy successful! Health check passed (HTTP $HTTP_CODE)"

            # Prune old images
            docker image prune -f
```

**Step 2: Verify YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/docker-deploy.yml'))" 2>&1 || echo "Install pyyaml or check manually"`

**Step 3: Commit**

```bash
git add .github/workflows/docker-deploy.yml
git commit -m "feat: add Docker build & deploy GitHub Actions workflow"
```

---

## Task 9: Create VPS setup script

**Files:**
- Create: `docker/scripts/vps-setup.sh`

This is a one-time script to prepare the VPS for Docker deployments.

**Step 1: Create the setup script**

```bash
#!/bin/bash
# docker/scripts/vps-setup.sh
# One-time VPS setup for Docker deployment
# Run as root on the VPS: bash vps-setup.sh

set -e

echo "=== WNS Numbering - VPS Docker Setup ==="

# 1. Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
    # Add deploy user to docker group
    usermod -aG docker ${SUDO_USER:-$USER}
    echo "Docker installed. You may need to re-login for group changes."
else
    echo "Docker already installed: $(docker --version)"
fi

# 2. Install Docker Compose plugin if not present
if ! docker compose version &> /dev/null; then
    echo "Installing Docker Compose plugin..."
    apt-get update && apt-get install -y docker-compose-plugin
fi

# 3. Create directory structure
echo "Creating directory structure..."
mkdir -p /opt/numbering/production/docker/nginx
mkdir -p /opt/numbering/staging/docker/nginx
mkdir -p /opt/numbering/proxy

# 4. Create production .env (template)
if [ ! -f /opt/numbering/production/.env ]; then
    cat > /opt/numbering/production/.env << 'ENVEOF'
APP_NAME="WNS Purchase Request System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://oasis.werkudara.com

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=numbering_prod
DB_USERNAME=numbering_user
DB_PASSWORD=CHANGE_ME

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
CACHE_PREFIX=numbering_prod_
SESSION_DRIVER=redis
SESSION_LIFETIME=525600
QUEUE_CONNECTION=redis

BROWSERSHOT_CHROME_URL=http://chrome:3000

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
BCRYPT_ROUNDS=12
ENVEOF
    echo "Created /opt/numbering/production/.env (EDIT THIS!)"
fi

# 5. Create staging .env (template)
if [ ! -f /opt/numbering/staging/.env ]; then
    cat > /opt/numbering/staging/.env << 'ENVEOF'
APP_NAME="WNS [STAGING]"
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://staging.oasis.werkudara.com

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=numbering_staging
DB_USERNAME=numbering_user
DB_PASSWORD=CHANGE_ME

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
CACHE_PREFIX=numbering_staging_
SESSION_DRIVER=redis
SESSION_LIFETIME=525600
QUEUE_CONNECTION=redis

BROWSERSHOT_CHROME_URL=http://chrome:3000

LOG_CHANNEL=stack
LOG_LEVEL=debug

MAIL_MAILER=log
BCRYPT_ROUNDS=12
ENVEOF
    echo "Created /opt/numbering/staging/.env (EDIT THIS!)"
fi

# 6. Create MySQL databases
echo "Creating MySQL databases (if not exists)..."
mysql -e "CREATE DATABASE IF NOT EXISTS numbering_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Skip: create numbering_prod manually"
mysql -e "CREATE DATABASE IF NOT EXISTS numbering_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Skip: create numbering_staging manually"

# 7. Install certbot for SSL
if ! command -v certbot &> /dev/null; then
    echo "Installing certbot..."
    apt-get update && apt-get install -y certbot
fi

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Edit /opt/numbering/production/.env (set APP_KEY, DB credentials)"
echo "2. Edit /opt/numbering/staging/.env (set APP_KEY, DB credentials)"
echo "3. Copy docker-compose.yml and docker/nginx/default.conf to both directories"
echo "4. Setup SSL: certbot certonly --standalone -d oasis.werkudara.com -d staging.oasis.werkudara.com"
echo "5. Copy docker/nginx/proxy.conf to /etc/nginx/sites-available/ (or /opt/numbering/proxy/)"
echo "6. Generate APP_KEY: docker compose exec app php artisan key:generate"
echo "7. Run migrations: docker compose exec app php artisan migrate --force"
```

**Step 2: Make it executable**

Run: `chmod +x docker/scripts/vps-setup.sh`

**Step 3: Commit**

```bash
git add docker/scripts/
git commit -m "chore: add VPS setup script for Docker deployment"
```

---

## Task 10: Rename old deploy workflow

**Files:**
- Rename: `.github/workflows/deploy-vps.yml` → `.github/workflows/deploy-vps.yml.bak`

**Step 1: Rename the old workflow**

```bash
mv .github/workflows/deploy-vps.yml .github/workflows/deploy-vps.yml.bak
```

**Step 2: Commit**

```bash
git add .github/workflows/
git commit -m "chore: archive old VPS deploy workflow, replaced by Docker deploy"
```

---

## Task 11: Run tests to verify no regression

**Step 1: Run the full test suite**

Run: `php artisan test`
Expected: All existing tests pass

**Step 2: If any tests fail, fix them before proceeding**

The Docker changes should not break any existing tests since we only added config options and a conditional in Browsershot usage.

---

## Task 12: Final commit and documentation

**Step 1: Verify all files are committed**

Run: `git status`
Expected: Clean working tree

**Step 2: Tag the release**

```bash
git tag -a v4.1.0-docker -m "Docker deployment support"
```

---

## VPS Deployment Checklist (Manual, One-Time)

After all code is merged, do this on the VPS:

1. SSH to VPS
2. Run `bash docker/scripts/vps-setup.sh`
3. Copy `docker-compose.yml` + `docker/nginx/default.conf` to `/opt/numbering/production/` and `/opt/numbering/staging/`
4. Edit `.env` files with real credentials
5. Setup SSL with certbot
6. Install shared Nginx proxy config
7. Generate APP_KEY per environment
8. Run first deploy via GitHub Actions (push to staging branch)
9. Verify staging works at `staging.oasis.werkudara.com`
10. Merge to main, verify production at `oasis.werkudara.com`
