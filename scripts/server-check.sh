#!/bin/bash
# =============================================================================
# OASIS Production Server Readiness Checker
# =============================================================================
# Designed for cPanel VPS / shared hosting WITHOUT sudo access.
# Auto-detects web server (Apache vs Nginx) and PHP handler (mod_php vs FPM).
#
# Jalankan dari dalam folder project di server:
#   cd ~/oasis.werkudara.com    (atau path project kamu)
#   bash scripts/server-check.sh
#
# Atau dari luar project:
#   APP_DIR=~/oasis.werkudara.com bash scripts/server-check.sh
#
# Jika punya sudo (VPS full access):
#   sudo bash scripts/server-check.sh
# =============================================================================

set -u

# --- Detect sudo/root access ---
IS_ROOT=false
if [ "$(id -u)" -eq 0 ]; then
    IS_ROOT=true
fi

ADMIN_ACTIONS=()

# --- Colors & Symbols ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

PASS="${GREEN}[OK]${NC}"
FAIL="${RED}[MISSING]${NC}"
WARN="${YELLOW}[WARNING]${NC}"
INFO="${CYAN}[INFO]${NC}"

# --- Counters ---
TOTAL=0
PASSED=0
FAILED=0
WARNINGS=0

MISSING_CRITICAL=()
MISSING_IMPORTANT=()
MISSING_OPTIONAL=()

# --- Detected environment ---
DETECTED_WEBSERVER="unknown"
DETECTED_PHP_HANDLER="unknown"

# --- Auto-detect project directory ---
detect_app_dir() {
    if [ -n "${APP_DIR:-}" ]; then
        echo "$APP_DIR"
        return
    fi

    if [ -f "$(pwd)/artisan" ] && [ -f "$(pwd)/composer.json" ]; then
        echo "$(pwd)"
        return
    fi

    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    CANDIDATE="$(cd "$SCRIPT_DIR/.." 2>/dev/null && pwd)"
    if [ -f "$CANDIDATE/artisan" ] && [ -f "$CANDIDATE/composer.json" ]; then
        echo "$CANDIDATE"
        return
    fi

    echo "$(pwd)"
}

APP_DIR="$(detect_app_dir)"

# --- Helper Functions ---
check_pass() {
    TOTAL=$((TOTAL + 1))
    PASSED=$((PASSED + 1))
    echo -e "  ${PASS} $1"
}

check_fail_critical() {
    TOTAL=$((TOTAL + 1))
    FAILED=$((FAILED + 1))
    MISSING_CRITICAL+=("$1")
    echo -e "  ${FAIL} $1 ${RED}(CRITICAL)${NC}"
}

check_fail_important() {
    TOTAL=$((TOTAL + 1))
    FAILED=$((FAILED + 1))
    MISSING_IMPORTANT+=("$1")
    echo -e "  ${FAIL} $1 ${YELLOW}(IMPORTANT)${NC}"
}

check_warn() {
    TOTAL=$((TOTAL + 1))
    WARNINGS=$((WARNINGS + 1))
    MISSING_OPTIONAL+=("$1")
    echo -e "  ${WARN} $1"
}

need_admin() {
    ADMIN_ACTIONS+=("$1")
}

section() {
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  $1${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

find_php() {
    for bin in php8.2 php8.3 php8.4 php; do
        if command -v "$bin" &> /dev/null; then
            echo "$bin"
            return
        fi
    done
    echo ""
}

# =============================================================================
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       OASIS - Server Readiness Check                                       ║${NC}"
echo -e "${BOLD}║       $(date '+%Y-%m-%d %H:%M:%S')                                                    ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${INFO} App directory: ${BOLD}${APP_DIR}${NC}"

if [ "$IS_ROOT" = true ]; then
    echo -e "  ${PASS} Running as root/sudo - full check mode"
else
    echo -e "  ${INFO} Running tanpa sudo (cPanel/shared hosting mode)"
fi

# Validate Laravel project
if [ -f "${APP_DIR}/artisan" ] && [ -f "${APP_DIR}/composer.json" ]; then
    echo -e "  ${PASS} Laravel project detected"
else
    echo -e "  ${RED}${BOLD}  ERROR: '${APP_DIR}' bukan Laravel project!${NC}"
    echo -e "  ${INFO} Cara pakai: cd /path/to/oasis && bash scripts/server-check.sh"
    exit 1
fi

# =============================================================================
section "1. WEB SERVER (auto-detect)"
# =============================================================================

detect_webserver() {
    # Method 1: Check running processes
    if pgrep -x httpd &> /dev/null || pgrep -x apache2 &> /dev/null; then
        DETECTED_WEBSERVER="apache"
    elif pgrep -x nginx &> /dev/null; then
        DETECTED_WEBSERVER="nginx"
    # Method 2: Check if LiteSpeed (common on cPanel)
    elif pgrep -x lsws &> /dev/null || pgrep -x litespeed &> /dev/null || pgrep -f lshttpd &> /dev/null; then
        DETECTED_WEBSERVER="litespeed"
    # Method 3: Check commands
    elif command -v httpd &> /dev/null || command -v apache2 &> /dev/null; then
        DETECTED_WEBSERVER="apache"
    elif command -v nginx &> /dev/null; then
        DETECTED_WEBSERVER="nginx"
    # Method 4: Check common cPanel paths
    elif [ -d "/etc/apache2" ] || [ -d "/etc/httpd" ] || [ -d "/usr/local/apache" ]; then
        DETECTED_WEBSERVER="apache"
    elif [ -d "/usr/local/lsws" ]; then
        DETECTED_WEBSERVER="litespeed"
    # Method 5: Probe localhost headers
    elif command -v curl &> /dev/null; then
        SERVER_HEADER=$(curl -sI http://localhost 2>/dev/null | grep -i "^server:" | head -1)
        if echo "$SERVER_HEADER" | grep -qi "apache"; then
            DETECTED_WEBSERVER="apache"
        elif echo "$SERVER_HEADER" | grep -qi "nginx"; then
            DETECTED_WEBSERVER="nginx"
        elif echo "$SERVER_HEADER" | grep -qi "litespeed"; then
            DETECTED_WEBSERVER="litespeed"
        fi
    fi
}

detect_php_handler() {
    if [ -z "$PHP_BIN" ]; then
        DETECTED_PHP_HANDLER="unknown"
        return
    fi

    # Check phpinfo for Server API
    SERVER_API=$($PHP_BIN -r 'echo php_sapi_name();' 2>/dev/null)

    case "$SERVER_API" in
        fpm-fcgi|php-fpm)
            DETECTED_PHP_HANDLER="php-fpm"
            ;;
        apache2handler|apache)
            DETECTED_PHP_HANDLER="mod_php"
            ;;
        cgi-fcgi|fcgi)
            DETECTED_PHP_HANDLER="fcgi"
            ;;
        litespeed)
            DETECTED_PHP_HANDLER="lsapi"
            ;;
        cli)
            # CLI mode - try to detect from server config
            if [ "$DETECTED_WEBSERVER" = "apache" ]; then
                # Check if mod_php is loaded
                if command -v httpd &> /dev/null; then
                    if httpd -M 2>/dev/null | grep -qi "php"; then
                        DETECTED_PHP_HANDLER="mod_php"
                    else
                        DETECTED_PHP_HANDLER="fcgi (probable)"
                    fi
                elif command -v apache2 &> /dev/null; then
                    if apache2 -M 2>/dev/null | grep -qi "php"; then
                        DETECTED_PHP_HANDLER="mod_php"
                    else
                        DETECTED_PHP_HANDLER="fcgi (probable)"
                    fi
                else
                    # cPanel default is usually mod_php or lsapi
                    if [ "$DETECTED_WEBSERVER" = "litespeed" ]; then
                        DETECTED_PHP_HANDLER="lsapi (probable)"
                    else
                        DETECTED_PHP_HANDLER="mod_php (probable)"
                    fi
                fi
            elif [ "$DETECTED_WEBSERVER" = "nginx" ]; then
                DETECTED_PHP_HANDLER="php-fpm (required by nginx)"
            elif [ "$DETECTED_WEBSERVER" = "litespeed" ]; then
                DETECTED_PHP_HANDLER="lsapi (probable)"
            else
                DETECTED_PHP_HANDLER="unknown (CLI detected)"
            fi
            ;;
        *)
            DETECTED_PHP_HANDLER="$SERVER_API"
            ;;
    esac
}

