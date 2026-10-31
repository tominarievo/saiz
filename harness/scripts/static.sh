#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"

{
  echo "== phpstan =="
  if [ -x vendor/bin/phpstan ]; then
    vendor/bin/phpstan analyse --no-progress --error-format=json > "$REPORT_DIR/phpstan.json"
  else
    echo "phpstan is not installed; skipped (add larastan in Phase 0/3)"
  fi

  echo "== pint =="
  if [ -x vendor/bin/pint ]; then
    vendor/bin/pint --test
  else
    echo "pint is not installed; skipped"
  fi
} 2>&1 | tee -a "$REPORT_DIR/static.log"
