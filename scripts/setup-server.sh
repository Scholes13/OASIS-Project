#!/bin/bash
# =============================================================================
# OASIS Server Setup (All-in-One)
# =============================================================================
# Satu script untuk setup semua background services:
#   1. Clear semua cron jobs yang ada
#   2. Setup Supervisor (queue worker + scheduler)
#   3. Set cron jobs yang dibutuhkan (log cleanup + cache warm)
#
# Jalankan dari folder project:
#   cd ~/oasis.werkudara.com
#   bash scripts/setup-server.sh
#
# Opsi:
#   --dry-run              Preview semua tanpa install
#   --user=www-data        Override worker user (default: auto-detect)
#   --numprocs=2           Jumlah queue worker processes (default: 2)
#   --supervisor-active    Skip setup, hanya adjust cron (supervisor sudah running)
# =============================================================================

set -u

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

PASS="${GREEN}[OK]${NC}"
FAIL="${RED}[FAIL]${NC}"
WARN="${YELLOW}[WARN]${NC}"
INFO="${CYAN}[INFO]${NC}"
SKIP="${CYAN}[SKIP]${NC}"

# --- Defaults ---
WORKER_USER=""
NUM_PROCS=2
DRY_RUN=false

# --- Parse arguments ---
FORCE_SUPERVISOR=false

for arg in "$@"; do
    case "$arg" in
        --dry-run)              DRY_RUN=true ;;
        --user=*)               WORKER_USER="${arg#*=}" ;;
        --numprocs=*)           NUM_PROCS="${arg#*=}" ;;
        --supervisor-active)    FORCE_SUPERVISOR=true ;;
        --help|-h)
            echo "Usage: bash scripts/setup-server.sh [OPTIONS]"
            echo ""
            echo "All-in-one setup: clear cron > Supervisor > safe cron jobs"
            echo ""
            echo "Options:"
            echo "  --dry-run              Preview tanpa install"
            echo "  --user=USER            Worker user (default: auto-detect)"
            echo "  --numprocs=N           Queue worker processes (default: 2)"
            echo "  --supervisor-active    Skip check, assume supervisor sudah running"
            echo "  --help                 Help"
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
    echo -e "${RED}ERROR: '${APP_DIR}' bukan Laravel project (artisan not found)${NC}"
    echo "Jalankan: cd /path/to/oasis && bash scripts/setup-server.sh"
    exit 1
fi

# --- Detect PHP binary (full path) ---
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

# --- Auto-detect worker user ---
if [ -z "$WORKER_USER" ]; then
    if [ -d "${APP_DIR}/storage" ]; then
        OWNER=$(stat -c '%U' "${APP_DIR}/storage" 2>/dev/null)
        if [ -n "$OWNER" ] && [ "$OWNER" != "root" ]; then
            WORKER_USER="$OWNER"
        fi
    fi
    if [ -z "$WORKER_USER" ]; then
        WORKER_USER=$(whoami)
    fi
fi

LOG_DIR="${APP_DIR}/storage/logs"
CRON_MARKER="# OASIS-CRON"

# =============================================================================
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       OASIS - Server Setup (All-in-One)                                    ║${NC}"
echo -e "${BOLD}║       $(date '+%Y-%m-%d %H:%M:%S')                                                    ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  App directory:  ${BOLD}${APP_DIR}${NC}"
echo -e "  PHP binary:     ${BOLD}${PHP_BIN}${NC}"
echo -e "  Worker user:    ${BOLD}${WORKER_USER}${NC}"
echo -e "  Queue procs:    ${BOLD}${NUM_PROCS}${NC}"
if [ "$DRY_RUN" = true ]; then
    echo -e "  Mode:           ${YELLOW}DRY RUN (preview only)${NC}"
fi
if [ "$FORCE_SUPERVISOR" = true ]; then
    echo -e "  Mode:           ${GREEN}--supervisor-active (cron adjustment only)${NC}"
