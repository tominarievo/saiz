#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"

{
  echo "gate=$GATE"
  date -Iseconds
  php -v | head -n1
  php artisan --version
  git rev-parse HEAD 2>/dev/null || echo "git unavailable"
  git branch --show-current 2>/dev/null || echo "git unavailable"
} > "$REPORT_DIR/versions.txt" 2>&1
