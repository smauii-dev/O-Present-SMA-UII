#!/usr/bin/env bash
# One-command DEV environment (isolated from production):
#   Vite HMR (frontend, :5173) + CI4 spark serve (backend, in smauii-opresent-app-dev)
# runs in parallel. The backend runs INSIDE the dev container so it uses the
# local dev PostgreSQL (smauii-opresent-db-dev), never NeonDB production.
#
# Requires: docker compose -f docker/docker-compose.dev.yml up -d --build (first time)
set -euo pipefail

CONTAINER="${OPRESENT_CONTAINER:-smauii-opresent-app-dev}"
PORT="${1:-8080}"

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  echo "✗ Dev container '${CONTAINER}' not running."
  echo "  Start it first:"
  echo "    docker compose -f docker/docker-compose.dev.yml up -d --build"
  exit 1
fi

# Start spark serve in background
docker exec -d ${CONTAINER} php spark serve --port ${PORT} --host 0.0.0.0

# Start Vite HMR in foreground
bun run dev
