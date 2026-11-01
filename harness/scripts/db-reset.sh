#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"

{
  echo "== migrate:fresh --seed =="
  php artisan migrate:fresh --seed --force
  echo "== DevDataSeeder =="
  php artisan db:seed --class=DevDataSeeder --force || true
} 2>&1 | tee -a "$REPORT_DIR/db-reset.log"
