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
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=redis

BROWSERSHOT_CHROME_URL=http://chrome:3000

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
BCRYPT_ROUNDS=12
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
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
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
ENVEOF
    echo "Created /opt/numbering/staging/.env (EDIT THIS!)"
fi

# 6. Create .env.docker for each environment
cat > /opt/numbering/production/.env.docker << 'EOF'
DOCKER_IMAGE=ghcr.io/scholes13/numbering
IMAGE_TAG=latest
APP_PORT=8080
EOF

cat > /opt/numbering/staging/.env.docker << 'EOF'
DOCKER_IMAGE=ghcr.io/scholes13/numbering
IMAGE_TAG=latest
APP_PORT=8081
EOF

# 7. Create MySQL databases
echo "Creating MySQL databases (if not exist)..."
mysql -e "CREATE DATABASE IF NOT EXISTS numbering_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Skip: create numbering_prod manually"
mysql -e "CREATE DATABASE IF NOT EXISTS numbering_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Skip: create numbering_staging manually"

# 8. Install certbot for SSL
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
echo "3. Copy docker-compose.yml to both /opt/numbering/production/ and /opt/numbering/staging/"
echo "4. Copy docker/nginx/default.conf to both directories under docker/nginx/"
echo "5. Setup SSL: certbot certonly --standalone -d oasis.werkudara.com -d staging.oasis.werkudara.com"
echo "6. Copy docker/nginx/proxy.conf to /etc/nginx/sites-available/"
echo "7. First deploy: push to staging branch in GitHub"
echo "8. Generate APP_KEY: docker compose exec app php artisan key:generate"
echo "9. Run migrations: docker compose exec app php artisan migrate --force"
