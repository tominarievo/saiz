#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G0}"
. "$(dirname "$0")/common.sh"

BASE_URL="${BASE_URL:-http://127.0.0.1}"
SMOKE_USERNAME="${SMOKE_USERNAME:-ando2@d-cradle.or.jp}"
SMOKE_PASSWORD="${SMOKE_PASSWORD:-${DEV_ADMIN_PASSWORD:-}}"
REQUIRE_AUTH="${REQUIRE_AUTH:-0}"

if [ -z "$SMOKE_PASSWORD" ]; then
  echo "SMOKE_PASSWORD or DEV_ADMIN_PASSWORD is required" >&2
  exit 1
fi
COOKIE_JAR="$REPORT_DIR/smoke-cookies.txt"
LOG="$REPORT_DIR/smoke.log"

status_of() {
  curl -s -o /dev/null -w "%{http_code}" "$1"
}

: > "$LOG"

echo "== public pages ==" | tee -a "$LOG"
login_status="$(status_of "$BASE_URL/login")"
echo "GET /login -> $login_status" | tee -a "$LOG"
if [ "$login_status" != "200" ]; then
  echo "login page did not return 200" >&2
  exit 1
fi

echo "== login ==" | tee -a "$LOG"
login_html="$(curl -s -c "$COOKIE_JAR" "$BASE_URL/login")"
token="$(printf '%s' "$login_html" | sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p' | head -n1)"
if [ -z "$token" ]; then
  token="$(printf '%s' "$login_html" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -n1)"
fi
if [ -z "$token" ]; then
  echo "csrf token not found; auth smoke tests cannot run" | tee -a "$LOG"
  if [ "$REQUIRE_AUTH" = "1" ]; then exit 1; fi
  exit 0
fi

login_code="$(
  curl -s -o /dev/null -w "%{http_code}" \
    -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -X POST "$BASE_URL/login" \
    --data-urlencode "_token=$token" \
    --data-urlencode "username=$SMOKE_USERNAME" \
    --data-urlencode "password=$SMOKE_PASSWORD"
)"
echo "POST /login -> $login_code" | tee -a "$LOG"

home_status="$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/home")"
echo "GET /home -> $home_status" | tee -a "$LOG"
if [ "$home_status" != "200" ]; then
  echo "authenticated smoke test failed" >&2
  if [ "$REQUIRE_AUTH" = "1" ]; then exit 1; fi
fi

echo "== authenticated pages ==" | tee -a "$LOG"
for path in /reports /shelter_views /organization_views /information /shelters /organizations; do
  code="$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL$path")"
  echo "GET $path -> $code" | tee -a "$LOG"
  if [ "$code" != "200" ]; then
    echo "smoke test failed for $path" >&2
    if [ "$REQUIRE_AUTH" = "1" ]; then exit 1; fi
  fi
done
