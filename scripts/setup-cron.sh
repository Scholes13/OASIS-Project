#!/bin/bash
# =============================================================================
# OASIS Cron Job Setup
# =============================================================================
# Setup Laravel scheduler dan queue worker cron jobs.
# Bisa dijalankan tanpa sudo (user-level crontab).
#
# Jalankan dari folder project:
#   cd ~/oasis.werkudara.com
#   bash scripts/setup-cron.sh
#
# Atau dengan APP_DIR:
#   APP_DIR=~/oasis.werkudara.com bash scripts/setup-cron.sh
#
# Opsi:
#   --dry-run     Tampilkan saja, jangan install
#   --remove      Hapus cron jobs OASIS
#   --show        Tampilkan cron jobs yang sudah ada
# =============================================================================

set -u

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# --- Parse arguments ---
DRY_RUN=false
REMOVE=false
SHOW_ONLY=false

for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=true ;;
        --remove)  REMOVE=true ;;
        --show)    SHOW_ONLY=true ;;
        --help|-h)
            echo "Usage: bash scripts/setup-cron.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --dry-run   Tampilkan cron entries tanpa install"
            echo "  --remove    Hapus cron jobs OASIS"
            echo "  --show      Tampilkan cron jobs yang sudah ada"
            echo "  --help      Tampilkan help ini"
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

# Validate
if [ ! -f "${APP_DIR}/artisan" ]; then
    echo -e "${RED}ERROR: '${APP_DIR}' bukan Laravel project (artisan not found)${NC}"
    echo "Jalankan dari folder project: cd /path/to/oasis && bash scripts/setup-cron.sh"
    exit 1
fi

# --- Detect PHP binary ---
find_php() {
    for bin in php8.2 php8.3 php8.4 php; do
        if command -v "$bin" &> /dev/null; then
            echo "$bin"
            return
        fi
    done
    echo "php"
}

PHP_BIN=$(find_php)

# --- OASIS cron marker ---
CRON_MARKER="# OASIS-CRON"

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║       OASIS - Cron Job Setup                                               ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  App directory: ${BOLD}${APP_DIR}${NC}"
echo -e "  PHP binary:    ${BOLD}${PHP_BIN}${NC}"
echo ""

# =============================================================================
# --show: Display current cron
# =============================================================================
if [ "$SHOW_ONLY" = true ]; then
    echo -e "${BOLD}Current crontab:${NC}"
    echo ""
    CURRENT=$(crontab -l 2>/dev/null)
    if [ -n "$CURRENT" ]; then
        echo "$CURRENT"
        echo ""
        OASIS_COUNT=$(echo "$CURRENT" | grep -c "$CRON_MARKER" || true)
        echo -e "${CYAN}OASIS cron entries: ${OASIS_COUNT}${NC}"
    else
        echo -e "${YELLOW}Crontab is empty${NC}"
    fi
    exit 0
fi

# =============================================================================
# --remove: Remove OASIS cron jobs
# =============================================================================
if [ "$REMOVE" = true ]; then
    echo -e "${YELLOW}Removing OASIS cron jobs...${NC}"
    CURRENT=$(crontab -l 2>/dev/null)

    if [ -z "$CURRENT" ]; then
        echo -e "${YELLOW}Crontab already empty${NC}"
        exit 0
    fi

    # Count OASIS entries
    OASIS_COUNT=$(echo "$CURRENT" | grep -c "$CRON_MARKER" || true)
    if [ "$OASIS_COUNT" -eq 0 ]; then
        echo -e "${YELLOW}No OASIS cron entries found${NC}"
        exit 0
    fi

    # Remove lines with OASIS marker
    NEW_CRON=$(echo "$CURRENT" | grep -v "$CRON_MARKER")

    if [ "$DRY_RUN" = true ]; then
        echo -e "${CYAN}[DRY RUN] Would remove ${OASIS_COUNT} OASIS entries${NC}"
        echo ""
        echo "Remaining crontab would be:"
        echo "$NEW_CRON"
    else
        echo "$NEW_CRON" | crontab -
        echo -e "${GREEN}Removed ${OASIS_COUNT} OASIS cron entries${NC}"
    fi
    exit 0
fi

# =============================================================================
# Define cron jobs
# =============================================================================

# Log directory
LOG_DIR="${APP_DIR}/storage/logs"

# Cron entries to install
declare -a CRON_ENTRIES=()

# 1. Laravel Scheduler - runs every minute
CRON_ENTRIES+=("* * * * * cd ${APP_DIR} && ${PHP_BIN} artisan schedule:run >> ${LOG_DIR}/scheduler.log 2>&1 ${CRON_MARKER}-scheduler")

# 2. Queue Worker - runs every minute, processes jobs then exits
#    --stop-when-empty: exit setelah semua job selesai (cocok untuk cron)
#    --max-time=55: max 55 detik per run (cron jalan tiap 60 detik)
#    --tries=3: retry failed jobs 3x
#    --timeout=90: max 90 detik per job
CRON_ENTRIES+=("* * * * * cd ${APP_DIR} && ${PHP_BIN} artisan queue:work --stop-when-empty --max-time=55 --tries=3 --timeout=90 >> ${LOG_DIR}/queue-worker.log 2>&1 ${CRON_MARKER}-queue")

