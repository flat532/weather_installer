#!/bin/bash

# ============================================================
#  Weather Station Uninstaller
#  Powered by OpenWeatherMap + MySQL/SQLite + PHP
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

print_banner() {
    echo -e "${CYAN}"
    echo "  ╔══════════════════════════════════════════════╗"
    echo "  ║       WEATHER STATION UNINSTALLER            ║"
    echo "  ║       Powered by OpenWeatherMap + PHP        ║"
    echo "  ╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() { echo -e "\n${BOLD}${CYAN}-- $1${NC}"; }
print_ok()   { echo -e "  ${GREEN}[OK]${NC}   $1"; }
print_warn() { echo -e "  ${YELLOW}[WARN]${NC} $1"; }
print_err()  { echo -e "  ${RED}[ERR]${NC}  $1"; }

print_banner

# ── STEP 1: Install path ─────────────────────────────────────

print_step "Install path"
echo -ne "  ${BOLD}Path to uninstall (e.g. /home/user/domains/mydomain.com/public_html)${NC}: "
read INSTALL_PATH
INSTALL_PATH="${INSTALL_PATH%/}"

if [ -z "$INSTALL_PATH" ]; then
    print_err "Path cannot be empty."
    exit 1
fi

if [ ! -d "$INSTALL_PATH" ]; then
    print_err "Directory not found: $INSTALL_PATH"
    exit 1
fi

# ── STEP 2: Read .env to detect SQLite path ──────────────────

SQLITE_PATH=""
ENV_FILE="$INSTALL_PATH/.env"

if [ -f "$ENV_FILE" ]; then
    DB_TYPE=$(grep "^DB_TYPE=" "$ENV_FILE" | cut -d= -f2 | tr -d '"'"'" | tr -d '[:space:]')
    if [ "$DB_TYPE" = "sqlite" ]; then
        SQLITE_PATH=$(grep "^DB_PATH=" "$ENV_FILE" | cut -d= -f2 | tr -d '"'"'" | tr -d '[:space:]')
    fi
else
    print_warn ".env not found — SQLite file (if any) will not be removed automatically"
fi

# ── STEP 3: Show what will be removed ────────────────────────

print_step "The following will be removed"

echo ""
echo -e "  ${BOLD}Files in:${NC} $INSTALL_PATH"
for f in config.php update_data.php api.php db.php index.php index.html arch.sh .env .htaccess; do
    [ -f "$INSTALL_PATH/$f" ] && echo "    - $f"
done
[ -d "$INSTALL_PATH/archive" ] && echo "    - archive/"

if [ -n "$SQLITE_PATH" ] && [ -f "$SQLITE_PATH" ]; then
    echo ""
    echo -e "  ${BOLD}SQLite database:${NC} $SQLITE_PATH"
fi

echo ""
echo -e "  ${BOLD}Cron entries containing:${NC} $INSTALL_PATH"
EXISTING_CRON=$(crontab -l 2>/dev/null)
MATCHED_CRON=$(echo "$EXISTING_CRON" | grep -F "$INSTALL_PATH")
if [ -n "$MATCHED_CRON" ]; then
    echo "$MATCHED_CRON" | sed 's/^/    /'
else
    echo "    (none found)"
fi

echo ""
echo -ne "  ${RED}${BOLD}Proceed with uninstall?${NC} (${RED}y${NC}/${GREEN}N${NC}): "
read confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Uninstall aborted."
    exit 0
fi

# ── STEP 4: Remove files ─────────────────────────────────────

print_step "Removing files"

for f in config.php update_data.php api.php db.php index.php index.html arch.sh .env .htaccess; do
    if [ -f "$INSTALL_PATH/$f" ]; then
        rm -f "$INSTALL_PATH/$f"
        print_ok "Removed: $f"
    fi
done

if [ -d "$INSTALL_PATH/archive" ]; then
    rm -rf "$INSTALL_PATH/archive"
    print_ok "Removed: archive/"
fi

# ── STEP 5: Remove SQLite database ───────────────────────────

if [ -n "$SQLITE_PATH" ]; then
    if [ -f "$SQLITE_PATH" ]; then
        print_step "Removing SQLite database"
        rm -f "$SQLITE_PATH"
        print_ok "Removed: $SQLITE_PATH"
    else
        print_warn "SQLite file not found (already removed?): $SQLITE_PATH"
    fi
fi

# ── STEP 6: Remove cron entries ──────────────────────────────

print_step "Removing cron entries"

EXISTING_CRON=$(crontab -l 2>/dev/null)

if [ -z "$EXISTING_CRON" ]; then
    print_warn "Crontab is empty — nothing to remove"
else
    MATCHED=$(echo "$EXISTING_CRON" | grep -cF "$INSTALL_PATH")

    if [ "$MATCHED" -eq 0 ]; then
        print_warn "No cron entries found for: $INSTALL_PATH"
    else
        FILTERED=$(echo "$EXISTING_CRON" | grep -vF "$INSTALL_PATH")

        # Safety check: filtered result must still contain header lines
        HEADER_LINES=$(echo "$EXISTING_CRON" | grep -c "^#\|^MAILTO\|^PATH")
        FILTERED_HEADERS=$(echo "$FILTERED" | grep -c "^#\|^MAILTO\|^PATH")

        if [ "$HEADER_LINES" -gt 0 ] && [ "$FILTERED_HEADERS" -eq 0 ]; then
            print_err "Safety check failed — filtered crontab lost header lines. Aborting cron removal."
            print_warn "Remove manually these entries:"
            echo "$MATCHED_CRON" | sed 's/^/    /'
        else
            echo "$FILTERED" | crontab -
            if [ $? -eq 0 ]; then
                print_ok "Removed $MATCHED cron entry/entries for $INSTALL_PATH"
            else
                print_err "Could not write crontab. Remove manually:"
                echo "$EXISTING_CRON" | grep -F "$INSTALL_PATH" | sed 's/^/    /'
            fi
        fi
    fi
fi

# ── SUMMARY ──────────────────────────────────────────────────

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║        Uninstall complete!                   ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}Uninstalled from:${NC} $INSTALL_PATH"
echo -e "  ${YELLOW}Note:${NC} The directory itself was not removed."
echo ""