fi

# =============================================================================
# FAST PATH: --supervisor-active skips Step 1 & 2, goes straight to cron adjust
# =============================================================================
if [ "$FORCE_SUPERVISOR" = true ]; then
    SUPERVISOR_RUNNING=true

    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  Adjusting cron jobs (supervisor confirmed active)${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    # Remove existing OASIS cron entries
    EXISTING_CRON=$(crontab -l 2>/dev/null)
    OASIS_CRON_COUNT=0
    if [ -n "$EXISTING_CRON" ]; then
        OASIS_CRON_COUNT=$(echo "$EXISTING_CRON" | grep -c "$CRON_MARKER" || true)
    fi

    if [ "$OASIS_CRON_COUNT" -gt 0 ]; then
        echo -e "  ${INFO} Found ${OASIS_CRON_COUNT} OASIS cron entries to clean"
        echo "$EXISTING_CRON" | grep "$CRON_MARKER" | while read -r line; do
            echo -e "    ${RED}- ${line}${NC}"
        done
        echo ""
    fi

    # Build maintenance-only cron entries
    declare -a CRON_ENTRIES=()
    CRON_ENTRIES+=("0 4 * * 0 find ${LOG_DIR} -name '*.log' -mtime +30 -delete ${CRON_MARKER}-log-cleanup")
    CRON_ENTRIES+=("5 0 * * * cd ${APP_DIR} && ${PHP_BIN} artisan config:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan route:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan view:cache >> /dev/null 2>&1 ${CRON_MARKER}-cache-warm")

    echo -e "  ${INFO} Supervisor handles: scheduler + queue worker"
    echo -e "  ${INFO} Cron handles: maintenance only"
    echo ""
    echo -e "  ${GREEN}1. Log Cleanup${NC} (Minggu jam 4 pagi)"
    echo -e "  ${GREEN}2. Cache Warm${NC} (setiap hari jam 00:05)"
    echo ""

    if [ "$DRY_RUN" = true ]; then
        echo -e "  ${SKIP} [DRY RUN] Tidak ada yang di-install"
    else
        # Get current crontab without OASIS entries
        CURRENT_CRON=$(crontab -l 2>/dev/null | grep -v "$CRON_MARKER" || true)

        NEW_CRONTAB="$CURRENT_CRON"
        if [ -n "$NEW_CRONTAB" ]; then
            NEW_CRONTAB="${NEW_CRONTAB}
"
        fi

        NEW_CRONTAB="${NEW_CRONTAB}
# === OASIS Cron Jobs (managed by setup-server.sh) === ${CRON_MARKER}-header"

        for entry in "${CRON_ENTRIES[@]}"; do
            NEW_CRONTAB="${NEW_CRONTAB}
${entry}"
        done

        echo "$NEW_CRONTAB" | crontab - 2>/dev/null

        if [ $? -eq 0 ]; then
            INSTALLED=$(crontab -l 2>/dev/null | grep -c "$CRON_MARKER" || true)
            echo -e "  ${PASS} ${INSTALLED} cron entries installed (maintenance only)"

            if [ "$OASIS_CRON_COUNT" -gt 2 ]; then
                REMOVED=$((OASIS_CRON_COUNT - INSTALLED))
                echo -e "  ${PASS} Removed ${REMOVED} scheduler/queue cron entries (now handled by Supervisor)"
            fi
        else
            echo -e "  ${FAIL} Gagal install cron jobs"
        fi
    fi

    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║                              DONE                                          ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BOLD}Supervisor:${NC}  ${PASS} active (confirmed)"
    echo -e "  ${BOLD}Cron:${NC}        ${PASS} maintenance only (log-cleanup + cache-warm)"
    echo ""
    echo -e "  ${CYAN}crontab -l${NC}                               # verify cron"
    echo -e "  ${CYAN}supervisorctl status${NC}                     # verify supervisor"
    echo ""
    exit 0
