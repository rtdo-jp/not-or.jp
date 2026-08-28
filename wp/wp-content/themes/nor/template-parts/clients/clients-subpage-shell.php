<?php
/**
 * Template part: Clients subpage shell (IOT / Industries)
 *
 * Args:
 * - hero_args (array)    : arguments for template-parts/hero/hero-pages
 * - section_h2_ja (string)
 * - section_h2_en (string)
 * - cards_html (string)  : pre-rendered card list HTML
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$hero_args = isset($args['hero_args']) && is_array($args['hero_args']) ? $args['hero_args'] : [];

$section_h2_ja = isset($args['section_h2_ja']) ? trim((string) $args['section_h2_ja']) : '';
$section_h2_en = isset($args['section_h2_en']) ? trim((string) $args['section_h2_en']) : '';
if ($section_h2_ja === '') {
  $section_h2_ja = '—';
}
if ($section_h2_en === '') {
  $section_h2_en = '—';
}

$cards_html = isset($args['cards_html']) ? (string) $args['cards_html'] : '';
$cards_html = ltrim($cards_html, "\r\n");
$cards_html = rtrim($cards_html, "\r\n");

$hero_html = nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
  'trim'   => 'left',
  'indent' => 2,
]);

// Keep the hero section baseline at two spaces under <main>.
// Also normalize the first inner wrapper to four spaces.
// This keeps clients subpages aligned even if source/prettify pipelines drift.
$hero_html = (string) preg_replace('/^[ \t]*<section class="hero">/m', '  <section class="hero">', $hero_html, 1);
$hero_html = (string) preg_replace('/^[ \t]*<div class="inner">/m', '    <div class="inner">', $hero_html, 1);

echo "\n" . $hero_html;
?>
<?php
echo nor_render_list_section_shell([
  'section_class' => 'list-clients',
  'section_h2_ja' => $section_h2_ja,
  'section_h2_en' => $section_h2_en,
  'body_html' => $cards_html,
], [
  'trim' => 'left',
  'indent' => 2,
], true);

echo nor_render_indices([
  'trim' => 'left',
  'indent' => 2,
], true);
?>
