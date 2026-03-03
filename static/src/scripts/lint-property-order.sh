#!/usr/bin/env sh
set -eu

CSS_FILE="${1:-assets/css/nor.ui.css}"

if [ ! -f "$CSS_FILE" ]; then
  echo "ERROR: file not found: $CSS_FILE" >&2
  exit 2
fi

awk -v file="$CSS_FILE" '
function ltrim(s) {
  sub(/^[ \t\r\n]+/, "", s);
  return s;
}

function rtrim(s) {
  sub(/[ \t\r\n]+$/, "", s);
  return s;
}

function trim(s) {
  return rtrim(ltrim(s));
}

function normalize_prop(prop, p) {
  p = prop;
  sub(/^-[a-z]+-/, "", p);
  return p;
}

function group_name(rank) {
  if (rank == 0) return "vars";
  if (rank == 1) return "layout";
  if (rank == 2) return "box";
  if (rank == 3) return "typography";
  if (rank == 4) return "visual";
  return "other";
}

function group_rank(prop, p) {
  if (prop ~ /^--/) return 0;

  p = normalize_prop(prop);

  if (p == "content") return 0;

  if (p ~ /^(display|position|inset|inset-.*|top|right|bottom|left|z-index|isolation|contain|content-visibility|overflow|overflow-.*|clip|clip-path|resize|float|clear|columns|column-count|column-width|break-before|break-after|break-inside|page-break-.*|grid|grid-.*|flex|flex-.*|order|gap|row-gap|column-gap|justify-content|justify-items|justify-self|align-content|align-items|align-self|place-content|place-items|place-self)$/) return 1;

  if (p ~ /^(box-sizing|width|height|min-width|max-width|min-height|max-height|inline-size|block-size|min-inline-size|max-inline-size|min-block-size|max-block-size|margin|margin-.*|padding|padding-.*|border|border-.*|outline|outline-.*|border-radius|object-fit|object-position|aspect-ratio)$/) return 2;

  if (p ~ /^(font|font-.*|line-height|letter-spacing|word-spacing|text-.*|white-space|word-break|overflow-wrap|hyphens|tab-size|list-style|list-style-.*|quotes|vertical-align|font-variant.*|text-rendering)$/) return 3;

  if (p ~ /^(color|background|background-.*|fill|stroke|opacity|box-shadow|filter|mix-blend-mode|backdrop-filter|transform|transform-.*|perspective|perspective-origin|transform-style|will-change|transition|transition-.*|animation|animation-.*|cursor|pointer-events|user-select|appearance|content)$/) return 4;

  return 9;
}

BEGIN {
  depth = 0;
  block_id = 0;
  pending_header = "";
  violations = 0;
  in_comment = 0;
}

{
  raw = $0;
  line = raw;

  if (in_comment) {
    if (match(line, /\*\//)) {
      line = substr(line, RSTART + RLENGTH);
      in_comment = 0;
    } else {
      next;
    }
  }

  while (match(line, /\/\*/)) {
    pre = substr(line, 1, RSTART - 1);
    post = substr(line, RSTART + RLENGTH);

    if (match(post, /\*\//)) {
      line = pre substr(post, RSTART + RLENGTH);
    } else {
      line = pre;
      in_comment = 1;
      break;
    }
  }

  t = trim(line);

  if (t != "" && index(t, "{") == 0 && index(t, "}") == 0 && index(t, ";") == 0) {
    if (pending_header == "") pending_header = t;
    else pending_header = pending_header " " t;
  }

  open_count = gsub(/\{/, "{", line);
  if (open_count > 0) {
    rem = line;
    for (k = 1; k <= open_count; k++) {
      head = rem;
      sub(/\{.*/, "", head);
      head = trim(head);

      selector = trim((pending_header == "" ? head : pending_header " " head));
      if (selector == "") selector = "<anonymous>";
      pending_header = "";

      block_id++;
      block_by_depth[depth + 1] = block_id;
      block_header[block_id] = selector;

      parent_keyframes = (depth > 0 ? keyframes_ctx[depth] : 0);
      this_keyframes = (selector ~ /@keyframes/ ? 1 : 0);
      keyframes_ctx[depth + 1] = (parent_keyframes || this_keyframes) ? 1 : 0;

      delete prev_rank[block_id];
      delete prev_prop[block_id];
      delete prev_line[block_id];

      depth++;
      sub(/^[^\{]*\{/, "", rem);
    }
  }

  if (depth > 0 && keyframes_ctx[depth] == 0) {
    if (line ~ /^[ \t]*([A-Za-z][A-Za-z0-9-]*|--[A-Za-z0-9-]+)[ \t]*:/) {
      prop = line;
      sub(/^[ \t]*/, "", prop);
      sub(/[ \t]*:.*/, "", prop);

      rank = group_rank(prop);
      bid = block_by_depth[depth];

      if (rank != 9) {
        if ((bid in prev_rank) && rank < prev_rank[bid]) {
          printf "%s:%d: property-order: \"%s\" (%s) appears after \"%s\" (%s) in \"%s\"; expected group order: layout -> box -> typography -> visual\n",
            file,
            NR,
            prop,
            group_name(rank),
            prev_prop[bid],
            group_name(prev_rank[bid]),
            block_header[bid];
          violations++;
        }

        prev_rank[bid] = rank;
        prev_prop[bid] = prop;
        prev_line[bid] = NR;
      }
    }
  }

  close_count = gsub(/\}/, "}", line);
  if (close_count > 0) {
    for (k = 1; k <= close_count; k++) {
      if (depth > 0) {
        delete block_by_depth[depth];
        delete keyframes_ctx[depth];
        depth--;
      }
    }
  }
}

END {
  if (violations > 0) {
    printf "FAIL: %d property-order violations found in %s\n", violations, file > "/dev/stderr";
    exit 1;
  }

  printf "PASS: property order is consistent in %s\n", file;
}
' "$CSS_FILE"
