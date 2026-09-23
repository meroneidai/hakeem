#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

PORT="${HAKEEM_HTTP_PORT:-8080}"
APP_URL="${APP_URL:-}"
DOMAIN="${HAKEEM_DOMAIN:-}"
FRESH=0
REBUILD=0
COMPOSE=(docker compose)
DB_NAME="hakeem"
DB_USER="hakeem"
DB_PASSWORD=""

usage() {
    cat <<'EOF'
Usage: ./setup.sh [options]

First release / rebuild / resetup for Hakeem.

Installs PostgreSQL on the server (user/database: hakeem, unique password),
installs Docker if needed, builds the app image (PHP + Node 22 + npm run build),
starts containers, migrates + seeds (including demo catalog), and publishes
the site on port 80 through host nginx.

Run this from the project directory, usually /var/hakeem.

First release:
  ./setup.sh --domain eg.hakeem.com.sa --url https://eg.hakeem.com.sa

Full resetup (wipe DB volumes, no-cache image rebuild, migrate + seed):
  ./setup.sh --resetup --domain eg.hakeem.com.sa --url https://eg.hakeem.com.sa

Options:
  --url URL       Public URL (default: http://<ip> or http://<domain>)
  --domain NAME   Server name for nginx (example: hakeem.example.com)
  --port PORT     Internal Docker HTTP port (default: 8080)
  --fresh         Drop Docker volumes + host DB, then reseed
  --rebuild       Force docker compose build --no-cache (Node/npm/Vite image)
  --resetup       Same as --fresh --rebuild (first-release wipe and rebuild)
  -h, --help      Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --url) APP_URL="${2:-}"; shift 2 ;;
        --domain) DOMAIN="${2:-}"; shift 2 ;;
        --port) PORT="${2:-}"; shift 2 ;;
        --fresh) FRESH=1; shift ;;
        --rebuild) REBUILD=1; shift ;;
        --resetup) FRESH=1; REBUILD=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

as_root() {
    if [[ "$(id -u)" -eq 0 ]]; then
        "$@"
    elif command -v sudo >/dev/null 2>&1; then
        sudo "$@"
    else
        echo "Run this script as root, or install sudo." >&2
        exit 1
    fi
}

detect_host_ip() {
    hostname -I 2>/dev/null | awk '{print $1}'
}

is_debian() {
    [[ -f /etc/debian_version ]] || command -v apt-get >/dev/null 2>&1
}

as_postgres() {
    if [[ "$(id -u)" -eq 0 ]]; then
        if command -v runuser >/dev/null 2>&1; then
            runuser -u postgres -- "$@"
        else
            su -s /bin/bash postgres -c "$(printf '%q ' "$@")"
        fi
    elif command -v sudo >/dev/null 2>&1; then
        sudo -u postgres "$@"
    else
        echo "Run this script as root to configure PostgreSQL." >&2
        exit 1
    fi
}

env_value() {
    local key="$1"
    local file="$2"
    if [[ ! -f "$file" ]]; then
        return 0
    fi
    grep -E "^${key}=" "$file" | head -n1 | cut -d= -f2- || true
}

sql_literal() {
    printf "%s" "$1" | sed "s/'/''/g"
}

resolve_db_password() {
    local existing
    existing="$(env_value DB_PASSWORD .env.docker)"

    if [[ -n "$existing" ]]; then
        DB_PASSWORD="$existing"
        echo "==> Reusing PostgreSQL password from .env.docker"
        return
    fi

    DB_PASSWORD="$(random_hex)"
    echo "==> Generated unique PostgreSQL password"
}

install_host_packages() {
    if ! is_debian; then
        echo "This setup installs packages with apt. Use Debian or Ubuntu." >&2
        exit 1
    fi

    echo "==> Installing host packages (curl, nginx, openssl)"
    export DEBIAN_FRONTEND=noninteractive
    as_root apt-get update -y
    as_root apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        openssl \
        nginx
}

install_postgresql() {
    if ! is_debian; then
        echo "PostgreSQL install needs apt (Debian/Ubuntu)." >&2
        exit 1
    fi

    echo "==> Installing PostgreSQL"
    export DEBIAN_FRONTEND=noninteractive
    as_root apt-get update -y
    as_root apt-get install -y postgresql postgresql-contrib

    if command -v systemctl >/dev/null 2>&1; then
        as_root systemctl enable --now postgresql
        as_root systemctl status postgresql --no-pager || true
        if ! as_root systemctl is-active --quiet postgresql; then
            echo "PostgreSQL failed to start." >&2
            exit 1
        fi
    fi

    local tries=0
    until as_postgres pg_isready -q; do
        tries=$((tries + 1))
        if [[ "$tries" -ge 30 ]]; then
            echo "PostgreSQL is installed but not ready." >&2
            exit 1
        fi
        sleep 1
    done

    echo "==> Creating role and database (${DB_USER} / ${DB_NAME})"
    local escaped_password
    escaped_password="$(sql_literal "$DB_PASSWORD")"
    as_postgres psql -v ON_ERROR_STOP=1 <<SQL
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE USER ${DB_USER} WITH PASSWORD '${escaped_password}';
    ELSE
        ALTER USER ${DB_USER} WITH PASSWORD '${escaped_password}';
    END IF;
END
\$\$;
SQL

    if [[ "$FRESH" -eq 1 ]]; then
        as_postgres psql -v ON_ERROR_STOP=1 <<SQL
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = '${DB_NAME}' AND pid <> pg_backend_pid();
DROP DATABASE IF EXISTS ${DB_NAME};
SQL
    fi

    as_postgres psql -v ON_ERROR_STOP=1 <<SQL
SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec

GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
SQL

    as_postgres psql -v ON_ERROR_STOP=1 -d "$DB_NAME" <<SQL
GRANT ALL ON SCHEMA public TO ${DB_USER};
ALTER SCHEMA public OWNER TO ${DB_USER};
SQL

    configure_postgres_access
}

