#!/usr/bin/env bash
set -euo pipefail

GATE="${1:?usage: gate.sh G0|G1|G2|G3|G4|G5|G6}"
export GATE
. "$(dirname "$0")/common.sh"

STATUS=0

export DEV_ADMIN_PASSWORD="${DEV_ADMIN_PASSWORD:-$(php -r 'echo bin2hex(random_bytes(16));')}"

run_step() {
  local name="$1"
  shift
  echo "===== ${name} ====="
  if "$@"; then
    echo "RESULT ${name}: pass"
  else
    echo "RESULT ${name}: fail"
    STATUS=1
  fi
}

run_step preflight bash harness/scripts/preflight.sh "$GATE"
run_step db-reset bash harness/scripts/db-reset.sh "$GATE"
run_step test bash harness/scripts/test.sh "$GATE"

if [ "$GATE" != "G0" ]; then
  run_step security bash harness/scripts/security.sh "$GATE"
fi

if [ "$GATE" = "G3" ]; then
  run_step dependency-check bash harness/scripts/dependency-check.sh "$GATE"
fi

run_step static bash harness/scripts/static.sh "$GATE"
run_step db-reset-final bash harness/scripts/db-reset.sh "$GATE"

if [ "$GATE" != "G0" ]; then
  export REQUIRE_AUTH=1
fi
run_step smoke bash harness/scripts/smoke.sh "$GATE"
unset REQUIRE_AUTH || true

if [ "$GATE" = "G6" ]; then
  run_step cache-check bash harness/scripts/cache-check.sh "$GATE"
fi

run_step evidence bash harness/scripts/evidence.sh "$GATE"

GATE="$GATE" STATUS="$STATUS" php -r '
require "vendor/autoload.php";
$gate = getenv("GATE");
$status = getenv("STATUS");
$data = [
    "gate" => $gate,
    "status" => ($status === "0" ? "pass" : "fail"),
    "finished_at" => date("c"),
    "php" => PHP_VERSION,
    "laravel" => \Illuminate\Foundation\Application::VERSION,
];
$dir = "harness/reports/" . $gate;
if (!is_dir($dir)) { mkdir($dir, 0777, true); }
file_put_contents($dir . "/summary.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
'

echo "GATE ${GATE}: $([ "$STATUS" -eq 0 ] && echo pass || echo fail)"
exit "$STATUS"
