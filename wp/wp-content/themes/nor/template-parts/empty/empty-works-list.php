<?php
/**
 * Empty state for works list pages.
 *
 * Usage:
 * get_template_part('template-parts/empty/empty-works-list', null, [
 *   'title'           => 'Brand Identity',
 *   'primary_url'     => home_url('/categories/'),
 *   'primary_label'   => 'Back to List',
 *   'secondary_url'   => home_url('/'),
 *   'secondary_label' => 'Back to Home',
 * ]);
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$title = isset($args['title']) ? trim((string) $args['title']) : '';

$primary_url = isset($args['primary_url']) ? trim((string) $args['primary_url']) : '';
$primary_label = isset($args['primary_label']) ? trim((string) $args['primary_label']) : 'Back to List';
if ($primary_label === '') {
  $primary_label = 'Back to List';
}

$secondary_url = isset($args['secondary_url']) ? trim((string) $args['secondary_url']) : home_url('/');
$secondary_label = isset($args['secondary_label']) ? trim((string) $args['secondary_label']) : 'Back to Home';
if ($secondary_label === '') {
  $secondary_label = 'Back to Home';
}
?>
<div class="content empty" role="status">
  <div class="textpair">
    <p class="ja" lang="ja">「<q><?php echo esc_html($title); ?></q>」に一致する制作記録は見つかりませんでした。<br class="desktop tablet">キーワードで検索するか、「<a href="<?php echo esc_url(home_url('/categories/')); ?>"><i>Categories</i></a>」「<a href="<?php echo esc_url(home_url('/tags/')); ?>"><i>Tags</i></a>」「<a href="<?php echo esc_url(home_url('/archives/')); ?>"><i>Archives</i></a>」「<a href="<?php echo esc_url(home_url('/clients/index-by-initial/')); ?>"><i>Clients</i></a>」からお探しください。</p>
    <p class="en" lang="en">No works found for “<q><?php echo esc_html($title); ?></q>”. <br class="desktop tablet">Try searching, or browse “<a href="<?php echo esc_url(home_url('/categories/')); ?>"><i>Categories</i></a>”, “<a href="<?php echo esc_url(home_url('/tags/')); ?>"><i>Tags</i></a>”, “<a href="<?php echo esc_url(home_url('/archives/')); ?>"><i>Archives</i></a>”, or “<a href="<?php echo esc_url(home_url('/clients/index-by-initial/')); ?>"><i>Clients</i></a>”.</p>
  </div>
  <ul class="actions">
    <li>
<?php if ($primary_url !== '') : ?>
      <a href="<?php echo esc_url($primary_url); ?>" class="btn"><?php echo esc_html($primary_label); ?></a>
<?php else : ?>
      <span class="btn" aria-disabled="true">—</span>
<?php endif; ?>
    </li>
    <li>
<?php if ($secondary_url !== '') : ?>
      <a href="<?php echo esc_url($secondary_url); ?>" class="btn"><?php echo esc_html($secondary_label); ?></a>
<?php else : ?>
      <span class="btn" aria-disabled="true">—</span>
<?php endif; ?>
    </li>
  </ul>
</div>