# 3. Queue Restart - restart queue workers setelah deploy (daily cleanup)
CRON_ENTRIES+=("0 3 * * * cd ${APP_DIR} && ${PHP_BIN} artisan queue:restart >> /dev/null 2>&1 ${CRON_MARKER}-queue-restart")

# 4. Log Cleanup - hapus log lama tiap minggu (Minggu jam 4 pagi)
CRON_ENTRIES+=("0 4 * * 0 find ${LOG_DIR} -name '*.log' -mtime +30 -delete ${CRON_MARKER}-log-cleanup")

# 5. Cache Warm - rebuild cache setelah midnight (optional, helps performance)
CRON_ENTRIES+=("5 0 * * * cd ${APP_DIR} && ${PHP_BIN} artisan config:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan route:cache >> /dev/null 2>&1 && ${PHP_BIN} artisan view:cache >> /dev/null 2>&1 ${CRON_MARKER}-cache-warm")

# =============================================================================
# Display what will be installed
# =============================================================================

echo -e "${BOLD}Cron jobs yang akan di-setup:${NC}"
echo ""
echo -e "  ${GREEN}1. Laravel Scheduler${NC} (setiap menit)"
echo -e "     Menjalankan scheduled commands (SLA check, backdate expiry, dll)"
echo ""
echo -e "  ${GREEN}2. Queue Worker${NC} (setiap menit)"
echo -e "     Memproses background jobs (email, notifications, export)"
echo -e "     --stop-when-empty: exit setelah semua job selesai"
echo -e "     --max-time=55: max 55 detik per run"
echo ""
echo -e "  ${GREEN}3. Queue Restart${NC} (jam 3 pagi)"
echo -e "     Restart queue workers untuk pick up code changes"
echo ""
echo -e "  ${GREEN}4. Log Cleanup${NC} (Minggu jam 4 pagi)"
echo -e "     Hapus log files lebih dari 30 hari"
echo ""
echo -e "  ${GREEN}5. Cache Warm${NC} (jam 00:05)"
echo -e "     Rebuild config, route, dan view cache"
echo ""

echo -e "${BOLD}Cron entries:${NC}"
echo ""
for entry in "${CRON_ENTRIES[@]}"; do
    echo "  $entry"
done
echo ""

# =============================================================================
# Dry run check
# =============================================================================
if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}[DRY RUN] Tidak ada yang di-install. Jalankan tanpa --dry-run untuk install.${NC}"
    echo ""
    echo -e "${CYAN}Untuk install:${NC}"
    echo "  bash scripts/setup-cron.sh"
    echo ""
    echo -e "${CYAN}Atau tambah manual via cPanel > Cron Jobs${NC}"
    exit 0
fi

# =============================================================================
# Install cron jobs
# =============================================================================

echo -e "${CYAN}Installing cron jobs...${NC}"
echo ""

# Get existing crontab (without OASIS entries to avoid duplicates)
EXISTING=$(crontab -l 2>/dev/null | grep -v "$CRON_MARKER" || true)

# Build new crontab
NEW_CRONTAB="$EXISTING"

# Add blank line separator if existing crontab is not empty
if [ -n "$NEW_CRONTAB" ]; then
    NEW_CRONTAB="${NEW_CRONTAB}
"
fi

# Add header
NEW_CRONTAB="${NEW_CRONTAB}
# === OASIS Cron Jobs (managed by setup-cron.sh) === ${CRON_MARKER}-header"

# Add each entry
for entry in "${CRON_ENTRIES[@]}"; do
    NEW_CRONTAB="${NEW_CRONTAB}
${entry}"
done

# Install
echo "$NEW_CRONTAB" | crontab -

if [ $? -eq 0 ]; then
    echo -e "${GREEN}${BOLD}Cron jobs berhasil di-install!${NC}"
    echo ""

    # Verify
    echo -e "${BOLD}Verifikasi:${NC}"
    INSTALLED_COUNT=$(crontab -l 2>/dev/null | grep -c "$CRON_MARKER" || true)
    echo -e "  OASIS cron entries: ${GREEN}${INSTALLED_COUNT}${NC}"
    echo ""

    # Show full crontab
    echo -e "${BOLD}Full crontab:${NC}"
    crontab -l 2>/dev/null
    echo ""
else
    echo -e "${RED}${BOLD}GAGAL install cron jobs!${NC}"
    echo -e "${YELLOW}Tambah manual via cPanel > Cron Jobs:${NC}"
    echo ""
    for entry in "${CRON_ENTRIES[@]}"; do
        # Strip the marker comment for manual entry
        CLEAN_ENTRY=$(echo "$entry" | sed "s/ ${CRON_MARKER}.*//")
        echo "  $CLEAN_ENTRY"
    done
    echo ""
    exit 1
fi

echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Management commands:${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  Lihat cron:    ${CYAN}bash scripts/setup-cron.sh --show${NC}"
echo -e "  Hapus cron:    ${CYAN}bash scripts/setup-cron.sh --remove${NC}"
echo -e "  Reinstall:     ${CYAN}bash scripts/setup-cron.sh${NC}"
echo -e "  Preview only:  ${CYAN}bash scripts/setup-cron.sh --dry-run${NC}"
echo ""
