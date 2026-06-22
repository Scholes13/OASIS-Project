# cPanel CI/CD Deployment

This workflow deploys the Laravel app to cPanel over SSH/SCP.

## GitHub Secrets

Set these in GitHub: **Settings > Secrets and variables > Actions > Repository secrets**.

Required:

- `CPANEL_HOST`: cPanel SSH host, for example `server.example.com`
- `CPANEL_PORT`: SSH port, usually `22`
- `CPANEL_USERNAME`: cPanel username
- `CPANEL_APP_PATH`: app base path, for example `/home/USER/oasis.werkudara.com`
- `CPANEL_PHP_BIN`: PHP binary path, for example `/opt/cpanel/ea-php84/root/usr/bin/php` or `php`

Authentication, choose one:

- `CPANEL_SSH_KEY`: private SSH key for the cPanel account
- `CPANEL_SSH_PASSWORD`: cPanel SSH password

Environment:

- `CPANEL_ENV_B64`: optional base64 encoded production `.env`. If omitted, create `/home/USER/oasis.werkudara.com/shared/.env` manually on the server.

## cPanel Domain Setup

Point the domain document root to:

```text
/home/USER/oasis.werkudara.com/current/public
```

Confirm the hosting provider allows symlinks for document roots. If not, use a fixed deploy path instead of symlink releases.

## First Deploy Checklist

1. Create MySQL database and user in cPanel.
2. Prepare production `.env` with:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
DEBUGBAR_ENABLED=false
```

3. Encode `.env` for GitHub secret if desired:

```bash
base64 -w 0 .env
```

On macOS:

```bash
base64 .env | tr -d '\n'
```

4. Run workflow **deploy-cpanel** from GitHub Actions.

## Notes

- The build happens on GitHub Actions, so cPanel does not need Node.js.
- The release includes `vendor/` and `public/build/`.
- Server still needs PHP CLI, MySQL access, `tar`, and SSH.
- The workflow keeps the last five releases in `releases/`.
- Shared state lives in `shared/storage`, `shared/bootstrap-cache`, and `shared/.env`.
