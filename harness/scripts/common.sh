#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
GATE="${GATE:-G0}"
REPORT_DIR="$ROOT/harness/reports/$GATE"

mkdir -p "$REPORT_DIR"
cd "$ROOT"
