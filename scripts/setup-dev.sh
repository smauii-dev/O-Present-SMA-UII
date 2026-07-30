#!/usr/bin/env bash
# Bootstrap the DEV environment from scratch:
#   1. Build & start dev containers (smauii-opresent-app-dev + smauii-opresent-db-dev)
#   2. Run CI4 migrations
#   3. Seed essential data (roles, positions, users)
#
# Usage: bun run dev:setup   (or: bash scripts/setup-dev.sh)
set -euo pipefail

COMPOSE="docker compose -f docker/docker-compose.dev.yml"
CONTAINER="${OPRESENT_CONTAINER:-smauii-opresent-app-dev}"

echo "▶ Starting dev containers..."
$COMPOSE up -d --build

echo "▶ Waiting for app container..."
for i in $(seq 1 30); do
  if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then break; fi
  sleep 1
done

echo "▶ Running migrations..."
docker exec "${CONTAINER}" php spark migrate --all

echo "▶ Seeding roles..."
docker exec "${CONTAINER}" php spark db:seed RoleSeeder || true
docker exec "${CONTAINER}" php spark db:seed PositionSeeder || true
docker exec "${CONTAINER}" php spark db:seed UserSeeder || true

echo "✓ Dev environment ready!"
echo "  Access via nginx-proxy: http://dev-presensi.smauiiyk.sch.id (if configured)"
echo "  Or via docker exec: docker exec -it ${CONTAINER} bash"
echo "  Run 'bun run dev' to start Vite HMR + backend watcher."
