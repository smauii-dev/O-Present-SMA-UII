#!/usr/bin/env bash
#
# Production deploy for O-Present (presensi.smauiiyk.sch.id)
#
#   bash scripts/deploy.sh
#   bash scripts/deploy.sh --skip-migrate   # image/assets only
#
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/docker/docker-compose.yml"
ENV_FILE="$PROJECT_ROOT/.env.production"
APP_CONTAINER="${OPRESENT_CONTAINER:-smauii-opresent-app}"

cd "$PROJECT_ROOT"

echo "[Deploy] Root: $PROJECT_ROOT"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "[Deploy] ERROR: missing $ENV_FILE" >&2
  exit 1
fi

# 1) Frontend assets (bind-mounted into the app container)
if command -v bun >/dev/null 2>&1; then
  echo "[Deploy] bun install + vite build..."
  bun install
  bun run build
else
  echo "[Deploy] WARN: bun not found — skipping frontend build" >&2
fi

# 2) Rebuild image + recreate containers
echo "[Deploy] docker compose up -d --build..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --build

# 3) Wait for Apache
echo "[Deploy] Waiting for container..."
for i in $(seq 1 30); do
  if docker exec "$APP_CONTAINER" php -v >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

# 4) Clear compiled Twig + file cache (production caches templates;
#    without this, UI can stay on old HTML while :8100 looks new)
echo "[Deploy] Clearing Twig + CI cache..."
docker exec -u www-data "$APP_CONTAINER" bash -c \
  'rm -rf /var/www/html/writable/twig/* /var/www/html/writable/cache/*' || true
docker exec "$APP_CONTAINER" php spark cache:clear 2>/dev/null || true

SKIP_MIGRATE=0
for arg in "$@"; do
  [[ "$arg" == "--skip-migrate" ]] && SKIP_MIGRATE=1
done

if [[ "$SKIP_MIGRATE" -eq 0 ]]; then
  echo "[Deploy] php spark migrate --all..."
  docker exec "$APP_CONTAINER" php spark migrate --all

  echo "[Deploy] php spark lokasi:normalize..."
  docker exec "$APP_CONTAINER" php spark lokasi:normalize || true
fi

echo "[Deploy] Status:"
docker ps --filter "name=smauii-opresent-app" --filter "name=smauii-rustfs" --format 'table {{.Names}}\t{{.Status}}\t{{.Image}}'

echo "[Deploy] Done. Check https://presensi.smauiiyk.sch.id/login"
