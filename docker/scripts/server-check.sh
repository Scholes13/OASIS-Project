#!/bin/bash
# =============================================================================
# OASIS Production Server Readiness Checker (Non-Docker / Bare Metal)
# =============================================================================
# Jalankan dari dalam folder project di server:
#   cd /path/to/oasis
#   sudo bash docker/scripts/server-check.sh
#
# Script auto-detect project directory dari current working directory (pwd).
# Jika dijalankan dari luar project, bisa override:
#   APP_DIR=/path/to/oasis sudo bash server-check.sh
# =============================================================================

set -u

# --- Detect sudo/root access ---
IS_ROOT=false
if [ "$(id -u)" -eq 0 ]; then
    IS_ROOT=true
fi

# Items that need admin to install (collected when no sudo)
ADMIN_INSTALL_CMDS=()

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

# --- Auto-detect project directory ---
detect_app_dir() {
    # 1. Jika APP_DIR sudah di-set via environment, pakai itu
    if [ -n "${APP_DIR:-}" ]; then
        echo "$APP_DIR"
        return
    fi

    # 2. Cek current working directory (pwd) - apakah ini Laravel project?
    if [ -f "$(pwd)/artisan" ] && [ -f "$(pwd)/composer.json" ]; then
        echo "$(pwd)"
        return
    fi

    # 3. Jika script ada di dalam repo (docker/scripts/server-check.sh),
    #    naik 2 level ke root project
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    CANDIDATE="$(cd "$SCRIPT_DIR/../.." 2>/dev/null && pwd)"
    if [ -f "$CANDIDATE/artisan" ] && [ -f "$CANDIDATE/composer.json" ]; then
        echo "$CANDIDATE"
        return
    fi

    # 4. Fallback
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
    echo -e "  ${FAIL} $1 ${RED}(CRITICAL - app tidak bisa jalan)${NC}"
}

check_fail_important() {
    TOTAL=$((TOTAL + 1))
    FAILED=$((FAILED + 1))
    MISSING_IMPORTANT+=("$1")
    echo -e "  ${FAIL} $1 ${YELLOW}(fitur tertentu tidak jalan)${NC}"
}

check_warn() {
    TOTAL=$((TOTAL + 1))
    WARNINGS=$((WARNINGS + 1))
    MISSING_OPTIONAL+=("$1")
    echo -e "  ${WARN} $1"
}

# Collect install commands that need admin/sudo
need_admin() {
    # $1 = apt-get install command or similar
    ADMIN_INSTALL_CMDS+=("$1")
}

