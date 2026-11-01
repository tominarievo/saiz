#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

docker compose \
  -f docker-compose.yml \
  -f harness/docker/compose.harness.yml \
  run --rm node sh -c "npm ci && npm run production"