PHP_BIN=$(find_php)
detect_webserver
detect_php_handler

case "$DETECTED_WEBSERVER" in
    apache)
        check_pass "Web server: Apache"
        ;;
    nginx)
        check_pass "Web server: Nginx"
        ;;
    litespeed)
        check_pass "Web server: LiteSpeed (cPanel default)"
        ;;
    *)
        check_warn "Web server: tidak terdeteksi (mungkin tidak bisa cek tanpa sudo)"
        echo -e "    ${INFO} Cek via cPanel > Server Information"
        ;;
esac

echo -e "  ${INFO} PHP handler: ${BOLD}${DETECTED_PHP_HANDLER}${NC}"

# PHP-FPM check - only critical if Nginx detected
if [ "$DETECTED_WEBSERVER" = "nginx" ]; then
    if [ "$DETECTED_PHP_HANDLER" != "php-fpm" ] && [[ "$DETECTED_PHP_HANDLER" != *"fpm"* ]]; then
        check_fail_critical "Nginx membutuhkan PHP-FPM, tapi handler = ${DETECTED_PHP_HANDLER}"
        need_admin "Install dan aktifkan PHP-FPM (dibutuhkan oleh Nginx)"
    else
        check_pass "PHP-FPM aktif (dibutuhkan oleh Nginx)"
    fi
elif [ "$DETECTED_WEBSERVER" = "apache" ] || [ "$DETECTED_WEBSERVER" = "litespeed" ]; then
    echo -e "  ${INFO} PHP-FPM tidak dibutuhkan (${DETECTED_WEBSERVER} handle PHP langsung)"
fi

# =============================================================================
section "2. PHP VERSION"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    PHP_VER=$($PHP_BIN -r 'echo PHP_VERSION;' 2>/dev/null)
    PHP_MAJOR=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION;' 2>/dev/null)
    PHP_MINOR=$($PHP_BIN -r 'echo PHP_MINOR_VERSION;' 2>/dev/null)

    if [ "$PHP_MAJOR" -gt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]); then
        check_pass "PHP ${PHP_VER} (binary: ${PHP_BIN})"
    else
        check_fail_critical "PHP ${PHP_VER} terlalu lama (butuh >= 8.2)"
        need_admin "Upgrade PHP ke 8.2+ via cPanel > Select PHP Version"
    fi
else
    check_fail_critical "PHP not found"
    need_admin "Pastikan PHP 8.2+ tersedia via cPanel > Select PHP Version"
fi

# =============================================================================
section "3. PHP EXTENSIONS"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    LOADED_EXTS=$($PHP_BIN -m 2>/dev/null)

    # Critical - app mati total
    echo -e "  ${CYAN}Critical (app mati tanpa ini):${NC}"
    CRITICAL_EXTS="pdo_mysql mbstring openssl tokenizer xml ctype json fileinfo curl dom"
    for ext in $CRITICAL_EXTS; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_fail_critical "ext-${ext}"
            need_admin "Enable ext-${ext} via cPanel > Select PHP Version > Extensions"
        fi
    done

    # Important - fitur tertentu rusak
    echo ""
    echo -e "  ${CYAN}Important (fitur tertentu rusak tanpa ini):${NC}"

    declare -A IMPORTANT_EXTS
    IMPORTANT_EXTS=(
        ["gd"]="QR code & image processing"
        ["zip"]="Export Excel (PhpSpreadsheet)"
        ["bcmath"]="Kalkulasi presisi tinggi"
        ["intl"]="Format tanggal/angka"
        ["redis"]="Queue, cache, session Redis"
    )

    for ext in "${!IMPORTANT_EXTS[@]}"; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_fail_important "ext-${ext} -- ${IMPORTANT_EXTS[$ext]}"
            need_admin "Enable ext-${ext} via cPanel > Select PHP Version > Extensions"
        fi
    done

    # Optional
    echo ""
    echo -e "  ${CYAN}Recommended:${NC}"

    declare -A OPTIONAL_EXTS
    OPTIONAL_EXTS=(
        ["opcache"]="Performance (app lambat tanpa ini)"
        ["exif"]="Metadata gambar"
    )

    for ext in "${!OPTIONAL_EXTS[@]}"; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_warn "ext-${ext} -- ${OPTIONAL_EXTS[$ext]}"
        fi
    done
