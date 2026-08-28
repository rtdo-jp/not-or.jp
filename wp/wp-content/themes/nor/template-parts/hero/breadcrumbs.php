<?php
/**
 * Template part: Hero breadcrumbs
 *
 * Args:
 * - breadcrumbs (array) : [{ label, url, current(bool), label_html(pre-escaped) }]
 * - aria_label (string) : aria-label for the nav (default: "Breadcrumb")
 */

$args = isset($args) && is_array($args) ? $args : [];

$breadcrumbs = is_array($args['breadcrumbs'] ?? null) ? $args['breadcrumbs'] : [];
$aria_label  = is_string($args['aria_label'] ?? null) ? trim((string) $args['aria_label']) : 'Breadcrumb';
if ($aria_label === '') $aria_label = 'Breadcrumb';

if (empty($breadcrumbs)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="<?php echo esc_attr($aria_label); ?>">
  <ol>
<?php foreach ($breadcrumbs as $b) : ?>
<?php
  $label = isset($b['label']) && is_string($b['label']) ? $b['label'] : '';
  $label_html = isset($b['label_html']) && is_string($b['label_html']) ? $b['label_html'] : '';
  $url = isset($b['url']) && is_string($b['url']) ? $b['url'] : '';
  $current = !empty($b['current']);

  // `label_html` is treated as pre-escaped HTML (caller responsibility).
  $crumb_label = ($label_html !== '') ? $label_html : esc_html($label);

  if ($current) {
    $crumb_inner = '<span aria-current="page">' . $crumb_label . '</span>';
  } elseif ($url !== '') {
    $crumb_inner = '<a href="' . esc_url($url) . '">' . $crumb_label . '</a>';
  } else {
    $crumb_inner = '<span>' . $crumb_label . '</span>';
  }
?>
    <li><?php echo $crumb_inner; ?></li>
<?php endforeach; ?>
  </ol>
</nav>
