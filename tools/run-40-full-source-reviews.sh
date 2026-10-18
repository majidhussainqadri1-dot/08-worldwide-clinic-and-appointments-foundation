#!/usr/bin/env bash
# File08 T28: exactly 40 complete executions of the existing entire source-
# contract/security/regression suite. This is repeated automated SOURCE QA,
# not 40 live/staging journeys, not a measure of percentage completion.
set -euo pipefail
cd "$(dirname "$0")/.."
test -f tests/run-all.php
log="$(mktemp)"
trap 'rm -f "$log"' EXIT
for round in $(seq 1 40); do
  # Each round re-runs every suite entry in tests/run-all.php, not one
  # requirement in isolation. Preserve the failure output, never skip ahead.
  if ! php -d display_errors=1 -d error_reporting=E_ALL tests/run-all.php >"$log" 2>&1; then
    printf 'ROUND %02d/40: FAILED; subsequent rounds not executed\n' "$round"
    tail -n 90 "$log"
    exit 1
  fi
  if ! grep -Fq 'All File 08 source-level test suites passed without PHP diagnostics.' "$log"; then
    printf 'ROUND %02d/40: INCOMPLETE (suite success marker missing)\n' "$round"
    tail -n 90 "$log"
    exit 1
  fi
  printf 'ROUND %02d/40: PASS — complete source-level suite executed\n' "$round"
done
echo 'T28 FORTY-ROUND RESULT: PASS 40/40 source-level suite executions.'
echo 'LIMIT: Real WordPress integration, host-specific migration, deployed files and DB parity remain unverified.'