else
    echo -e "  ${INFO} Skipped - PHP not found"
fi

# =============================================================================
section "4. PHP CONFIGURATION (php.ini)"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    # memory_limit
    MEM_LIMIT=$($PHP_BIN -r 'echo ini_get("memory_limit");' 2>/dev/null)
    MEM_NUM=$(echo "$MEM_LIMIT" | grep -oP '\d+')
    if [ "$MEM_LIMIT" = "-1" ]; then
        check_pass "memory_limit = unlimited"
    elif [ -n "$MEM_NUM" ] && [ "$MEM_NUM" -ge 256 ]; then
        check_pass "memory_limit = ${MEM_LIMIT} (>= 256M)"
    else
        check_warn "memory_limit = ${MEM_LIMIT} (recommended: 256M)"
        echo -e "    ${INFO} Ubah via cPanel > Select PHP Version > Options"
    fi

    # max_execution_time
    MAX_EXEC=$($PHP_BIN -r 'echo ini_get("max_execution_time");' 2>/dev/null)
    if [ "$MAX_EXEC" = "0" ]; then
        check_pass "max_execution_time = unlimited"
    elif [ -n "$MAX_EXEC" ] && [ "$MAX_EXEC" -ge 120 ]; then
        check_pass "max_execution_time = ${MAX_EXEC}s (>= 120)"
    else
        check_warn "max_execution_time = ${MAX_EXEC}s (recommended: 120)"
        echo -e "    ${INFO} Ubah via cPanel > Select PHP Version > Options"
    fi

    # upload_max_filesize
    UPLOAD_MAX=$($PHP_BIN -r 'echo ini_get("upload_max_filesize");' 2>/dev/null)
    UPLOAD_NUM=$(echo "$UPLOAD_MAX" | grep -oP '\d+')
    if [ -n "$UPLOAD_NUM" ] && [ "$UPLOAD_NUM" -ge 50 ]; then
        check_pass "upload_max_filesize = ${UPLOAD_MAX} (>= 50M)"
    else
        check_warn "upload_max_filesize = ${UPLOAD_MAX} (recommended: 50M)"
        echo -e "    ${INFO} Ubah via cPanel > Select PHP Version > Options"
    fi

    # post_max_size
    POST_MAX=$($PHP_BIN -r 'echo ini_get("post_max_size");' 2>/dev/null)
    POST_NUM=$(echo "$POST_MAX" | grep -oP '\d+')
    if [ -n "$POST_NUM" ] && [ "$POST_NUM" -ge 50 ]; then
        check_pass "post_max_size = ${POST_MAX} (>= 50M)"
    else
        check_warn "post_max_size = ${POST_MAX} (recommended: 50M)"
        echo -e "    ${INFO} Ubah via cPanel > Select PHP Version > Options"
    fi

    # OPcache
    OPCACHE_ENABLED=$($PHP_BIN -r 'echo ini_get("opcache.enable");' 2>/dev/null)
    if [ "$OPCACHE_ENABLED" = "1" ]; then
        check_pass "OPcache enabled"
    else
        check_warn "OPcache DISABLED (app akan lambat)"
        echo -e "    ${INFO} Enable via cPanel > Select PHP Version > Extensions > opcache"
    fi

    # Timezone
    TZ=$($PHP_BIN -r 'echo ini_get("date.timezone");' 2>/dev/null)
    if [ -n "$TZ" ]; then
        check_pass "date.timezone = ${TZ}"
    else
        check_warn "date.timezone not set (recommended: Asia/Jakarta)"
    fi
else
    echo -e "  ${INFO} Skipped - PHP not found"
fi

# =============================================================================
section "5. COMPOSER"
# =============================================================================

if command -v composer &> /dev/null; then
    COMPOSER_VER=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    COMPOSER_MAJOR=$(echo "$COMPOSER_VER" | cut -d. -f1)
    if [ -n "$COMPOSER_MAJOR" ] && [ "$COMPOSER_MAJOR" -ge 2 ]; then
        check_pass "Composer v${COMPOSER_VER}"
    else
        check_warn "Composer v${COMPOSER_VER} (v2+ recommended)"
    fi
else
    check_fail_critical "Composer not found"
    echo -e "    ${INFO} Install tanpa sudo:"
    echo -e "    ${INFO}   curl -sS https://getcomposer.org/installer | php"
    echo -e "    ${INFO}   mv composer.phar ~/bin/composer"
    echo -e "    ${INFO}   echo 'export PATH=\$HOME/bin:\$PATH' >> ~/.bashrc"
fi

# =============================================================================
section "6. NODE.JS & NPM"
# =============================================================================

if command -v node &> /dev/null; then
    NODE_VER=$(node --version 2>/dev/null | tr -d 'v')
    NODE_MAJOR=$(echo "$NODE_VER" | cut -d. -f1)
    if [ "$NODE_MAJOR" -ge 20 ]; then
        check_pass "Node.js v${NODE_VER}"
    elif [ "$NODE_MAJOR" -ge 18 ]; then
        check_warn "Node.js v${NODE_VER} (v20+ recommended)"
    else
        check_fail_critical "Node.js v${NODE_VER} terlalu lama (butuh >= 18)"
    fi