section() {
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  $1${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Detect PHP binary
find_php() {
    for bin in php8.2 php8.3 php; do
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
echo -e "${BOLD}║       OASIS - Production Server Readiness Check (Bare Metal)               ║${NC}"
echo -e "${BOLD}║       $(date '+%Y-%m-%d %H:%M:%S')                                                    ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${INFO} App directory: ${BOLD}${APP_DIR}${NC}"

if [ "$IS_ROOT" = true ]; then
    echo -e "  ${PASS} Running as root/sudo - full check mode"
else
    echo -e "  ${WARN} Running TANPA sudo - beberapa check terbatas"
    echo -e "  ${INFO} Untuk full check: sudo bash docker/scripts/server-check.sh"
    echo -e "  ${INFO} Script tetap jalan dan akan generate daftar untuk minta admin."
fi

# Validate that this is actually a Laravel/OASIS project
if [ -f "${APP_DIR}/artisan" ] && [ -f "${APP_DIR}/composer.json" ]; then
    echo -e "  ${PASS} Laravel project detected (artisan + composer.json found)"
else
    echo -e "  ${RED}${BOLD}  ERROR: '${APP_DIR}' bukan Laravel project!${NC}"
    echo -e "  ${RED}  File artisan atau composer.json tidak ditemukan.${NC}"
    echo ""
    echo -e "  ${INFO} Cara pakai yang benar:"
    echo -e "  ${INFO}   cd /path/to/oasis-project"
    echo -e "  ${INFO}   bash docker/scripts/server-check.sh"
    echo ""
    echo -e "  ${INFO} Atau override manual:"
    echo -e "  ${INFO}   APP_DIR=/path/to/oasis bash server-check.sh"
    echo ""
    exit 1
fi

# =============================================================================
section "1. PHP 8.2+"
# =============================================================================

PHP_BIN=$(find_php)

if [ -n "$PHP_BIN" ]; then
    PHP_VER=$($PHP_BIN -r 'echo PHP_VERSION;' 2>/dev/null)
    PHP_MAJOR=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION;' 2>/dev/null)
    PHP_MINOR=$($PHP_BIN -r 'echo PHP_MINOR_VERSION;' 2>/dev/null)

    if [ "$PHP_MAJOR" -gt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]); then
        check_pass "PHP ${PHP_VER} (binary: ${PHP_BIN})"
    else
        check_fail_critical "PHP ${PHP_VER} terlalu lama (butuh >= 8.2)"
    fi
else
    check_fail_critical "PHP not installed"
    need_admin "# PHP 8.2 + extensions"
    need_admin "add-apt-repository ppa:ondrej/php -y"
    need_admin "apt-get update"
    need_admin "apt-get install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-gd php8.2-zip php8.2-bcmath php8.2-mbstring php8.2-intl php8.2-xml php8.2-curl php8.2-redis php8.2-opcache php8.2-fileinfo"
    echo -e "    ${INFO} Minta admin install PHP 8.2"
fi

# =============================================================================
section "2. PHP EXTENSIONS"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    # List of required extensions and what breaks without them
    declare -A EXT_CRITICAL=(
        ["pdo_mysql"]="Koneksi database - APP MATI TOTAL"
        ["mbstring"]="String processing - APP MATI TOTAL"
        ["openssl"]="HTTPS, encryption - APP MATI TOTAL"
        ["tokenizer"]="Laravel blade/compiler - APP MATI TOTAL"
        ["xml"]="Laravel/Composer - APP MATI TOTAL"
        ["ctype"]="Laravel validation - APP MATI TOTAL"
        ["json"]="JSON processing - APP MATI TOTAL"
        ["fileinfo"]="File upload detection - upload gagal"
        ["curl"]="HTTP client - external API calls gagal"
        ["dom"]="PhpSpreadsheet/XML - export gagal"
    )

    declare -A EXT_IMPORTANT=(
        ["gd"]="QR code generation & image processing gagal"
        ["zip"]="Export Excel (PhpSpreadsheet) gagal"
        ["bcmath"]="Kalkulasi presisi tinggi gagal"
        ["intl"]="Formatting tanggal/angka/locale gagal"
        ["redis"]="Queue worker, cache, session Redis gagal"
        ["pcntl"]="Queue worker graceful shutdown gagal"
    )

    declare -A EXT_OPTIONAL=(
        ["opcache"]="App jalan tapi SANGAT LAMBAT di production"
        ["exif"]="Metadata gambar tidak terbaca"
    )

    LOADED_EXTS=$($PHP_BIN -m 2>/dev/null)

    echo -e "  ${CYAN}Critical extensions (app mati tanpa ini):${NC}"
    for ext in "${!EXT_CRITICAL[@]}"; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_fail_critical "ext-${ext} -- ${EXT_CRITICAL[$ext]}"
        fi
    done

    echo ""
    echo -e "  ${CYAN}Important extensions (fitur rusak tanpa ini):${NC}"
    for ext in "${!EXT_IMPORTANT[@]}"; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_fail_important "ext-${ext} -- ${EXT_IMPORTANT[$ext]}"
        fi
    done

    echo ""
    echo -e "  ${CYAN}Recommended extensions:${NC}"
    for ext in "${!EXT_OPTIONAL[@]}"; do
        if echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            check_pass "ext-${ext}"
        else
            check_warn "ext-${ext} -- ${EXT_OPTIONAL[$ext]}"
        fi
    done

    # Show install command for missing extensions
    ALL_MISSING=()
    for ext in "${!EXT_CRITICAL[@]}" "${!EXT_IMPORTANT[@]}" "${!EXT_OPTIONAL[@]}"; do
        if ! echo "$LOADED_EXTS" | grep -qi "^${ext}$"; then
            ALL_MISSING+=("$ext")
        fi
    done

    if [ ${#ALL_MISSING[@]} -gt 0 ]; then
        PHP_SHORT_VER=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null)
        INSTALL_PKGS=""
        for ext in "${ALL_MISSING[@]}"; do
            INSTALL_PKGS+=" php${PHP_SHORT_VER}-${ext}"
        done
        echo ""
        echo -e "  ${INFO} Install missing extensions:"
        echo -e "  ${INFO}   apt-get install -y${INSTALL_PKGS}"
        echo -e "  ${INFO}   systemctl restart php${PHP_SHORT_VER}-fpm"
        need_admin "# PHP extensions yang kurang"
        need_admin "apt-get install -y${INSTALL_PKGS}"
        need_admin "systemctl restart php${PHP_SHORT_VER}-fpm"
    fi
else
    echo -e "  ${INFO} Skipped - PHP not installed"
fi

# =============================================================================
section "3. COMPOSER"
# =============================================================================

if command -v composer &> /dev/null; then
    COMPOSER_VER=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    COMPOSER_MAJOR=$(echo "$COMPOSER_VER" | cut -d. -f1)
    if [ "$COMPOSER_MAJOR" -ge 2 ]; then
        check_pass "Composer v${COMPOSER_VER}"
    else
        check_warn "Composer v${COMPOSER_VER} (v2+ recommended)"
    fi
else
    check_fail_critical "Composer not installed"
    need_admin "# Composer"
    need_admin "curl -sS https://getcomposer.org/installer | php"
    need_admin "mv composer.phar /usr/local/bin/composer"
    echo -e "    ${INFO} Minta admin install Composer"
fi

# =============================================================================
section "4. NODE.JS & NPM"
# =============================================================================

if command -v node &> /dev/null; then
    NODE_VER=$(node --version 2>/dev/null | tr -d 'v')
    NODE_MAJOR=$(echo "$NODE_VER" | cut -d. -f1)
    if [ "$NODE_MAJOR" -ge 20 ]; then
        check_pass "Node.js v${NODE_VER}"
    elif [ "$NODE_MAJOR" -ge 18 ]; then
        check_warn "Node.js v${NODE_VER} (v20+ recommended, Vite 7 butuh modern Node)"
    else
        check_fail_critical "Node.js v${NODE_VER} terlalu lama (butuh >= 18, recommended 20+)"
    fi
else
    check_fail_critical "Node.js not installed (dibutuhkan untuk build frontend)"
    need_admin "# Node.js 20"
    need_admin "curl -fsSL https://deb.nodesource.com/setup_20.x | bash -"
    need_admin "apt-get install -y nodejs"
    echo -e "    ${INFO} Minta admin install Node.js 20"
fi

if command -v npm &> /dev/null; then
    NPM_VER=$(npm --version 2>/dev/null)
    check_pass "npm v${NPM_VER}"
else
    if ! command -v node &> /dev/null; then
        echo -e "  ${INFO} npm akan ter-install bersama Node.js"
    else
        check_fail_critical "npm not installed"
    fi
fi

# =============================================================================
section "5. MYSQL / MARIADB"
# =============================================================================

# Check MySQL/MariaDB server
if command -v mysql &> /dev/null; then
    MYSQL_VER=$(mysql --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "MySQL/MariaDB client v${MYSQL_VER}"
else
    check_warn "MySQL client not found (OK jika database di server lain)"
fi

if systemctl is-active --quiet mysql 2>/dev/null; then
    check_pass "MySQL service is running"
elif systemctl is-active --quiet mysqld 2>/dev/null; then
    check_pass "MySQL (mysqld) service is running"
elif systemctl is-active --quiet mariadb 2>/dev/null; then
    check_pass "MariaDB service is running"
elif pgrep -x mysqld &> /dev/null || pgrep -x mariadbd &> /dev/null; then
    check_pass "MySQL/MariaDB process is running"
else
    check_warn "MySQL/MariaDB service not detected on this host"
    echo -e "    ${INFO} OK jika database di server lain. Pastikan DB_HOST di .env benar."
    need_admin "# MySQL (skip jika DB di server lain)"
    need_admin "apt-get install -y mysql-server"
fi

if ss -tlnp 2>/dev/null | grep -q ':3306' || netstat -tlnp 2>/dev/null | grep -q ':3306'; then
    check_pass "Port 3306 (MySQL) is listening"
else
    echo -e "  ${INFO} Port 3306 not listening locally (OK jika DB remote)"
fi

# =============================================================================
section "6. REDIS"
# =============================================================================

if command -v redis-server &> /dev/null; then
    REDIS_VER=$(redis-server --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Redis server v${REDIS_VER}"
else
    check_fail_important "Redis server not installed"
    echo -e "    ${INFO} Tanpa Redis: queue worker, broadcasting (Reverb), session & cache Redis GAGAL"
    need_admin "# Redis"
    need_admin "apt-get install -y redis-server"
    need_admin "systemctl enable --now redis-server"
fi

if systemctl is-active --quiet redis-server 2>/dev/null || systemctl is-active --quiet redis 2>/dev/null; then
    check_pass "Redis service is running"
elif pgrep -x redis-server &> /dev/null; then
    check_pass "Redis process is running"
else
    if command -v redis-server &> /dev/null; then
        check_fail_important "Redis installed but NOT running"
        echo -e "    ${INFO} Start: systemctl enable --now redis-server"
    else
        echo -e "  ${INFO} Redis not running (not installed)"
    fi
fi

if ss -tlnp 2>/dev/null | grep -q ':6379' || netstat -tlnp 2>/dev/null | grep -q ':6379'; then
    check_pass "Port 6379 (Redis) is listening"
else
    if command -v redis-server &> /dev/null; then
        check_warn "Port 6379 not listening"
    fi
fi

# =============================================================================
section "7. NGINX"
# =============================================================================

if command -v nginx &> /dev/null; then
    NGINX_VER=$(nginx -v 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Nginx v${NGINX_VER}"
else
    check_fail_critical "Nginx not installed"
    need_admin "# Nginx"
    need_admin "apt-get install -y nginx"
    need_admin "systemctl enable --now nginx"
    echo -e "    ${INFO} Minta admin install Nginx"
fi

if systemctl is-active --quiet nginx 2>/dev/null; then
    check_pass "Nginx service is running"
else
    if command -v nginx &> /dev/null; then
        check_fail_important "Nginx installed but NOT running"
        echo -e "    ${INFO} Start: systemctl enable --now nginx"
    fi
fi

# Check Nginx config test (needs root)
if command -v nginx &> /dev/null; then
    if [ "$IS_ROOT" = true ]; then
        if nginx -t 2>/dev/null; then
            check_pass "Nginx config syntax OK"
        else
            check_fail_important "Nginx config has errors (nginx -t failed)"
            echo -e "    ${INFO} Fix: sudo nginx -t 2>&1  (lihat error detail)"
        fi
    else
        echo -e "  ${INFO} Nginx config test skipped (butuh sudo)"
    fi
fi

# Check ports
if ss -tlnp 2>/dev/null | grep -q ':80' || netstat -tlnp 2>/dev/null | grep -q ':80'; then
    check_pass "Port 80 (HTTP) is listening"
else
    check_warn "Port 80 not listening"
fi

if ss -tlnp 2>/dev/null | grep -q ':443' || netstat -tlnp 2>/dev/null | grep -q ':443'; then
    check_pass "Port 443 (HTTPS) is listening"
else
    check_warn "Port 443 not listening (HTTPS belum aktif)"
fi

# =============================================================================
section "8. PHP-FPM"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    PHP_SHORT_VER=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null)
    FPM_SERVICE="php${PHP_SHORT_VER}-fpm"

    if systemctl is-active --quiet "$FPM_SERVICE" 2>/dev/null; then
        check_pass "${FPM_SERVICE} service is running"
    elif systemctl is-active --quiet php-fpm 2>/dev/null; then
        check_pass "php-fpm service is running"
    elif pgrep -x php-fpm &> /dev/null || pgrep -f "php-fpm" &> /dev/null; then
        check_pass "PHP-FPM process is running"
    else
        check_fail_critical "PHP-FPM is NOT running"
        need_admin "# PHP-FPM"
        need_admin "apt-get install -y php${PHP_SHORT_VER}-fpm"
        need_admin "systemctl enable --now php${PHP_SHORT_VER}-fpm"
        echo -e "    ${INFO} Minta admin install/start PHP-FPM"
    fi

    # Check FPM socket/port
    FPM_SOCK="/run/php/php${PHP_SHORT_VER}-fpm.sock"
    if [ -S "$FPM_SOCK" ]; then
        check_pass "PHP-FPM socket exists: ${FPM_SOCK}"
    elif ss -tlnp 2>/dev/null | grep -q ':9000'; then
        check_pass "PHP-FPM listening on port 9000"
    else
        check_warn "PHP-FPM socket/port not detected"
        echo -e "    ${INFO} Expected socket: ${FPM_SOCK}"
        echo -e "    ${INFO} Atau port 9000 jika dikonfigurasi TCP"
    fi
else
    echo -e "  ${INFO} Skipped - PHP not installed"
fi

# =============================================================================
section "9. CHROMIUM / GOOGLE CHROME (Browsershot PDF)"
# =============================================================================

CHROME_FOUND=false
CHROME_PATH=""

# Check various Chrome/Chromium binaries
for bin in google-chrome google-chrome-stable chromium chromium-browser; do
    if command -v "$bin" &> /dev/null; then
        CHROME_PATH=$(command -v "$bin")
        CHROME_VER=$("$bin" --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
        check_pass "Chrome/Chromium: ${bin} v${CHROME_VER}"
        CHROME_FOUND=true
        break
    fi
done

if [ "$CHROME_FOUND" = false ]; then
    # Check common paths
    for path in /usr/bin/google-chrome /usr/bin/chromium /usr/bin/chromium-browser /snap/bin/chromium; do
        if [ -x "$path" ]; then
            CHROME_PATH="$path"
            CHROME_VER=$("$path" --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
            check_pass "Chrome/Chromium found: ${path} v${CHROME_VER}"
            CHROME_FOUND=true
            break
        fi
    done
fi

if [ "$CHROME_FOUND" = false ]; then
    check_fail_important "Chrome/Chromium NOT found"
    echo -e "    ${INFO} Tanpa ini: SEMUA fitur cetak/export PDF gagal (Browsershot)"
    need_admin "# Chromium (untuk PDF generation)"
    need_admin "apt-get install -y chromium-browser"
fi

# Check required libs for headless Chrome
if [ "$CHROME_FOUND" = true ]; then
    MISSING_LIBS=()
    for lib in libnss3 libatk-bridge2.0-0 libdrm2 libxkbcommon0 libgbm1 libasound2; do
        if ! dpkg -l "$lib" &> /dev/null 2>&1 && ! ldconfig -p 2>/dev/null | grep -q "$lib"; then
            MISSING_LIBS+=("$lib")
        fi
    done
    if [ ${#MISSING_LIBS[@]} -gt 0 ]; then
        check_warn "Chrome dependency libs mungkin kurang: ${MISSING_LIBS[*]}"
        echo -e "    ${INFO} Install: apt-get install -y ${MISSING_LIBS[*]}"
    else
        echo -e "  ${INFO} Chrome headless dependencies terlihat lengkap"
    fi
fi

# =============================================================================
section "10. SSL / CERTBOT"
# =============================================================================

if command -v certbot &> /dev/null; then
    CERTBOT_VER=$(certbot --version 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Certbot v${CERTBOT_VER}"
else
    check_fail_important "Certbot not installed (dibutuhkan untuk SSL Let's Encrypt)"
    need_admin "# Certbot (SSL)"
    need_admin "apt-get install -y certbot python3-certbot-nginx"
fi

# Check existing certificates
if [ -d /etc/letsencrypt/live ]; then
    CERT_COUNT=$(ls -1 /etc/letsencrypt/live/ 2>/dev/null | grep -v README | wc -l)
    if [ "$CERT_COUNT" -gt 0 ]; then
        check_pass "SSL certificates found (${CERT_COUNT} domain(s))"
        for domain_dir in /etc/letsencrypt/live/*/; do
            domain=$(basename "$domain_dir")
            if [ "$domain" != "README" ] && [ -f "${domain_dir}cert.pem" ]; then
                EXPIRY=$(openssl x509 -enddate -noout -in "${domain_dir}cert.pem" 2>/dev/null | cut -d= -f2)
                EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s 2>/dev/null)
                NOW_EPOCH=$(date +%s)
                if [ -n "$EXPIRY_EPOCH" ]; then
                    DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))
                    if [ "$DAYS_LEFT" -lt 7 ]; then
                        check_fail_important "Certificate ${domain}: expires in ${DAYS_LEFT} days!"
                    elif [ "$DAYS_LEFT" -lt 30 ]; then
                        check_warn "Certificate ${domain}: expires in ${DAYS_LEFT} days"
                    else
                        echo -e "    ${INFO} Certificate ${domain}: valid ${DAYS_LEFT} days"
                    fi
                fi
            fi
        done
    else
        check_warn "No SSL certificates in /etc/letsencrypt/live/"
    fi
else
    check_warn "No Let's Encrypt directory found"
fi

# Certbot auto-renewal
if systemctl is-enabled certbot.timer &> /dev/null 2>&1; then
    check_pass "Certbot auto-renewal timer enabled"
elif crontab -l 2>/dev/null | grep -q "certbot"; then
    check_pass "Certbot renewal cron job found"
elif [ -f /etc/cron.d/certbot ]; then
    check_pass "Certbot renewal cron in /etc/cron.d/"
else
    check_warn "Certbot auto-renewal not detected"
    echo -e "    ${INFO} SSL certificates perlu di-renew otomatis!"
    echo -e "    ${INFO} Setup: systemctl enable certbot.timer"
fi

# =============================================================================
section "11. SUPERVISOR (Queue Worker & Scheduler)"
# =============================================================================

if command -v supervisord &> /dev/null; then
    SUP_VER=$(supervisord --version 2>/dev/null)
    check_pass "Supervisor v${SUP_VER}"
else
    check_fail_important "Supervisor not installed"
    echo -e "    ${INFO} Tanpa Supervisor: queue worker & scheduler tidak auto-restart jika crash"
    need_admin "# Supervisor (queue worker & scheduler)"
    need_admin "apt-get install -y supervisor"
    need_admin "systemctl enable --now supervisor"
fi

if systemctl is-active --quiet supervisor 2>/dev/null || systemctl is-active --quiet supervisord 2>/dev/null; then
    check_pass "Supervisor service is running"
else
    if command -v supervisord &> /dev/null; then
        check_fail_important "Supervisor installed but NOT running"
        echo -e "    ${INFO} Start: systemctl enable --now supervisor"
    fi
fi

# Check for OASIS worker configs
if [ -d /etc/supervisor/conf.d ]; then
    if ls /etc/supervisor/conf.d/ 2>/dev/null | grep -qi "oasis\|numbering\|queue\|laravel"; then
        check_pass "Supervisor config for app found in /etc/supervisor/conf.d/"
    else
        check_warn "No Supervisor config for OASIS queue worker"
        echo -e "    ${INFO} Buat file /etc/supervisor/conf.d/oasis-worker.conf:"
        echo -e "    ${INFO}"
        echo -e "    ${INFO}   [program:oasis-worker]"
        echo -e "    ${INFO}   process_name=%(program_name)s_%(process_num)02d"
        echo -e "    ${INFO}   command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90"
        echo -e "    ${INFO}   autostart=true"
        echo -e "    ${INFO}   autorestart=true"
        echo -e "    ${INFO}   stopasgroup=true"
        echo -e "    ${INFO}   killasgroup=true"
        echo -e "    ${INFO}   user=www-data"
        echo -e "    ${INFO}   numprocs=2"
        echo -e "    ${INFO}   redirect_stderr=true"
        echo -e "    ${INFO}   stdout_logfile=${APP_DIR}/storage/logs/worker.log"
        echo -e "    ${INFO}"
        echo -e "    ${INFO}   [program:oasis-scheduler]"
        echo -e "    ${INFO}   command=php ${APP_DIR}/artisan schedule:work"
        echo -e "    ${INFO}   autostart=true"
        echo -e "    ${INFO}   autorestart=true"
        echo -e "    ${INFO}   user=www-data"
        echo -e "    ${INFO}   redirect_stderr=true"
        echo -e "    ${INFO}   stdout_logfile=${APP_DIR}/storage/logs/scheduler.log"
        echo -e "    ${INFO}"
        echo -e "    ${INFO} Lalu: supervisorctl reread && supervisorctl update"
    fi
fi

# =============================================================================
section "12. PHP CONFIGURATION (php.ini)"
# =============================================================================

if [ -n "$PHP_BIN" ]; then
    echo -e "  ${CYAN}Mengecek php.ini settings yang penting untuk production:${NC}"

    # memory_limit
    MEM_LIMIT=$($PHP_BIN -r 'echo ini_get("memory_limit");' 2>/dev/null)
    MEM_NUM=$(echo "$MEM_LIMIT" | grep -oP '\d+')
    if [ -n "$MEM_NUM" ] && [ "$MEM_NUM" -ge 256 ]; then
        check_pass "memory_limit = ${MEM_LIMIT} (>= 256M)"
    elif [ "$MEM_LIMIT" = "-1" ]; then
        check_pass "memory_limit = unlimited"
    else
        check_warn "memory_limit = ${MEM_LIMIT} (recommended: 256M)"
    fi

    # max_execution_time
    MAX_EXEC=$($PHP_BIN -r 'echo ini_get("max_execution_time");' 2>/dev/null)
    if [ -n "$MAX_EXEC" ] && [ "$MAX_EXEC" -ge 120 ]; then
        check_pass "max_execution_time = ${MAX_EXEC}s (>= 120)"
    elif [ "$MAX_EXEC" = "0" ]; then
        check_pass "max_execution_time = unlimited"
    else
        check_warn "max_execution_time = ${MAX_EXEC}s (recommended: 120)"
    fi

    # upload_max_filesize
    UPLOAD_MAX=$($PHP_BIN -r 'echo ini_get("upload_max_filesize");' 2>/dev/null)
    UPLOAD_NUM=$(echo "$UPLOAD_MAX" | grep -oP '\d+')
    if [ -n "$UPLOAD_NUM" ] && [ "$UPLOAD_NUM" -ge 50 ]; then
        check_pass "upload_max_filesize = ${UPLOAD_MAX} (>= 50M)"
    else
        check_warn "upload_max_filesize = ${UPLOAD_MAX} (recommended: 50M)"
    fi

    # post_max_size
    POST_MAX=$($PHP_BIN -r 'echo ini_get("post_max_size");' 2>/dev/null)
    POST_NUM=$(echo "$POST_MAX" | grep -oP '\d+')
    if [ -n "$POST_NUM" ] && [ "$POST_NUM" -ge 50 ]; then
        check_pass "post_max_size = ${POST_MAX} (>= 50M)"
    else
        check_warn "post_max_size = ${POST_MAX} (recommended: 50M)"
    fi

    # OPcache
    OPCACHE_ENABLED=$($PHP_BIN -r 'echo ini_get("opcache.enable");' 2>/dev/null)
    if [ "$OPCACHE_ENABLED" = "1" ]; then
        check_pass "OPcache enabled"

        OPCACHE_MEM=$($PHP_BIN -r 'echo ini_get("opcache.memory_consumption");' 2>/dev/null)
        if [ -n "$OPCACHE_MEM" ] && [ "$OPCACHE_MEM" -ge 128 ]; then
            check_pass "opcache.memory_consumption = ${OPCACHE_MEM}MB (>= 128)"
        else
            check_warn "opcache.memory_consumption = ${OPCACHE_MEM}MB (recommended: 128)"
        fi

        OPCACHE_VALIDATE=$($PHP_BIN -r 'echo ini_get("opcache.validate_timestamps");' 2>/dev/null)
        if [ "$OPCACHE_VALIDATE" = "0" ]; then
            check_pass "opcache.validate_timestamps = 0 (production optimal)"
        else
            check_warn "opcache.validate_timestamps = 1 (set ke 0 untuk production, restart FPM setelah deploy)"
        fi
    else
        check_warn "OPcache DISABLED (app akan sangat lambat!)"
        echo -e "    ${INFO} Enable di php.ini: opcache.enable=1"
    fi

    # Timezone
    TZ=$($PHP_BIN -r 'echo ini_get("date.timezone");' 2>/dev/null)
    if [ -n "$TZ" ]; then
        check_pass "date.timezone = ${TZ}"
    else
        check_warn "date.timezone not set (recommended: Asia/Jakarta)"
    fi

    echo ""
    echo -e "  ${INFO} PHP ini file: $($PHP_BIN --ini 2>/dev/null | head -1)"
else
    echo -e "  ${INFO} Skipped - PHP not installed"
fi

# =============================================================================
section "13. LARAVEL DIRECTORY & PERMISSIONS"
# =============================================================================

if [ -d "$APP_DIR" ]; then
    check_pass "App directory exists: ${APP_DIR}"

    # Check .env
    if [ -f "${APP_DIR}/.env" ]; then
        check_pass ".env file exists"
    else
        check_fail_critical ".env file MISSING"
        echo -e "    ${INFO} Copy: cp ${APP_DIR}/.env.example ${APP_DIR}/.env"
        echo -e "    ${INFO} Generate key: php artisan key:generate"
    fi

    # Check vendor
    if [ -d "${APP_DIR}/vendor" ]; then
        check_pass "vendor/ directory exists (composer install done)"
    else
        check_fail_critical "vendor/ directory MISSING"
        echo -e "    ${INFO} Run: cd ${APP_DIR} && composer install --no-dev --optimize-autoloader"
    fi

    # Check node_modules (needed for build, not runtime)
    if [ -d "${APP_DIR}/public/build" ]; then
        check_pass "public/build/ exists (frontend sudah di-build)"
    else
        check_fail_critical "public/build/ MISSING (frontend belum di-build)"
        echo -e "    ${INFO} Run: cd ${APP_DIR} && npm ci && npm run build"
    fi

    # Check writable directories
    WRITABLE_DIRS=(
        "storage/app"
        "storage/app/public"
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
    )

    echo ""
    echo -e "  ${CYAN}Directory permissions (harus writable oleh www-data):${NC}"
    for dir in "${WRITABLE_DIRS[@]}"; do
        FULL_PATH="${APP_DIR}/${dir}"
        if [ -d "$FULL_PATH" ]; then
            if [ -w "$FULL_PATH" ]; then
                check_pass "${dir}/ writable"
            else
                check_fail_important "${dir}/ NOT writable"
            fi
        else
            check_fail_important "${dir}/ directory MISSING"
        fi
    done

    # Check storage link
    if [ -L "${APP_DIR}/public/storage" ]; then
        check_pass "public/storage symlink exists"
    else
        check_warn "public/storage symlink missing"
        echo -e "    ${INFO} Run: cd ${APP_DIR} && php artisan storage:link"
    fi

    # Check ownership
    OWNER=$(stat -c '%U' "${APP_DIR}/storage" 2>/dev/null)
    if [ "$OWNER" = "www-data" ]; then
        check_pass "storage/ owned by www-data"
    elif [ -n "$OWNER" ]; then
        check_warn "storage/ owned by '${OWNER}' (expected: www-data)"
        echo -e "    ${INFO} Fix: chown -R www-data:www-data ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache"
    fi

else
    check_warn "App directory not found: ${APP_DIR}"
    echo -e "    ${INFO} Set path: APP_DIR=/your/path sudo bash server-check.sh"
    echo -e "    ${INFO} Atau clone repo ke ${APP_DIR}"
fi

# =============================================================================
section "14. DISK SPACE"
# =============================================================================

ROOT_USAGE=$(df / 2>/dev/null | tail -1 | awk '{print $5}' | tr -d '%')
ROOT_AVAIL=$(df -h / 2>/dev/null | tail -1 | awk '{print $4}')

if [ -n "$ROOT_USAGE" ]; then
    if [ "$ROOT_USAGE" -gt 90 ]; then
        check_fail_important "Disk usage ${ROOT_USAGE}% (tersisa ${ROOT_AVAIL}) - HAMPIR PENUH!"
    elif [ "$ROOT_USAGE" -gt 80 ]; then
        check_warn "Disk usage ${ROOT_USAGE}% (tersisa ${ROOT_AVAIL}) - mulai penuh"
    else
        check_pass "Disk usage ${ROOT_USAGE}% (tersisa ${ROOT_AVAIL})"
    fi
fi

# =============================================================================
section "15. MEMORY & CPU"
# =============================================================================

TOTAL_RAM=$(free -m 2>/dev/null | awk '/^Mem:/{print $2}')
AVAIL_RAM=$(free -m 2>/dev/null | awk '/^Mem:/{print $7}')

if [ -n "$TOTAL_RAM" ]; then
    if [ "$TOTAL_RAM" -lt 1024 ]; then
        check_fail_important "RAM total: ${TOTAL_RAM}MB (minimum 2GB recommended)"
    elif [ "$TOTAL_RAM" -lt 2048 ]; then
        check_warn "RAM total: ${TOTAL_RAM}MB (2GB+ recommended untuk Chromium)"
    else
        check_pass "RAM total: ${TOTAL_RAM}MB"
    fi

    if [ -n "$AVAIL_RAM" ] && [ "$AVAIL_RAM" -lt 512 ]; then
        check_warn "RAM available: ${AVAIL_RAM}MB (rendah!)"
    elif [ -n "$AVAIL_RAM" ]; then
        echo -e "  ${INFO} RAM available: ${AVAIL_RAM}MB"
    fi
fi

CPU_CORES=$(nproc 2>/dev/null)
if [ -n "$CPU_CORES" ]; then
    if [ "$CPU_CORES" -lt 2 ]; then
        check_warn "CPU cores: ${CPU_CORES} (2+ recommended)"
    else
        check_pass "CPU cores: ${CPU_CORES}"
    fi
fi

# =============================================================================
section "16. SWAP SPACE"
# =============================================================================

SWAP_TOTAL=$(free -m 2>/dev/null | awk '/^Swap:/{print $2}')
if [ -n "$SWAP_TOTAL" ] && [ "$SWAP_TOTAL" -gt 0 ]; then
    check_pass "Swap space: ${SWAP_TOTAL}MB"
else
    check_warn "No swap space (recommended 1-2GB untuk server kecil)"
    need_admin "# Swap space"
    need_admin "fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile"
    need_admin "echo '/swapfile none swap sw 0 0' >> /etc/fstab"
fi

# =============================================================================
section "17. FIREWALL & PORTS"
# =============================================================================

if command -v ufw &> /dev/null; then
    UFW_STATUS=$(ufw status 2>/dev/null | head -1)
    echo -e "  ${INFO} UFW: ${UFW_STATUS}"

    if ufw status 2>/dev/null | grep -qi "inactive"; then
        echo -e "  ${INFO} UFW inactive - pastikan firewall lain tidak block port"
    else
        for port_desc in "22/tcp:SSH" "80/tcp:HTTP" "443/tcp:HTTPS"; do
            port=$(echo "$port_desc" | cut -d: -f1)
            desc=$(echo "$port_desc" | cut -d: -f2)
            if ufw status 2>/dev/null | grep -q "${port}.*ALLOW"; then
                check_pass "UFW: Port ${port} (${desc}) allowed"
            else
                check_warn "UFW: Port ${port} (${desc}) mungkin tidak di-allow"
                echo -e "    ${INFO} Fix: ufw allow ${port}"
            fi
        done
    fi
elif command -v firewall-cmd &> /dev/null; then
    echo -e "  ${INFO} Firewalld detected"
    for svc in http https ssh; do
        if firewall-cmd --list-services 2>/dev/null | grep -q "$svc"; then
            check_pass "Firewalld: ${svc} allowed"
        else
            check_warn "Firewalld: ${svc} mungkin tidak di-allow"
        fi
    done
else
    echo -e "  ${INFO} No UFW/firewalld detected"
fi

# =============================================================================
section "18. GIT (untuk deploy)"
# =============================================================================

if command -v git &> /dev/null; then
    GIT_VER=$(git --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    check_pass "Git v${GIT_VER}"
else
    check_warn "Git not installed (dibutuhkan untuk deploy via git pull)"
    need_admin "# Git"
    need_admin "apt-get install -y git"
fi

# =============================================================================
section "19. UNZIP (untuk Composer)"
# =============================================================================

if command -v unzip &> /dev/null; then
    check_pass "unzip installed"
else
    check_warn "unzip not installed (Composer butuh ini)"
    need_admin "# Unzip"
    need_admin "apt-get install -y unzip"
fi

# =============================================================================
# SUMMARY
# =============================================================================
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║                              SUMMARY                                       ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  Total checks:  ${BOLD}${TOTAL}${NC}"
echo -e "  Passed:        ${GREEN}${PASSED}${NC}"
echo -e "  Failed:        ${RED}${FAILED}${NC}"
echo -e "  Warnings:      ${YELLOW}${WARNINGS}${NC}"

if [ ${#MISSING_CRITICAL[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${RED}${BOLD}CRITICAL - App tidak bisa jalan tanpa ini:${NC}"
    for item in "${MISSING_CRITICAL[@]}"; do
        echo -e "    ${RED}x ${item}${NC}"
    done
fi

if [ ${#MISSING_IMPORTANT[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${YELLOW}${BOLD}IMPORTANT - Fitur tertentu tidak jalan:${NC}"
    for item in "${MISSING_IMPORTANT[@]}"; do
        echo -e "    ${YELLOW}! ${item}${NC}"
    done
fi

if [ ${#MISSING_OPTIONAL[@]} -gt 0 ]; then
    echo ""
    echo -e "  ${YELLOW}${BOLD}WARNINGS - Perlu perhatian:${NC}"
    for item in "${MISSING_OPTIONAL[@]}"; do
        echo -e "    ${YELLOW}~ ${item}${NC}"
    done
fi

if [ ${#MISSING_CRITICAL[@]} -eq 0 ] && [ ${#MISSING_IMPORTANT[@]} -eq 0 ]; then
    echo ""
    echo -e "  ${GREEN}${BOLD}Server siap untuk deployment OASIS!${NC}"
fi

# =============================================================================
# ADMIN REQUEST (jika tanpa sudo dan ada yang perlu di-install)
# =============================================================================
if [ "$IS_ROOT" = false ] && [ ${#ADMIN_INSTALL_CMDS[@]} -gt 0 ]; then
    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║  TIDAK ADA AKSES SUDO - Kirim pesan ini ke Admin Server                   ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${YELLOW}Copy-paste pesan di bawah ini dan kirim ke admin server:${NC}"
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Plain text output (no colors) for copy-paste
    echo ""
    echo "Pak/Bu Admin,"
    echo ""
    echo "Untuk deploy aplikasi OASIS di server ini, mohon bantu install/jalankan"
    echo "command berikut dengan akses root/sudo:"
    echo ""
    echo "-----------------------------------------------------------"
    echo ""
    echo "apt-get update"
    echo ""
    for cmd in "${ADMIN_INSTALL_CMDS[@]}"; do
        echo "$cmd"
    done
    echo ""
    echo "-----------------------------------------------------------"
    echo ""
    echo "Setelah selesai, mohon info agar saya bisa lanjut deploy."
    echo "Terima kasih."
    echo ""

    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Also save to file for easy copy
    ADMIN_MSG_FILE="${APP_DIR}/storage/admin-install-request.txt"
    # Fallback if storage dir doesn't exist
    if [ ! -d "${APP_DIR}/storage" ]; then
        ADMIN_MSG_FILE="/tmp/oasis-admin-install-request.txt"
    fi

    {
        echo "Pak/Bu Admin,"
        echo ""
        echo "Untuk deploy aplikasi OASIS di server ini, mohon bantu install/jalankan"
        echo "command berikut dengan akses root/sudo:"
        echo ""
        echo "-----------------------------------------------------------"
        echo ""
        echo "apt-get update"
        echo ""
        for cmd in "${ADMIN_INSTALL_CMDS[@]}"; do
            echo "$cmd"
        done
        echo ""
        echo "-----------------------------------------------------------"
        echo ""
        echo "Setelah selesai, mohon info agar saya bisa lanjut deploy."
        echo "Terima kasih."
        echo ""
        echo "(Generated by OASIS server-check.sh on $(date '+%Y-%m-%d %H:%M:%S'))"
    } > "$ADMIN_MSG_FILE" 2>/dev/null

    if [ -f "$ADMIN_MSG_FILE" ]; then
        echo ""
        echo -e "  ${GREEN}Pesan juga disimpan ke file:${NC} ${BOLD}${ADMIN_MSG_FILE}${NC}"
        echo -e "  ${INFO} Kirim file ini ke admin via email/chat."
    fi

    echo ""

# =============================================================================
# QUICK INSTALL GUIDE (jika punya sudo)
# =============================================================================
elif [ "$IS_ROOT" = true ] && [ ${#ADMIN_INSTALL_CMDS[@]} -gt 0 ]; then
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  QUICK FIX - Jalankan command ini untuk install yang kurang:${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "  apt-get update"
    for cmd in "${ADMIN_INSTALL_CMDS[@]}"; do
        echo "  $cmd"
    done
    echo ""
fi

# =============================================================================
# DEPLOY STEPS (selalu tampilkan)
# =============================================================================
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  DEPLOY STEPS (setelah semua ter-install):${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  cd ${APP_DIR}"
echo -e "  git clone <repo-url> .          # atau git pull"
echo -e "  cp .env.example .env            # edit sesuai production"
echo -e "  composer install --no-dev --optimize-autoloader"
echo -e "  npm ci && npm run build"
echo -e "  php artisan key:generate"
echo -e "  php artisan migrate --force"
echo -e "  php artisan storage:link"
echo -e "  php artisan config:cache"
echo -e "  php artisan route:cache"
echo -e "  php artisan view:cache"
echo -e "  chown -R www-data:www-data storage bootstrap/cache"
echo -e "  supervisorctl reread && supervisorctl update"
echo ""