configure_postgres_access() {
    local conf hba
    conf="$(as_postgres psql -tAc 'SHOW config_file' | tr -d '[:space:]')"
    hba="$(as_postgres psql -tAc 'SHOW hba_file' | tr -d '[:space:]')"

    if [[ -z "$conf" || ! -f "$conf" ]]; then
        echo "Could not find postgresql.conf" >&2
        exit 1
    fi

    echo "==> Allowing Docker containers to reach host PostgreSQL"
    if as_root grep -qE "^#?listen_addresses" "$conf"; then
        as_root sed -i -E "s/^#?listen_addresses.*/listen_addresses = '*'/" "$conf"
    else
        printf "\nlisten_addresses = '*'\n" | as_root tee -a "$conf" >/dev/null
    fi

    if [[ -n "$hba" && -f "$hba" ]] && ! as_root grep -qE "^host[[:space:]]+${DB_NAME}[[:space:]]+${DB_USER}[[:space:]]+172\\.16\\.0\\.0/12" "$hba"; then
        printf '\nhost\t%s\t%s\t127.0.0.1/32\tscram-sha-256\nhost\t%s\t%s\t172.16.0.0/12\tscram-sha-256\nhost\t%s\t%s\t192.168.0.0/16\tscram-sha-256\nhost\t%s\t%s\t10.0.0.0/8\tscram-sha-256\n' \
            "$DB_NAME" "$DB_USER" "$DB_NAME" "$DB_USER" "$DB_NAME" "$DB_USER" "$DB_NAME" "$DB_USER" \
            | as_root tee -a "$hba" >/dev/null
    fi

    if command -v systemctl >/dev/null 2>&1; then
        as_root systemctl reload postgresql
    fi
}

install_docker() {
    if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        echo "==> Docker is already installed"
        return 0
    fi

    echo "==> Installing Docker Engine + Compose"
    if ! command -v curl >/dev/null 2>&1; then
        install_host_packages
    fi

    as_root sh -c 'curl -fsSL https://get.docker.com | sh'
    if command -v systemctl >/dev/null 2>&1; then
        as_root systemctl enable --now docker
    fi

    local tries=0
    until docker info >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [[ "$tries" -ge 30 ]]; then
            echo "Docker installed but the daemon is not ready. Reboot and rerun ./setup.sh" >&2
            exit 1
        fi
        sleep 2
    done
}

resolve_compose() {
    if docker compose version >/dev/null 2>&1; then
        COMPOSE=(docker compose)
        return
    fi

    if command -v docker-compose >/dev/null 2>&1; then
        COMPOSE=(docker-compose)
        return
    fi

    echo "Docker Compose is missing after Docker install." >&2
    exit 1
}

random_hex() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 24
        return
    fi
    tr -dc 'a-f0-9' </dev/urandom | head -c 48
}

random_app_key() {
    if command -v openssl >/dev/null 2>&1; then
        printf 'base64:%s' "$(openssl rand -base64 32 | tr -d '\n')"
        return
    fi
    printf 'base64:%s' "$(head -c 32 /dev/urandom | base64 | tr -d '\n')"
}

set_env() {
    local key="$1"
    local value="$2"
    local file=".env.docker"

    if grep -q "^${key}=" "$file"; then
        awk -v k="$key" -v v="$value" '
            BEGIN { FS=OFS="=" }
            $1==k { print k "=" v; next }
            { print }
        ' "$file" > "${file}.tmp"
        mv "${file}.tmp" "$file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$file"
    fi
}

write_env_file() {
    if [[ ! -f .env.docker.example ]]; then
        echo "Missing .env.docker.example in ${ROOT}" >&2
        exit 1
    fi

    if [[ ! -f .env.docker ]]; then
        cp .env.docker.example .env.docker
    fi

    local current_key current_hermes
    current_key="$(grep -E '^APP_KEY=' .env.docker | head -n1 | cut -d= -f2- || true)"
    current_hermes="$(grep -E '^HERMES_AGENT_KEY=' .env.docker | head -n1 | cut -d= -f2- || true)"

    if [[ -z "$current_key" ]]; then
        set_env APP_KEY "$(random_app_key)"
    fi
    if [[ -z "$current_hermes" ]]; then
        set_env HERMES_AGENT_KEY "$(random_hex)"
    fi

    set_env APP_NAME Hakeem
    set_env APP_URL "$APP_URL"
    set_env APP_ENV local
    set_env APP_DEBUG true
    set_env HAKEEM_HTTP_PORT "$PORT"
    set_env HAKEEM_HTTP_BIND 127.0.0.1
    set_env HAKEEM_SEED_DEMO true
    set_env DB_CONNECTION pgsql
    set_env DB_HOST host.docker.internal
    set_env DB_PORT 5432
    set_env DB_DATABASE "$DB_NAME"
    set_env DB_USERNAME "$DB_USER"
    set_env DB_PASSWORD "$DB_PASSWORD"
}