else
    # Node mungkin tidak perlu di server jika build lokal lalu upload
    check_warn "Node.js not found"
    echo -e "    ${INFO} Dibutuhkan jika build frontend di server (npm run build)"
    echo -e "    ${INFO} Alternatif: build lokal, lalu upload public/build/ ke server"
    echo -e "    ${INFO} Install tanpa sudo via nvm:"
    echo -e "    ${INFO}   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.0/install.sh | bash"
    echo -e "    ${INFO}   nvm install 20"
fi

if command -v npm &> /dev/null; then
    NPM_VER=$(npm --version 2>/dev/null)
    check_pass "npm v${NPM_VER}"
elif ! command -v node &> /dev/null; then
    echo -e "  ${INFO} npm akan ter-install bersama Node.js"
fi

# =============================================================================
section "7. MYSQL / DATABASE"
# =============================================================================

if command -v mysql &> /dev/null; then
    MYSQL_VER=$(mysql --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "MySQL client v${MYSQL_VER}"
else
    echo -e "  ${INFO} MySQL client tidak ditemukan di PATH (normal di cPanel)"
fi

# Test database connection from .env
if [ -f "${APP_DIR}/.env" ]; then
    DB_HOST=$(grep -E "^DB_HOST=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep -E "^DB_DATABASE=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_USER=$(grep -E "^DB_USERNAME=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")

    if [ -n "$DB_HOST" ] && [ -n "$DB_NAME" ]; then
        echo -e "  ${INFO} DB config: ${DB_USER}@${DB_HOST} / ${DB_NAME}"

        # Try artisan db connection test
        if [ -n "$PHP_BIN" ] && [ -f "${APP_DIR}/artisan" ]; then
            DB_TEST=$($PHP_BIN "${APP_DIR}/artisan" db:show --json 2>/dev/null | head -1)
            if [ $? -eq 0 ] && [ -n "$DB_TEST" ]; then
                check_pass "Database connection OK"
            else
                # Fallback: simpler test
                DB_TEST2=$($PHP_BIN -r "
                    try {
                        \$env = parse_ini_file('${APP_DIR}/.env', false, INI_SCANNER_RAW);
                        \$dsn = 'mysql:host=' . (\$env['DB_HOST'] ?? 'localhost') . ';port=' . (\$env['DB_PORT'] ?? '3306') . ';dbname=' . (\$env['DB_DATABASE'] ?? '');
                        new PDO(\$dsn, \$env['DB_USERNAME'] ?? '', \$env['DB_PASSWORD'] ?? '');
                        echo 'OK';
                    } catch (Exception \$e) {
                        echo 'FAIL: ' . \$e->getMessage();
                    }
                " 2>/dev/null)
                if [ "$DB_TEST2" = "OK" ]; then
                    check_pass "Database connection OK (direct PDO test)"
                else
                    check_fail_critical "Database connection GAGAL"
                    echo -e "    ${INFO} ${DB_TEST2}"
                    echo -e "    ${INFO} Cek DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env"
                    echo -e "    ${INFO} Buat database via cPanel > MySQL Databases"
                fi
            fi
        fi
    fi
else
    echo -e "  ${INFO} .env tidak ada, skip database check"
fi

# =============================================================================
section "8. REDIS"
# =============================================================================

if command -v redis-cli &> /dev/null; then
    REDIS_PING=$(redis-cli ping 2>/dev/null)
    if [ "$REDIS_PING" = "PONG" ]; then
        REDIS_VER=$(redis-cli info server 2>/dev/null | grep "redis_version:" | cut -d: -f2 | tr -d '\r')
        check_pass "Redis v${REDIS_VER} - responding"
    else
        check_fail_important "Redis client ada tapi server tidak merespons"
    fi
else
    # Check from .env if Redis is configured
    if [ -f "${APP_DIR}/.env" ]; then
        CACHE_DRIVER=$(grep -E "^CACHE_STORE=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
        QUEUE_CONN=$(grep -E "^QUEUE_CONNECTION=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")

        if [ "$CACHE_DRIVER" = "redis" ] || [ "$QUEUE_CONN" = "redis" ]; then
            check_fail_important "Redis tidak ditemukan tapi .env menggunakan Redis"
            echo -e "    ${INFO} CACHE_STORE=${CACHE_DRIVER}, QUEUE_CONNECTION=${QUEUE_CONN}"
            echo -e "    ${INFO} Opsi 1: Minta hosting aktifkan Redis"
            echo -e "    ${INFO} Opsi 2: Ganti ke file/database di .env:"
            echo -e "    ${INFO}   CACHE_STORE=file"
            echo -e "    ${INFO}   QUEUE_CONNECTION=database"
            echo -e "    ${INFO}   SESSION_DRIVER=file"
        else
            echo -e "  ${INFO} Redis tidak digunakan (.env pakai ${CACHE_DRIVER:-file}/${QUEUE_CONN:-sync})"
        fi
    else
        echo -e "  ${INFO} Redis tidak ditemukan (opsional)"
    fi
fi

# =============================================================================
section "9. LARAVEL PROJECT FILES"
# =============================================================================

# .env
if [ -f "${APP_DIR}/.env" ]; then
    check_pass ".env file exists"

    # Check APP_KEY
    APP_KEY=$(grep -E "^APP_KEY=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2)
    if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "" ]; then
        check_pass "APP_KEY is set"
    else
        check_fail_critical "APP_KEY is EMPTY"
        echo -e "    ${INFO} Run: php artisan key:generate"
    fi

    # Check APP_ENV
    APP_ENV=$(grep -E "^APP_ENV=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ "$APP_ENV" = "production" ]; then
        check_pass "APP_ENV = production"
    else
        check_warn "APP_ENV = ${APP_ENV} (should be 'production' di server)"
    fi

    # Check APP_DEBUG
    APP_DEBUG=$(grep -E "^APP_DEBUG=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ "$APP_DEBUG" = "false" ]; then
        check_pass "APP_DEBUG = false"
    else
        check_fail_important "APP_DEBUG = ${APP_DEBUG} (HARUS false di production!)"
        echo -e "    ${INFO} Debug mode menampilkan error detail ke user = security risk"
    fi
else
    check_fail_critical ".env file MISSING"
    echo -e "    ${INFO} cp .env.example .env && php artisan key:generate"
fi

# vendor/
if [ -d "${APP_DIR}/vendor" ] && [ -f "${APP_DIR}/vendor/autoload.php" ]; then
    check_pass "vendor/ exists (composer install done)"
else
    check_fail_critical "vendor/ MISSING atau incomplete"
    echo -e "    ${INFO} Run: composer install --no-dev --optimize-autoloader"
fi

# public/build/
if [ -d "${APP_DIR}/public/build" ] && [ -f "${APP_DIR}/public/build/manifest.json" ]; then
    check_pass "public/build/ exists (frontend sudah di-build)"
else
    check_fail_critical "public/build/ MISSING (frontend belum di-build)"
    echo -e "    ${INFO} Opsi 1 (build di server): npm ci && npm run build"
    echo -e "    ${INFO} Opsi 2 (build lokal): npm run build, lalu upload public/build/ ke server"
fi

# storage symlink
if [ -L "${APP_DIR}/public/storage" ]; then
    check_pass "public/storage symlink exists"
else
    check_warn "public/storage symlink missing"
    echo -e "    ${INFO} Run: php artisan storage:link"
fi

# =============================================================================
section "10. DIRECTORY PERMISSIONS"
# =============================================================================

WRITABLE_DIRS=(
    "storage/app"
    "storage/app/public"
    "storage/framework/cache"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/logs"
    "bootstrap/cache"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    FULL_PATH="${APP_DIR}/${dir}"
    if [ -d "$FULL_PATH" ]; then
        if [ -w "$FULL_PATH" ]; then
            check_pass "${dir}/ writable"
        else
            check_fail_important "${dir}/ NOT writable"
            echo -e "    ${INFO} Fix: chmod -R 775 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache"
        fi
    else
        check_fail_important "${dir}/ directory MISSING"
        echo -e "    ${INFO} Run: mkdir -p ${FULL_PATH}"
    fi
done

# =============================================================================
section "11. CHROMIUM / CHROME (PDF via Browsershot)"
# =============================================================================

CHROME_FOUND=false

for bin in google-chrome google-chrome-stable chromium chromium-browser; do
    if command -v "$bin" &> /dev/null; then
        CHROME_VER=$("$bin" --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
        check_pass "Chrome/Chromium: ${bin} v${CHROME_VER}"
        CHROME_FOUND=true
        break
    fi
done

if [ "$CHROME_FOUND" = false ]; then
    for path in /usr/bin/google-chrome /usr/bin/chromium /usr/bin/chromium-browser /snap/bin/chromium; do
        if [ -x "$path" ]; then
            CHROME_VER=$("$path" --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
            check_pass "Chrome/Chromium: ${path} v${CHROME_VER}"
            CHROME_FOUND=true
            break
        fi
    done
fi

if [ "$CHROME_FOUND" = false ]; then
    check_fail_important "Chrome/Chromium NOT found"
    echo -e "    ${INFO} Tanpa ini: fitur cetak/export PDF (Browsershot) tidak jalan"
    need_admin "Install Chromium di server (dibutuhkan untuk PDF generation)"
fi

# =============================================================================
section "12. SUPERVISOR (Queue Worker & Scheduler)"
# =============================================================================

SUPERVISOR_FOUND=false
SUPERVISORD_BIN=""
SUPERVISORCTL_BIN=""

# --- Find supervisord binary ---
# 1. Check PATH
if command -v supervisord &> /dev/null; then
    SUPERVISORD_BIN=$(command -v supervisord)
elif command -v supervisord3 &> /dev/null; then
    SUPERVISORD_BIN=$(command -v supervisord3)
fi

# 2. Check common install locations if not in PATH
if [ -z "$SUPERVISORD_BIN" ]; then
    for path in \
        /usr/bin/supervisord \
        /usr/local/bin/supervisord \
        /usr/sbin/supervisord \
        /opt/cpanel/ea-php*/root/usr/bin/supervisord \
        /usr/local/sbin/supervisord \
        /home/*/bin/supervisord \
        ~/.local/bin/supervisord; do
        # Expand glob
        for expanded in $path; do
            if [ -x "$expanded" ] 2>/dev/null; then
                SUPERVISORD_BIN="$expanded"
                break 2
            fi
        done
    done
fi

# --- Find supervisorctl binary ---
if command -v supervisorctl &> /dev/null; then
    SUPERVISORCTL_BIN=$(command -v supervisorctl)
elif command -v supervisorctl3 &> /dev/null; then
    SUPERVISORCTL_BIN=$(command -v supervisorctl3)
fi

if [ -z "$SUPERVISORCTL_BIN" ]; then
    for path in \
        /usr/bin/supervisorctl \
        /usr/local/bin/supervisorctl \
        /usr/sbin/supervisorctl \
        /opt/cpanel/ea-php*/root/usr/bin/supervisorctl \
        /usr/local/sbin/supervisorctl \
        /home/*/bin/supervisorctl \
        ~/.local/bin/supervisorctl; do
        for expanded in $path; do
            if [ -x "$expanded" ] 2>/dev/null; then
                SUPERVISORCTL_BIN="$expanded"
                break 2
            fi
        done
    done
fi

# --- Detect running process ---
SUP_RUNNING=false
if pgrep -x supervisord &> /dev/null || pgrep -f "supervisord" &> /dev/null; then
    SUP_RUNNING=true
fi

# --- Report findings ---
if [ "$SUP_RUNNING" = true ]; then
    SUPERVISOR_FOUND=true
    check_pass "Supervisor process is running"
    if [ -n "$SUPERVISORD_BIN" ]; then
        echo -e "    ${INFO} Binary: ${SUPERVISORD_BIN}"
    else
        # Process running but binary not found in known paths — find it
        SUP_PID=$(pgrep -x supervisord 2>/dev/null || pgrep -f "supervisord" 2>/dev/null | head -1)
        if [ -n "$SUP_PID" ]; then
            SUP_EXE=$(readlink -f /proc/$SUP_PID/exe 2>/dev/null)
            if [ -n "$SUP_EXE" ]; then
                SUPERVISORD_BIN="$SUP_EXE"
                echo -e "    ${INFO} Binary (from process): ${SUP_EXE}"
            fi
        fi
    fi
elif [ -n "$SUPERVISORD_BIN" ]; then
    SUPERVISOR_FOUND=true
    SUP_VER=$($SUPERVISORD_BIN --version 2>/dev/null)
    check_warn "Supervisor v${SUP_VER} installed but NOT running"
    echo -e "    ${INFO} Binary: ${SUPERVISORD_BIN}"
    echo -e "    ${INFO} Start: sudo systemctl start supervisor"
    need_admin "Start Supervisor service"
elif [ -n "$SUPERVISORCTL_BIN" ]; then
    SUPERVISOR_FOUND=true
    check_warn "supervisorctl found at ${SUPERVISORCTL_BIN} but supervisord not detected"
fi

# --- Check config and worker status ---
if [ "$SUPERVISOR_FOUND" = true ]; then
    # Find supervisorctl if we have supervisord path but not ctl
    if [ -z "$SUPERVISORCTL_BIN" ] && [ -n "$SUPERVISORD_BIN" ]; then
        CTL_DIR=$(dirname "$SUPERVISORD_BIN")
        if [ -x "${CTL_DIR}/supervisorctl" ]; then
            SUPERVISORCTL_BIN="${CTL_DIR}/supervisorctl"
        fi
    fi

    if [ -n "$SUPERVISORCTL_BIN" ]; then
        SUP_STATUS=$($SUPERVISORCTL_BIN status 2>/dev/null)
        if [ -n "$SUP_STATUS" ]; then
            if echo "$SUP_STATUS" | grep -qi "oasis\|numbering\|laravel\|queue\|worker"; then
                RUNNING_WORKERS=$(echo "$SUP_STATUS" | grep -ci "RUNNING")
                check_pass "Supervisor workers: ${RUNNING_WORKERS} running"
                echo "$SUP_STATUS" | while read -r line; do
                    echo -e "    ${INFO} ${line}"
                done
            else
                check_warn "Supervisor jalan tapi tidak ada OASIS worker config"
                echo -e "    ${INFO} Jalankan: bash scripts/setup-server.sh"
            fi
        else
            echo -e "  ${INFO} supervisorctl tidak bisa connect (mungkin butuh sudo)"
            echo -e "  ${INFO} Coba: sudo ${SUPERVISORCTL_BIN} status"
        fi
    fi

    # Show config locations
    echo ""
    echo -e "  ${CYAN}Config locations yang dicek:${NC}"
    for dir in /etc/supervisor/conf.d /etc/supervisord.d /usr/local/etc/supervisor/conf.d; do
        if [ -d "$dir" ]; then
            CONF_COUNT=$(ls -1 "$dir"/*.conf 2>/dev/null | wc -l)
            echo -e "    ${INFO} ${dir}/ (${CONF_COUNT} config files)"
        fi
    done
else
    echo -e "  ${WARN} Supervisor tidak terdeteksi"
    echo ""
    echo -e "  ${CYAN}Lokasi yang dicek:${NC}"
    echo -e "    ${INFO} PATH: supervisord, supervisorctl"
    echo -e "    ${INFO} /usr/bin/, /usr/local/bin/, /usr/sbin/"
    echo -e "    ${INFO} /opt/cpanel/, ~/.local/bin/"
    echo -e "    ${INFO} Process: pgrep supervisord"
    echo ""
    echo -e "  ${INFO} Jika admin sudah install, minta info:"
    echo -e "  ${INFO}   1. Path binary: which supervisord"
    echo -e "  ${INFO}   2. Apakah service jalan: systemctl status supervisor"
    echo -e "  ${INFO}   3. Config dir: cat /etc/supervisor/supervisord.conf | grep files"
    echo -e "  ${INFO} Tanpa Supervisor, queue worker bisa jalan via cron"
fi

# =============================================================================
section "13. CRON JOBS (Scheduler & Queue)"
# =============================================================================

echo -e "  ${CYAN}Mengecek cron jobs untuk Laravel:${NC}"

CRONTAB_CONTENT=$(crontab -l 2>/dev/null)

if [ -n "$CRONTAB_CONTENT" ]; then
    # Check Laravel scheduler
    if echo "$CRONTAB_CONTENT" | grep -q "schedule:run\|schedule:work"; then
        check_pass "Laravel scheduler cron found"
    else
        check_fail_important "Laravel scheduler cron MISSING"
        echo -e "    ${INFO} Tambah via cPanel > Cron Jobs, atau jalankan:"
        echo -e "    ${INFO}   bash scripts/setup-cron.sh"
    fi

    # Check queue worker
    if echo "$CRONTAB_CONTENT" | grep -q "queue:work\|queue:listen"; then
        check_pass "Queue worker cron found"
    elif [ "$SUPERVISOR_FOUND" = true ]; then
        echo -e "  ${INFO} Queue worker via Supervisor (bukan cron)"
    else
        check_warn "Queue worker tidak ditemukan di cron atau Supervisor"
        echo -e "    ${INFO} Jalankan: bash scripts/setup-cron.sh"
    fi
else
    check_warn "Crontab kosong atau tidak bisa dibaca"
    echo -e "    ${INFO} Jalankan: bash scripts/setup-cron.sh"
fi

# =============================================================================
section "14. SSL / HTTPS"
# =============================================================================

if [ -f "${APP_DIR}/.env" ]; then
    APP_URL=$(grep -E "^APP_URL=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    if echo "$APP_URL" | grep -qi "^https://"; then
        check_pass "APP_URL uses HTTPS: ${APP_URL}"
    elif [ -n "$APP_URL" ]; then
        check_warn "APP_URL uses HTTP: ${APP_URL} (recommended: HTTPS)"
        echo -e "    ${INFO} Aktifkan SSL via cPanel > SSL/TLS atau Let's Encrypt"
    fi
fi

# Check certbot
if command -v certbot &> /dev/null; then
    CERTBOT_VER=$(certbot --version 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Certbot v${CERTBOT_VER}"
else
    echo -e "  ${INFO} Certbot tidak ditemukan (SSL mungkin dikelola via cPanel)"
fi

# Check if HTTPS actually works (probe APP_URL)
if [ -n "${APP_URL:-}" ] && command -v curl &> /dev/null; then
    if echo "$APP_URL" | grep -qi "^https://"; then
        HTTP_CODE=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 5 "$APP_URL" 2>/dev/null)
        if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
            check_pass "HTTPS responding (HTTP ${HTTP_CODE})"
        elif [ -n "$HTTP_CODE" ] && [ "$HTTP_CODE" != "000" ]; then
            check_warn "HTTPS returned HTTP ${HTTP_CODE}"
        else
            echo -e "  ${INFO} Tidak bisa test HTTPS dari server (mungkin firewall/DNS)"
        fi
    fi
fi

# =============================================================================
section "15. MEMORY & SYSTEM"
# =============================================================================

# Memory - works without sudo via /proc
if [ -f /proc/meminfo ]; then
    TOTAL_RAM_KB=$(grep "^MemTotal:" /proc/meminfo 2>/dev/null | awk '{print $2}')
    AVAIL_RAM_KB=$(grep "^MemAvailable:" /proc/meminfo 2>/dev/null | awk '{print $2}')

    if [ -n "$TOTAL_RAM_KB" ]; then
        TOTAL_RAM_MB=$((TOTAL_RAM_KB / 1024))
        if [ "$TOTAL_RAM_MB" -lt 1024 ]; then
            check_warn "RAM total: ${TOTAL_RAM_MB}MB (2GB+ recommended)"
        else
            check_pass "RAM total: ${TOTAL_RAM_MB}MB"
        fi
    fi

    if [ -n "$AVAIL_RAM_KB" ]; then
        AVAIL_RAM_MB=$((AVAIL_RAM_KB / 1024))
        if [ "$AVAIL_RAM_MB" -lt 256 ]; then
            check_warn "RAM available: ${AVAIL_RAM_MB}MB (rendah!)"
        else
            echo -e "  ${INFO} RAM available: ${AVAIL_RAM_MB}MB"
        fi
    fi
elif command -v free &> /dev/null; then
    TOTAL_RAM=$(free -m 2>/dev/null | awk '/^Mem:/{print $2}')
    AVAIL_RAM=$(free -m 2>/dev/null | awk '/^Mem:/{print $7}')
    if [ -n "$TOTAL_RAM" ]; then
        if [ "$TOTAL_RAM" -lt 1024 ]; then
            check_warn "RAM total: ${TOTAL_RAM}MB (2GB+ recommended)"
        else
            check_pass "RAM total: ${TOTAL_RAM}MB"
        fi
    fi
    if [ -n "$AVAIL_RAM" ] && [ "$AVAIL_RAM" -lt 256 ]; then
        check_warn "RAM available: ${AVAIL_RAM}MB (rendah!)"
    elif [ -n "$AVAIL_RAM" ]; then
        echo -e "  ${INFO} RAM available: ${AVAIL_RAM}MB"
    fi
fi

# Swap
if [ -f /proc/swaps ]; then
    SWAP_LINES=$(wc -l < /proc/swaps 2>/dev/null)
    if [ "$SWAP_LINES" -gt 1 ]; then
        SWAP_SIZE=$(awk 'NR>1{sum+=$3}END{printf "%.0f", sum/1024}' /proc/swaps 2>/dev/null)
        check_pass "Swap space: ${SWAP_SIZE}MB"
    else
        check_warn "No swap space (recommended untuk server kecil)"
        need_admin "Aktifkan swap space (1-2GB recommended)"
    fi
elif command -v free &> /dev/null; then
    SWAP_TOTAL=$(free -m 2>/dev/null | awk '/^Swap:/{print $2}')
    if [ -n "$SWAP_TOTAL" ] && [ "$SWAP_TOTAL" -gt 0 ]; then
        check_pass "Swap space: ${SWAP_TOTAL}MB"
    else
        check_warn "No swap space"
        need_admin "Aktifkan swap space (1-2GB recommended)"
    fi
fi

# CPU cores
if [ -f /proc/cpuinfo ]; then
    CPU_CORES=$(grep -c "^processor" /proc/cpuinfo 2>/dev/null)
    if [ -n "$CPU_CORES" ]; then
        if [ "$CPU_CORES" -lt 2 ]; then
            check_warn "CPU cores: ${CPU_CORES} (2+ recommended)"
        else
            check_pass "CPU cores: ${CPU_CORES}"
        fi
    fi
elif command -v nproc &> /dev/null; then
    CPU_CORES=$(nproc 2>/dev/null)
    if [ -n "$CPU_CORES" ]; then
        check_pass "CPU cores: ${CPU_CORES}"
    fi
fi

# =============================================================================
section "16. DISK SPACE"
# =============================================================================

if command -v df &> /dev/null; then
    # Try home directory first (cPanel quota), fallback to root
    HOME_DIR=$(eval echo ~)
    DISK_INFO=$(df -h "$HOME_DIR" 2>/dev/null | tail -1)

    if [ -n "$DISK_INFO" ]; then
        USAGE=$(echo "$DISK_INFO" | awk '{print $5}' | tr -d '%')
        AVAIL=$(echo "$DISK_INFO" | awk '{print $4}')
        TOTAL_DISK=$(echo "$DISK_INFO" | awk '{print $2}')

        if [ -n "$USAGE" ]; then
            if [ "$USAGE" -gt 90 ]; then
                check_fail_important "Disk usage ${USAGE}% (tersisa ${AVAIL} dari ${TOTAL_DISK}) - HAMPIR PENUH!"
            elif [ "$USAGE" -gt 80 ]; then
                check_warn "Disk usage ${USAGE}% (tersisa ${AVAIL} dari ${TOTAL_DISK})"
            else
                check_pass "Disk usage ${USAGE}% (tersisa ${AVAIL} dari ${TOTAL_DISK})"
            fi
        fi
    fi
fi

# =============================================================================
section "17. GIT"
# =============================================================================

if command -v git &> /dev/null; then
    GIT_VER=$(git --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Git v${GIT_VER}"

    # Check if project is a git repo
    if [ -d "${APP_DIR}/.git" ]; then
        check_pass "Project is a git repository"
        CURRENT_BRANCH=$(cd "$APP_DIR" && git branch --show-current 2>/dev/null)
        LAST_COMMIT=$(cd "$APP_DIR" && git log -1 --format='%h %s' 2>/dev/null)
        echo -e "    ${INFO} Branch: ${CURRENT_BRANCH}"
        echo -e "    ${INFO} Last commit: ${LAST_COMMIT}"
    fi
else
    check_warn "Git not found (dibutuhkan untuk deploy via git pull)"
fi

# =============================================================================
section "18. LARAVEL HEALTH"
# =============================================================================

if [ -n "$PHP_BIN" ] && [ -f "${APP_DIR}/artisan" ]; then
    # Check if artisan runs
    ARTISAN_TEST=$($PHP_BIN "${APP_DIR}/artisan" --version 2>/dev/null)
    if [ $? -eq 0 ] && [ -n "$ARTISAN_TEST" ]; then
        check_pass "Artisan: ${ARTISAN_TEST}"
    else
        check_fail_critical "php artisan gagal dijalankan"
        echo -e "    ${INFO} Cek error: php artisan --version"
    fi

    # Check config cache
    if [ -f "${APP_DIR}/bootstrap/cache/config.php" ]; then
        check_pass "Config cache exists"
    else
        check_warn "Config not cached (run: php artisan config:cache)"
    fi

    # Check route cache
    if [ -f "${APP_DIR}/bootstrap/cache/routes-v7.php" ]; then
        check_pass "Route cache exists"
    else
        check_warn "Routes not cached (run: php artisan route:cache)"
    fi

    # Check view cache
    VIEW_CACHE_DIR="${APP_DIR}/storage/framework/views"
    if [ -d "$VIEW_CACHE_DIR" ]; then
        VIEW_COUNT=$(find "$VIEW_CACHE_DIR" -name "*.php" 2>/dev/null | wc -l)
        if [ "$VIEW_COUNT" -gt 0 ]; then
            check_pass "View cache: ${VIEW_COUNT} compiled views"
        else
            check_warn "Views not cached (run: php artisan view:cache)"
        fi
    fi
fi

# =============================================================================
# SUMMARY
# =============================================================================
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║                              SUMMARY                                       ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${INFO} Environment: ${BOLD}${DETECTED_WEBSERVER}${NC} + ${BOLD}${DETECTED_PHP_HANDLER}${NC}"
echo -e "  Total checks:  ${BOLD}${TOTAL}${NC}"
echo -e "  Passed:        ${GREEN}${PASSED}${NC}"
echo -e "  Failed:        ${RED}${FAILED}${NC}"
echo -e "  Warnings:      ${YELLOW}${WARNINGS}${NC}"

if [ ${#MISSING_CRITICAL[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${RED}${BOLD}CRITICAL - App tidak bisa jalan:${NC}"
    for item in "${MISSING_CRITICAL[@]}"; do
        echo -e "    ${RED}x ${item}${NC}"
    done
fi

if [ ${#MISSING_IMPORTANT[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${YELLOW}${BOLD}IMPORTANT - Fitur tertentu rusak:${NC}"
    for item in "${MISSING_IMPORTANT[@]}"; do
        echo -e "    ${YELLOW}! ${item}${NC}"
    done
fi

if [ ${#MISSING_OPTIONAL[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${YELLOW}${BOLD}WARNINGS:${NC}"
    for item in "${MISSING_OPTIONAL[@]}"; do
        echo -e "    ${YELLOW}~ ${item}${NC}"
    done
fi

if [ ${#MISSING_CRITICAL[@]} -eq 0 ] && [ ${#MISSING_IMPORTANT[@]} -eq 0 ]; then
    echo ""
    echo -e "  ${GREEN}${BOLD}Server siap untuk OASIS!${NC}"
fi

# =============================================================================
# ADMIN REQUEST (jika ada yang perlu diminta ke hosting)
# =============================================================================
if [ ${#ADMIN_ACTIONS[@]} -gt 0 ]; then
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  PERLU BANTUAN HOSTING PROVIDER / ADMIN:${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    IDX=1
    for action in "${ADMIN_ACTIONS[@]}"; do
        echo -e "  ${IDX}. ${action}"
        IDX=$((IDX + 1))
    done
    echo ""
fi

# =============================================================================
# DEPLOY CHECKLIST
# =============================================================================
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  DEPLOY CHECKLIST:${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  cd ${APP_DIR}"
echo -e "  git pull origin main"
echo -e "  composer install --no-dev --optimize-autoloader"
echo -e "  npm ci && npm run build          # atau upload public/build/ dari lokal"
echo -e "  php artisan migrate --force"
echo -e "  php artisan config:cache"
echo -e "  php artisan route:cache"
echo -e "  php artisan view:cache"
echo -e "  php artisan storage:link          # sekali saja"
echo ""
