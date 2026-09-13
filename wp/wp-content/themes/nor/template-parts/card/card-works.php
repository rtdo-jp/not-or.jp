<?php
/**
 * Template part: Card (Works)
 *
 * Renders one <article class="card works"> item.
 *
 * Args (required unless noted):
 * - post_id (int)
 * - num (string)           : 3-digit work number (e.g. "007")
 * - permalink (string)
 * - title (string)
 * - excerpt (string)
 * - published (string)     : Y-m-d
 * - published_dt (string)  : ISO 8601 datetime
 * - is_new (bool)          : optional
 * - category (WP_Term|null): optional (primary category)
 * - content_mode (string)  : "Text-only" | "Full-content"
 * - doc_types (WP_Term[])  : optional
 * - site_types (WP_Term[]) : optional
 * - roles (WP_Term[])      : optional
 * - tools (WP_Term[])      : optional
 * - clients (WP_Term[])    : optional
 * - industries (WP_Term[]) : optional
 * - types_label (string)   : optional (overrides computed types label)
 * - types_terms (WP_Term[]): optional (overrides computed types terms)
 * - types_colon (bool)     : optional (append ":" after types label)
 */

$args = isset($args) && is_array($args) ? $args : [];

$post_id     = (int) ($args['post_id'] ?? 0);
$num         = is_string($args['num'] ?? null) ? (string) $args['num'] : '';
$permalink   = is_string($args['permalink'] ?? null) ? (string) $args['permalink'] : '';
$title       = is_string($args['title'] ?? null) ? (string) $args['title'] : '';
$excerpt     = is_string($args['excerpt'] ?? null) ? (string) $args['excerpt'] : '';
$published   = is_string($args['published'] ?? null) ? (string) $args['published'] : '';
$published_dt= is_string($args['published_dt'] ?? null) ? (string) $args['published_dt'] : '';
$is_new      = !empty($args['is_new']);

$category    = ($args['category'] ?? null);
$category    = ($category instanceof WP_Term) ? $category : null;

$content_mode = is_string($args['content_mode'] ?? null) ? (string) $args['content_mode'] : 'Text-only';

$doc_types  = (isset($args['doc_types']) && is_array($args['doc_types'])) ? $args['doc_types'] : [];
$site_types = (isset($args['site_types']) && is_array($args['site_types'])) ? $args['site_types'] : [];
$roles      = (isset($args['roles']) && is_array($args['roles'])) ? $args['roles'] : [];
$tools      = (isset($args['tools']) && is_array($args['tools'])) ? $args['tools'] : [];
$clients    = (isset($args['clients']) && is_array($args['clients'])) ? $args['clients'] : [];
$industries = (isset($args['industries']) && is_array($args['industries'])) ? $args['industries'] : [];
$types_label_override = (isset($args['types_label']) && is_string($args['types_label'])) ? trim((string) $args['types_label']) : '';
$types_terms_override = (isset($args['types_terms']) && is_array($args['types_terms'])) ? $args['types_terms'] : null;
$types_colon = !empty($args['types_colon']);

// Document types と Site types は排他（どちらか1つだけ表示）
$primary_type_label = '';
$primary_type_terms = [];

if ($types_terms_override !== null) {
  $primary_type_label = ($types_label_override !== '') ? $types_label_override : 'Types';
  $primary_type_terms = $types_terms_override;
} else {
  if (!empty($doc_types)) {
    $primary_type_label = 'Document types';
    $primary_type_terms = $doc_types;
  } elseif (!empty($site_types)) {
    $primary_type_label = 'Site types';
    $primary_type_terms = $site_types;
  } else {
    $primary_type_label = 'Document types';
    $primary_type_terms = [];
  }
}
// B "Inline rich text": excerpt is not itself wrapped in an outer <a> (only
// the card title is), so <a> must stay a real link — but
// nor_render_inline_rich_text()'s card_context couples "unwrap <a>" together
// with "collapse newlines to a single space", and we only want the latter
// here. So the lower-level pair it calls internally is used directly instead:
// sanitize to the B allowlist, then collapse newlines/runs of whitespace to
// a single space (matching this card's pre-existing single-line layout)
// while still auto-<abbr>-wrapping dictionary terms and leaving explicit
// <a>/<abbr>/etc. markup untouched.
if (function_exists('nor_sanitize_inline_rich_text') && function_exists('nor_inline_rich_text_apply_abbr_and_breaks')) {
  $excerpt_safe = nor_sanitize_inline_rich_text($excerpt);
  $excerpt_html = ($excerpt_safe !== '')
    ? nor_inline_rich_text_apply_abbr_and_breaks(
        $excerpt_safe,
        function_exists('nor_get_abbreviation_map') ? nor_get_abbreviation_map() : [],
        false // convert_newlines_to_br = false: join as a single line, not <br>
      )
    : '';
} else {
  $excerpt_html = esc_html(trim((string) preg_replace('/\s+/u', ' ', $excerpt)));
}

