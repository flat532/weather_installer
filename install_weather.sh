#!/bin/bash

# ============================================================
#  Weather Station Installer
#  Stacja pogodowa oparta na OpenWeatherMap + MySQL + PHP
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
    echo "  ║       Stacja Pogodowa — OpenWeatherMap       ║"
    echo "  ╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() { echo -e "\n${BOLD}${CYAN}▶ $1${NC}"; }
print_ok()   { echo -e "  ${GREEN}✔ $1${NC}"; }
print_warn() { echo -e "  ${YELLOW}⚠ $1${NC}"; }
print_err()  { echo -e "  ${RED}✘ $1${NC}"; }

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
        print_err "Pole '$name' nie może być puste."
        return 1
    fi
    return 0
}

# ── START ────────────────────────────────────────────────────

print_banner

echo -e "  Instalator skonfiguruje projekt stacji pogodowej."
echo -e "  Przed startem upewnij się, że masz:"
echo -e "    ${YELLOW}•${NC} Klucz API OpenWeatherMap (openweathermap.org)"
echo -e "    ${YELLOW}•${NC} Dostęp do crona na serwerze"
echo ""
echo -ne "  Kontynuować? (${GREEN}T${NC}/${RED}n${NC}): "
read confirm
if [[ "$confirm" =~ ^[Nn]$ ]]; then
    echo "Instalacja przerwana."
    exit 0
fi

# ── KROK 1: Ścieżka instalacji ───────────────────────────────

print_step "Ścieżka instalacji"
ask "Pełna ścieżka docelowa (np. /home/user/domains/mojadomena.pl/public_html)" "" INSTALL_PATH
validate_not_empty "$INSTALL_PATH" "Ścieżka instalacji" || exit 1
INSTALL_PATH="${INSTALL_PATH%/}"

if [ -d "$INSTALL_PATH" ] && [ "$(ls -A "$INSTALL_PATH" 2>/dev/null)" ]; then
    print_warn "Katalog '$INSTALL_PATH' istnieje i nie jest pusty."
    echo -ne "  Kontynuować mimo to? (${YELLOW}t${NC}/${RED}N${NC}): "
    read overwrite
    if [[ ! "$overwrite" =~ ^[Tt]$ ]]; then
        echo "Instalacja przerwana."
        exit 0
    fi
fi

# ── KROK 2: Typ bazy danych ──────────────────────────────────

print_step "Typ bazy danych"
echo -e "  ${BOLD}Wybierz typ bazy danych:${NC}"
echo -e "    ${CYAN}1${NC}) MySQL / MariaDB  — wymaga serwera, użytkownika i hasła"
echo -e "    ${CYAN}2${NC}) SQLite           — jeden plik, zero konfiguracji"
echo -ne "  Wybór [${YELLOW}1${NC}/${YELLOW}2${NC}]: "
read DB_CHOICE

if [ "$DB_CHOICE" = "2" ]; then
    DB_TYPE="sqlite"
    SQLITE_DEFAULT="${INSTALL_PATH%/public_html}/weather.sqlite"
    ask "Ścieżka do pliku SQLite" "$SQLITE_DEFAULT" SQLITE_PATH
    validate_not_empty "$SQLITE_PATH" "Ścieżka SQLite" || exit 1
    print_ok "Tryb: SQLite → $SQLITE_PATH"
else
    DB_TYPE="mysql"
    ask "Host bazy danych" "localhost" DB_HOST
    ask "Nazwa bazy danych" "" DB_NAME
    validate_not_empty "$DB_NAME" "Nazwa bazy danych" || exit 1
    ask "Użytkownik bazy danych" "" DB_USER
    validate_not_empty "$DB_USER" "Użytkownik bazy danych" || exit 1
    ask "Hasło bazy danych" "" DB_PASS "true"
    validate_not_empty "$DB_PASS" "Hasło bazy danych" || exit 1
    print_ok "Tryb: MySQL @ $DB_HOST / $DB_NAME"
fi

# ── KROK 3: Pogoda ──────────────────────────────────────────

