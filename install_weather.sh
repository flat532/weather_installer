#!/bin/bash

# ============================================================
#  Weather Station Installer
#  Powered by OpenWeatherMap + MySQL/SQLite + PHP
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE_DIR="$SCRIPT_DIR/template"

print_banner() {
    echo -e "${CYAN}"
    echo "  ╔══════════════════════════════════════════════╗"
    echo "  ║       WEATHER STATION INSTALLER              ║"
    echo "  ║       Powered by OpenWeatherMap + PHP        ║"
    echo "  ╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() { echo -e "\n${BOLD}${CYAN}-- $1${NC}"; }
print_ok()   { echo -e "  ${GREEN}[OK]${NC}   $1"; }
print_warn() { echo -e "  ${YELLOW}[WARN]${NC} $1"; }
print_err()  { echo -e "  ${RED}[ERR]${NC}  $1"; }

ask() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    local secret="$4"

    if [ -n "$default" ]; then
        echo -ne "  ${BOLD}${prompt}${NC} [${YELLOW}${default}${NC}]: "
    else
        echo -ne "  ${BOLD}${prompt}${NC}: "
    fi

    if [ "$secret" = "true" ]; then
        read -s value
        echo
    else
        read value
    fi

    if [ -z "$value" ] && [ -n "$default" ]; then
        value="$default"
    fi

    eval "$var_name='$value'"
}

validate_not_empty() {
    local val="$1"
    local name="$2"
    if [ -z "$val" ]; then
        print_err "Field '$name' cannot be empty."
        return 1
    fi
    return 0
}

# ── START ────────────────────────────────────────────────────

print_banner

echo -e "  This installer will set up a weather station project."
echo -e "  Before you begin, make sure you have:"
echo -e "    ${YELLOW}•${NC} An OpenWeatherMap API key (openweathermap.org)"
echo -e "    ${YELLOW}•${NC} Cron access on the server"
echo ""
echo -ne "  Continue? (${GREEN}Y${NC}/${RED}n${NC}): "
read confirm
if [[ "$confirm" =~ ^[Nn]$ ]]; then
    echo "Installation aborted."
    exit 0
fi

# ── STEP 1: Install path ─────────────────────────────────────

print_step "Install path"
ask "Full destination path (e.g. /home/user/domains/mydomain.com/public_html)" "" INSTALL_PATH
validate_not_empty "$INSTALL_PATH" "Install path" || exit 1
INSTALL_PATH="${INSTALL_PATH%/}"

if [ -d "$INSTALL_PATH" ] && [ "$(ls -A "$INSTALL_PATH" 2>/dev/null)" ]; then
    print_warn "Directory '$INSTALL_PATH' already exists and is not empty."
    echo -ne "  Continue anyway? (${YELLOW}y${NC}/${RED}N${NC}): "
    read overwrite
    if [[ ! "$overwrite" =~ ^[Yy]$ ]]; then
        echo "Installation aborted."
        exit 0
    fi
fi

# ── STEP 2: Database type ────────────────────────────────────

print_step "Database type"
echo -e "  ${BOLD}Select database type:${NC}"
echo -e "    ${CYAN}1${NC}) MySQL / MariaDB  — requires a server, username and password"
echo -e "    ${CYAN}2${NC}) SQLite           — single file, zero configuration"
echo -ne "  Choice [${YELLOW}1${NC}/${YELLOW}2${NC}]: "
read DB_CHOICE

if [ "$DB_CHOICE" = "2" ]; then
    DB_TYPE="sqlite"
    SQLITE_DEFAULT="${INSTALL_PATH%/public_html}/weather.sqlite"
    ask "Path to SQLite file" "$SQLITE_DEFAULT" SQLITE_PATH
    validate_not_empty "$SQLITE_PATH" "SQLite path" || exit 1
    print_ok "Mode: SQLite → $SQLITE_PATH"
else
    DB_TYPE="mysql"
    ask "Database host" "localhost" DB_HOST
    ask "Database name" "" DB_NAME
    validate_not_empty "$DB_NAME" "Database name" || exit 1
    ask "Database user" "" DB_USER
    validate_not_empty "$DB_USER" "Database user" || exit 1
    ask "Database password" "" DB_PASS "true"
    validate_not_empty "$DB_PASS" "Database password" || exit 1
    print_ok "Mode: MySQL @ $DB_HOST / $DB_NAME"