fi

# =============================================================================
# STEP 1: CLEAR ALL EXISTING CRON JOBS
# =============================================================================
echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  STEP 1: Clear existing OASIS cron jobs${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

EXISTING_CRON=$(crontab -l 2>/dev/null)
OASIS_CRON_COUNT=0

if [ -n "$EXISTING_CRON" ]; then
    OASIS_CRON_COUNT=$(echo "$EXISTING_CRON" | grep -c "$CRON_MARKER" || true)
fi

if [ "$OASIS_CRON_COUNT" -gt 0 ]; then
    echo -e "  ${INFO} Found ${OASIS_CRON_COUNT} OASIS cron entries"

    # Show what will be removed
    echo "$EXISTING_CRON" | grep "$CRON_MARKER" | while read -r line; do
        echo -e "  ${RED}  - ${line}${NC}"
    done
    echo ""

    if [ "$DRY_RUN" = true ]; then
        echo -e "  ${SKIP} [DRY RUN] Would remove ${OASIS_CRON_COUNT} entries"
    else
        # Remove only OASIS entries, keep other cron jobs
        CLEANED_CRON=$(echo "$EXISTING_CRON" | grep -v "$CRON_MARKER")
        echo "$CLEANED_CRON" | crontab - 2>/dev/null
        echo -e "  ${PASS} Removed ${OASIS_CRON_COUNT} OASIS cron entries"
        echo -e "  ${INFO} Non-OASIS cron jobs tetap utuh"
    fi
else
    echo -e "  ${PASS} Tidak ada OASIS cron jobs (bersih)"
fi

# =============================================================================
# STEP 2: SETUP SUPERVISOR
# =============================================================================
echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  STEP 2: Setup Supervisor (queue worker + scheduler)${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# --- Find Supervisor binaries ---
SUPERVISORD_BIN=""
SUPERVISORCTL_BIN=""

# Check PATH first
for cmd in supervisord supervisord3; do
    if command -v "$cmd" &> /dev/null; then
        SUPERVISORD_BIN=$(command -v "$cmd")
        break
    fi
done

for cmd in supervisorctl supervisorctl3; do
    if command -v "$cmd" &> /dev/null; then
        SUPERVISORCTL_BIN=$(command -v "$cmd")
        break
    fi
done

# Check common install locations if not in PATH
if [ -z "$SUPERVISORD_BIN" ]; then
    for path in \
        /usr/bin/supervisord \
        /usr/local/bin/supervisord \
        /usr/sbin/supervisord \
        /opt/cpanel/ea-php*/root/usr/bin/supervisord \
        /usr/local/sbin/supervisord \
        /home/*/bin/supervisord \
        ~/.local/bin/supervisord; do
        for expanded in $path; do
            if [ -x "$expanded" ] 2>/dev/null; then
                SUPERVISORD_BIN="$expanded"
                break 2
            fi
        done
    done
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

# Also check running process
if [ -z "$SUPERVISORD_BIN" ]; then
    if pgrep -x supervisord &> /dev/null || pgrep -f "supervisord" &> /dev/null; then
        SUP_PID=$(pgrep -x supervisord 2>/dev/null || pgrep -f "supervisord" 2>/dev/null | head -1)
        if [ -n "$SUP_PID" ]; then
            SUP_EXE=$(readlink -f /proc/$SUP_PID/exe 2>/dev/null)
            if [ -n "$SUP_EXE" ]; then
                SUPERVISORD_BIN="$SUP_EXE"
            fi
        fi
    fi
fi

# Derive ctl from daemon path if needed
if [ -z "$SUPERVISORCTL_BIN" ] && [ -n "$SUPERVISORD_BIN" ]; then
    CTL_DIR=$(dirname "$SUPERVISORD_BIN")
    if [ -x "${CTL_DIR}/supervisorctl" ]; then
        SUPERVISORCTL_BIN="${CTL_DIR}/supervisorctl"
    fi
fi

SUPERVISOR_FOUND=false
SUPERVISOR_RUNNING=false

if [ -z "$SUPERVISORD_BIN" ] && [ -z "$SUPERVISORCTL_BIN" ]; then
    echo -e "  ${FAIL} Supervisor tidak ditemukan!"
    echo ""
    echo -e "  ${CYAN}Lokasi yang dicek:${NC}"
    echo -e "    ${INFO} PATH: supervisord, supervisorctl"
    echo -e "    ${INFO} /usr/bin/, /usr/local/bin/, /usr/sbin/"
    echo -e "    ${INFO} /opt/cpanel/, ~/.local/bin/"
    echo -e "    ${INFO} Process: pgrep supervisord"
    echo ""
    echo -e "  ${INFO} Jika admin sudah install, minta info:"
    echo -e "  ${INFO}   which supervisord && which supervisorctl"
    echo ""
    echo -e "  ${YELLOW}Melanjutkan ke Step 3 dengan FULL cron mode...${NC}"
else
    SUPERVISOR_FOUND=true
    echo -e "  ${PASS} Supervisor ditemukan"
    if [ -n "$SUPERVISORD_BIN" ]; then
        echo -e "    ${INFO} supervisord: ${SUPERVISORD_BIN}"
    fi
    if [ -n "$SUPERVISORCTL_BIN" ]; then
        echo -e "    ${INFO} supervisorctl: ${SUPERVISORCTL_BIN}"
    fi
fi

if [ "$SUPERVISOR_FOUND" = true ]; then
    # Detect config directory
    CONF_DIR=""
    for dir in /etc/supervisor/conf.d /etc/supervisord.d /usr/local/etc/supervisor/conf.d; do
        if [ -d "$dir" ]; then
            CONF_DIR="$dir"
            break
        fi
    done

    # Fallback: parse from supervisord.conf
    if [ -z "$CONF_DIR" ]; then
        for f in /etc/supervisor/supervisord.conf /etc/supervisord.conf /usr/local/etc/supervisord.conf; do
            if [ -f "$f" ]; then
                CONF_DIR=$(grep -oP '(?<=files\s=\s).*' "$f" 2>/dev/null | sed 's|/\*\.conf||' | sed 's|/\*\.ini||' | head -1)
                break
            fi
        done
    fi

    if [ -z "$CONF_DIR" ]; then
        CONF_DIR="/etc/supervisor/conf.d"
    fi

    CONF_FILE="${CONF_DIR}/oasis.conf"

    echo -e "  ${INFO} Config directory: ${CONF_DIR}"
    echo -e "  ${INFO} Config file: ${CONF_FILE}"
    echo ""

    # Detect queue connection from .env
    QUEUE_CONN="database"
    if [ -f "${APP_DIR}/.env" ]; then
        ENV_QUEUE=$(grep -E "^QUEUE_CONNECTION=" "${APP_DIR}/.env" 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
        if [ -n "$ENV_QUEUE" ]; then
            QUEUE_CONN="$ENV_QUEUE"
        fi
    fi
    echo -e "  ${INFO} Queue connection: ${QUEUE_CONN} (from .env)"

    # Generate config
    SUPERVISOR_CONFIG="[program:oasis-worker]
process_name=%(program_name)s_%(process_num)02d
command=${PHP_BIN} ${APP_DIR}/artisan queue:work ${QUEUE_CONN} --sleep=3 --tries=3 --max-time=3600 --timeout=90 --memory=128
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

    echo -e "  ${BOLD}Config:${NC}"
    echo ""
    echo "$SUPERVISOR_CONFIG" | while read -r line; do
        echo -e "    ${line}"
    done
    echo ""

    if [ "$DRY_RUN" = true ]; then
        echo -e "  ${SKIP} [DRY RUN] Would write to ${CONF_FILE}"
    else
        # Ensure log directory exists
        mkdir -p "$LOG_DIR" 2>/dev/null

        # Write config
        echo "$SUPERVISOR_CONFIG" > "$CONF_FILE" 2>/dev/null
        WRITE_OK=$?

        if [ $WRITE_OK -eq 0 ]; then
            echo -e "  ${PASS} Config written to ${CONF_FILE}"

            # Reload Supervisor
            if [ -n "$SUPERVISORCTL_BIN" ]; then
                $SUPERVISORCTL_BIN reread 2>/dev/null
                $SUPERVISORCTL_BIN update 2>/dev/null
                sleep 2
            else
                echo -e "  ${WARN} supervisorctl tidak ditemukan, reload manual:"
                echo -e "  ${INFO} supervisorctl reread && supervisorctl update"
            fi
        else
            echo -e "  ${WARN} Tidak bisa tulis ke ${CONF_FILE} (permission denied)"
            echo ""
            echo -e "  ${YELLOW}Jika config belum ada, minta vendor jalankan:${NC}"
            echo ""
            echo -e "  sudo bash -c 'cat > ${CONF_FILE} << \"EOFCONF\""
            echo "$SUPERVISOR_CONFIG"
            echo "EOFCONF'"
            echo ""
            echo "  sudo ${SUPERVISORCTL_BIN:-supervisorctl} reread"
            echo "  sudo ${SUPERVISORCTL_BIN:-supervisorctl} update"
            echo ""
        fi
    fi

    # --- Check if OASIS workers are actually running (regardless of config write) ---
    if [ -n "$SUPERVISORCTL_BIN" ]; then
        echo ""
        echo -e "  ${BOLD}Checking running workers...${NC}"

        # supervisorctl may fail with PermissionError — capture both stdout and stderr
        FULL_OUTPUT=$($SUPERVISORCTL_BIN status 2>&1 || true)

        if echo "$FULL_OUTPUT" | grep -qi "permission\|denied\|error"; then
            # Permission denied — try sudo (non-interactive, will silently fail if no sudo access)
            FULL_OUTPUT=$(sudo -n $SUPERVISORCTL_BIN status 2>&1 || true)
        fi

        RUNNING_OUTPUT=$(echo "$FULL_OUTPUT" | grep -i "oasis" || true)
        RUNNING_COUNT=0
        if [ -n "$RUNNING_OUTPUT" ]; then
            RUNNING_COUNT=$(echo "$RUNNING_OUTPUT" | grep -c "RUNNING" 2>/dev/null || true)
        fi

        if [ "$RUNNING_COUNT" -gt 0 ]; then
            echo "$RUNNING_OUTPUT" | while read -r line; do
                echo -e "    ${line}"
            done
            echo ""
            echo -e "  ${PASS} ${RUNNING_COUNT} OASIS workers running — Supervisor aktif"
            SUPERVISOR_RUNNING=true
        elif echo "$FULL_OUTPUT" | grep -qi "permission\|denied"; then
            echo -e "  ${WARN} Tidak bisa cek status (permission denied)"
            echo -e "  ${INFO} Cek manual: sudo ${SUPERVISORCTL_BIN} status"
            if [ "$FORCE_SUPERVISOR" = true ]; then
                echo ""
                echo -e "  ${PASS} --supervisor-active flag detected"
                echo -e "  ${PASS} Supervisor dianggap aktif — cron mode: maintenance only"
                SUPERVISOR_RUNNING=true
            else
                echo ""
                echo -e "  ${INFO} Menggunakan full cron mode sebagai fallback"
                echo -e "  ${INFO} Jika supervisor sudah aktif, jalankan ulang dengan:"
                echo -e "  ${CYAN}  bash scripts/setup-server.sh --supervisor-active${NC}"
            fi
        else
            echo -e "  ${WARN} Tidak ada OASIS workers yang RUNNING"
            echo -e "  ${INFO} Cek: ${SUPERVISORCTL_BIN} status"
        fi
    fi
fi

# =============================================================================
# STEP 3: SET CRON JOBS (only what's needed)
# =============================================================================
echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  STEP 3: Set cron jobs${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Build cron entries based on whether Supervisor is handling scheduler/queue
declare -a CRON_ENTRIES=()

if [ "$SUPERVISOR_RUNNING" = true ]; then
    echo -e "  ${INFO} Supervisor aktif — cron hanya untuk maintenance jobs"
    echo ""

    # Only maintenance cron jobs (no scheduler/queue — Supervisor handles those)
    CRON_ENTRIES+=("0 4 * * 0 find ${LOG_DIR} -name '*.log' -mtime +30 -delete ${CRON_MARKER}-log-cleanup")
    CRON_ENTRIES+=("5 0 * * * cd ${APP_DIR} && ${PHP_BIN} artisan config:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan route:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan view:cache >> /dev/null 2>&1 ${CRON_MARKER}-cache-warm")

    echo -e "  ${GREEN}1. Log Cleanup${NC} (Minggu jam 4 pagi)"
    echo -e "     Hapus log files > 30 hari"
    echo ""
    echo -e "  ${GREEN}2. Cache Warm${NC} (setiap hari jam 00:05)"
    echo -e "     Rebuild config, route, view cache"
    echo ""
    echo -e "  ${CYAN}TIDAK di-install (sudah di Supervisor):${NC}"
    echo -e "    - Laravel Scheduler (oasis-scheduler)"
    echo -e "    - Queue Worker (oasis-worker)"
else
    echo -e "  ${WARN} Supervisor tidak aktif — cron handle semua"
    echo ""

    # Full cron mode: scheduler + queue + maintenance
    CRON_ENTRIES+=("* * * * * cd ${APP_DIR} && ${PHP_BIN} artisan schedule:run >> ${LOG_DIR}/scheduler.log 2>&1 ${CRON_MARKER}-scheduler")
    CRON_ENTRIES+=("* * * * * cd ${APP_DIR} && ${PHP_BIN} artisan queue:work --stop-when-empty --max-time=55 --tries=3 --timeout=90 >> ${LOG_DIR}/queue-worker.log 2>&1 ${CRON_MARKER}-queue")
    CRON_ENTRIES+=("0 3 * * * cd ${APP_DIR} && ${PHP_BIN} artisan queue:restart >> /dev/null 2>&1 ${CRON_MARKER}-queue-restart")
    CRON_ENTRIES+=("0 4 * * 0 find ${LOG_DIR} -name '*.log' -mtime +30 -delete ${CRON_MARKER}-log-cleanup")
    CRON_ENTRIES+=("5 0 * * * cd ${APP_DIR} && ${PHP_BIN} artisan config:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan route:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan view:cache >> /dev/null 2>&1 ${CRON_MARKER}-cache-warm")

    echo -e "  ${GREEN}1. Laravel Scheduler${NC} (setiap menit)"
    echo -e "  ${GREEN}2. Queue Worker${NC} (setiap menit, --stop-when-empty)"
    echo -e "  ${GREEN}3. Queue Restart${NC} (jam 3 pagi)"
    echo -e "  ${GREEN}4. Log Cleanup${NC} (Minggu jam 4 pagi)"
    echo -e "  ${GREEN}5. Cache Warm${NC} (jam 00:05)"
fi

echo ""

if [ "$DRY_RUN" = true ]; then
    echo -e "  ${BOLD}Cron entries (preview):${NC}"
    for entry in "${CRON_ENTRIES[@]}"; do
        echo -e "    ${entry}"
    done
    echo ""
    echo -e "  ${SKIP} [DRY RUN] Tidak ada yang di-install"
else
    # Get current crontab (without any OASIS entries)
    CURRENT_CRON=$(crontab -l 2>/dev/null | grep -v "$CRON_MARKER" || true)

    # Build new crontab
    NEW_CRONTAB="$CURRENT_CRON"
    if [ -n "$NEW_CRONTAB" ]; then
        NEW_CRONTAB="${NEW_CRONTAB}
"
    fi

    NEW_CRONTAB="${NEW_CRONTAB}
# === OASIS Cron Jobs (managed by setup-server.sh) === ${CRON_MARKER}-header"

    for entry in "${CRON_ENTRIES[@]}"; do
        NEW_CRONTAB="${NEW_CRONTAB}
${entry}"
    done

    echo "$NEW_CRONTAB" | crontab - 2>/dev/null

    if [ $? -eq 0 ]; then
        INSTALLED=$(crontab -l 2>/dev/null | grep -c "$CRON_MARKER" || true)
        echo -e "  ${PASS} ${INSTALLED} cron entries installed"
    else
        echo -e "  ${FAIL} Gagal install cron jobs"
        echo -e "  ${INFO} Tambah manual via cPanel > Cron Jobs:"
        echo ""
        for entry in "${CRON_ENTRIES[@]}"; do
            CLEAN=$(echo "$entry" | sed "s/ ${CRON_MARKER}.*//")
            echo "    $CLEAN"
        done
    fi
fi

# =============================================================================
# SUMMARY
# =============================================================================
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║                              DONE                                          ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ "$DRY_RUN" = true ]; then
    echo -e "  ${YELLOW}DRY RUN — tidak ada perubahan.${NC}"
    echo -e "  Jalankan tanpa --dry-run untuk install:"
    echo -e "    ${CYAN}bash scripts/setup-server.sh${NC}"
else
    echo -e "  ${BOLD}Step 1:${NC} Cron lama     ${PASS} cleared"
    if [ "$SUPERVISOR_RUNNING" = true ]; then
        echo -e "  ${BOLD}Step 2:${NC} Supervisor   ${PASS} RUNNING — oasis-worker + oasis-scheduler"
        echo -e "  ${BOLD}Step 3:${NC} Cron baru    ${PASS} maintenance only (log-cleanup + cache-warm)"
    else
        echo -e "  ${BOLD}Step 2:${NC} Supervisor   ${WARN} not running"
        echo -e "  ${BOLD}Step 3:${NC} Cron baru    ${PASS} full mode (scheduler + queue + maintenance)"
    fi
fi

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Useful commands:${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
if [ "$SUPERVISOR_RUNNING" = true ]; then
    echo -e "  Supervisor:"
    echo -e "    ${CYAN}supervisorctl status${NC}                    # lihat status workers"
    echo -e "    ${CYAN}supervisorctl restart oasis-worker:*${NC}    # restart queue workers"
    echo -e "    ${CYAN}supervisorctl restart oasis-scheduler${NC}   # restart scheduler"
    echo -e "    ${CYAN}supervisorctl tail -f oasis-worker:oasis-worker_00${NC}  # live log"
    echo ""
fi
echo -e "  Cron:"
echo -e "    ${CYAN}crontab -l${NC}                               # lihat cron jobs"
echo ""
echo -e "  Setelah deploy:"
echo -e "    ${CYAN}cd ${APP_DIR}${NC}"
echo -e "    ${CYAN}git pull origin main${NC}"
echo -e "    ${CYAN}composer install --no-dev --optimize-autoloader${NC}"
echo -e "    ${CYAN}npm ci && npm run build${NC}"
echo -e "    ${CYAN}php artisan migrate --force${NC}"
if [ "$SUPERVISOR_RUNNING" = true ]; then
    echo -e "    ${CYAN}php artisan queue:restart${NC}                # graceful restart workers"
else
    echo -e "    ${CYAN}php artisan config:cache && php artisan route:cache && php artisan view:cache${NC}"
fi
echo ""