print_step "Konfiguracja pogody"
ask "Nazwa miasta (po angielsku, np. Warsaw, Krakow, Gliwice)" "Gliwice" WEATHER_LOCATION
ask "Klucz API OpenWeatherMap" "" WEATHER_API_KEY
validate_not_empty "$WEATHER_API_KEY" "Klucz API" || exit 1

# ── KROK 4: Test połączenia z bazą ──────────────────────────

if [ "$DB_TYPE" = "mysql" ]; then
    print_step "Testowanie połączenia z bazą danych"

    DB_TEST=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" 2>&1)
    if echo "$DB_TEST" | grep -q "ERROR\|error\|denied"; then
        print_err "Nie można połączyć się z bazą danych:"
        echo "    $DB_TEST"
        echo ""
        echo -ne "  Kontynuować mimo błędu? (${YELLOW}t${NC}/${RED}N${NC}): "
        read db_force
        if [[ ! "$db_force" =~ ^[Tt]$ ]]; then
            exit 1
        fi
    else
        print_ok "Połączenie z bazą danych: OK"
    fi
fi

# ── KROK 5: Test klucza API ──────────────────────────────────

print_step "Testowanie klucza API OpenWeatherMap"

API_TEST=$(curl -s -o /dev/null -w "%{http_code}" \
    "http://api.openweathermap.org/data/2.5/weather?q=${WEATHER_LOCATION}&appid=${WEATHER_API_KEY}&units=metric")

if [ "$API_TEST" = "200" ]; then
    print_ok "Klucz API i miasto: OK (HTTP 200)"
elif [ "$API_TEST" = "401" ]; then
    print_warn "Klucz API jest nieprawidłowy (HTTP 401). Możesz kontynuować, ale dane nie będą pobierane."
elif [ "$API_TEST" = "404" ]; then
    print_warn "Miasto '$WEATHER_LOCATION' nie zostało znalezione (HTTP 404). Sprawdź nazwę."
else
    print_warn "Nieoczekiwana odpowiedź API: HTTP $API_TEST"
fi

# ── KROK 6: Tworzenie struktury katalogów ───────────────────

print_step "Tworzenie katalogów"

mkdir -p "$INSTALL_PATH/archive/old"
print_ok "Katalog archive/ utworzony"

# ── KROK 7: Kopiowanie plików ────────────────────────────────

print_step "Kopiowanie plików projektu"

for f in config.php update_data.php api.php db.php index.php .htaccess; do
    if [ -f "$TEMPLATE_DIR/$f" ]; then
        cp "$TEMPLATE_DIR/$f" "$INSTALL_PATH/$f"
        print_ok "Skopiowano: $f"
    else
        print_warn "Brak pliku szablonu: $f"
    fi
done

# arch.sh — zastąp placeholder rzeczywistą ścieżką
sed "s|__INSTALL_PATH__|${INSTALL_PATH}|g" "$TEMPLATE_DIR/arch.sh" > "$INSTALL_PATH/arch.sh"
chmod +x "$INSTALL_PATH/arch.sh"
print_ok "Skopiowano i skonfigurowano: arch.sh"

# ── KROK 8: Generowanie .env ─────────────────────────────────

print_step "Generowanie pliku .env"

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
print_ok "Plik .env utworzony (uprawnienia 600)"

# ── KROK 9: Tworzenie tabeli ─────────────────────────────────

print_step "Tworzenie tabeli w bazie danych"

