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

usage() {
    cat <<'EOF'
Usage: ./setup.sh [options]

Installs Docker if needed, starts Hakeem (PHP, PostgreSQL, queue) in
containers, writes .env.docker for you, and publishes the site on port 80
through host nginx. You do not install PHP or Composer on the server.

Run this from the project directory, usually /var/hakeem.

Options:
  --url URL       Public URL (default: http://<ip> or http://<domain>)
  --domain NAME   Server name for nginx (example: hakeem.example.com)
  --port PORT     Internal Docker HTTP port (default: 8080)
  --fresh         Drop Docker volumes and reseed
  --rebuild       Rebuild the app image
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

install_host_packages() {
    if ! is_debian; then
        return 0
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
    set_env DB_CONNECTION pgsql
    set_env DB_HOST postgres
    set_env DB_PORT 5432
    set_env DB_DATABASE hakeem
    set_env DB_USERNAME hakeem
    set_env DB_PASSWORD hakeem
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

    local args=(up -d --wait)
    if [[ "$REBUILD" -eq 1 || "$FRESH" -eq 1 ]]; then
        args=(up -d --build --wait)
    elif ! docker image inspect hakeem-testing:latest >/dev/null 2>&1; then
        args=(up -d --build --wait)
    fi

    "${COMPOSE[@]}" --env-file .env.docker "${args[@]}"

    echo "==> Migrating and seeding"
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan storage:link --force
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan migrate --force --no-interaction
    "${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan db:seed --force --no-interaction
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
install_docker
resolve_compose
write_env_file
start_stack
publish_nginx

HERMES_KEY="$(grep -E '^HERMES_AGENT_KEY=' .env.docker | head -n1 | cut -d= -f2-)"

cat <<EOF

Hakeem is ready. PHP, Composer, and PostgreSQL are inside Docker.
Host nginx publishes the site on port 80.

  Project:  ${ROOT}
  Site:     ${APP_URL}
  Health:   ${APP_URL}/up
  Admin:    ${APP_URL}/admin/login
            admin@hakeem.test  or  01000000000 / password
  Clinic:   ${APP_URL}/login
            01111111111 / password
  Hermes:   X-Hermes-Key: ${HERMES_KEY}

Point your domain A record to this server, then rerun:
  ./setup.sh --domain your-domain.com

EOF
