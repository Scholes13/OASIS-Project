# Docker + CI/CD Design — WNS Purchase Request System

**Date:** 2026-03-03
**Approach:** Hybrid Docker (App in Docker, MySQL on Host)
**VPS:** 1 VPS (Ubuntu 22.04, 2GB+ RAM), both staging and production

---

## 1. Architecture Overview

```
🏢 VPS (1 server)
│
├── Nginx Proxy (shared, routes by domain)
│   ├── oasis.werkudara.com         → production stack
│   └── staging.oasis.werkudara.com → staging stack
│
├── /opt/numbering/production/
│   ├── docker-compose.yml
│   ├── .env (APP_ENV=production, DB=numbering_prod)
│   └── docker/ (nginx config, etc.)
│
├── /opt/numbering/staging/
│   ├── docker-compose.yml
│   ├── .env (APP_ENV=staging, DB=numbering_staging)
│   └── docker/ (nginx config, etc.)
│
├── MySQL 8.0 (host-native, NOT in Docker)
│   ├── numbering_prod
│   └── numbering_staging
│
└── Shared Chrome container (Browsershot)
```

---

## 2. Dockerfile (Multi-stage Build)

Single Dockerfile for all containers (app, queue, scheduler):

- **Stage 1 — Frontend:** `node:20-alpine` → `npm ci && npm run build`
- **Stage 2 — Vendor:** `composer:2` → `composer install --no-dev`
- **Stage 3 — Production:** `php:8.2-fpm-alpine`
  - PHP extensions: pdo_mysql, gd, redis, pcntl, bcmath, zip
  - Copy vendor from stage 2
  - Copy built assets from stage 1
  - Copy application code
  - Configure PHP-FPM pool

Image size target: ~200MB

---

## 3. docker-compose.yml (per environment)

### Services:

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | ghcr.io/<repo>:tag | PHP-FPM (Laravel web) |
| `nginx` | nginx:alpine | Reverse proxy + static files |
| `queue` | ghcr.io/<repo>:tag | `php artisan queue:work redis --tries=3` |
| `scheduler` | ghcr.io/<repo>:tag | `php artisan schedule:work` |
| `redis` | redis:7-alpine | Cache, session, queue driver |
| `chrome` | browserless/chromium | Browsershot PDF generation |

### Key config:
- MySQL connection via `host.docker.internal` or host IP
- Redis replaces database driver for cache/session/queue
- Storage volume for file uploads persistence
- Chrome shared between staging and production (1 container)

### RAM Budget (2GB VPS):
```
MySQL (host)         ~400MB
Production stack     ~500MB
Staging stack        ~400MB
Nginx proxy          ~20MB
Overhead             ~200MB
Total               ~1.5GB ✓
```

---

## 4. Nginx Proxy (Shared)

Located at `/opt/numbering/proxy/`:

- Routes `oasis.werkudara.com` → production app (internal port)
- Routes `staging.oasis.werkudara.com` → staging app (internal port)
- Handles SSL termination (Let's Encrypt / certbot)
- Serves static assets directly

---

## 5. CI/CD (GitHub Actions)

### Workflow: `.github/workflows/docker-deploy.yml`

**Triggers:**
- `push` to `main` → deploy production
- `push` to `staging` → deploy staging
- `workflow_dispatch` → manual deploy with branch + environment selection

**Jobs:**

1. **build-and-push:**
   - Checkout code
   - Login to GitHub Container Registry (GHCR)
   - Build multi-stage Docker image
   - Tag: `ghcr.io/<owner>/<repo>:<branch>-<sha>`
   - Push to GHCR

2. **deploy** (depends on build):
   - SSH to VPS
   - `cd /opt/numbering/<environment>/`
   - `docker compose pull`
   - `docker compose up -d`
   - `docker compose exec app php artisan migrate --force`
   - Health check: `curl -f https://<domain>/`
   - On failure: rollback to previous image tag

### GitHub Secrets:
- `VPS_HOST` — VPS IP
- `VPS_USER` — SSH username
- `VPS_SSH_KEY` — SSH private key
- `GHCR_TOKEN` — GitHub token (or use GITHUB_TOKEN)

### Replaces: `.github/workflows/deploy-vps.yml` (current manual deploy)

---

## 6. Developer Workflow (After Docker)

```
1. Code locally
2. Push to "staging" branch → auto-deploy to staging.oasis.werkudara.com
3. Test on staging
4. Merge to "main" → auto-deploy to oasis.werkudara.com
5. No SSH needed.
```

---

## 7. Files to Create

```
Dockerfile
.dockerignore
docker/
  ├── nginx/
  │   ├── default.conf          (per-stack nginx config)
  │   └── proxy.conf            (shared proxy config)
  ├── php/
  │   ├── php.ini               (production PHP settings)
  │   └── www.conf              (PHP-FPM pool config)
  └── supervisord/              (optional, if needed)
docker-compose.yml              (template, copied to each environment)
docker-compose.override.yml     (local dev overrides)
.github/workflows/docker-deploy.yml
```

---

## 8. Environment Changes

### .env additions:
```env
# Redis (replaces database driver)
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Browsershot (remote Chrome)
BROWSERSHOT_CHROME_URL=http://chrome:3000

# Database (host MySQL)
DB_HOST=host.docker.internal
```

---

## 9. Browsershot Configuration

Change from local Chrome to remote Chrome container:
- `browserless/chromium` container
- Browsershot connects via URL instead of local binary
- Solves the "Chrome not installed" problem on production
