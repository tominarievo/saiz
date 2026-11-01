#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"
LOG="$REPORT_DIR/preflight.log"

{
  echo "== dotenv =="
  if [ ! -f .env ]; then
    cp .env.example .env
  fi
  if ! grep -qE '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
  fi

  echo "== versions =="
  php -v
  php artisan --version
  php -m | sort

  echo "== composer validate =="
  composer validate --no-check-publish

  echo "== composer install =="
  composer install --no-interaction --prefer-dist --no-progress

  echo "== artisan about (optional) =="
  php artisan about 2>/dev/null || echo "about command is not available in this Laravel version"

  echo "== route:list =="
  php artisan route:list > "$REPORT_DIR/routes.txt"

  echo "== clear caches =="
  php artisan config:clear
  php artisan view:clear

  echo "== storage:link =="
  php artisan storage:link || true

  echo "== composer audit (report only) =="
  composer audit --format=json > "$REPORT_DIR/composer-audit.json" || true
} 2>&1 | tee "$LOG"
