#!/usr/bin/env bash
# Run the CI4 backend dev server inside the DEV Docker container (bind-mount aware).
# Uses local dev PostgreSQL (smauii-opresent-db-dev), never NeonDB production.
#
# For production: OPRESENT_CONTAINER=smauii-opresent-app bash scripts/serve.sh
# Usage: scripts/serve.sh [port]
set -euo pipefail

CONTAINER="${OPRESENT_CONTAINER:-smauii-opresent-app-dev}"
PORT="${1:-8080}"

docker exec "${CONTAINER}" php spark serve --port "${PORT}" --host 0.0.0.0
