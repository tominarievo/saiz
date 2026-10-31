#!/usr/bin/env bash
set -euo pipefail

GATE="${1:-G3}"
. "$(dirname "$0")/common.sh"

fail=0

if grep -q '"name": "laravelcollective/html"' composer.lock; then
  echo "laravelcollective/html is still present" >&2
  fail=1
fi

if grep -q '"name": "fideloper/proxy"' composer.lock; then
  echo "fideloper/proxy is still present" >&2
  fail=1
fi

if [ ! -f app/Support/Form.php ]; then
  echo "App\\Support\\Form shim is missing" >&2
  fail=1
fi

if ! grep -Fq 'App\Support\Form::class' config/app.php; then
  echo "Form facade alias is missing" >&2
  fail=1
fi

if [ "$fail" -ne 0 ]; then
  exit 1
fi

echo "dependency check: pass"
