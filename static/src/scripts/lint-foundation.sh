#!/usr/bin/env sh
set -eu

CSS_FILE="${1:-assets/css/nor.ui.css}"

if [ ! -f "$CSS_FILE" ]; then
  echo "ERROR: file not found: $CSS_FILE" >&2
  exit 2
fi

start_line="$(awk '/^[[:space:]]*Foundation[[:space:]]*$/{print NR; exit}' "$CSS_FILE")"
if [ -z "$start_line" ]; then
  echo "ERROR: Foundation section marker not found in $CSS_FILE" >&2
  exit 2
fi

end_line="$(awk -v s="$start_line" 'NR > s && /^[[:space:]]*Components[[:space:]]*$/{print NR; exit}' "$CSS_FILE")"
if [ -z "$end_line" ]; then
  echo "ERROR: Components section marker not found after Foundation in $CSS_FILE" >&2
  exit 2
fi

foundation_block="$(awk -v s="$start_line" -v e="$end_line" 'NR > s && NR < e { print NR ":" $0 }' "$CSS_FILE")"

pattern='body\.|\.content-|\.list-'

if command -v rg >/dev/null 2>&1; then
  violations="$(printf '%s\n' "$foundation_block" | rg -n --no-heading -e "$pattern" || true)"
else
  violations="$(printf '%s\n' "$foundation_block" | grep -En "$pattern" || true)"
fi

if [ -n "$violations" ]; then
  echo "FAIL: forbidden patterns found in Foundation block of $CSS_FILE" >&2
  echo "Forbidden: body\\. | .content- | .list-" >&2
  echo "$violations" >&2
  exit 1
fi

echo "PASS: no forbidden patterns in Foundation block of $CSS_FILE"
