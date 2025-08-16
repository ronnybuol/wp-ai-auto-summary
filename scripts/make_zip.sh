#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT/plugin"
zip -r ../ai-auto-summary-ondemand-patched.zip ai-auto-summary-ondemand-patched
cd "$ROOT/backend-php"
zip -r ../ai-summarize-php.zip ai-summarize
echo "Artifacts:"
ls -lh "$ROOT"/*.zip
