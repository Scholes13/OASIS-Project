#!/bin/bash
# =============================================================================
# OASIS Supervisor Worker Setup
# =============================================================================
# Generate dan install Supervisor config untuk Laravel queue worker & scheduler.
#
# Jalankan dari folder project:
#   cd ~/oasis.werkudara.com
#   bash scripts/setup-supervisor.sh
#
# Opsi:
#   --dry-run       Tampilkan config saja, jangan install
#   --remove        Hapus config OASIS dari Supervisor
#   --status        Tampilkan status workers
#   --restart       Restart semua OASIS workers
#   --user=www-data Override user yang menjalankan worker (default: auto-detect)
#   --numprocs=2    Jumlah queue worker processes (default: 2)
# =============================================================================

set -u

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# --- Defaults ---
WORKER_USER=""
NUM_PROCS=2
DRY_RUN=false
REMOVE=false
STATUS_ONLY=false
RESTART_ONLY=false

# --- Parse arguments ---
for arg in "$@"; do
    case "$arg" in
        --dry-run)      DRY_RUN=true ;;
        --remove)       REMOVE=true ;;
        --status)       STATUS_ONLY=true ;;
        --restart)      RESTART_ONLY=true ;;
        --user=*)       WORKER_USER="${arg#*=}" ;;
        --numprocs=*)   NUM_PROCS="${arg#*=}" ;;
        --help|-h)
            echo "Usage: bash scripts/setup-supervisor.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --dry-run         Tampilkan config tanpa install"
            echo "  --remove          Hapus config OASIS dari Supervisor"
            echo "  --status          Tampilkan status workers"
            echo "  --restart         Restart semua OASIS workers"
            echo "  --user=USER       User yang menjalankan worker (default: auto-detect)"
            echo "  --numprocs=N      Jumlah queue worker processes (default: 2)"
            echo "  --help            Tampilkan help ini"
            exit 0
            ;;
    esac
done

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

if [ ! -f "${APP_DIR}/artisan" ]; then
    echo -e "${RED}ERROR: '${APP_DIR}' bukan Laravel project${NC}"
    exit 1
fi

# --- Detect PHP binary ---
find_php() {
    for bin in php8.2 php8.3 php8.4 php; do
        if command -v "$bin" &> /dev/null; then
            echo "$(command -v "$bin")"
            return
        fi
    done
    echo "/usr/bin/php"
}

PHP_BIN=$(find_php)

# --- Auto-detect user ---
if [ -z "$WORKER_USER" ]; then
    # Try common web server users
    if [ -d "${APP_DIR}/storage" ]; then
        OWNER=$(stat -c '%U' "${APP_DIR}/storage" 2>/dev/null)
        if [ -n "$OWNER" ] && [ "$OWNER" != "root" ]; then
            WORKER_USER="$OWNER"
        fi
    fi
    # Fallback: current user
    if [ -z "$WORKER_USER" ]; then
        WORKER_USER=$(whoami)
    fi
fi

# --- Detect Supervisor config directory ---
CONF_DIR=""
for dir in /etc/supervisor/conf.d /etc/supervisord.d /usr/local/etc/supervisor/conf.d; do
    if [ -d "$dir" ]; then
        CONF_DIR="$dir"
        break
    fi
done

if [ -z "$CONF_DIR" ]; then
    # Try to detect from supervisord config
    if command -v supervisord &> /dev/null; then
        MAIN_CONF=$(supervisord -c /dev/null 2>&1 | grep -oP '(?<=file: ).*' | head -1)
        if [ -z "$MAIN_CONF" ]; then
            for f in /etc/supervisor/supervisord.conf /etc/supervisord.conf /usr/local/etc/supervisord.conf; do
                if [ -f "$f" ]; then
                    CONF_DIR=$(grep -oP '(?<=files\s=\s).*' "$f" 2>/dev/null | sed 's|/\*\.conf||' | sed 's|/\*\.ini||' | head -1)
                    break
                fi
            done
        fi
    fi
fi

if [ -z "$CONF_DIR" ]; then
    CONF_DIR="/etc/supervisor/conf.d"
fi

CONF_FILE="${CONF_DIR}/oasis.conf"
LOG_DIR="${APP_DIR}/storage/logs"

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       OASIS - Supervisor Worker Setup                                      ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  App directory:  ${BOLD}${APP_DIR}${NC}"
echo -e "  PHP binary:     ${BOLD}${PHP_BIN}${NC}"
echo -e "  Worker user:    ${BOLD}${WORKER_USER}${NC}"
echo -e "  Num processes:  ${BOLD}${NUM_PROCS}${NC}"
echo -e "  Config dir:     ${BOLD}${CONF_DIR}${NC}"
echo -e "  Config file:    ${BOLD}${CONF_FILE}${NC}"
echo ""