fi

# ── STEP 3: Weather config ───────────────────────────────────

print_step "Weather configuration"
ask "City name (e.g. Warsaw, London, Berlin)" "London" WEATHER_LOCATION
ask "OpenWeatherMap API key" "" WEATHER_API_KEY
validate_not_empty "$WEATHER_API_KEY" "API key" || exit 1

# ── STEP 4: Test DB connection ───────────────────────────────

if [ "$DB_TYPE" = "mysql" ]; then
    print_step "Testing database connection"

    DB_TEST=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" 2>&1)
    if echo "$DB_TEST" | grep -q "ERROR\|error\|denied"; then
        print_err "Cannot connect to database:"
        echo "    $DB_TEST"
        echo ""
        echo -ne "  Continue anyway? (${YELLOW}y${NC}/${RED}N${NC}): "
        read db_force
        if [[ ! "$db_force" =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        print_ok "Database connection: OK"
    fi
fi

# ── STEP 5: Test API key ─────────────────────────────────────

print_step "Testing OpenWeatherMap API key"

API_TEST=$(curl -s -o /dev/null -w "%{http_code}" \
    "http://api.openweathermap.org/data/2.5/weather?q=${WEATHER_LOCATION}&appid=${WEATHER_API_KEY}&units=metric")

if [ "$API_TEST" = "200" ]; then
    print_ok "API key and city: OK (HTTP 200)"
elif [ "$API_TEST" = "401" ]; then
    print_warn "Invalid API key (HTTP 401). You can continue, but data won't be fetched."
elif [ "$API_TEST" = "404" ]; then
    print_warn "City '$WEATHER_LOCATION' not found (HTTP 404). Check the name."
else
    print_warn "Unexpected API response: HTTP $API_TEST"
fi

# ── STEP 6: Create directory structure ───────────────────────

print_step "Creating directories"

mkdir -p "$INSTALL_PATH/archive/old"
print_ok "Directory archive/ created"

# ── STEP 7: Copy files ───────────────────────────────────────

print_step "Copying project files"

for f in config.php update_data.php api.php db.php index.php .htaccess; do
    if [ -f "$TEMPLATE_DIR/$f" ]; then
        cp "$TEMPLATE_DIR/$f" "$INSTALL_PATH/$f"
        print_ok "Copied: $f"
    else
        print_warn "Missing template file: $f"
    fi
done

# arch.sh — replace placeholder with actual install path
sed "s|__INSTALL_PATH__|${INSTALL_PATH}|g" "$TEMPLATE_DIR/arch.sh" > "$INSTALL_PATH/arch.sh"
chmod +x "$INSTALL_PATH/arch.sh"
print_ok "Copied and configured: arch.sh"

# ── STEP 8: Generate .env ────────────────────────────────────

print_step "Generating .env file"

if [ "$DB_TYPE" = "sqlite" ]; then
    cat > "$INSTALL_PATH/.env" <<EOF
DB_TYPE=sqlite
DB_PATH=${SQLITE_PATH}

WEATHER_API_KEY=${WEATHER_API_KEY}
WEATHER_LOCATION=${WEATHER_LOCATION}
WEATHER_API_URL=http://api.openweathermap.org/data/2.5/weather
EOF
else
    cat > "$INSTALL_PATH/.env" <<EOF
DB_TYPE=mysql
DB_HOST=${DB_HOST}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_CHARSET=utf8mb4

WEATHER_API_KEY=${WEATHER_API_KEY}
WEATHER_LOCATION=${WEATHER_LOCATION}
WEATHER_API_URL=http://api.openweathermap.org/data/2.5/weather
EOF
fi

chmod 600 "$INSTALL_PATH/.env"
print_ok ".env file created (permissions 600)"

# ── STEP 9: Create database table ────────────────────────────

print_step "Creating database table"

if [ "$DB_TYPE" = "sqlite" ]; then
    SQLITE_DIR=$(dirname "$SQLITE_PATH")
    mkdir -p "$SQLITE_DIR"

    if [ -f "$SQLITE_PATH" ]; then
        print_warn "SQLite file already exists: $SQLITE_PATH"
        echo -ne "  Delete and create fresh (clean install)? (${RED}y${NC}/${GREEN}N${NC}): "
        read sqlite_overwrite
        if [[ "$sqlite_overwrite" =~ ^[Yy]$ ]]; then
            rm "$SQLITE_PATH"
            print_ok "Old database removed"
        else
            print_warn "Keeping existing database — previous data remains"
        fi
    fi

    PHP_RESULT=$(php -r "
        try {
            \$pdo = new PDO('sqlite:$SQLITE_PATH');
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$sql = file_get_contents('$TEMPLATE_DIR/schema_sqlite.sql');
            \$pdo->exec(\$sql);
            echo 'OK';
        } catch (Exception \$e) {
            echo 'ERR:' . \$e->getMessage();
        }
    " 2>&1)
    if [ "$PHP_RESULT" = "OK" ]; then
        print_ok "SQLite database and weather_data table ready: $SQLITE_PATH"
    else
        print_warn "Problem creating SQLite database: $PHP_RESULT"
    fi
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TEMPLATE_DIR/schema.sql" 2>&1
    if [ $? -eq 0 ]; then
        print_ok "Table weather_data created (or already existed)"
    else
        print_warn "Could not create table automatically. Run manually:"
        echo "    mysql -h $DB_HOST -u $DB_USER -p $DB_NAME < $TEMPLATE_DIR/schema.sql"
    fi
fi

# ── STEP 10: First data fetch ────────────────────────────────

print_step "First data fetch"
echo -ne "  Fetch weather data now? (${GREEN}Y${NC}/${RED}n${NC}): "
read run_first

if [[ ! "$run_first" =~ ^[Nn]$ ]]; then
    RESULT=$(php "$INSTALL_PATH/update_data.php" 2>&1)
    if echo "$RESULT" | grep -q "✅"; then
        print_ok "Data fetched successfully:"
        echo "$RESULT" | sed 's/^/    /'
    else
        print_warn "Script response:"
        echo "$RESULT" | sed 's/^/    /'
    fi
fi

# ── STEP 11: Configure CRON ──────────────────────────────────

print_step "Configuring CRON"

CRON_FETCH="0 * * * * php ${INSTALL_PATH}/update_data.php >/dev/null 2>&1"
CRON_ARCH="59 * * * * ${INSTALL_PATH}/arch.sh >/dev/null 2>&1"

# Read existing crontab exactly once
EXISTING_CRON=$(crontab -l 2>/dev/null)
NEW_ENTRIES=""

if echo "$EXISTING_CRON" | grep -qF "$INSTALL_PATH/update_data.php"; then
    print_warn "Cron entry for update_data.php already exists — skipped"
else
    NEW_ENTRIES="${NEW_ENTRIES}"$'\n'"${CRON_FETCH}"
    print_ok "Queued cron: fetch weather data every hour"
fi

if echo "$EXISTING_CRON" | grep -qF "$INSTALL_PATH/arch.sh"; then
    print_warn "Cron entry for arch.sh already exists — skipped"
else
    NEW_ENTRIES="${NEW_ENTRIES}"$'\n'"${CRON_ARCH}"
    print_ok "Queued cron: archive JSON files every hour"
fi

# Write back exactly once — existing content untouched, new entries appended
if [ -n "$NEW_ENTRIES" ]; then
    ( echo "$EXISTING_CRON"; echo "$NEW_ENTRIES" ) | crontab -
    if [ $? -eq 0 ]; then
        print_ok "Crontab saved successfully"
    else
        print_err "Could not save crontab. Add manually:"
        echo "    $CRON_FETCH"
        echo "    $CRON_ARCH"
    fi
fi

# ── SUMMARY ──────────────────────────────────────────────────

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║        Installation complete!                ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}Install path:${NC} $INSTALL_PATH"
echo -e "  ${BOLD}City:${NC}         $WEATHER_LOCATION"
if [ "$DB_TYPE" = "sqlite" ]; then
    echo -e "  ${BOLD}Database:${NC}     SQLite → $SQLITE_PATH"
else
    echo -e "  ${BOLD}Database:${NC}     MySQL → $DB_NAME @ $DB_HOST"
fi
echo ""
echo -e "  ${YELLOW}Site available after configuring your domain to:${NC}"
echo -e "  ${CYAN}${INSTALL_PATH}/index.php${NC}"
echo ""
