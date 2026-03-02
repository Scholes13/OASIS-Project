#!/bin/bash
# docker/scripts/vps-setup.sh
# VPS setup for OASIS Docker deployment (staging only)
# Run on VPS: sudo bash vps-setup.sh
#
# IMPORTANT: This script sets up STAGING only.
# Production at /home/werkuda1/oasis.werkudara.com remains untouched.

set -e

echo "=== OASIS - VPS Docker Setup (Staging) ==="

# 1. Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
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

# 3. Create staging directory structure
echo "Creating staging directory structure..."
mkdir -p /opt/oasis/staging/docker/nginx

# 4. Create staging .env
if [ ! -f /opt/oasis/staging/.env ]; then
    cat > /opt/oasis/staging/.env << 'ENVEOF'
APP_NAME="OASIS [STAGING]"
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://staging.oasis.werkudara.com

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=werkuda1_oasis_staging
DB_USERNAME=werkuda1
DB_PASSWORD=CHANGE_ME

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
CACHE_PREFIX=oasis_staging_
SESSION_DRIVER=redis
SESSION_LIFETIME=525600
QUEUE_CONNECTION=redis

BROWSERSHOT_CHROME_URL=http://chrome:3000

LOG_CHANNEL=stack
LOG_LEVEL=debug

MAIL_MAILER=log
BCRYPT_ROUNDS=12
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
ENVEOF
    echo "Created /opt/oasis/staging/.env (EDIT credentials!)"
else
    echo "Staging .env already exists, skipping."
fi

# 5. Create .env.docker for staging
cat > /opt/oasis/staging/.env.docker << 'EOF'
DOCKER_IMAGE=ghcr.io/scholes13/numbering
IMAGE_TAG=latest
APP_PORT=8081
EOF

# 6. Create staging database
echo "Creating staging database (if not exists)..."
mysql -e "CREATE DATABASE IF NOT EXISTS werkuda1_oasis_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Skip: create werkuda1_oasis_staging manually via cPanel/phpMyAdmin"

# 7. Setup SSL for staging subdomain
if command -v certbot &> /dev/null; then
    echo ""
    echo "To setup SSL for staging subdomain, run:"
    echo "  certbot certonly --standalone -d staging.oasis.werkudara.com"
else
    echo "Installing certbot..."
    apt-get update && apt-get install -y certbot
    echo "Run: certbot certonly --standalone -d staging.oasis.werkudara.com"
fi

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Edit /opt/oasis/staging/.env (set APP_KEY, DB password)"
echo "2. Copy docker-compose.yml to /opt/oasis/staging/"
echo "3. Copy docker/nginx/default.conf to /opt/oasis/staging/docker/nginx/"
echo "4. Setup SSL for staging.oasis.werkudara.com"
echo "5. Setup Nginx proxy for staging subdomain"
echo "6. Push to 'staging' branch in GitHub to trigger first deploy"
echo "7. Generate APP_KEY: cd /opt/oasis/staging && docker compose exec app php artisan key:generate"
echo "8. Run migrations: docker compose exec app php artisan migrate --force"
echo ""
echo "NOTE: Production at /home/werkuda1/oasis.werkudara.com is NOT affected."