# =============================================================================
# --status
# =============================================================================
if [ "$STATUS_ONLY" = true ]; then
    echo -e "${BOLD}Supervisor status:${NC}"
    echo ""
    if command -v supervisorctl &> /dev/null; then
        supervisorctl status 2>/dev/null | grep -i "oasis\|numbering" || echo "  No OASIS workers found"
        echo ""
        echo -e "${BOLD}All processes:${NC}"
        supervisorctl status 2>/dev/null || echo "  Cannot connect to supervisord"
    else
        echo -e "${RED}supervisorctl not found${NC}"
    fi
    exit 0
fi

# =============================================================================
# --restart
# =============================================================================
if [ "$RESTART_ONLY" = true ]; then
    echo -e "${CYAN}Restarting OASIS workers...${NC}"
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread 2>/dev/null
        supervisorctl update 2>/dev/null
        supervisorctl restart "oasis-worker:*" 2>/dev/null
        supervisorctl restart "oasis-scheduler" 2>/dev/null
        echo ""
        echo -e "${GREEN}Done. Current status:${NC}"
        supervisorctl status 2>/dev/null | grep -i "oasis" || echo "  No OASIS workers found"
    else
        echo -e "${RED}supervisorctl not found${NC}"
    fi
    exit 0
fi

# =============================================================================
# --remove
# =============================================================================
if [ "$REMOVE" = true ]; then
    echo -e "${YELLOW}Removing OASIS Supervisor config...${NC}"

    if [ -f "$CONF_FILE" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo -e "${CYAN}[DRY RUN] Would remove: ${CONF_FILE}${NC}"
        else
            # Stop workers first
            if command -v supervisorctl &> /dev/null; then
                supervisorctl stop "oasis-worker:*" 2>/dev/null
                supervisorctl stop "oasis-scheduler" 2>/dev/null
            fi

            rm -f "$CONF_FILE"

            if command -v supervisorctl &> /dev/null; then
                supervisorctl reread 2>/dev/null
                supervisorctl update 2>/dev/null
            fi

            echo -e "${GREEN}Removed ${CONF_FILE}${NC}"
        fi
    else
        echo -e "${YELLOW}Config file not found: ${CONF_FILE}${NC}"
    fi
    exit 0
fi

# =============================================================================
# Generate config
# =============================================================================

CONFIG_CONTENT="[program:oasis-worker]
process_name=%(program_name)s_%(process_num)02d
command=${PHP_BIN} ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90 --memory=128
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${WORKER_USER}
numprocs=${NUM_PROCS}
redirect_stderr=true
stdout_logfile=${LOG_DIR}/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=3600

[program:oasis-scheduler]
process_name=%(program_name)s
command=${PHP_BIN} ${APP_DIR}/artisan schedule:work
autostart=true
autorestart=true
user=${WORKER_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${LOG_DIR}/scheduler.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=3"

# =============================================================================
# Display config
# =============================================================================

echo -e "${BOLD}Supervisor config yang akan di-install:${NC}"
echo ""
echo -e "${CYAN}--- ${CONF_FILE} ---${NC}"
echo ""
echo "$CONFIG_CONTENT"
echo ""
echo -e "${CYAN}--- end ---${NC}"
echo ""

echo -e "${BOLD}Workers yang akan jalan:${NC}"
echo ""
echo -e "  ${GREEN}oasis-worker${NC} (x${NUM_PROCS} processes)"
echo -e "    Queue worker dengan auto-restart"
echo -e "    --sleep=3: poll setiap 3 detik saat idle"
echo -e "    --tries=3: retry failed jobs 3x"
echo -e "    --max-time=3600: restart worker setiap 1 jam (memory leak prevention)"
echo -e "    --timeout=90: max 90 detik per job"
echo -e "    --memory=128: restart jika memory > 128MB"
echo ""
echo -e "  ${GREEN}oasis-scheduler${NC} (1 process)"
echo -e "    Laravel schedule:work (pengganti cron scheduler)"
echo -e "    Auto-restart jika crash"
echo ""

# =============================================================================
# Dry run check
# =============================================================================
if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}[DRY RUN] Tidak ada yang di-install.${NC}"
    echo ""
    echo -e "Untuk install:"
    echo -e "  ${CYAN}bash scripts/setup-supervisor.sh${NC}"
    echo ""
    echo -e "Atau copy-paste config di atas ke:"
    echo -e "  ${CYAN}${CONF_FILE}${NC}"
    echo -e "Lalu jalankan:"
    echo -e "  ${CYAN}supervisorctl reread && supervisorctl update${NC}"
    exit 0
fi

# =============================================================================
# Install
# =============================================================================

echo -e "${CYAN}Installing Supervisor config...${NC}"
echo ""

# Check if we can write to config dir
if [ ! -d "$CONF_DIR" ]; then
    echo -e "${YELLOW}Config directory tidak ada: ${CONF_DIR}${NC}"
    echo -e "${YELLOW}Mencoba buat...${NC}"
    mkdir -p "$CONF_DIR" 2>/dev/null
    if [ $? -ne 0 ]; then
        echo -e "${RED}Tidak bisa buat directory. Coba dengan sudo:${NC}"
        echo -e "  sudo mkdir -p ${CONF_DIR}"
        echo ""
        echo -e "Atau copy config manual:"
        echo "$CONFIG_CONTENT"
        exit 1
    fi
fi

# Write config
echo "$CONFIG_CONTENT" > "$CONF_FILE" 2>/dev/null

if [ $? -ne 0 ]; then
    echo -e "${RED}Tidak bisa tulis ke ${CONF_FILE}${NC}"
    echo -e "${YELLOW}Coba dengan sudo:${NC}"
    echo ""
    echo -e "  sudo bash -c 'cat > ${CONF_FILE} << \"EOF\""
    echo "$CONFIG_CONTENT"
    echo "EOF'"
    echo ""
    echo -e "  sudo supervisorctl reread"
    echo -e "  sudo supervisorctl update"
    exit 1
fi

echo -e "${GREEN}Config written to ${CONF_FILE}${NC}"

# Ensure log directory exists
mkdir -p "$LOG_DIR" 2>/dev/null

# Reload Supervisor
if command -v supervisorctl &> /dev/null; then
    echo ""
    echo -e "${CYAN}Reloading Supervisor...${NC}"

    supervisorctl reread 2>/dev/null
    REREAD_EXIT=$?

    supervisorctl update 2>/dev/null
    UPDATE_EXIT=$?

    if [ $REREAD_EXIT -eq 0 ] && [ $UPDATE_EXIT -eq 0 ]; then
        echo -e "${GREEN}Supervisor reloaded${NC}"
        echo ""

        # Wait a moment for processes to start
        sleep 2

        echo -e "${BOLD}Worker status:${NC}"
        supervisorctl status 2>/dev/null | grep -i "oasis" || echo "  Workers starting..."
    else
        echo -e "${YELLOW}Supervisor reload mungkin butuh sudo:${NC}"
        echo -e "  sudo supervisorctl reread"
        echo -e "  sudo supervisorctl update"
    fi
else
    echo -e "${YELLOW}supervisorctl not found. Reload manual:${NC}"
    echo -e "  supervisorctl reread && supervisorctl update"
fi

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Management commands:${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  Status:         ${CYAN}bash scripts/setup-supervisor.sh --status${NC}"
echo -e "  Restart:        ${CYAN}bash scripts/setup-supervisor.sh --restart${NC}"
echo -e "  Remove:         ${CYAN}bash scripts/setup-supervisor.sh --remove${NC}"
echo -e "  Preview config: ${CYAN}bash scripts/setup-supervisor.sh --dry-run${NC}"
echo ""
echo -e "  Manual commands:"
echo -e "    ${CYAN}supervisorctl status${NC}"
echo -e "    ${CYAN}supervisorctl restart oasis-worker:*${NC}"
echo -e "    ${CYAN}supervisorctl restart oasis-scheduler${NC}"
echo -e "    ${CYAN}supervisorctl tail -f oasis-worker:oasis-worker_00${NC}"
echo ""
echo -e "${BOLD}  Setelah deploy (git pull + composer install):${NC}"
echo -e "    ${CYAN}php artisan queue:restart${NC}    # graceful restart workers"
echo ""
echo -e "${BOLD}  PENTING:${NC} Jika pakai Supervisor untuk scheduler,"
echo -e "  JANGAN tambah cron scheduler juga (double execution)."
echo -e "  Queue worker cron juga tidak perlu jika Supervisor sudah handle."
echo ""
