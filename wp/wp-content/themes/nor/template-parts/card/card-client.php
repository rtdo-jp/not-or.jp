<?php
/**
 * Template part: Card (Client)
 *
 * Renders one <article class="card client"> item.
 *
 * Args:
 * - title_id (string)
 * - title_lines (string[])        : each item rendered as <span class="character-line">...</span>
 * - lu (array|null)               : ['dt' => string, 'd' => string, 'is_new' => bool]
 * - count (int)
 * - notice (string)
 * - notice_lang (string)          : e.g. 'en' | 'ja'
 * - aria_label (string)           : nav aria-label
 * - links (array)                 : [['url' => string, 'label' => string], ...]
 * - render_body_when_empty (bool) : default true
 */

$args = isset($args) && is_array($args) ? $args : [];

$title_id = isset($args['title_id']) && is_string($args['title_id']) ? (string) $args['title_id'] : '';
$title_lines = isset($args['title_lines']) && is_array($args['title_lines']) ? $args['title_lines'] : [];
$lu = isset($args['lu']) && is_array($args['lu']) ? $args['lu'] : null;
$count = isset($args['count']) ? (int) $args['count'] : 0;
$notice = isset($args['notice']) && is_string($args['notice']) ? (string) $args['notice'] : '';
$notice_lang = isset($args['notice_lang']) && is_string($args['notice_lang']) ? (string) $args['notice_lang'] : 'en';
$aria_label = isset($args['aria_label']) && is_string($args['aria_label']) ? (string) $args['aria_label'] : '';
$links = isset($args['links']) && is_array($args['links']) ? $args['links'] : [];
$render_body_when_empty = array_key_exists('render_body_when_empty', $args) ? (bool) $args['render_body_when_empty'] : true;

$title_lines = array_values(array_filter(array_map(function ($line) {
  return is_string($line) ? trim($line) : '';
}, $title_lines), function ($line) {
  return $line !== '';
}));

if (empty($title_lines)) {
  return;
}

$has_links = !empty($links);
$show_body = $has_links || $render_body_when_empty;

$title_lines_html = '';
foreach ($title_lines as $line) {
  $title_lines_html .= '<span class="character-line">' . esc_html($line) . '</span>';
}

// "Last updated:" itself is always shown; only the value differs. No <time>
// is emitted when there's no date to avoid a dummy datetime="" attribute.
if ($lu) {
  $updated_html = 'Last updated: <time datetime="' . esc_attr((string) ($lu['dt'] ?? '')) . '" class="value">' . esc_html((string) ($lu['d'] ?? '')) . '</time>';
  if (!empty($lu['is_new'])) {
    $updated_html .= '<span class="new">New</span>';
  }
} else {
  $updated_html = 'Last updated: <span class="value">—</span>';
}

$link_items = [];
if ($has_links) {
  foreach ($links as $row) {
    $url = isset($row['url']) && is_string($row['url']) ? (string) $row['url'] : '';
    $label = isset($row['label']) && is_string($row['label']) ? (string) $row['label'] : '';
    if ($url === '' || $label === '') {
      continue;
    }
    $link_items[] = [
      'url' => $url,
      'label' => $label,
    ];
  }
}
?>
<article class="card client">
  <header class="head">
    <div class="title">
      <h3 id="<?php echo esc_attr($title_id); ?>"><?php echo $title_lines_html; ?></h3>
<?php if ($updated_html !== '') : ?>
      <p><?php echo $updated_html; ?></p>
<?php endif; ?>
    </div>
    <div class="stats">
      <p class="pair">
        <data class="count" value="<?php echo esc_attr($count); ?>"><?php echo esc_html($count); ?></data>
        <span class="unit">clients recorded.</span>
      </p>
    </div>
    <p class="notice" lang="<?php echo esc_attr($notice_lang); ?>"><?php echo esc_html($notice); ?></p>
  </header>
<?php if ($show_body) : ?>
  <nav class="body" aria-label="<?php echo esc_attr($aria_label); ?>">
    <ul class="nav-list">
<?php if (!empty($link_items)) : ?>
<?php foreach ($link_items as $item) : ?>
      <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
<?php endforeach; ?>
<?php else : ?>
      <li><span class="value">—</span></li>
<?php endif; ?>
    </ul>
  </nav>
<?php endif; ?>
</article>
