#!/usr/bin/env bash

set -euo pipefail

export HOME=/home/saiz
export PATH="/home/saiz/.opencode/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export OPENCODE_MODEL="${OPENCODE_MODEL:-opencode-go/deepseek-v4.1-flash}"

cd /home/saiz/work/saiz

exec /usr/bin/python3 harness/scripts/refresh_disaster_links.py "$@"
