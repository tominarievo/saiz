#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G1}"
. "$(dirname "$0")/common.sh"

{
  if [ -d tests/Feature/Security ]; then
    echo "== security tests =="
    vendor/bin/phpunit tests/Feature/Security --colors=never --log-junit "$REPORT_DIR/security-junit.xml"
  else
    echo "security tests are not implemented yet (tests/Feature/Security)" >&2
    exit 1
  fi

  echo "== route auth audit =="
  php harness/scripts/route-audit.php "$REPORT_DIR/route-audit.json"

  echo "== composer audit (critical/high, allowlist aware) =="
  audit_file="$REPORT_DIR/composer-audit-strict.json"
  audit_ok=0
  for i in 1 2 3; do
    composer audit --format=json > "$audit_file" 2>/dev/null || true
    if php -r 'json_decode(file_get_contents($argv[1])); exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);' "$audit_file" 2>/dev/null; then
      audit_ok=1
      break
    fi
    sleep 2
  done
  if [ "$audit_ok" -ne 1 ]; then
    echo "composer audit unavailable; using cached snapshot" >&2
    cp harness/security-audit-snapshot.json "$audit_file"
  fi
  php harness/scripts/audit-check.php "$audit_file" harness/security-audit-allowlist.json
} 2>&1 | tee -a "$REPORT_DIR/security.log"
