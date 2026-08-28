<?php
/**
 * Template part: Taxonomy landing list shell (Categories / Tags)
 *
 * Args:
 * - section_class (string): e.g. list-categories, list-tags
 * - section_h2_ja (string)
 * - section_h2_en (string)
 * - section_h2_ja_html (string): pre-escaped HTML (optional)
 * - section_h2_en_html (string): pre-escaped HTML (optional)
 * - body_html (string): pre-rendered cards HTML
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$section_class = isset($args['section_class']) ? trim((string) $args['section_class']) : '';
if ($section_class === '') $section_class = 'list-categories';

$section_h2_ja = isset($args['section_h2_ja']) ? trim((string) $args['section_h2_ja']) : '';
$section_h2_en = isset($args['section_h2_en']) ? trim((string) $args['section_h2_en']) : '';
if ($section_h2_ja === '') $section_h2_ja = '—';
if ($section_h2_en === '') $section_h2_en = '—';
$section_h2_ja_html = isset($args['section_h2_ja_html']) ? trim((string) $args['section_h2_ja_html']) : '';
$section_h2_en_html = isset($args['section_h2_en_html']) ? trim((string) $args['section_h2_en_html']) : '';

$section_h2_ja_out = ($section_h2_ja_html !== '') ? $section_h2_ja_html : esc_html($section_h2_ja);
$section_h2_en_out = ($section_h2_en_html !== '') ? $section_h2_en_html : esc_html($section_h2_en);

$body_html = isset($args['body_html']) ? (string) $args['body_html'] : '';
$body_html = ltrim($body_html, "\r\n");
$body_html = rtrim($body_html, "\r\n");
?>
<section class="section <?php echo esc_attr($section_class); ?>">
  <h2 class="visually-hidden"><span lang="ja"><?php echo $section_h2_ja_out; ?></span>（<span lang="en"><?php echo $section_h2_en_out; ?></span>）</h2>
  <div class="inner">

<?php
if ($body_html !== '') {
  echo $body_html . "\n";
}
?>
  </div>
</section>
