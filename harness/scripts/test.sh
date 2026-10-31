#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"

{
  echo "== phpunit =="
  vendor/bin/phpunit --colors=never --log-junit "$REPORT_DIR/junit.xml"
} 2>&1 | tee -a "$REPORT_DIR/test.log"
