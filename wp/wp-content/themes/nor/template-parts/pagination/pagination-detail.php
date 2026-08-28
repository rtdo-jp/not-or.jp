<?php
/**
 * Pagination (Detail)
 *
 * Usage:
 *   get_template_part('template-parts/pagination/pagination-detail', null, [
 *     'aria_label' => 'Works navigation',
 *     'back_url'   => get_post_type_archive_link('works'),
 *     'prev_url'   => $prev_url,
 *     'next_url'   => $next_url,
 *   ]);
 */

// $args is provided by get_template_part(..., ..., $args)
$args = (isset($args) && is_array($args)) ? $args : [];

$defaults = [
  'aria_label' => 'Works navigation',
  'back_url'   => '',
  'prev_url'   => '',
  'next_url'   => '',
  'back_label' => 'Back to Works',
  'prev_label' => 'Prev',
  'next_label' => 'Next',
];

$a = wp_parse_args($args, $defaults);

$aria_label = is_string($a['aria_label']) ? $a['aria_label'] : 'Works navigation';
$back_url   = is_string($a['back_url']) ? $a['back_url'] : '';
$prev_url   = is_string($a['prev_url']) ? $a['prev_url'] : '';
$next_url   = is_string($a['next_url']) ? $a['next_url'] : '';

$back_label = is_string($a['back_label']) ? $a['back_label'] : 'Back to Works';
$prev_label = is_string($a['prev_label']) ? $a['prev_label'] : 'Prev';
$next_label = is_string($a['next_label']) ? $a['next_label'] : 'Next';
?>

<nav class="pagination" aria-label="<?php echo esc_attr($aria_label); ?>">
  <ul class="pager-back-list">
    <li><?php if ($back_url !== '') : ?><a href="<?php echo esc_url($back_url); ?>" class="btn"><?php echo esc_html($back_label); ?></a><?php else : ?><span class="btn is-disabled"><?php echo esc_html($back_label); ?></span><?php endif; ?></li>
  </ul>
  <ul class="pager-prev-next">
    <li><?php if ($prev_url !== '') : ?><a href="<?php echo esc_url($prev_url); ?>" class="btn" rel="prev"><?php echo esc_html($prev_label); ?></a><?php else : ?><span class="btn is-disabled"><?php echo esc_html($prev_label); ?></span><?php endif; ?></li>
    <li><?php if ($next_url !== '') : ?><a href="<?php echo esc_url($next_url); ?>" class="btn" rel="next"><?php echo esc_html($next_label); ?></a><?php else : ?><span class="btn is-disabled"><?php echo esc_html($next_label); ?></span><?php endif; ?></li>
  </ul>
</nav>
