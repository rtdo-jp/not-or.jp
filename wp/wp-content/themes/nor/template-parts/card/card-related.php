<?php
/**
 * Related works section card.
 *
 * Usage:
 * get_template_part('template-parts/card/card-related', null, [
 *   'title'             => 'Same category',
 *   'description'       => 'Other works in Brand Identity.',
 *   'description_html'  => optional pre-escaped HTML (e.g. abbr-enriched)
 *   'ids'          => [123, 456],
 *   'render_items' => callable, // function(array $ids): void
 * ]);
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$title = isset($args['title']) ? trim((string) $args['title']) : '';
if ($title === '') {
  $title = 'Related';
}

$description = isset($args['description']) ? trim((string) $args['description']) : '';
if ($description === '') {
  $description = '—';
}

// `description_html` is treated as pre-escaped HTML (caller responsibility).
$description_html = isset($args['description_html']) && is_string($args['description_html']) ? trim($args['description_html']) : '';

$ids = isset($args['ids']) && is_array($args['ids']) ? $args['ids'] : [];
$render_items = (isset($args['render_items']) && is_callable($args['render_items'])) ? $args['render_items'] : null;
?>
<section class="card related">
  <div class="inner">
    <header class="head">
      <h2><span><?php echo esc_html($title); ?></span></h2>
      <p><?php echo ($description_html !== '') ? $description_html : esc_html($description); ?></p>
    </header>
    <div class="body">
      <ul>

<?php
if ($render_items !== null && !empty($ids)) {
  $render_items($ids);
}
?>
      </ul>
    </div>
  </div>
</section>
