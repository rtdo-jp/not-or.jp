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
$excerpt = trim((string) preg_replace('/\s+/u', ' ', $excerpt));

$category_html = '<span class="value">—</span>';
if ($category instanceof WP_Term) {
  $cat_url = get_term_link($category);
  if (!is_wp_error($cat_url)) {
    $category_html = '<span class="value"><a href="' . esc_url($cat_url) . '">' . esc_html($category->name) . '</a></span>';
  }
}

$collect_term_items = static function (array $terms, int $limit): array {
  $items = [];
  $slice = ($limit > 0) ? array_slice($terms, 0, $limit) : [];
  foreach ($slice as $t) {
    if (!$t instanceof WP_Term) continue;
    $u = get_term_link($t);
    if (is_wp_error($u)) continue;
    $label = function_exists('nor_get_term_public_name')
      ? nor_get_term_public_name($t, (string) $t->name)
      : (string) $t->name;
    $items[] = [
      'url'  => (string) $u,
      'name' => $label,
    ];
  }
  return $items;
};

$render_term_list = static function (array $terms, int $limit, int $indent = 16) use ($collect_term_items): void {
  $items = $collect_term_items($terms, $limit);
  $ul_indent = str_repeat(' ', max(0, $indent));
  $li_indent = str_repeat(' ', max(0, $indent + 2));

  echo $ul_indent . "<ul>\n";
  if (!empty($items)) {
    foreach ($items as $item) {
      echo $li_indent . '<li><span class="value"><a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . "</a></span></li>\n";
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
            <p><?php echo esc_html($excerpt); ?></p>
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
<?php $render_term_list($primary_type_terms, PHP_INT_MAX); ?>
              </dd>
            </dl>
            <div class="roles-tools">
              <dl class="roles">
                <dt>Roles</dt>
                <dd>
<?php $render_term_list($roles, 4, 18); ?>
                </dd>
              </dl>
              <dl class="tools">
                <dt>Tools</dt>
                <dd>
<?php $render_term_list($tools, 4, 18); ?>
                </dd>
              </dl>
            </div>
          </div>
          <div class="client-industries">
            <dl class="client">
              <dt>Clients</dt>
              <dd>
<?php $render_term_list($clients, 4); ?>
              </dd>
            </dl>
            <dl class="industries">
              <dt>Industries</dt>
              <dd>
<?php $render_term_list($industries, 1); ?>
              </dd>
            </dl>
          </div>
        </div>
      </article>