$category_html = '<span class="value">—</span>';
if ($category instanceof WP_Term) {
  $cat_url = get_term_link($category);
  if (!is_wp_error($cat_url)) {
    $category_html = '<span class="value"><a href="' . esc_url($cat_url) . '">' . nor_render_work_category_label($category, 'inline') . '</a></span>';
  }
}

// Default label: client-masking (where applicable) then common-dictionary
// abbr enrichment. Tools-group terms pass nor_render_work_tag_tool_label
// instead (see below) to keep their dedicated separator markup.
$default_term_label = static function (WP_Term $t): string {
  $label = function_exists('nor_get_term_public_name')
    ? nor_get_term_public_name($t, (string) $t->name)
    : (string) $t->name;
  return function_exists('nor_render_label_with_abbr')
    ? nor_render_label_with_abbr($label)
    : esc_html($label);
};

$collect_term_items = static function (array $terms, int $limit, callable $label_fn): array {
  $items = [];
  $slice = ($limit > 0) ? array_slice($terms, 0, $limit) : [];
  foreach ($slice as $t) {
    if (!$t instanceof WP_Term) continue;
    if ($t->taxonomy === 'work_industry') {
      // work_industry's own taxonomy archive is retired (301s to
      // /clients/index-by-industry/#client-industry-{slug}); link directly at the
      // real destination instead of bouncing through it. Other taxonomies
      // (roles/tools/clients/etc.) keep using get_term_link() below.
      $u = home_url('/clients/index-by-industry/#client-industry-' . $t->slug);
    } else {
      $u = get_term_link($t);
      if (is_wp_error($u)) continue;
    }
    $items[] = [
      'url'        => (string) $u,
      'label_html' => $label_fn($t),
    ];
  }
  return $items;
};

$render_term_list = static function (array $terms, int $limit, callable $label_fn, int $indent = 16) use ($collect_term_items): void {
  $items = $collect_term_items($terms, $limit, $label_fn);
  $ul_indent = str_repeat(' ', max(0, $indent));
  $li_indent = str_repeat(' ', max(0, $indent + 2));

  echo $ul_indent . "<ul>\n";
  if (!empty($items)) {
    foreach ($items as $item) {
      echo $li_indent . '<li><span class="value"><a href="' . esc_url($item['url']) . '">' . $item['label_html'] . "</a></span></li>\n";
    }
  } else {
    echo $li_indent . "<li><span class=\"value\">—</span></li>\n";
  }
  echo $ul_indent . "</ul>\n";
};
?>
      <article class="card works">
        <header class="head">
          <div class="title">
            <h3>
              <a href="<?php echo esc_url($permalink); ?>">
                <span class="character-line">
                  <data class="index" aria-hidden="true" value="<?php echo esc_attr($num); ?>">#<?php echo esc_html($num); ?></data>
                  <cite class="value"><?php echo esc_html($title); ?></cite>
                </span>
              </a>
            </h3>
          </div>
          <div class="meta">
            <p><?php echo $excerpt_html; ?></p>
            <dl>
              <dt>Published</dt>
              <dd><time datetime="<?php echo esc_attr($published_dt); ?>" class="value"><?php echo esc_html($published); ?></time><?php if ($is_new) : ?><span class="new">New</span><?php endif; ?></dd>
              <dt>Categories</dt>
              <dd><?php echo $category_html; ?></dd>
              <dt>Content mode</dt>
              <dd><?php echo esc_html($content_mode); ?></dd>
            </dl>
          </div>
        </header>
        <div class="body">
          <div class="types-roles-tools">
            <dl class="types">
              <dt><?php echo esc_html($primary_type_label); ?><?php echo $types_colon ? ':' : ''; ?></dt>
              <dd>
<?php $render_term_list($primary_type_terms, PHP_INT_MAX, $default_term_label); ?>
              </dd>
            </dl>
            <div class="roles-tools">
              <dl class="roles">
                <dt>Roles</dt>
                <dd>
<?php $render_term_list($roles, 4, $default_term_label, 18); ?>
                </dd>
              </dl>
              <dl class="tools">
                <dt>Tools</dt>
                <dd>
<?php $render_term_list($tools, 4, 'nor_render_work_tag_tool_label', 18); ?>
                </dd>
              </dl>
            </div>
          </div>
          <div class="client-industries">
            <dl class="client">
              <dt>Clients</dt>
              <dd>
<?php $render_term_list($clients, 4, $default_term_label); ?>
              </dd>
            </dl>
            <dl class="industries">
              <dt>Industries</dt>
              <dd>
<?php $render_term_list($industries, 1, $default_term_label); ?>
              </dd>
            </dl>
          </div>
        </div>
      </article>