publish_nginx() {
    if ! command -v nginx >/dev/null 2>&1; then
        echo "==> nginx not found; site will be on ${APP_URL}"
        return 0
    fi

    local server_name="_"
    if [[ -n "$DOMAIN" ]]; then
        server_name="$DOMAIN"
    fi

    local dest="/etc/nginx/sites-available/hakeem"
    echo "==> Writing nginx site ${dest} (proxy to Docker, not host PHP)"
    as_root sed \
        -e "s/SERVER_NAME_PLACEHOLDER/${server_name}/g" \
        -e "s/DOCKER_PORT_PLACEHOLDER/${PORT}/g" \
        "${ROOT}/docker/host-nginx.conf" | as_root tee "$dest" >/dev/null

    if [[ -d /etc/nginx/sites-enabled ]]; then
        as_root ln -sfn "$dest" /etc/nginx/sites-enabled/hakeem
        if [[ -e /etc/nginx/sites-enabled/default ]]; then
            as_root rm -f /etc/nginx/sites-enabled/default
        fi
    fi

    as_root nginx -t
    if command -v systemctl >/dev/null 2>&1; then
        as_root systemctl enable --now nginx
        as_root systemctl reload nginx
    else
        as_root nginx -s reload || as_root service nginx reload
    fi
}

start_stack() {
    echo "==> Building and starting Hakeem containers"
    if [[ "$FRESH" -eq 1 ]]; then
        "${COMPOSE[@]}" --env-file .env.docker down -v --remove-orphans
    fi

    if [[ "$REBUILD" -eq 1 ]]; then
        echo "==> Rebuilding image with --no-cache (Node 22, npm install, npm run build)"
        "${COMPOSE[@]}" --env-file .env.docker build --no-cache --pull
        "${COMPOSE[@]}" --env-file .env.docker up -d --wait --force-recreate
    else
        echo "==> Building image (Node 22, npm install, npm run build)"
        "${COMPOSE[@]}" --env-file .env.docker up -d --build --wait
    fi

    verify_frontend_build

    echo "==> Migrating and seeding (service types + demo catalog when HAKEEM_SEED_DEMO=true)"
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan storage:link --force
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan migrate --force --no-interaction
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan db:seed --force --no-interaction
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan optimize:clear
}

verify_frontend_build() {
    echo "==> Verifying Node, npm, and Vite assets"
    "${COMPOSE[@]}" --env-file .env.docker exec -T app node -v
    "${COMPOSE[@]}" --env-file .env.docker exec -T app npm -v

    if ! "${COMPOSE[@]}" --env-file .env.docker exec -T app test -f public/build/manifest.json; then
        echo "Vite build is missing (public/build/manifest.json). Node/npm build failed." >&2
        exit 1
    fi

    echo "==> Vite manifest is present"
}

if [[ -z "$APP_URL" ]]; then
    if [[ -n "$DOMAIN" ]]; then
        APP_URL="http://${DOMAIN}"
    else
        HOST_IP="$(detect_host_ip)"
        if [[ -z "$HOST_IP" ]]; then
            HOST_IP="localhost"
        fi
        APP_URL="http://${HOST_IP}"
    fi
fi

install_host_packages
resolve_db_password
install_postgresql
install_docker
resolve_compose
write_env_file
start_stack
publish_nginx

HERMES_KEY="$(grep -E '^HERMES_AGENT_KEY=' .env.docker | head -n1 | cut -d= -f2-)"

cat <<EOF

Hakeem is ready. Host PostgreSQL holds the data. PHP, Composer, Node, and npm run in Docker.
The image runs npm install and npm run build so Vite assets are in public/build.
Host nginx publishes the site on port 80. Demo clinics/doctors are seeded (HAKEEM_SEED_DEMO).

  Project:  ${ROOT}
  Site:     ${APP_URL}
  Health:   ${APP_URL}/up
  Services: ${APP_URL}/services/home-visit
  Database: ${DB_NAME} @ 127.0.0.1:5432
  DB user:  ${DB_USER}
  DB pass:  ${DB_PASSWORD}
  Admin:    ${APP_URL}/admin/login
            admin@hakeem.test  or  01000000000 / password
  Clinic:   ${APP_URL}/login
            01111111111 / password
  Hermes:   X-Hermes-Key: ${HERMES_KEY}

Point your domain A record to this server, then rerun:
  ./setup.sh --domain your-domain.com

Full wipe and rebuild later:
  ./setup.sh --resetup --domain your-domain.com --url https://your-domain.com

EOF
