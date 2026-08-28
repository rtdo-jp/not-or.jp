<?php
/**
 * Template part: Card (Taxonomy)
 *
 * Args:
 * - term (WP_Term) : optional
 * - num (string) : 2-digit index (e.g. "01")
 * - jp_desc (string)
 * - en_desc (string)
 * - projects (int)
 * - lu (array|null) : ['dt' => string, 'd' => string, 'is_new' => bool]
 * - card_id (string) : optional
 * - card_label (string) : default "Category"
 * - term_url (string)
 * - aria_more (string)
 * - title_html (string) : optional pre-rendered title HTML
 * - title_link (bool) : default true
 * - children (WP_Term[]) : optional
 * - children_aria (string) : optional
 * - more_disabled (bool) : default false
 * - render_category_name (callable) : fn(WP_Term $term): string (returns HTML)
 * - render_term_label (callable) : fn(WP_Term $term): string (returns HTML)
 * - render_desc (callable) : fn(string $raw): string (returns HTML)
 */

$args = isset($args) && is_array($args) ? $args : [];

$t = $args['term'] ?? null;
$has_term = ($t && !is_wp_error($t) && $t instanceof WP_Term);

$num = isset($args['num']) && is_string($args['num']) ? $args['num'] : '';
$jp_desc = isset($args['jp_desc']) && is_string($args['jp_desc']) ? $args['jp_desc'] : '';
$en_desc = isset($args['en_desc']) && is_string($args['en_desc']) ? $args['en_desc'] : '';
$projects = isset($args['projects']) ? (int) $args['projects'] : 0;
$lu = isset($args['lu']) && is_array($args['lu']) ? $args['lu'] : null;
if ($num === '') return;

$card_id = isset($args['card_id']) && is_string($args['card_id']) ? trim($args['card_id']) : '';
$card_label = isset($args['card_label']) && is_string($args['card_label']) ? trim($args['card_label']) : 'Category';
if ($card_label === '') $card_label = 'Category';

$term_url = isset($args['term_url']) && is_string($args['term_url']) ? trim($args['term_url']) : '';

$aria_more = isset($args['aria_more']) && is_string($args['aria_more']) ? $args['aria_more'] : '';
if ($aria_more === '' && $has_term) {
  $aria_more = sprintf('View all works in “%s”', $t->name);
}

$render_category_name = $args['render_category_name'] ?? null;
$render_term_label = $args['render_term_label'] ?? null;
$render_desc = $args['render_desc'] ?? null;
$title_html = isset($args['title_html']) && is_string($args['title_html']) ? trim($args['title_html']) : '';
$title_link = array_key_exists('title_link', $args) ? (bool) $args['title_link'] : true;

$children = (isset($args['children']) && is_array($args['children'])) ? $args['children'] : [];
$children_aria = isset($args['children_aria']) && is_string($args['children_aria']) ? $args['children_aria'] : '';
$more_disabled = !empty($args['more_disabled']);

$jp_desc_html = '';
if (is_callable($render_desc)) {
  $jp_desc_html = (string) call_user_func($render_desc, $jp_desc);
}

$en_desc_html = '';
if (is_callable($render_desc)) {
  $en_desc_html = (string) call_user_func($render_desc, $en_desc);
}

$title_inner_html = '';
if ($title_html !== '') {
  $title_inner_html = $title_html;
} elseif (is_callable($render_category_name) && $has_term) {
  $title_inner_html = (string) call_user_func($render_category_name, $t);
} elseif ($has_term) {
  $title_inner_html = '<span class="character-line">' . esc_html((string) $t->name) . '</span>';
}

$title_node_html = $title_inner_html;
if ($title_link && $term_url !== '') {
  $title_node_html = '<a href="' . esc_url($term_url) . '">' . $title_inner_html . '</a>';
} else {
  $title_node_html = '<span>' . $title_inner_html . '</span>';
}

$updated_html = '';
if ($lu) {
  $updated_html = 'Last updated:<time datetime="' . esc_attr((string) ($lu['dt'] ?? '')) . '" class="value">' . esc_html((string) ($lu['d'] ?? '')) . '</time>';
  if (!empty($lu['is_new'])) {
    $updated_html .= '<span class="new">New</span>';
  }
}

?>
    <article class="card taxonomy"<?php echo ($card_id !== '') ? ' id="' . esc_attr($card_id) . '"' : ''; ?>>
      <header class="head">
        <p class="label"><?php echo esc_html($card_label); ?></p>
        <p class="index" aria-hidden="true"><data value="<?php echo esc_attr($num); ?>"><?php echo esc_html($num); ?></data></p>
      </header>
      <div class="body">
        <div class="content">
<?php if ($jp_desc_html !== '') : ?>
          <p class="ja" lang="ja"><?php echo $jp_desc_html; ?></p>
<?php endif; ?>
          <div class="specific">
            <div class="stats">
              <p class="pair">
                <data class="count" value="<?php echo esc_attr($projects); ?>"><?php echo esc_html($projects); ?></data>
                <span class="unit">projects <br>recorded.</span>
              </p>
            </div>
            <header class="title">
              <h3><?php echo $title_node_html; ?></h3>
<?php if ($updated_html !== '') : ?>
              <p><?php echo $updated_html; ?></p>
<?php endif; ?>
            </header>
          </div>
<?php if ($en_desc_html !== '' || !empty($children)) : ?>
          <div class="meta">
<?php if ($en_desc_html !== '') : ?>
            <p class="en" lang="en"><?php echo $en_desc_html; ?></p>
<?php endif; ?>
<?php if (!empty($children)) : ?>
            <ul class="children"<?php echo ($children_aria !== '') ? ' aria-label="' . esc_attr($children_aria) . '"' : ''; ?>>
<?php
  foreach ($children as $ct) {
    if (!$ct || is_wp_error($ct) || !($ct instanceof WP_Term)) continue;
    $u = get_term_link($ct);
    if (is_wp_error($u)) continue;
    $label = is_callable($render_term_label) ? (string) call_user_func($render_term_label, $ct) : esc_html($ct->name);
    echo '              <li><a href="' . esc_url($u) . '">' . $label . "</a></li>\n";
  }
?>
            </ul>
<?php endif; ?>
          </div>
<?php endif; ?>
        </div>
        <div class="actions">
          <p class="more"><?php if ($more_disabled || $term_url === '') : ?><span class="btn is-disabled" aria-disabled="true">More</span><?php else : ?><a href="<?php echo esc_url($term_url); ?>" class="btn" aria-label="<?php echo esc_attr($aria_more); ?>">More</a><?php endif; ?></p>
        </div>
      </div>
    </article>
