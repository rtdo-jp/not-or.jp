#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'USAGE'
Usage:
  scripts/check-head-meta.sh [--base URL] [--ua USER_AGENT] [--auth USER:PASS] [PATH ...]

Examples:
  scripts/check-head-meta.sh
  scripts/check-head-meta.sh --base https://stg.not-or.jp
  scripts/check-head-meta.sh --base https://not-or.jp --auth user:pass / /contact/
  scripts/check-head-meta.sh --base https://not-or.jp / /about/ /policies/
USAGE
}

BASE_URL="https://not-or.jp"
CURL_UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
CURL_AUTH=""

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --base)
      if [[ -z "${2:-}" ]]; then
        echo "ERROR: --base requires a URL" >&2
        exit 2
      fi
      BASE_URL="$2"
      shift 2
      ;;
    --ua)
      if [[ -z "${2:-}" ]]; then
        echo "ERROR: --ua requires a value" >&2
        exit 2
      fi
      CURL_UA="$2"
      shift 2
      ;;
    --auth)
      if [[ -z "${2:-}" ]]; then
        echo "ERROR: --auth requires USER:PASS" >&2
        exit 2
      fi
      CURL_AUTH="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      break
      ;;
  esac
done

if [[ "$BASE_URL" != http://* && "$BASE_URL" != https://* ]]; then
  echo "ERROR: --base must start with http:// or https://" >&2
  exit 2
fi

if [[ "$#" -gt 0 ]]; then
  PAGES=("$@")
else
  PAGES=(
    "/"
    "/about/"
    "/policies/"
    "/notes/"
    "/contact/"
    "/faqs/"
    "/categories/"
    "/tags/"
    "/archives/"
    "/search/"
    "/clients/iot/"
    "/clients/industries/"
  )
fi

extract_canonical() {
  perl -0777 -ne 'if (/<link\b[^>]*\brel=["\x27]canonical["\x27][^>]*\bhref=["\x27]([^"\x27>]+)["\x27]/is) { print $1; }'
}

extract_og_url() {
  perl -0777 -ne 'if (/<meta\b[^>]*\bproperty=["\x27]og:url["\x27][^>]*\bcontent=["\x27]([^"\x27>]+)["\x27]/is) { print $1; }'
}

extract_robots() {
  perl -0777 -ne 'if (/<meta\b[^>]*\bname=["\x27]robots["\x27][^>]*\bcontent=["\x27]([^"\x27>]+)["\x27]/is) { print $1; }'
}

has_query_or_fragment() {
  local url="$1"
  [[ "$url" == *\?* || "$url" == *\#* ]]
}

is_absolute_http_url() {
  local url="$1"
  [[ "$url" == http://* || "$url" == https://* ]]
}

fail=0

printf 'Base URL: %s\n' "$BASE_URL"
printf 'Checking %d page(s)...\n\n' "${#PAGES[@]}"

for path in "${PAGES[@]}"; do
  if [[ "$path" != /* ]]; then
    path="/$path"
  fi

  url="${BASE_URL%/}${path}"

  echo "== ${url}"

  curl_args=(
    -fsSL
    --compressed
    --max-time 20
    -A "$CURL_UA"
    -H 'Accept: text/html,application/xhtml+xml'
    -H 'Accept-Language: ja,en;q=0.9'
  )
  if [[ -n "$CURL_AUTH" ]]; then
    curl_args+=(-u "$CURL_AUTH")
  fi
  curl_args+=("$url")

  if ! html="$(curl "${curl_args[@]}")"; then
    echo "  [FAIL] fetch error"
    fail=1
    echo
    continue
  fi

  canonical="$(printf '%s' "$html" | extract_canonical)"
  og_url="$(printf '%s' "$html" | extract_og_url)"
  robots="$(printf '%s' "$html" | extract_robots)"

  if [[ -z "$canonical" ]]; then
    echo "  [FAIL] canonical not found"
    fail=1
  else
    echo "  canonical : $canonical"
  fi

  if [[ -z "$og_url" ]]; then
    echo "  [FAIL] og:url not found"
    fail=1
  else
    echo "  og:url    : $og_url"
  fi

  if [[ -n "$robots" ]]; then
    echo "  robots    : $robots"
  else
    echo "  [WARN] robots not found"
  fi

  if [[ -n "$canonical" && -n "$og_url" ]]; then
    if [[ "$canonical" == "$og_url" ]]; then
      echo "  [PASS] canonical == og:url"
    else
      echo "  [FAIL] canonical != og:url"
      fail=1
    fi
  fi

  if [[ -n "$canonical" ]]; then
    if is_absolute_http_url "$canonical"; then
      echo "  [PASS] canonical is absolute"
    else
      echo "  [FAIL] canonical is not absolute"
      fail=1
    fi

    if has_query_or_fragment "$canonical"; then
      echo "  [FAIL] canonical has query/fragment"
      fail=1
    else
      echo "  [PASS] canonical has no query/fragment"
    fi
  fi

  if [[ -n "$og_url" ]]; then
    if is_absolute_http_url "$og_url"; then
      echo "  [PASS] og:url is absolute"
    else
      echo "  [FAIL] og:url is not absolute"
      fail=1
    fi

    if has_query_or_fragment "$og_url"; then
      echo "  [FAIL] og:url has query/fragment"
      fail=1
    else
      echo "  [PASS] og:url has no query/fragment"
    fi
  fi

  echo

done

if [[ "$fail" -ne 0 ]]; then
  echo "Result: FAIL"
  exit 1
fi

echo "Result: PASS"
