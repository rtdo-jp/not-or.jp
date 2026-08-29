<?php
/**
 * Empty state for the Writings list page (home.php).
 *
 * Usage:
 * get_template_part('template-parts/empty/empty-writings-list', null, [
 *   'title' => 'Writings',
 * ]);
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$title = isset($args['title']) ? trim((string) $args['title']) : 'Writings';
if ($title === '') $title = 'Writings';
?>
<div class="content empty" role="status">
  <div class="textpair">
    <p class="ja" lang="ja">「<q><?php echo esc_html($title); ?></q>」に該当するWritingsは、現在公開されていません。<br class="desktop tablet">「<a href="<?php echo esc_url(home_url('/')); ?>"><i>Home</i></a>」に戻るか、他のページをご覧ください。</p>
    <p class="en" lang="en">There are currently no writings published under “<q><?php echo esc_html($title); ?></q>.” <br class="desktop tablet">Return to “<a href="<?php echo esc_url(home_url('/')); ?>"><i>Home</i></a>”, or browse other sections of the site.</p>
  </div>
  <ul class="actions">
    <li><a href="<?php echo esc_url(home_url('/writings/')); ?>" class="btn">Back to Writings</a></li>
    <li><a href="<?php echo esc_url(home_url('/')); ?>" class="btn">Back to Home</a></li>
  </ul>
</div>