if [ "$DB_TYPE" = "sqlite" ]; then
    SQLITE_DIR=$(dirname "$SQLITE_PATH")
    mkdir -p "$SQLITE_DIR"

    if [ -f "$SQLITE_PATH" ]; then
        print_warn "Plik bazy SQLite już istnieje: $SQLITE_PATH"
        echo -ne "  Usunąć i utworzyć nową (czysta instalacja)? (${RED}t${NC}/${GREEN}N${NC}): "
        read sqlite_overwrite
        if [[ "$sqlite_overwrite" =~ ^[Tt]$ ]]; then
            rm "$SQLITE_PATH"
            print_ok "Stara baza usunięta"
        else
            print_warn "Zachowano istniejącą bazę — dane z poprzedniej instalacji pozostają"
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
        print_ok "Baza SQLite i tabela weather_data gotowe: $SQLITE_PATH"
    else
        print_warn "Problem z utworzeniem bazy SQLite: $PHP_RESULT"
    fi
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TEMPLATE_DIR/schema.sql" 2>&1
    if [ $? -eq 0 ]; then
        print_ok "Tabela weather_data utworzona (lub już istniała)"
    else
        print_warn "Nie udało się automatycznie utworzyć tabeli. Uruchom ręcznie:"
        echo "    mysql -h $DB_HOST -u $DB_USER -p $DB_NAME < $TEMPLATE_DIR/schema.sql"
    fi
fi

# ── KROK 10: Pierwsze pobranie danych ────────────────────────

print_step "Pierwsze pobranie danych"
echo -ne "  Uruchomić teraz pierwsze pobranie danych? (${GREEN}T${NC}/${RED}n${NC}): "
read run_first

if [[ ! "$run_first" =~ ^[Nn]$ ]]; then
    RESULT=$(php "$INSTALL_PATH/update_data.php" 2>&1)
    if echo "$RESULT" | grep -q "✅"; then
        print_ok "Dane pobrane pomyślnie:"
        echo "$RESULT" | sed 's/^/    /'
    else
        print_warn "Odpowiedź skryptu:"
        echo "$RESULT" | sed 's/^/    /'
    fi
fi

# ── KROK 11: Konfiguracja CRON ───────────────────────────────

print_step "Konfiguracja CRON"

CRON_FETCH="0 * * * * php ${INSTALL_PATH}/update_data.php >/dev/null 2>&1"
CRON_ARCH="59 * * * * ${INSTALL_PATH}/arch.sh >/dev/null 2>&1"

CURRENT_CRON=$(crontab -l 2>/dev/null)
CRON_CHANGED=0

if echo "$CURRENT_CRON" | grep -qF "$INSTALL_PATH/update_data.php"; then
    print_warn "Wpis cron dla update_data.php już istnieje — pominięto"
else
    CURRENT_CRON="${CURRENT_CRON}"$'\n'"${CRON_FETCH}"
    CRON_CHANGED=1
    print_ok "Dodano wpis cron: pobieranie danych co godzinę"
fi

if echo "$CURRENT_CRON" | grep -qF "$INSTALL_PATH/arch.sh"; then
    print_warn "Wpis cron dla arch.sh już istnieje — pominięto"
else
    CURRENT_CRON="${CURRENT_CRON}"$'\n'"${CRON_ARCH}"
    CRON_CHANGED=1
    print_ok "Dodano wpis cron: archiwizacja co godzinę"
fi

if [ $CRON_CHANGED -eq 1 ]; then
    echo "$CURRENT_CRON" | crontab -
    if [ $? -eq 0 ]; then
        print_ok "Crontab zapisany pomyślnie"
    else
        print_err "Nie udało się zapisać crontaba. Dodaj ręcznie:"
        echo "    $CRON_FETCH"
        echo "    $CRON_ARCH"
    fi
fi

# ── PODSUMOWANIE ─────────────────────────────────────────────

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║         Instalacja zakończona!               ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}Ścieżka instalacji:${NC} $INSTALL_PATH"
echo -e "  ${BOLD}Miasto:${NC}             $WEATHER_LOCATION"
if [ "$DB_TYPE" = "sqlite" ]; then
    echo -e "  ${BOLD}Baza danych:${NC}        SQLite → $SQLITE_PATH"
else
    echo -e "  ${BOLD}Baza danych:${NC}        MySQL → $DB_NAME @ $DB_HOST"
fi
echo ""
echo -e "  ${YELLOW}Strona dostępna po skonfigurowaniu domeny na:${NC}"
echo -e "  ${CYAN}${INSTALL_PATH}/index.html${NC}"
echo ""
