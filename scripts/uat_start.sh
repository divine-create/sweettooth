#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEMPLATE="$BASE_DIR/accounting_md/08_uat_results_template.md"
OUT_DIR="$BASE_DIR/accounting_md/uat_runs"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="$OUT_DIR/uat_results_${STAMP}.md"

mkdir -p "$OUT_DIR"

if [[ ! -f "$TEMPLATE" ]]; then
  echo "Template not found: $TEMPLATE" >&2
  exit 1
fi

cp "$TEMPLATE" "$OUT_FILE"
echo "UAT results file created: $OUT_FILE"
