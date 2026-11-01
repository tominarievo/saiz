#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G6}"
. "$(dirname "$0")/common.sh"

{
  echo "== config:cache =="
  php artisan config:cache

  echo "== route:cache =="
  php artisan route:cache

  echo "== view:cache =="
  php artisan view:cache

  echo "== artisan about =="
  php artisan about

  echo "== cached HTTP login =="
  curl -s -o /dev/null -w "login=%{http_code}\n" http://127.0.0.1/login

  echo "== clear caches =="
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
} 2>&1 | tee -a "$REPORT_DIR/cache-check.log"
