#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

COMPOSE=(docker compose)
if ! docker compose version >/dev/null 2>&1; then
    if command -v docker-compose >/dev/null 2>&1; then
        COMPOSE=(docker-compose)
    else
        echo "Docker Compose is required. Install Docker Engine + the compose plugin." >&2
        exit 1
    fi
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is required." >&2
    exit 1
fi

PORT="${HAKEEM_HTTP_PORT:-8080}"
APP_URL="${APP_URL:-}"
FRESH=0
REBUILD=0

usage() {
    cat <<'EOF'
Usage: ./setup.sh [options]

Prepare a testing server stack: PostgreSQL, nginx, PHP-FPM, queue, scheduler,
migrations, and demo seed data.

Options:
  --url URL       Public URL (default: http://<host-ip>:8080)
  --port PORT     Host HTTP port (default: 8080)
  --fresh         Drop Docker volumes and reseed
  --rebuild       Rebuild the app image
  -h, --help      Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --url)
            APP_URL="${2:-}"
            shift 2
            ;;
        --port)
            PORT="${2:-}"
            shift 2
            ;;
        --fresh)
            FRESH=1
            shift
            ;;
        --rebuild)
            REBUILD=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

if [[ -z "$APP_URL" ]]; then
    HOST_IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
    if [[ -z "$HOST_IP" ]]; then
        HOST_IP="localhost"
    fi
    APP_URL="http://${HOST_IP}:${PORT}"
fi

if [[ ! -f .env.docker ]]; then
    cp .env.docker.example .env.docker
fi

set_env() {
    local key="$1"
    local value="$2"
    local file=".env.docker"

    if grep -q "^${key}=" "$file"; then
        # portable in-place replace without leaking the value into sed delimiters
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

current_key="$(grep -E '^APP_KEY=' .env.docker | head -n1 | cut -d= -f2- || true)"
if [[ -z "$current_key" ]]; then
    set_env APP_KEY "base64:$(openssl rand -base64 32 | tr -d '\n')"
fi

current_hermes="$(grep -E '^HERMES_AGENT_KEY=' .env.docker | head -n1 | cut -d= -f2- || true)"
if [[ -z "$current_hermes" ]]; then
    set_env HERMES_AGENT_KEY "$(openssl rand -hex 24)"
fi

set_env APP_URL "$APP_URL"
set_env HAKEEM_HTTP_PORT "$PORT"
set_env APP_ENV local
set_env APP_DEBUG true
set_env DB_CONNECTION pgsql
set_env DB_HOST postgres
set_env DB_PORT 5432

echo "==> Building and starting Hakeem testing stack on ${APP_URL}"

if [[ "$FRESH" -eq 1 ]]; then
    "${COMPOSE[@]}" --env-file .env.docker down -v --remove-orphans
fi

BUILD_ARGS=(up -d --wait)
if [[ "$REBUILD" -eq 1 || "$FRESH" -eq 1 ]]; then
    BUILD_ARGS=(up -d --build --wait)
elif ! docker image inspect hakeem-testing:latest >/dev/null 2>&1; then
    BUILD_ARGS=(up -d --build --wait)
fi

"${COMPOSE[@]}" --env-file .env.docker "${BUILD_ARGS[@]}"

echo "==> Linking storage and seeding demo data"
"${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan storage:link --force
"${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan migrate --force --no-interaction
"${COMPOSE[@]}" --env-file .env.docker exec -T app php artisan db:seed --force --no-interaction

HERMES_KEY="$(grep -E '^HERMES_AGENT_KEY=' .env.docker | head -n1 | cut -d= -f2-)"

cat <<EOF

Hakeem testing stack is ready.

  Site:     ${APP_URL}
  Health:   ${APP_URL}/up
  Agent:    ${APP_URL}/api/agent/v1

  Admin:    ${APP_URL}/admin/login
            admin@hakeem.test  or  01000000000
            password

  Clinic:   ${APP_URL}/login
            01111111111
            password

  Hermes:   X-Hermes-Key: ${HERMES_KEY}

Useful commands:
  ${COMPOSE[*]} --env-file .env.docker logs -f app
  ${COMPOSE[*]} --env-file .env.docker exec app php artisan tinker
  ./setup.sh --fresh --rebuild --url ${APP_URL}

EOF
