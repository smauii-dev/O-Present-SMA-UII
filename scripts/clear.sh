#!/usr/bin/env bash
# Clear all CodeIgniter writable caches (Twig compile, app cache, etc.)
# inside the DEV container. Run this after editing Twig/PHP views.
#
# For production: OPRESENT_CONTAINER=smauii-opresent-app bun run clear
set -euo pipefail

CONTAINER="${OPRESENT_CONTAINER:-smauii-opresent-app-dev}"

docker exec "${CONTAINER}" sh -c 'rm -rf /var/www/html/writable/cache/twig/*'
docker exec "${CONTAINER}" php spark cache:clear
