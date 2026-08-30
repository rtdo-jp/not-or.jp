<?php
/**
 * nor theme functions
 */

// Theme supports
add_action('after_setup_theme', function () {
  // <title> は header.php で明示出力しているため、コアの自動 title-tag は使わない。
  // Enables Featured Image (main visual) for Works.
  add_theme_support('post-thumbnails');

  register_nav_menus([
    'global_primary'   => 'Global navigation (Primary)',
    'global_secondary' => 'Global navigation (Secondary)',
  ]);

});

// Frontend: disable WP admin bar to avoid loading its dependent scripts/styles.
add_filter('show_admin_bar', '__return_false');

// <meta name="robots"> は header.php で明示出力しているため、
// コアの自動出力（サイト全体 noindex 設定等）との二重出力を防ぐ。
add_action('init', function () {
  remove_action('wp_head', 'wp_robots', 1);
  remove_action('wp_head', 'wp_generator');
  remove_action('wp_head', 'rest_output_link_wp_head', 10);
  remove_action('wp_head', 'rsd_link');
  // Disable core auto-sizes contain inline CSS fix.
  remove_action('wp_head', 'wp_print_auto_sizes_contain_css_fix');
  remove_action('wp_enqueue_scripts', 'wp_enqueue_img_auto_sizes_contain_css_fix');
  // Disable emoji assets in head/output.
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('admin_print_scripts', 'print_emoji_detection_script');
  remove_action('admin_print_styles', 'print_emoji_styles');
  // Disable core global styles enqueue path itself (not only dequeue).
  remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
  remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
  remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
  // Canonical is rendered by our header.php; prevent duplicate rel=canonical from core.
  remove_action('wp_head', 'rel_canonical');
});

add_action('wp_enqueue_scripts', function () {
  // Safety net: prevent the inline style block id='wp-img-auto-sizes-contain-inline-css'.
  wp_dequeue_style('wp-img-auto-sizes-contain');
  wp_deregister_style('wp-img-auto-sizes-contain');

  // This site renders from custom fields/templates only, so block/theme global CSS is unnecessary.
  // Removes:
  // - wp-block-library-inline-css
  // - classic-theme-styles-inline-css
  // - global-styles-inline-css
  wp_dequeue_style('wp-block-library');
  wp_deregister_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');
  wp_deregister_style('wp-block-library-theme');
  wp_dequeue_style('classic-theme-styles');
  wp_deregister_style('classic-theme-styles');
  wp_dequeue_style('global-styles');
  wp_deregister_style('global-styles');
}, 100);

/**
 * Upload image output format map.
 * Convert generated JPEG/PNG derivatives to WebP when the server supports it.
 */
function nor_get_upload_image_output_format_map(): array {
  if (!function_exists('wp_image_editor_supports')) {
    return [];
  }

  if (!wp_image_editor_supports(['mime_type' => 'image/webp'])) {
    return [];
  }

  return [
    'image/jpeg' => 'image/webp',
    'image/png'  => 'image/webp',
  ];
}

add_filter('image_editor_output_format', function ($formats) {
  $map = nor_get_upload_image_output_format_map();
  if (empty($map)) {
    return $formats;
  }

  if (!is_array($formats)) {
    $formats = [];
  }

  foreach ($map as $from => $to) {
    $formats[$from] = $to;
  }

  return $formats;
});

function nor_get_environment_type(): string {
  $env = '';
  if (function_exists('wp_get_environment_type')) {
    $env = (string) wp_get_environment_type();
  } elseif (defined('WP_ENVIRONMENT_TYPE')) {
    $env = (string) WP_ENVIRONMENT_TYPE;
  }
  $env = strtolower(trim($env));
  return ($env !== '') ? $env : 'production';
}

/**
 * Admin: External site integrations + SEO/LLMO settings
 */
function nor_sanitize_social_url($raw): string {
  $raw = trim((string) wp_unslash($raw));
  if ($raw === '') return '';

  if (strpos($raw, '//') === 0) {
    $raw = 'https:' . $raw;
  }

  if (!preg_match('#^https?://#i', $raw)) {
    return '';
  }

  $val = (string) esc_url_raw($raw);
  return trim($val);
}

function nor_sanitize_ga_measurement_id($raw): string {
  $raw = trim((string) wp_unslash($raw));
  if ($raw === '') return '';
  $raw = strtoupper(sanitize_text_field($raw));

  // GA4 style only (e.g. G-XXXXXXXXXX).
  if (!preg_match('/^G-[A-Z0-9]+$/', $raw)) {
    return '';
  }
  return $raw;
}

function nor_sanitize_head_text($raw): string {
  $val = sanitize_text_field((string) wp_unslash($raw));
  return trim($val);
}

function nor_sanitize_head_textarea($raw): string {
  $val = sanitize_textarea_field((string) wp_unslash($raw));
  return trim($val);
}

function nor_sanitize_responsive_text(string $raw): string {
  $safe = sanitize_textarea_field($raw);
  $safe = str_replace(["\r\n", "\r"], "\n", $safe);
  return trim($safe);
}

function nor_sanitize_inline_html(string $raw): string {
  return nor_sanitize_responsive_text($raw);
}

function nor_about_allowed_html(): array {
  if (function_exists('nor_notes_allowed_html')) {
    return nor_notes_allowed_html();
  }
  if (function_exists('nor_policies_allowed_html')) {
    return nor_policies_allowed_html();
  }

  $allowed = wp_kses_allowed_html('post');
  if (!is_array($allowed)) $allowed = [];

  if (!isset($allowed['a']) || !is_array($allowed['a'])) $allowed['a'] = [];
  $allowed['a']['target'] = true;
  $allowed['a']['rel'] = true;
  $allowed['a']['class'] = true;

  if (!isset($allowed['abbr']) || !is_array($allowed['abbr'])) $allowed['abbr'] = [];
  $allowed['abbr']['title'] = true;

  if (!isset($allowed['code']) || !is_array($allowed['code'])) $allowed['code'] = [];
  $allowed['code']['class'] = true;

  if (!isset($allowed['br']) || !is_array($allowed['br'])) $allowed['br'] = [];
  $allowed['br']['class'] = true;

  if (!isset($allowed['span']) || !is_array($allowed['span'])) $allowed['span'] = [];
  $allowed['span']['class'] = true;
  $allowed['span']['lang'] = true;

  if (!isset($allowed['div']) || !is_array($allowed['div'])) $allowed['div'] = [];
  $allowed['div']['class'] = true;
  $allowed['div']['lang'] = true;

  if (!isset($allowed['li']) || !is_array($allowed['li'])) $allowed['li'] = [];
  $allowed['li']['class'] = true;

  if (!isset($allowed['time']) || !is_array($allowed['time'])) $allowed['time'] = [];
  $allowed['time']['datetime'] = true;
  $allowed['time']['class'] = true;

  return $allowed;
}

function nor_sanitize_about_rich_html(string $raw): string {
  $raw = trim($raw);
  if ($raw === '') return '';
  $raw = wp_check_invalid_utf8($raw, true);
  return trim((string) wp_kses($raw, nor_about_allowed_html()));
}

function nor_render_inline_html(string $raw, string $fallback = ''): string {
  $safe = nor_sanitize_responsive_text($raw);
  if ($safe === '') {
    return ($fallback !== '') ? esc_html($fallback) : '';
  }
  return str_replace("\n", '<br class="desktop tablet">', esc_html($safe));
}

/**
 * Render plain text with optional token-based <abbr> replacement.
 * Input remains plain text; this only enriches output HTML.
 */
function nor_render_inline_with_abbr(string $raw, string $fallback = '', array $abbr_map = []): string {
  $safe = nor_sanitize_responsive_text($raw);
  if ($safe === '') {
    return ($fallback !== '') ? esc_html($fallback) : '';
  }

  $render_line = static function (string $line) use ($abbr_map): string {
    if ($line === '') return '';
    if (empty($abbr_map)) return esc_html($line);

    $map = [];
    foreach ($abbr_map as $token => $title) {
      $t = trim((string) $token);
      if ($t === '') continue;
      $map[$t] = (string) $title;
    }
    if (empty($map)) return esc_html($line);

    $tokens = array_keys($map);
    usort($tokens, static function ($a, $b): int {
      return strlen((string) $b) <=> strlen((string) $a);
    });

    $parts = [];
    foreach ($tokens as $token) {
      $parts[] = '(?<![A-Za-z0-9])' . preg_quote($token, '/') . '(?![A-Za-z0-9])';
    }
    $pattern = '/' . implode('|', $parts) . '/u';

    if (@preg_match($pattern, '') === false) {
      return esc_html($line);
    }

    if (!preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
      return esc_html($line);
    }

    $out = '';
    $offset = 0;
    foreach ($matches[0] as $m) {
      $token = (string) ($m[0] ?? '');
      $pos   = (int) ($m[1] ?? 0);
      if ($token === '' || $pos < $offset) continue;

      $out .= esc_html(substr($line, $offset, $pos - $offset));
      $title = $map[$token] ?? '';
      $out  .= '<abbr title="' . esc_attr($title) . '">' . esc_html($token) . '</abbr>';
      $offset = $pos + strlen($token);
    }

    $out .= esc_html(substr($line, $offset));
    return $out;
  };

  $lines = explode("\n", $safe);
  $rendered = array_map($render_line, $lines);
  return implode('<br class="desktop tablet">', $rendered);
}

/**
 * Render rich text for inline contexts.
 *
 * @param array<string,mixed> $allowed_html
 */
function nor_render_rich_inline(string $raw, array $allowed_html, bool $preserve_block = false): string {
  $text = trim($raw);
  if ($text === '') return '';

  $safe = wp_kses($text, $allowed_html);
  if ($safe === '') return '';

  if ($preserve_block) {
    $has_block = (bool) preg_match('/<(p|ul|ol|li|blockquote|h[1-6]|pre|table)\b/i', $safe);
    if ($has_block) return $safe;
  }

  return nl2br($safe, false);
}

/**
 * Render rich text for label-like contexts.
 *
 * @param array<string,mixed> $allowed_html
 */
function nor_render_rich_label(string $raw, array $allowed_html): string {
  $text = trim($raw);
  if ($text === '') return '';
  return (string) wp_kses($text, $allowed_html);
}

/**
 * Render rich text by compacting non-empty lines into one flow.
 *
 * @param array<string,mixed> $allowed_html
 */
function nor_render_rich_compact(string $raw, array $allowed_html, string $separator = '<br>'): string {
  $text = trim($raw);
  if ($text === '') return '';

  $safe = wp_kses($text, $allowed_html);
  if ($safe === '') return '';

  $safe = str_replace(["\r\n", "\r"], "\n", $safe);
  $lines = array_map('trim', explode("\n", $safe));
  $lines = array_values(array_filter($lines, static fn($line): bool => $line !== ''));
  if (empty($lines)) return '';

  return implode($separator, $lines);
}

/**
 * Render rich text for block contexts.
 * Plain lines are wrapped with <p>, existing block tags are preserved.
 *
 * @param array<string,mixed> $allowed_html
 */
function nor_render_rich_block(string $raw, array $allowed_html): string {
  $text = trim($raw);
  if ($text === '') return '';

  $safe = wp_kses($text, $allowed_html);
  if ($safe === '') return '';

  $normalized = str_replace(["\r\n", "\r"], "\n", $safe);
  $lines = explode("\n", $normalized);
  $output = [];
  $block_depth = 0;

  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    $has_block_tag = (bool) preg_match('/<\/?(p|ul|ol|li|blockquote|h[1-6]|pre|table|thead|tbody|tr|td|th)\b/i', $line);
    $open_count = preg_match_all('/<(p|ul|ol|li|blockquote|h[1-6]|pre|table|thead|tbody|tr|td|th)\b(?:(?!\/>)[^>])*>/i', $line);
    $close_count = preg_match_all('/<\/(p|ul|ol|li|blockquote|h[1-6]|pre|table|thead|tbody|tr|td|th)>/i', $line);

    if ($has_block_tag || $block_depth > 0) {
      $output[] = $line;
    } else {
      $output[] = '<p>' . $line . '</p>';
    }

    $block_depth += ((int) $open_count - (int) $close_count);
    if ($block_depth < 0) $block_depth = 0;
  }

  if (empty($output)) return '';
  return implode("\n", $output);
}

/**
 * Render multiline plain text using simple <br> conversion.
 * Used by taxonomy cards where responsive line-break classes are not required.
 *
 * @param mixed $raw
 */
function nor_render_desc_with_br($raw): string {
  $s = is_string($raw) ? trim($raw) : '';
  if ($s === '') return '';
  return str_replace("\n", '<br>', esc_html(str_replace(["\r\n", "\r"], "\n", $s)));
}

/**
 * Render work_category label HTML with optional context.
 *
 * Context:
 * - inline: for lists/inline links
 * - card: for card titles using .character-line wrappers
 *
 * @param mixed  $term
 * @param string $context
 */
function nor_render_work_category_label($term, string $context = 'inline'): string {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) return '';

  $slug = (string) $term->slug;
  $name = (string) $term->name;
  $ctx = strtolower(trim($context));

  $is_ui_ux = ($slug === 'ui-ux');
  $is_video = ($slug === 'video-production') || (str_contains($slug, 'video') && str_contains($slug, 'production'));
  $is_presentations_documents = ($slug === 'presentations-documents') || (str_contains($slug, 'presentations') && str_contains($slug, 'documents'));

  if ($ctx === 'card') {
    if ($is_ui_ux) {
      return '<span class="character-line"><abbr title="User Interface">UI</abbr>/<abbr title="User Experience">UX</abbr></span>';
    }
    if ($is_video) {
      return '<span class="character-line">Video </span><span class="character-line">Production</span>';
    }
    if ($is_presentations_documents) {
      return '<span class="character-line">Presentations &amp; </span><span class="character-line">Documents</span>';
    }
    return '<span class="character-line">' . esc_html($name) . '</span>';
  }

  if ($is_ui_ux) {
    return '<abbr title="User Interface">UI</abbr>/<abbr title="User Experience">UX</abbr>';
  }
  if ($is_presentations_documents) {
    return 'Presentations &amp; documents';
  }
  if ($is_video) {
    return 'Video production';
  }
  return esc_html($name);
}

/**
 * Abbreviation map for tools labels in work_tag taxonomy.
 *
 * @return array<string,array<int,array<string,string>>>
 */
function nor_get_work_tag_tool_abbr_map(): array {
  return [
    'html-css' => [
      ['abbr' => 'HTML', 'title' => 'HyperText Markup Language'],
      ['text' => '/'],
      ['abbr' => 'CSS',  'title' => 'Cascading Style Sheets'],
    ],
    'js'  => [['abbr' => 'JS',  'title' => 'JavaScript']],
    'php' => [['abbr' => 'PHP', 'title' => 'Hypertext Preprocessor']],
  ];
}

/**
 * Render tools-group term label with abbreviation markup when applicable.
 *
 * @param mixed $term
 */
function nor_render_work_tag_tool_label($term): string {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) return '';

  $map = nor_get_work_tag_tool_abbr_map();
  $slug = (string) $term->slug;
  if (!isset($map[$slug])) {
    return esc_html((string) $term->name);
  }

  $out = '';
  foreach ($map[$slug] as $part) {
    if (isset($part['abbr'])) {
      $out .= '<abbr title="' . esc_attr((string) ($part['title'] ?? '')) . '">' . esc_html((string) $part['abbr']) . '</abbr>';
    } else {
      $out .= esc_html((string) ($part['text'] ?? ''));
    }
  }
  return $out;
}

function nor_inline_html_to_plain_text(string $raw): string {
  $safe = nor_sanitize_responsive_text($raw);
  if ($safe === '') return '';
  $safe = str_replace("\n", ' ', $safe);
  $text = (string) preg_replace('/\s+/u', ' ', $safe);
  return trim($text);
}

function nor_sanitize_checkbox_flag($raw): string {
  return ((string) $raw === '1') ? '1' : '0';
}

function nor_sanitize_head_url($raw): string {
  $raw = trim((string) wp_unslash($raw));
  if ($raw === '') return '';

  if (function_exists('nor_seo_meta_normalize_url')) {
    return (string) nor_seo_meta_normalize_url($raw);
  }

  if (strpos($raw, '//') === 0) {
    $raw = 'https:' . $raw;
  }
  if (preg_match('#^https?://#i', $raw)) {
    return (string) esc_url_raw($raw);
  }
  if (str_starts_with($raw, '/')) {
    return (string) home_url($raw);
  }
  return '';
}

function nor_sanitize_works_archive_posts_per_page($raw): int {
  $n = (int) $raw;
  if ($n < 1) $n = 12;
  if ($n > 100) $n = 100;
  return $n;
}

function nor_get_works_home_count(): int {
  // Home is intentionally fixed as the first page of Works.
  return 6;
}

function nor_get_works_archive_per_page(): int {
  $n = (int) get_option('nor_works_archive_posts_per_page', 12);
  if ($n < 1) $n = 12;
  if ($n > 100) $n = 100;
  return $n;
}

/**
 * Get a trimmed string post meta value.
 */
function nor_get_post_meta_text(int $post_id, string $meta_key, string $fallback = ''): string {
  $value = get_post_meta($post_id, $meta_key, true);
  $text = is_string($value) ? trim($value) : '';
  if ($text !== '') return $text;
  return trim($fallback);
}

/**
 * Get a trimmed string term meta value.
 */
function nor_get_term_meta_text(int $term_id, string $meta_key, string $fallback = ''): string {
  if ($term_id <= 0) {
    return trim($fallback);
  }
  $value = get_term_meta($term_id, $meta_key, true);
  $text = is_string($value) ? trim($value) : '';
  if ($text !== '') return $text;
  return trim($fallback);
}

/**
 * Get English description for a term from theme meta key.
 */
function nor_get_term_desc_en(int $term_id, string $fallback = ''): string {
  return nor_get_term_meta_text($term_id, 'nor_desc_en', $fallback);
}

/**
 * Reserved slugs for public work_client URLs.
 *
 * @return array<int,string>
 */
function nor_get_work_client_mask_reserved_slugs(): array {
  return ['clients', 'iot', 'industries'];
}

/**
 * Resolve public-facing name/slug for a term.
 * For work_client, optional masking meta can override both values.
 *
 * @param mixed $term
 * @return array{name:string,slug:string,is_masked:bool}
 */
function nor_get_work_client_public_payload($term): array {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return ['name' => '', 'slug' => '', 'is_masked' => false];
  }

  $name = trim((string) $term->name);
  $slug = trim((string) $term->slug);

  if ((string) $term->taxonomy !== 'work_client') {
    return ['name' => $name, 'slug' => $slug, 'is_masked' => false];
  }

  $term_id = (int) $term->term_id;
  $enabled = (nor_get_term_meta_text($term_id, 'nor_mask_enabled') === '1');
  if (!$enabled) {
    return ['name' => $name, 'slug' => $slug, 'is_masked' => false];
  }

  $mask_name = nor_get_term_meta_text($term_id, 'nor_mask_name');
  $mask_slug = sanitize_title(nor_get_term_meta_text($term_id, 'nor_mask_slug'));
  if ($mask_slug !== '' && in_array($mask_slug, nor_get_work_client_mask_reserved_slugs(), true)) {
    $mask_slug = '';
  }

  $public_name = ($mask_name !== '') ? $mask_name : $name;
  $public_slug = ($mask_slug !== '') ? $mask_slug : $slug;
  $is_masked = ($public_name !== $name) || ($public_slug !== $slug);

  return [
    'name'      => $public_name,
    'slug'      => $public_slug,
    'is_masked' => $is_masked,
  ];
}

/**
 * Resolve public-facing term name.
 *
 * @param mixed  $term
 * @param string $fallback
 */
function nor_get_term_public_name($term, string $fallback = ''): string {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return trim($fallback);
  }
  $payload = nor_get_work_client_public_payload($term);
  $name = trim((string) ($payload['name'] ?? ''));
  if ($name !== '') return $name;
  return trim($fallback);
}

/**
 * Build replacement map: formal work_client name => public display name.
 *
 * @return array<string,string>
 */
function nor_get_work_client_public_name_map(): array {
  static $map = null;
  if (is_array($map)) {
    return $map;
  }

  $map = [];

  $terms = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
  ]);

  if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
    return $map;
  }

  foreach ($terms as $term) {
    if (!($term instanceof WP_Term)) {
      continue;
    }

    $real_name = trim((string) $term->name);
    if ($real_name === '') {
      continue;
    }

    $public_name = nor_get_term_public_name($term, $real_name);
    if ($public_name === '' || $public_name === $real_name) {
      continue;
    }

    $map[$real_name] = $public_name;
  }

  if (count($map) > 1) {
    uksort($map, static function (string $a, string $b): int {
      $len_a = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
      $len_b = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
      return $len_b <=> $len_a;
    });
  }

  return $map;
}

/**
 * Build candidate aliases from a client formal name for display masking.
 *
 * Example:
 * - "APAMAN株式会社" => ["APAMAN株式会社", "APAMAN"]
 * - "株式会社サンプル" => ["株式会社サンプル", "サンプル"]
 *
 * @return array<int,string>
 */
function nor_get_work_client_name_aliases(string $name): array {
  $name = trim((string) $name);
  if ($name === '') {
    return [];
  }

  $aliases = [$name];

  $legal_pattern = '(株式会社|有限会社|合同会社|合資会社|合名会社|一般社団法人|一般財団法人|学校法人|社会福祉法人|医療法人)';

  $stripped = preg_replace('/^' . $legal_pattern . '\s*/u', '', $name);
  $stripped = is_string($stripped) ? trim($stripped) : $name;
  if ($stripped !== '' && $stripped !== $name) {
    $aliases[] = $stripped;
  }

  $stripped = preg_replace('/\s*' . $legal_pattern . '$/u', '', $name);
  $stripped = is_string($stripped) ? trim($stripped) : $name;
  if ($stripped !== '' && $stripped !== $name) {
    $aliases[] = $stripped;
  }

  // "Co., Ltd." style suffix fallback for English legal forms.
  $stripped = preg_replace('/(?:,\s*)?(Co\.,?\s*Ltd\.?|Company Limited|Incorporated|Inc\.?|Corporation|Corp\.?|Ltd\.?)$/iu', '', $name);
  $stripped = is_string($stripped) ? trim($stripped) : $name;
  if ($stripped !== '' && $stripped !== $name) {
    $aliases[] = $stripped;
  }

  // Punctuation-normalized variant (e.g. "BBIX, Inc." => "BBIX Inc").
  $normalized = preg_replace('/[,\.;:]+/u', ' ', $name);
  $normalized = is_string($normalized) ? trim((string) preg_replace('/\s+/u', ' ', $normalized)) : $name;
  if ($normalized !== '' && $normalized !== $name) {
    $aliases[] = $normalized;
  }

  $normalized_stripped = preg_replace('/(?:,\s*)?(Co\.,?\s*Ltd\.?|Company Limited|Incorporated|Inc\.?|Corporation|Corp\.?|Ltd\.?)$/iu', '', (string) $normalized);
  $normalized_stripped = is_string($normalized_stripped) ? trim((string) preg_replace('/\s+/u', ' ', $normalized_stripped)) : (string) $normalized;
  if ($normalized_stripped !== '' && $normalized_stripped !== $name) {
    $aliases[] = $normalized_stripped;
  }

  // Extract meaningful ASCII tokens (e.g. APAMAN) for mixed/company names.
  if (preg_match_all('/[A-Za-z][A-Za-z0-9&._-]{2,}/u', $name, $m)) {
    $stop = ['inc', 'ltd', 'co', 'corp', 'corporation', 'company'];
    foreach ((array) ($m[0] ?? []) as $token) {
      $token = trim((string) $token);
      if ($token === '') {
        continue;
      }
      $lower = strtolower($token);
      if (in_array($lower, $stop, true)) {
        continue;
      }
      $aliases[] = $token;
    }
  }

  $aliases = array_values(array_unique(array_filter($aliases, static function ($v) {
    return is_string($v) && trim($v) !== '';
  })));

  if (count($aliases) > 1) {
    usort($aliases, static function (string $a, string $b): int {
      $len_a = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
      $len_b = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
      return $len_b <=> $len_a;
    });
  }

  return $aliases;
}

/**
 * Convert a masked slug to an English display label for inline text.
 *
 * Example: major-real-estate-a => Major real estate A
 */
function nor_humanize_mask_slug_for_en_text(string $slug): string {
  $slug = trim((string) $slug);
  if ($slug === '') {
    return '';
  }

  $slug = preg_replace('/[^a-z0-9\-]+/i', '-', $slug);
  $slug = is_string($slug) ? trim($slug, "- \t\n\r\0\x0B") : '';
  if ($slug === '') {
    return '';
  }

  $parts = array_values(array_filter(explode('-', strtolower($slug)), static function ($v) {
    return is_string($v) && $v !== '';
  }));
  if (empty($parts)) {
    return '';
  }

  $out = [];
  foreach ($parts as $idx => $part) {
    if (preg_match('/^[a-z]$/', $part)) {
      $out[] = strtoupper($part);
      continue;
    }
    if (preg_match('/^[0-9]+$/', $part)) {
      $out[] = $part;
      continue;
    }

    // Sentence-like casing: only first word is capitalized.
    if ($idx === 0) {
      $out[] = ucfirst($part);
    } else {
      $out[] = $part;
    }
  }

  return trim(implode(' ', $out));
}

/**
 * Build English corporate-name variants from a base label.
 *
 * @return array<int,string>
 */
function nor_get_en_corporate_label_aliases(string $base): array {
  $base = trim((string) $base);
  if ($base === '') {
    return [];
  }

  $aliases = [
    $base,
    $base . ' Inc.',
    $base . ', Inc.',
    $base . ' Co., Ltd.',
    $base . ' Co.,Ltd.',
    $base . ' Ltd.',
    $base . ' Corporation',
    $base . ' Corp.',
  ];

  // Upper/lower mixed variants that appear in existing data.
  $aliases[] = strtoupper($base) . ' Inc.';
  $aliases[] = strtoupper($base) . ' Co., Ltd.';

  return array_values(array_unique(array_filter($aliases, static function ($v) {
    return is_string($v) && trim($v) !== '';
  })));
}

/**
 * Build English label aliases from a term slug.
 *
 * Example:
 * - "gold-swan-capital" => ["Gold swan capital", "Gold Swan Capital", ...]
 *
 * @return array<int,string>
 */
function nor_get_en_slug_label_aliases(string $slug): array {
  $slug = sanitize_title((string) $slug);
  if ($slug === '') {
    return [];
  }

  $base = nor_humanize_mask_slug_for_en_text($slug);
  if ($base === '') {
    return [];
  }

  $parts = array_values(array_filter(explode('-', strtolower($slug)), static function ($v) {
    return is_string($v) && $v !== '';
  }));

  $title = '';
  if (!empty($parts)) {
    $title = implode(' ', array_map(static function (string $part): string {
      return ucfirst($part);
    }, $parts));
  }

  $aliases = [$base];
  if ($title !== '') {
    $aliases[] = $title;
  }

  // Add corporate suffix variants for both sentence-like and title-like labels.
  $aliases = array_merge($aliases, nor_get_en_corporate_label_aliases($base));
  if ($title !== '' && $title !== $base) {
    $aliases = array_merge($aliases, nor_get_en_corporate_label_aliases($title));
  }

  return array_values(array_unique(array_filter($aliases, static function ($v) {
    return is_string($v) && trim($v) !== '';
  })));
}

/**
 * Get English-facing public name for a client term.
 * If masked slug exists, prefer a humanized slug label.
 *
 * @param mixed $term
 */
function nor_get_term_public_name_en($term, string $fallback = ''): string {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return trim($fallback);
  }

  $payload = nor_get_work_client_public_payload($term);
  $enabled = (nor_get_term_meta_text((int) $term->term_id, 'nor_mask_enabled') === '1');
  $is_masked = !empty($payload['is_masked']);
  $slug = trim((string) ($payload['slug'] ?? ''));
  $name = trim((string) ($payload['name'] ?? ''));

  if (($enabled || $is_masked) && $slug !== '') {
    $label = nor_humanize_mask_slug_for_en_text($slug);
    if ($label !== '') {
      return $label;
    }
  }

  if ($name !== '') {
    return $name;
  }

  return trim($fallback);
}

/**
 * Get client label for Clients-list pages (IOT / Industries).
 *
 * Rules:
 * - Base label uses public name (mask-aware).
 * - If the base label is an English corporate formal name
 *   (e.g. "BBIX, Inc.", "APAMAN Co., Ltd."), prefer humanized slug.
 * - Japanese labels remain unchanged.
 *
 * @param mixed  $term
 * @param string $fallback
 */
function nor_get_work_client_list_label($term, string $fallback = ''): string {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return trim($fallback);
  }

  $base = nor_get_term_public_name($term, $fallback);
  $base = trim((string) $base);
  if ($base === '') {
    return trim($fallback);
  }

  if ((string) $term->taxonomy !== 'work_client') {
    return $base;
  }

  // Keep Japanese labels untouched.
  if (preg_match('/[ぁ-んァ-ヶ一-龠々]/u', $base)) {
    return $base;
  }

  // Detect English corporate formal patterns.
  $looks_english_corp = (bool) preg_match(
    '/(?:,\s*)?(Inc\.?|Ltd\.?|Co\.?,?\s*Ltd\.?|Corporation|Corp\.?|Company Limited|Incorporated)\b/i',
    $base
  );
  if (!$looks_english_corp) {
    return $base;
  }

  $payload = nor_get_work_client_public_payload($term);
  $slug = trim((string) ($payload['slug'] ?? ''));
  if ($slug === '') {
    return $base;
  }

  $slug_label = nor_humanize_mask_slug_for_en_text($slug);
  if ($slug_label === '') {
    return $base;
  }

  return $slug_label;
}

/**
 * Build replacement map for one work_client term.
 *
 * @param mixed  $term
 * @param string $variant "default" | "en"
 * @return array<string,string>
 */
function nor_get_work_client_term_public_name_map($term, string $variant = 'default'): array {
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return [];
  }
  if ((string) $term->taxonomy !== 'work_client') {
    return [];
  }

  $variant = strtolower(trim((string) $variant));
  if ($variant !== 'en') {
    $variant = 'default';
  }

  $payload = nor_get_work_client_public_payload($term);
  $enabled = (nor_get_term_meta_text((int) $term->term_id, 'nor_mask_enabled') === '1');
  $real_name = trim((string) $term->name);
  $public_name = ($variant === 'en')
    ? nor_get_term_public_name_en($term, (string) ($payload['name'] ?? ''))
    : trim((string) ($payload['name'] ?? ''));

  if (!$enabled || $real_name === '' || $public_name === '') {
    return [];
  }

  $aliases = nor_get_work_client_name_aliases($real_name);
  if ($variant === 'en') {
    $aliases = array_merge($aliases, nor_get_en_corporate_label_aliases($public_name));
    $aliases = array_merge($aliases, nor_get_en_slug_label_aliases((string) $term->slug));
  }
  $map = [];
  foreach ($aliases as $alias) {
    if ($alias === '' || $alias === $public_name) {
      continue;
    }
    $map[$alias] = $public_name;
  }

  if (count($map) > 1) {
    uksort($map, static function (string $a, string $b): int {
      $len_a = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
      $len_b = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
      return $len_b <=> $len_a;
    });
  }

  return $map;
}

/**
 * Replace formal work_client name aliases in arbitrary text for a specific term.
 *
 * @param mixed  $term
 * @param string $text
 * @param string $variant "default" | "en"
 */
function nor_mask_work_client_term_text($term, string $text, string $variant = 'default'): string {
  $text = (string) $text;
  if ($text === '') {
    return '';
  }

  $map = nor_get_work_client_term_public_name_map($term, $variant);
  if (empty($map)) {
    return $text;
  }

  return strtr($text, $map);
}

/**
 * Build replacement map from clients assigned to a Works post.
 *
 * @return array<string,string>
 */
function nor_get_post_work_client_public_name_map(int $post_id, string $variant = 'default'): array {
  $post_id = max(0, (int) $post_id);
  if ($post_id <= 0 || (string) get_post_type($post_id) !== 'works') {
    return [];
  }

  static $cache = [];
  $variant = strtolower(trim((string) $variant));
  if ($variant !== 'en') {
    $variant = 'default';
  }

  $cache_key = $post_id . '|' . $variant;
  if (array_key_exists($cache_key, $cache)) {
    return is_array($cache[$cache_key]) ? $cache[$cache_key] : [];
  }

  $map = [];
  $terms = get_the_terms($post_id, 'work_client');
  if (empty($terms) || is_wp_error($terms) || !is_array($terms)) {
    $cache[$cache_key] = [];
    return [];
  }

  foreach ($terms as $term) {
    if (!($term instanceof WP_Term)) {
      continue;
    }

    $payload = nor_get_work_client_public_payload($term);
    $enabled = (nor_get_term_meta_text((int) $term->term_id, 'nor_mask_enabled') === '1');
    $real_name = trim((string) $term->name);
    $public_name = ($variant === 'en')
      ? nor_get_term_public_name_en($term, (string) ($payload['name'] ?? ''))
      : trim((string) ($payload['name'] ?? ''));

    if (!$enabled || $real_name === '' || $public_name === '') {
      continue;
    }

    $aliases = nor_get_work_client_name_aliases($real_name);
    if ($variant === 'en') {
      $aliases = array_merge($aliases, nor_get_en_corporate_label_aliases($public_name));
      $aliases = array_merge($aliases, nor_get_en_slug_label_aliases((string) $term->slug));
    }
    foreach ($aliases as $alias) {
      if ($alias === '' || $alias === $public_name) {
        continue;
      }
      $map[$alias] = $public_name;
    }
  }

  if (count($map) > 1) {
    uksort($map, static function (string $a, string $b): int {
      $len_a = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
      $len_b = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
      return $len_b <=> $len_a;
    });
  }

  $cache[$cache_key] = $map;
  return $map;
}

/**
 * Replace formal work_client names in a text with public display names.
 */
function nor_mask_work_client_names_in_text(string $text, int $post_id = 0, string $variant = 'default'): string {
  $text = (string) $text;
  if ($text === '') {
    return '';
  }

  $variant = strtolower(trim((string) $variant));
  if ($variant !== 'en') {
    $variant = 'default';
  }

  // For English text, avoid global JA-biased replacements and rely on post-bound map.
  $map = ($variant === 'en') ? [] : nor_get_work_client_public_name_map();
  $post_map = nor_get_post_work_client_public_name_map($post_id, $variant);
  if (!empty($post_map)) {
    $map = array_merge($map, $post_map);
    if (count($map) > 1) {
      uksort($map, static function (string $a, string $b): int {
        $len_a = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
        $len_b = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
        return $len_b <=> $len_a;
      });
    }
  }

  if (empty($map)) {
    return $text;
  }

  return strtr($text, $map);
}

/**
 * Get public-facing Works title with client-name masking applied.
 */
function nor_get_work_public_title(int $post_id, string $fallback = ''): string {
  $post_id = max(0, (int) $post_id);

  $title = trim((string) get_the_title($post_id));
  if ($title === '') {
    $title = trim($fallback);
  }
  if ($title === '') {
    return '';
  }

  if ((string) get_post_type($post_id) !== 'works') {
    return $title;
  }

  return nor_mask_work_client_names_in_text($title, $post_id);
}

/**
 * Get public-facing Works free text with client-name masking applied.
 */
function nor_get_work_public_text(int $post_id, string $text, string $variant = 'default'): string {
  $post_id = max(0, (int) $post_id);
  $text = (string) $text;
  if ($text === '') {
    return '';
  }

  if ((string) get_post_type($post_id) !== 'works') {
    return $text;
  }

  return nor_mask_work_client_names_in_text($text, $post_id, $variant);
}

/**
 * Validate whether a public work_client slug can be used.
 */
function nor_is_work_client_public_slug_available(string $slug, int $current_term_id = 0): bool {
  $slug = sanitize_title($slug);
  if ($slug === '') return false;
  $current_term_id = max(0, (int) $current_term_id);

  static $cache = [];
  $cache_key = $slug . '|' . $current_term_id;
  if (isset($cache[$cache_key])) {
    return (bool) $cache[$cache_key];
  }

  if (in_array($slug, nor_get_work_client_mask_reserved_slugs(), true)) {
    $cache[$cache_key] = false;
    return false;
  }

  $real = get_term_by('slug', $slug, 'work_client');
  if ($real && !is_wp_error($real) && $real instanceof WP_Term && (int) $real->term_id !== $current_term_id) {
    $cache[$cache_key] = false;
    return false;
  }

  $masked_terms = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
    'fields'     => 'ids',
    'meta_query' => [
      [
        'key'   => 'nor_mask_enabled',
        'value' => '1',
      ],
      [
        'key'   => 'nor_mask_slug',
        'value' => $slug,
      ],
    ],
  ]);

  if (!is_wp_error($masked_terms) && is_array($masked_terms) && !empty($masked_terms)) {
    foreach ($masked_terms as $id) {
      if ((int) $id !== $current_term_id) {
        $cache[$cache_key] = false;
        return false;
      }
    }
  }

  $cache[$cache_key] = true;
  return true;
}

/**
 * Find work_client term ID by masked public slug.
 */
function nor_get_work_client_public_term_id_by_slug(string $slug): int {
  $slug = sanitize_title($slug);
  if ($slug === '') return 0;

  static $cache = [];
  if (isset($cache[$slug])) {
    return (int) $cache[$slug];
  }

  $terms = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
    'fields'     => 'ids',
    'number'     => 2,
    'meta_query' => [
      [
        'key'   => 'nor_mask_enabled',
        'value' => '1',
      ],
      [
        'key'   => 'nor_mask_slug',
        'value' => $slug,
      ],
    ],
  ]);

  if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
    $cache[$slug] = 0;
    return 0;
  }

  $id = (int) ($terms[0] ?? 0);
  $cache[$slug] = ($id > 0) ? $id : 0;
  return (int) $cache[$slug];
}

// Public URL replacement for masked client slugs.
add_filter('term_link', function ($termlink, $term, $taxonomy) {
  if ($taxonomy !== 'work_client' || !($term instanceof WP_Term)) {
    return $termlink;
  }

  $payload = nor_get_work_client_public_payload($term);
  $slug = trim((string) ($payload['slug'] ?? ''));
  if ($slug === '' || empty($payload['is_masked'])) {
    return $termlink;
  }

  return home_url('/clients/' . rawurlencode($slug) . '/');
}, 10, 3);

// Route masked client slugs back to the real taxonomy term.
add_filter('request', function ($vars) {
  if (!is_array($vars) || is_admin()) {
    return $vars;
  }

  if (!isset($vars['work_client'])) {
    return $vars;
  }

  $requested_slug = sanitize_title((string) $vars['work_client']);
  if ($requested_slug === '') {
    return $vars;
  }

  $exists = get_term_by('slug', $requested_slug, 'work_client');
  if ($exists && !is_wp_error($exists) && $exists instanceof WP_Term) {
    return $vars;
  }

  $mapped_term_id = nor_get_work_client_public_term_id_by_slug($requested_slug);
  if ($mapped_term_id <= 0) {
    return $vars;
  }

  $mapped_term = get_term($mapped_term_id, 'work_client');
  if ($mapped_term && !is_wp_error($mapped_term) && $mapped_term instanceof WP_Term) {
    $vars['work_client'] = (string) $mapped_term->slug;
  }

  return $vars;
});

/**
 * Build "new" flag from timestamp and window days.
 */
function nor_is_recent_timestamp(int $timestamp, int $window_days = 30): bool {
  if ($timestamp <= 0) return false;
  $days = max(1, $window_days);
  return (time() - $timestamp) <= ($days * DAY_IN_SECONDS);
}

/**
 * Build date payload used by cards/sidebars.
 *
 * @return array{dt:string,d:string,is_new:bool}
 */
function nor_build_date_payload(string $datetime_iso, string $date_ymd, int $timestamp, int $window_days = 30): array {
  return [
    'dt' => $datetime_iso,
    'd' => $date_ymd,
    'is_new' => nor_is_recent_timestamp($timestamp, $window_days),
  ];
}

/**
 * Get published Works count.
 */
function nor_get_published_works_count(): int {
  $c = wp_count_posts('works');
  if (!$c || is_wp_error($c) || !isset($c->publish)) {
    return 0;
  }
  return (int) $c->publish;
}

/**
 * Get published Writings (post) count.
 */
function nor_get_published_writings_count(): int {
  $c = wp_count_posts('post');
  if (!$c || is_wp_error($c) || !isset($c->publish)) {
    return 0;
  }
  return (int) $c->publish;
}

/**
 * Format a count-dependent noun (+ optional verb) for the shared
 * "<data class=count>N</data><span class=unit>...</span>" stat pattern.
 * count === 1 uses $singular; every other count (including 0) uses $plural.
 * Pass verb='' for nouns that stand alone with no verb (e.g. "tag groups.").
 */
function nor_format_count_unit(int $count, string $singular, string $plural, string $verb = ''): string {
  $noun = trim(($count === 1) ? $singular : $plural);
  $verb = trim($verb);
  return ($verb !== '') ? ($noun . ' ' . $verb . '.') : ($noun . '.');
}

/**
 * Get section heading pair from page meta.
 *
 * @return array{ja:string,en:string}
 */
function nor_get_page_section_h2_pair(int $page_id, string $fallback = '—'): array {
  $fb = trim($fallback);
  if ($fb === '') $fb = '—';

  $ja = nor_get_post_meta_text($page_id, 'nor_section_h2_ja');
  $en = nor_get_post_meta_text($page_id, 'nor_section_h2_en');
  if ($ja === '') $ja = $fb;
  if ($en === '') $en = $fb;

  return [
    'ja' => $ja,
    'en' => $en,
  ];
}

/**
 * Get basic page meta bundle used by page templates.
 *
 * @return array{title:string,tagline:string,desc_ja:string,desc_en:string,section_h2_ja:string,section_h2_en:string}
 */
function nor_get_page_meta_bundle(int $page_id, string $title_fallback = '—', string $section_h2_fallback = '—'): array {
  $title = trim((string) get_the_title($page_id));
  $title_fb = trim($title_fallback);
  if ($title_fb === '') $title_fb = '—';
  if ($title === '') $title = $title_fb;

  $h2 = nor_get_page_section_h2_pair($page_id, $section_h2_fallback);

  return [
    'title' => $title,
    'tagline' => nor_get_post_meta_text($page_id, 'nor_tagline'),
    'desc_ja' => nor_get_post_meta_text($page_id, 'nor_desc_ja'),
    'desc_en' => nor_get_post_meta_text($page_id, 'nor_desc_en'),
    'section_h2_ja' => (string) ($h2['ja'] ?? '—'),
    'section_h2_en' => (string) ($h2['en'] ?? '—'),
  ];
}

/**
 * Build common content-page hero/section context.
 *
 * @return array{
 *   title:string,
 *   tagline:string,
 *   desc_ja:string,
 *   desc_en:string,
 *   section_h2_ja:string,
 *   section_h2_en:string,
 *   works_count:int,
 *   hero_args:array<string,mixed>
 * }
 */
function nor_get_content_page_hero_context(int $page_id, array $options = []): array {
  $opts = wp_parse_args($options, [
    'title_fallback' => '—',
    'section_h2_fallback' => '—',
    'unit' => 'works archived.',
    'widget' => 'none',
  ]);

  $meta = nor_get_page_meta_bundle(
    $page_id,
    is_string($opts['title_fallback']) ? (string) $opts['title_fallback'] : '—',
    is_string($opts['section_h2_fallback']) ? (string) $opts['section_h2_fallback'] : '—'
  );

  $works_count = nor_get_published_works_count();

  $hero_args = [
    'count' => (int) $works_count,
    'unit' => is_string($opts['unit']) ? (string) $opts['unit'] : 'works archived.',
    'title' => (string) $meta['title'],
    'tagline' => (string) $meta['tagline'],
    'desc_ja' => (string) $meta['desc_ja'],
    'desc_en' => (string) $meta['desc_en'],
    'widget' => is_string($opts['widget']) ? (string) $opts['widget'] : 'none',
    'breadcrumbs' => nor_build_single_breadcrumb((string) $meta['title']),
  ];

  return [
    'title' => (string) $meta['title'],
    'tagline' => (string) $meta['tagline'],
    'desc_ja' => (string) $meta['desc_ja'],
    'desc_en' => (string) $meta['desc_en'],
    'section_h2_ja' => (string) $meta['section_h2_ja'],
    'section_h2_en' => (string) $meta['section_h2_en'],
    'works_count' => (int) $works_count,
    'hero_args' => $hero_args,
  ];
}

/**
 * Resolve page meta bundle by page path.
 *
 * @return array{
 *   page_id:int,
 *   found:bool,
 *   title:string,
 *   tagline:string,
 *   desc_ja:string,
 *   desc_en:string,
 *   section_h2_ja:string,
 *   section_h2_en:string
 * }
 */
function nor_get_page_meta_bundle_by_path(string $path, string $title_fallback = '—', string $section_h2_fallback = '—'): array {
  $normalized = trim(trim($path), '/');

  $title_fb = trim($title_fallback);
  if ($title_fb === '') $title_fb = '—';
  $h2_fb = trim($section_h2_fallback);
  if ($h2_fb === '') $h2_fb = '—';

  if ($normalized === '') {
    return [
      'page_id' => 0,
      'found' => false,
      'title' => $title_fb,
      'tagline' => '',
      'desc_ja' => '',
      'desc_en' => '',
      'section_h2_ja' => $h2_fb,
      'section_h2_en' => $h2_fb,
    ];
  }

  $page = get_page_by_path($normalized);
  $page_id = ($page instanceof WP_Post) ? (int) $page->ID : 0;
  if ($page_id <= 0) {
    return [
      'page_id' => 0,
      'found' => false,
      'title' => $title_fb,
      'tagline' => '',
      'desc_ja' => '',
      'desc_en' => '',
      'section_h2_ja' => $h2_fb,
      'section_h2_en' => $h2_fb,
    ];
  }

  $meta = nor_get_page_meta_bundle($page_id, $title_fb, $h2_fb);
  return [
    'page_id' => $page_id,
    'found' => true,
    'title' => (string) ($meta['title'] ?? $title_fb),
    'tagline' => (string) ($meta['tagline'] ?? ''),
    'desc_ja' => (string) ($meta['desc_ja'] ?? ''),
    'desc_en' => (string) ($meta['desc_en'] ?? ''),
    'section_h2_ja' => (string) ($meta['section_h2_ja'] ?? $h2_fb),
    'section_h2_en' => (string) ($meta['section_h2_en'] ?? $h2_fb),
  ];
}

/**
 * Get /works/ archive copy from options.
 *
 * @return array{
 *   title:string,
 *   tagline:string,
 *   desc_ja:string,
 *   desc_en:string,
 *   section_h2_ja:string,
 *   section_h2_en:string
 * }
 */
function nor_get_works_archive_copy(string $title = 'Works Index', string $fallback = '—'): array {
  $title_text = trim($title);
  if ($title_text === '') $title_text = 'Works Index';

  $fb = trim($fallback);
  if ($fb === '') $fb = '—';

  $read = static function (string $option_key): string {
    return trim((string) get_option($option_key, ''));
  };

  $tagline = $read('nor_works_archive_tagline');
  $desc_ja = $read('nor_works_archive_desc_ja');
  $desc_en = $read('nor_works_archive_desc_en');
  $section_h2_ja = $read('nor_works_archive_section_h2_ja');
  $section_h2_en = $read('nor_works_archive_section_h2_en');

  if ($tagline === '') $tagline = $fb;
  if ($desc_ja === '') $desc_ja = $fb;
  if ($desc_en === '') $desc_en = $fb;
  if ($section_h2_ja === '') $section_h2_ja = $fb;
  if ($section_h2_en === '') $section_h2_en = $fb;

  return [
    'title' => $title_text,
    'tagline' => $tagline,
    'desc_ja' => $desc_ja,
    'desc_en' => $desc_en,
    'section_h2_ja' => $section_h2_ja,
    'section_h2_en' => $section_h2_en,
  ];
}

/**
 * Resolve schema page type and breadcrumb list for header structured data.
 *
 * @return array{schema_page_type:string,breadcrumbs:array<int,array<string,mixed>>}
 */
function nor_get_header_schema_context(string $site_url, string $page_url, string $req_path, string $works_archive_year, bool $is_home): array {
  $schema_page_type = 'WebPage';
  $breadcrumbs = [
    ['name' => 'Home', 'url' => $site_url],
  ];

  if ($is_home) {
    $schema_page_type = 'WebPage';
  } elseif (is_singular('works')) {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => 'Works', 'url' => home_url('/works/')];
    $work_id = (int) get_queried_object_id();
    $work_title = trim((string) wp_strip_all_tags((string) get_the_title($work_id)));
    if (function_exists('nor_get_work_public_title')) {
      $work_title = nor_get_work_public_title($work_id, $work_title);
    }
    $breadcrumbs[] = ['name' => $work_title, 'url' => $page_url];
  } elseif (is_singular('post')) {
    // Writing detail (post_type = post, e.g. /writings/{slug}/).
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => 'Writings', 'url' => home_url('/writings/')];
    $writing_id = (int) get_queried_object_id();
    $writing_title = trim((string) wp_strip_all_tags((string) get_the_title($writing_id)));
    $breadcrumbs[] = ['name' => $writing_title, 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('about')) {
    $schema_page_type = 'AboutPage';
    $breadcrumbs[] = ['name' => 'About', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('contact')) {
    $schema_page_type = 'ContactPage';
    $breadcrumbs[] = ['name' => 'Contact', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('search')) {
    $schema_page_type = 'SearchResultsPage';
    $breadcrumbs[] = ['name' => 'Search', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('faqs')) {
    $schema_page_type = 'FAQPage';
    $breadcrumbs[] = ['name' => 'FAQs', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('categories')) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Categories', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('tags')) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Tags', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('archives')) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Archives', 'url' => $page_url];
  } elseif ((function_exists('is_page') && (is_page('iot') || is_page('clients/iot'))) || $req_path === 'clients/iot') {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Clients', 'url' => home_url('/clients/')];
    $breadcrumbs[] = ['name' => 'Index of Terms', 'url' => $page_url];
  } elseif ((function_exists('is_page') && (is_page('industries') || is_page('clients/industries'))) || $req_path === 'clients/industries') {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Clients', 'url' => home_url('/clients/')];
    $breadcrumbs[] = ['name' => 'Industries', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('policies')) {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => 'Policies', 'url' => $page_url];
  } elseif (function_exists('is_page') && is_page('notes')) {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => 'Notes', 'url' => $page_url];
  } elseif (function_exists('is_post_type_archive') && is_post_type_archive('works')) {
    $schema_page_type = 'CollectionPage';
    if ($works_archive_year !== '') {
      $breadcrumbs[] = ['name' => 'Archives', 'url' => home_url('/archives/')];
      $breadcrumbs[] = ['name' => $works_archive_year, 'url' => $page_url];
    } else {
      $breadcrumbs[] = ['name' => 'Works', 'url' => home_url('/works/')];
      $paged = max(1, (int) get_query_var('paged'));
      if ($paged > 1) {
        $breadcrumbs[] = ['name' => 'Page ' . $paged, 'url' => $page_url];
      } else {
        $breadcrumbs[] = ['name' => 'Works Index', 'url' => home_url('/works/')];
      }
    }
  } elseif (function_exists('is_tax') && is_tax('work_category')) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Categories', 'url' => home_url('/categories/')];
    $term = get_queried_object();
    if ($term instanceof WP_Term) $breadcrumbs[] = ['name' => $term->name, 'url' => $page_url];
  } elseif (function_exists('is_tax') && is_tax('work_tag')) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Tags', 'url' => home_url('/tags/')];
    $term = get_queried_object();
    if ($term instanceof WP_Term) $breadcrumbs[] = ['name' => $term->name, 'url' => $page_url];
  } elseif (function_exists('is_tax') && (is_tax('work_client') || is_tax('work_industry'))) {
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Clients', 'url' => home_url('/clients/')];
    $term = get_queried_object();
    if ($term instanceof WP_Term) {
      $label = ((string) $term->taxonomy === 'work_client')
        ? nor_get_term_public_name($term, (string) $term->name)
        : (string) $term->name;
      $breadcrumbs[] = ['name' => $label, 'url' => $page_url];
    }
  } elseif (function_exists('is_page') && is_page('writings')) {
    // Writings list (fixed page "writings", rendered by page-writings.php).
    // /writings/page/{n}/ is resolved by a dedicated top-priority rewrite rule
    // straight to the `paged` query var (see the add_rewrite_rule() call for
    // '^writings/page/...'), not WordPress's default page `page` query var.
    $schema_page_type = 'CollectionPage';
    $breadcrumbs[] = ['name' => 'Writings', 'url' => home_url('/writings/')];
    $writings_page_num = max(1, (int) get_query_var('paged'));
    if ($writings_page_num > 1) {
      $breadcrumbs[] = ['name' => 'Page ' . $writings_page_num, 'url' => $page_url];
    } else {
      $breadcrumbs[] = ['name' => 'Writings Index', 'url' => home_url('/writings/')];
    }
  } elseif (function_exists('is_404') && is_404()) {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => '404 Not Found', 'url' => null];
  } elseif ($req_path === '403') {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => '403 Forbidden', 'url' => null];
  } elseif ($req_path === '410') {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => '410 Gone', 'url' => null];
  } elseif ($req_path === '5xx') {
    $schema_page_type = 'WebPage';
    $breadcrumbs[] = ['name' => '5xx Server Error', 'url' => null];
  } else {
    $q_title = trim((string) wp_strip_all_tags((string) get_the_title((int) get_queried_object_id())));
    if ($q_title !== '') $breadcrumbs[] = ['name' => $q_title, 'url' => $page_url];
  }

  return [
    'schema_page_type' => $schema_page_type,
    'breadcrumbs' => $breadcrumbs,
  ];
}

/**
 * Build pagination args for template-parts/pagination/pagination-list.php.
 *
 * @param int      $current_page Current page number.
 * @param int      $total_pages  Total page count.
 * @param callable $page_url_cb  Callback: fn(int $n): string.
 * @param array    $overrides    Optional UI overrides.
 */
function nor_build_list_pagination_args(int $current_page, int $total_pages, callable $page_url_cb, array $overrides = []): array {
  $current = max(1, $current_page);
  $total = max(1, $total_pages);
  if ($current > $total) {
    $current = $total;
  }

  $page_url = static function (int $n) use ($page_url_cb): string {
    $url = call_user_func($page_url_cb, max(1, $n));
    return is_string($url) ? $url : '';
  };

  $first_url = $page_url(1);
  $prev_url  = ($current > 1) ? $page_url($current - 1) : '';
  $next_url  = ($current < $total) ? $page_url($current + 1) : '';
  $last_url  = $page_url($total);

  $page_urls = [];
  for ($n = 1; $n <= $total; $n++) {
    $page_urls[$n] = $page_url($n);
  }

  $opts = wp_parse_args($overrides, [
    'aria_label'      => 'Works pagination',
    'next_rel'        => 'next',
    'prev_rel'        => 'prev',
    'show_first_prev' => true,
    'show_next_last'  => true,
  ]);

  return [
    'current'         => (int) $current,
    'total'           => (int) $total,
    'first_url'       => (string) $first_url,
    'prev_url'        => (string) $prev_url,
    'next_url'        => (string) $next_url,
    'last_url'        => (string) $last_url,
    'page_urls'       => $page_urls,
    'aria_label'      => (string) $opts['aria_label'],
    'next_rel'        => (string) $opts['next_rel'],
    'prev_rel'        => (string) $opts['prev_rel'],
    'show_first_prev' => (bool) $opts['show_first_prev'],
    'show_next_last'  => (bool) $opts['show_next_last'],
  ];
}

/**
 * Capture get_template_part() output as a string.
 *
 * @param string            $slug
 * @param string|null       $name
 * @param array<string,mixed>|null $args
 */
function nor_capture_template_part(string $slug, ?string $name = null, ?array $args = null): string {
  ob_start();
  if (is_array($args)) {
    get_template_part($slug, $name, $args);
  } else {
    get_template_part($slug, $name);
  }
  return (string) ob_get_clean();
}

/**
 * Normalize indentation based on the first non-empty line.
 * Removes up to the detected indent width from each line.
 */
function nor_normalize_first_indent(string $html): string {
  if ($html === '') return $html;

  $lines = preg_split('/\R/u', $html);
  if (!is_array($lines)) return $html;

  $first_indent = null;
  foreach ($lines as $line) {
    if (trim((string) $line) === '') continue;
    if (preg_match('/^[ \t]+/', (string) $line, $m)) {
      $first_indent = strlen((string) $m[0]);
    } else {
      $first_indent = 0;
    }
    break;
  }

  if ($first_indent === null || $first_indent <= 0) {
    return $html;
  }

  return (string) preg_replace('/^[ \t]{0,' . (int) $first_indent . '}/m', '', $html);
}

/**
 * Render a template part with optional trim/indent normalization.
 *
 * Options:
 * - trim: 'none' | 'left' | 'right' | 'both' (default: 'none')
 * - normalize_first_indent: bool (default: false)
 * - strip_leading_spaces: int exact spaces to strip from non-empty lines (default: 0)
 * - indent: int spaces to prepend to non-empty lines (default: 0)
 * - suffix: string appended only when output is non-empty (default: '')
 *
 * @param string            $slug
 * @param string|null       $name
 * @param array<string,mixed>|null $args
 * @param array<string,mixed> $options
 */
function nor_render_template_part(string $slug, ?string $name = null, ?array $args = null, array $options = []): string {
  $html = nor_capture_template_part($slug, $name, $args);

  $opts = wp_parse_args($options, [
    'trim' => 'none',
    'normalize_first_indent' => false,
    'strip_leading_spaces' => 0,
    'indent' => 0,
    'suffix' => '',
  ]);

  $trim_mode = is_string($opts['trim']) ? strtolower($opts['trim']) : 'none';
  if ($trim_mode === 'both' || $trim_mode === 'left') {
    $html = ltrim($html, "\r\n");
  }
  if ($trim_mode === 'both' || $trim_mode === 'right') {
    $html = rtrim($html, "\r\n");
  }

  if (!empty($opts['normalize_first_indent'])) {
    $html = nor_normalize_first_indent($html);
  }

  $strip_leading_spaces = max(0, (int) ($opts['strip_leading_spaces'] ?? 0));
  if ($strip_leading_spaces > 0) {
    $html = (string) preg_replace('/^(?=.*\S) {' . $strip_leading_spaces . '}/m', '', $html);
  }

  $indent = max(0, (int) ($opts['indent'] ?? 0));
  if ($indent > 0) {
    $html = (string) preg_replace('/^(?=.*\S)/m', str_repeat(' ', $indent), $html);
  }

  if ($html === '') {
    return '';
  }

  $suffix = is_string($opts['suffix']) ? $opts['suffix'] : '';
  if ($suffix !== '') {
    $html .= $suffix;
  }

  return $html;
}

/**
 * Render the shared indices section partial.
 *
 * @param array<string,mixed> $options Rendering options for nor_render_template_part().
 * @param bool                $prepend_newline Whether to prepend a single LF.
 */
function nor_render_indices(array $options = [], bool $prepend_newline = false): string {
  $opts = wp_parse_args($options, [
    'trim' => 'left',
    'indent' => 2,
  ]);

  $html = nor_render_template_part('template-parts/indices', null, null, $opts);
  if ($html === '') {
    return '';
  }

  return $prepend_newline ? ("\n" . $html) : $html;
}

/**
 * Build hero breadcrumb items.
 *
 * @param array<int,array<string,mixed>|string> $items
 * @param bool                                  $prepend_home
 * @return array<int,array<string,mixed>>
 */
function nor_build_breadcrumbs(array $items, bool $prepend_home = true): array {
  $out = [];

  if ($prepend_home) {
    $out[] = [
      'label' => 'Home',
      'url' => home_url('/'),
    ];
  }

  foreach ($items as $item) {
    if (is_string($item)) {
      $label = trim($item);
      if ($label === '') continue;
      $out[] = [
        'label' => $label,
      ];
      continue;
    }

    if (!is_array($item)) continue;

    $label = isset($item['label']) && is_string($item['label']) ? trim((string) $item['label']) : '';
    $label_html = isset($item['label_html']) && is_string($item['label_html']) ? trim((string) $item['label_html']) : '';
    if ($label === '' && $label_html === '') continue;

    $row = [];
    if ($label !== '') {
      $row['label'] = $label;
    }
    if ($label_html !== '') {
      $row['label_html'] = $label_html;
    }

    $url = isset($item['url']) && is_string($item['url']) ? trim((string) $item['url']) : '';
    if ($url !== '') {
      $row['url'] = $url;
    }

    if (!empty($item['current'])) {
      $row['current'] = true;
      unset($row['url']);
    }

    $out[] = $row;
  }

  $has_current = false;
  foreach ($out as $row) {
    if (!empty($row['current'])) {
      $has_current = true;
      break;
    }
  }
  if (!$has_current && !empty($out)) {
    $last = count($out) - 1;
    $out[$last]['current'] = true;
    unset($out[$last]['url']);
  }

  return $out;
}

/**
 * Build a single-current breadcrumb list.
 *
 * @return array<int,array<string,mixed>>
 */
function nor_build_single_breadcrumb(string $label, bool $prepend_home = true): array {
  $text = trim($label);
  if ($text === '') $text = '—';

  return nor_build_breadcrumbs([
    ['label' => $text, 'current' => true],
  ], $prepend_home);
}

/**
 * Render shared empty state for works lists.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $options
 * @param bool                $prepend_newline
 */
function nor_render_empty_works_list(array $args = [], array $options = [], bool $prepend_newline = false): string {
  $opts = wp_parse_args($options, [
    'trim' => 'right',
  ]);

  $html = nor_render_template_part('template-parts/empty/empty-works-list', null, $args, $opts);
  if ($html === '') {
    return '';
  }

  return $prepend_newline ? ("\n" . $html) : $html;
}

/**
 * Render shared list section shell.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $options
 * @param bool                $prepend_newline
 */
function nor_render_list_section_shell(array $args = [], array $options = [], bool $prepend_newline = false): string {
  $shell_args = wp_parse_args($args, [
    'section_class' => 'list-works',
    'section_h2_ja' => '—',
    'section_h2_en' => '—',
    'body_html' => '',
  ]);

  $opts = wp_parse_args($options, [
    'trim' => 'left',
    'indent' => 2,
  ]);

  $html = nor_render_template_part('template-parts/taxonomy/taxonomy-list-shell', null, $shell_args, $opts);
  if ($html === '') {
    return '';
  }

  return $prepend_newline ? ("\n" . $html) : $html;
}

/**
 * Render a standard "Back to List" button paragraph.
 *
 * @param string $url
 * @param string $label
 * @param array<string,mixed> $options
 */
function nor_render_back_list_button(string $url, string $label = 'Back to List', array $options = []): string {
  $href = trim($url);
  if ($href === '') {
    $href = home_url('/');
  }

  $text = trim($label);
  if ($text === '') {
    $text = 'Back to List';
  }

  $html = '<p class="back-list"><a href="' . esc_url($href) . '" class="btn">' . esc_html($text) . '</a></p>';

  $opts = wp_parse_args($options, [
    'indent' => 0,
    'suffix' => '',
  ]);

  $indent = max(0, (int) ($opts['indent'] ?? 0));
  if ($indent > 0) {
    $html = str_repeat(' ', $indent) . $html;
  }

  $suffix = is_string($opts['suffix']) ? $opts['suffix'] : '';
  if ($suffix !== '') {
    $html .= $suffix;
  }

  return $html;
}

/**
 * Build common landing-page shell args (meta + hero args + section heading).
 *
 * Usage:
 * $landing = nor_get_landing_page_shell_args($page_id, [
 *   'count'        => 8,
 *   'unit'         => 'categories listed.',
 *   'desc_abbr_map'=> ['UI' => 'User Interface'],
 * ]);
 *
 * Returns:
 * - hero_args (array)
 * - section_h2_ja (string)
 * - section_h2_en (string)
 * - title (string)
 */
function nor_get_landing_page_shell_args(int $page_id, array $options = []): array {
  $opts = wp_parse_args($options, [
    'count' => 0,
    'unit' => '',
    'title_fallback' => '—',
    'section_h2_fallback' => '—',
    'desc_abbr_map' => [],
  ]);

  $title_fallback = is_string($opts['title_fallback']) ? trim($opts['title_fallback']) : '—';
  if ($title_fallback === '') $title_fallback = '—';
  $section_h2_fallback = is_string($opts['section_h2_fallback']) ? trim($opts['section_h2_fallback']) : '—';
  if ($section_h2_fallback === '') $section_h2_fallback = '—';

  $meta = nor_get_page_meta_bundle($page_id, $title_fallback, $section_h2_fallback);
  $page_title = (string) ($meta['title'] ?? '—');
  $page_tagline = (string) ($meta['tagline'] ?? '');
  $page_desc_ja = (string) ($meta['desc_ja'] ?? '');
  $page_desc_en = (string) ($meta['desc_en'] ?? '');
  $section_h2_ja = (string) ($meta['section_h2_ja'] ?? '—');
  $section_h2_en = (string) ($meta['section_h2_en'] ?? '—');

  $desc_abbr_map = is_array($opts['desc_abbr_map']) ? $opts['desc_abbr_map'] : [];
  $page_desc_ja_html = '';
  $page_desc_en_html = '';
  if (!empty($desc_abbr_map)) {
    $page_desc_ja_html = nor_render_inline_with_abbr($page_desc_ja, '', $desc_abbr_map);
    $page_desc_en_html = nor_render_inline_with_abbr($page_desc_en, '', $desc_abbr_map);
  }

  $hero_args = [
    'count' => (int) $opts['count'],
    'unit'  => is_string($opts['unit']) ? $opts['unit'] : '',
    'title' => $page_title,
    'tagline' => $page_tagline,
    'desc_ja' => $page_desc_ja,
    'desc_en' => $page_desc_en,
    'breadcrumbs' => nor_build_single_breadcrumb($page_title),
  ];
  if ($page_desc_ja_html !== '') {
    $hero_args['desc_ja_html'] = $page_desc_ja_html;
  }
  if ($page_desc_en_html !== '') {
    $hero_args['desc_en_html'] = $page_desc_en_html;
  }

  return [
    'hero_args' => $hero_args,
    'section_h2_ja' => $section_h2_ja,
    'section_h2_en' => $section_h2_en,
    'title' => $page_title,
  ];
}

/**
 * Build Clients view tabs (Index of Terms / Industries) for hero widget.
 *
 * @param string              $current_view    'iot' or 'industries'
 * @param array<string,mixed> $label_fallbacks Optional fallbacks: ['iot' => '...', 'industries' => '...']
 * @return array<string,mixed>
 */
function nor_get_clients_tabs_nav(string $current_view = 'iot', array $label_fallbacks = []): array {
  $current = strtolower(trim($current_view));
  if ($current !== 'industries') {
    $current = 'iot';
  }

  $fallback_iot = isset($label_fallbacks['iot']) && is_string($label_fallbacks['iot'])
    ? trim((string) $label_fallbacks['iot'])
    : '';
  $fallback_industries = isset($label_fallbacks['industries']) && is_string($label_fallbacks['industries'])
    ? trim((string) $label_fallbacks['industries'])
    : '';

  $iot_page = get_page_by_path('clients/iot');
  $industries_page = get_page_by_path('clients/industries');

  $iot_label = ($iot_page instanceof WP_Post) ? trim((string) get_the_title($iot_page)) : '';
  $industries_label = ($industries_page instanceof WP_Post) ? trim((string) get_the_title($industries_page)) : '';

  if ($iot_label === '' && $fallback_iot !== '') {
    $iot_label = $fallback_iot;
  }
  if ($industries_label === '' && $fallback_industries !== '') {
    $industries_label = $fallback_industries;
  }

  return [
    'nav_list_aria' => 'Client views',
    'iot_label' => $iot_label,
    'industries_label' => $industries_label,
    'nav_list' => [
      [
        'label' => $iot_label,
        'url' => home_url('/clients/iot/'),
        'current' => ($current === 'iot'),
      ],
      [
        'label' => $industries_label,
        'url' => home_url('/clients/industries/'),
        'current' => ($current === 'industries'),
      ],
    ],
  ];
}

/**
 * Shared work_tag group definitions for Tags pages/indices.
 *
 * @return array<int,array<string,mixed>>
 */
function nor_get_work_tag_group_definitions(): array {
  return [
    [
      'slug'          => 'document-types',
      'label'         => 'Document types',
      'nav_class'     => 'document-types',
      'nav_aria'      => 'Document types navigation',
      'card_id'       => 'tag-group-document-types',
      'card_aria'     => 'Document types',
      'split'         => true,
      'title_link'    => true,
      'more_disabled' => false,
    ],
    [
      'slug'          => 'site-types',
      'label'         => 'Site types',
      'nav_class'     => 'site-types',
      'nav_aria'      => 'Site types navigation',
      'card_id'       => 'tag-group-site-types',
      'card_aria'     => 'Site types',
      'split'         => false,
      'title_link'    => true,
      'more_disabled' => false,
    ],
    [
      'slug'          => 'roles',
      'label'         => 'Roles',
      'nav_class'     => 'roles',
      'nav_aria'      => 'Roles navigation',
      'card_id'       => 'tag-group-roles',
      'card_aria'     => 'Roles',
      'split'         => false,
      'title_link'    => false,
      'more_disabled' => true,
    ],
    [
      'slug'          => 'tools',
      'label'         => 'Tools',
      'nav_class'     => 'tools',
      'nav_aria'      => 'Tools navigation',
      'card_id'       => 'tag-group-tools',
      'card_aria'     => 'Tools',
      'split'         => false,
      'title_link'    => false,
      'more_disabled' => true,
    ],
  ];
}

/**
 * Get a work_tag group term by slug.
 *
 * @param string $slug
 * @return WP_Term|null
 */
function nor_get_work_tag_group_term(string $slug): ?WP_Term {
  $slug = trim($slug);
  if ($slug === '') return null;

  $term = get_term_by('slug', $slug, 'work_tag');
  if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) {
    return null;
  }

  return $term;
}

/**
 * Determine whether a work_tag term has at least one published work.
 *
 * @param int $term_id
 * @return bool
 */
function nor_work_tag_term_has_published_works(int $term_id): bool {
  $term_id = (int) $term_id;
  if ($term_id <= 0) return false;

  static $cache = [];
  if (array_key_exists($term_id, $cache)) {
    return (bool) $cache[$term_id];
  }

  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'tax_query'      => [[
      'taxonomy'         => 'work_tag',
      'field'            => 'term_id',
      'terms'            => [$term_id],
      'operator'         => 'IN',
      'include_children' => false,
    ]],
  ]);

  $has = $q->have_posts();
  wp_reset_postdata();

  $cache[$term_id] = $has ? 1 : 0;
  return $has;
}

/**
 * Get child terms under a work_tag parent term.
 *
 * @param int  $parent_id
 * @param bool $published_only Filter out terms without published works.
 * @return array<int,WP_Term>
 */
function nor_get_work_tag_group_children_by_parent(int $parent_id, bool $published_only = false): array {
  $parent_id = (int) $parent_id;
  if ($parent_id <= 0) return [];

  $terms = get_terms([
    'taxonomy'   => 'work_tag',
    'hide_empty' => false,
    'parent'     => $parent_id,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);

  if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
    return [];
  }

  if (!$published_only) {
    return $terms;
  }

  return array_values(array_filter($terms, function ($term) {
    if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) return false;
    return nor_work_tag_term_has_published_works((int) $term->term_id);
  }));
}

/**
 * Get child terms under a work_tag group slug.
 *
 * @param string $group_slug
 * @param bool   $published_only Filter out terms without published works.
 * @return array<int,WP_Term>
 */
function nor_get_work_tag_group_children(string $group_slug, bool $published_only = false): array {
  $group = nor_get_work_tag_group_term($group_slug);
  if (!$group) return [];

  return nor_get_work_tag_group_children_by_parent((int) $group->term_id, $published_only);
}

/**
 * Count published works matched by taxonomy terms.
 *
 * @param string $taxonomy
 * @param array<int|string> $term_ids
 * @param bool $include_children
 * @return int
 */
function nor_count_published_works_for_terms(string $taxonomy, array $term_ids, bool $include_children = true): int {
  $taxonomy = trim($taxonomy);
  if ($taxonomy === '') return 0;

  $ids = array_values(array_unique(array_filter(array_map('intval', $term_ids))));
  if (empty($ids)) return 0;

  static $cache = [];
  $cache_key = $taxonomy . '|' . ($include_children ? '1' : '0') . '|' . implode(',', $ids);
  if (isset($cache[$cache_key])) {
    return (int) $cache[$cache_key];
  }

  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'tax_query'      => [[
      'taxonomy'         => $taxonomy,
      'field'            => 'term_id',
      'terms'            => $ids,
      'operator'         => 'IN',
      'include_children' => (bool) $include_children,
    ]],
    'no_found_rows'  => false,
  ]);

  $found = max(0, (int) ($q->found_posts ?? 0));
  wp_reset_postdata();
  $cache[$cache_key] = $found;
  return $found;
}

/**
 * Get latest published work date payload matched by taxonomy terms.
 *
 * @param string $taxonomy
 * @param array<int|string> $term_ids
 * @param bool $include_children
 * @return array{dt:string,d:string,is_new:bool}|null
 */
function nor_get_latest_published_work_for_terms(string $taxonomy, array $term_ids, bool $include_children = true): ?array {
  $taxonomy = trim($taxonomy);
  if ($taxonomy === '') return null;

  $ids = array_values(array_unique(array_filter(array_map('intval', $term_ids))));
  if (empty($ids)) return null;

  static $cache = [];
  $cache_key = $taxonomy . '|' . ($include_children ? '1' : '0') . '|' . implode(',', $ids);
  if (array_key_exists($cache_key, $cache)) {
    return is_array($cache[$cache_key]) ? $cache[$cache_key] : null;
  }

  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'tax_query'      => [[
      'taxonomy'         => $taxonomy,
      'field'            => 'term_id',
      'terms'            => $ids,
      'operator'         => 'IN',
      'include_children' => (bool) $include_children,
    ]],
    'no_found_rows'  => true,
  ]);

  if ($q->have_posts()) {
    $q->the_post();
    $dt = (string) get_the_date('c');
    $d  = (string) get_the_date('Y-m-d');
    $ts = (int) get_the_time('U');
    wp_reset_postdata();
    $payload = nor_build_date_payload($dt, $d, $ts);
    $cache[$cache_key] = $payload;
    return $payload;
  }

  wp_reset_postdata();
  $cache[$cache_key] = null;
  return null;
}

/**
 * Count published Works in a given year.
 */
function nor_count_published_works_by_year(int $year): int {
  $y = (int) $year;
  if ($y <= 0) return 0;

  static $cache = [];
  if (isset($cache[$y])) {
    return (int) $cache[$y];
  }

  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => false,
    'date_query'     => [[
      'year' => $y,
    ]],
  ]);

  $found = max(0, (int) ($q->found_posts ?? 0));
  wp_reset_postdata();
  $cache[$y] = $found;
  return $found;
}

/**
 * Get latest published Works payload in a given year.
 *
 * @return array{dt:string,d:string,is_new:bool}|null
 */
function nor_get_latest_published_work_by_year(int $year): ?array {
  $y = (int) $year;
  if ($y <= 0) return null;

  static $cache = [];
  if (array_key_exists($y, $cache)) {
    return is_array($cache[$y]) ? $cache[$y] : null;
  }

  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
    'date_query'     => [[
      'year' => $y,
    ]],
  ]);

  if ($q->have_posts()) {
    $q->the_post();
    $dt = (string) get_the_date('c');
    $d  = (string) get_the_date('Y-m-d');
    $ts = (int) get_the_time('U');
    wp_reset_postdata();
    $payload = nor_build_date_payload($dt, $d, $ts);
    $cache[$y] = $payload;
    return $payload;
  }

  wp_reset_postdata();
  $cache[$y] = null;
  return null;
}

/**
 * Get related Works IDs by taxonomy terms.
 *
 * @param string           $taxonomy
 * @param array<int>|array<string> $term_ids
 * @param array<int>       $exclude_ids
 * @param int              $limit
 * @param bool             $include_children
 * @return array<int>
 */
function nor_get_related_work_ids_by_terms(string $taxonomy, array $term_ids, array $exclude_ids = [], int $limit = 5, bool $include_children = true): array {
  $tax = trim($taxonomy);
  if ($tax === '') return [];

  $terms = array_values(array_unique(array_filter(array_map('intval', $term_ids))));
  if (empty($terms)) return [];

  $exclude = array_values(array_unique(array_filter(array_map('intval', $exclude_ids))));
  $per_page = max(1, (int) $limit);

  $q = new WP_Query([
    'post_type'           => 'works',
    'post_status'         => 'publish',
    'posts_per_page'      => $per_page,
    'post__not_in'        => $exclude,
    'ignore_sticky_posts' => true,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'fields'              => 'ids',
    'tax_query'           => [[
      'taxonomy'         => $tax,
      'field'            => 'term_id',
      'terms'            => $terms,
      'operator'         => 'IN',
      'include_children' => (bool) $include_children,
    ]],
    'no_found_rows'      => true,
  ]);

  $ids = [];
  if (!empty($q->posts) && is_array($q->posts)) {
    foreach ($q->posts as $pid) {
      $id = (int) $pid;
      if ($id > 0) $ids[] = $id;
    }
  }
  wp_reset_postdata();

  return array_values(array_unique($ids));
}

/**
 * Find published Works IDs matched by keyword.
 * Match target:
 * - Full-text search (title/content/excerpt)
 * - work_tag term name
 * - work_client term name
 *
 * @return array<int>
 */
function nor_find_work_ids_by_keyword(string $keyword): array {
  $q = trim(wp_strip_all_tags($keyword));
  if ($q === '') return [];

  static $cache = [];
  if (isset($cache[$q]) && is_array($cache[$q])) {
    return $cache[$q];
  }

  $text_ids_q = new WP_Query([
    'post_type'              => 'works',
    'post_status'            => 'publish',
    's'                      => $q,
    'posts_per_page'         => -1,
    'fields'                 => 'ids',
    'no_found_rows'          => true,
    'ignore_sticky_posts'    => true,
    'orderby'                => 'date',
    'order'                  => 'DESC',
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
  $text_ids = !empty($text_ids_q->posts) ? array_map('intval', (array) $text_ids_q->posts) : [];
  wp_reset_postdata();

  $tag_term_ids = get_terms([
    'taxonomy'   => 'work_tag',
    'hide_empty' => false,
    'fields'     => 'ids',
    'search'     => $q,
  ]);
  if (is_wp_error($tag_term_ids) || !is_array($tag_term_ids)) $tag_term_ids = [];
  $tag_term_ids = array_values(array_filter(array_map('intval', $tag_term_ids)));

  $client_term_ids = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
    'fields'     => 'ids',
    'search'     => $q,
  ]);
  if (is_wp_error($client_term_ids) || !is_array($client_term_ids)) $client_term_ids = [];
  $client_term_ids = array_values(array_filter(array_map('intval', $client_term_ids)));

  $tax_ids = [];
  if (!empty($tag_term_ids) || !empty($client_term_ids)) {
    $tax_query = ['relation' => 'OR'];

    if (!empty($tag_term_ids)) {
      $tax_query[] = [
        'taxonomy'         => 'work_tag',
        'field'            => 'term_id',
        'terms'            => $tag_term_ids,
        'include_children' => true,
      ];
    }

    if (!empty($client_term_ids)) {
      $tax_query[] = [
        'taxonomy'         => 'work_client',
        'field'            => 'term_id',
        'terms'            => $client_term_ids,
        'include_children' => false,
      ];
    }

    $tax_ids_q = new WP_Query([
      'post_type'              => 'works',
      'post_status'            => 'publish',
      'tax_query'              => $tax_query,
      'posts_per_page'         => -1,
      'fields'                 => 'ids',
      'no_found_rows'          => true,
      'ignore_sticky_posts'    => true,
      'orderby'                => 'date',
      'order'                  => 'DESC',
      'update_post_meta_cache' => false,
      'update_post_term_cache' => false,
    ]);
    $tax_ids = !empty($tax_ids_q->posts) ? array_map('intval', (array) $tax_ids_q->posts) : [];
    wp_reset_postdata();
  }

  $matched = array_values(array_unique(array_filter(array_merge($text_ids, $tax_ids))));
  $cache[$q] = $matched;
  return $matched;
}

add_action('admin_init', function () {
  register_setting('nor_external_sites_options', 'nor_ga_measurement_id', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_ga_measurement_id',
    'default'           => 'G-NSEHNNMJDH',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_google_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_x_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_facebook_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_instagram_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_linkedin_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_external_sites_options', 'nor_social_github_url', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_social_url',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_default_meta_title_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_default_meta_desc_ja_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_default_meta_desc_en_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_default_og_image_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_url',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_default_og_title_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_meta_title_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_meta_desc_ja_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_meta_desc_en_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_canonical_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_url',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_og_image_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_url',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_og_title_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_head_meta_options', 'nor_home_use_default_settings', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_checkbox_flag',
    'default'           => '1',
  ]);

  register_setting('nor_works_archive_meta_options', 'nor_works_archive_meta_desc_ja_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_meta_desc_en_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_textarea',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_og_image_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_url',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_og_title_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_robots_override', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_tagline', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_desc_ja', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_responsive_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_desc_en', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_responsive_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_section_h2_ja', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_section_h2_en', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => '',
  ]);
  register_setting('nor_works_archive_meta_options', 'nor_works_archive_posts_per_page', [
    'type'              => 'integer',
    'sanitize_callback' => 'nor_sanitize_works_archive_posts_per_page',
    'default'           => 12,
  ]);
});

function nor_render_external_sites_settings_page(): void {
  if (!current_user_can('manage_options')) return;

  $ga = (string) get_option('nor_ga_measurement_id', 'G-NSEHNNMJDH');
  $social_google = (string) get_option('nor_social_google_url', '');
  $social_x = (string) get_option('nor_social_x_url', '');
  $social_facebook = (string) get_option('nor_social_facebook_url', '');
  $social_instagram = (string) get_option('nor_social_instagram_url', '');
  $social_linkedin = (string) get_option('nor_social_linkedin_url', '');
  $social_github = (string) get_option('nor_social_github_url', '');
  ?>
  <div class="wrap">
    <h1>外部サイト連携</h1>
    <form method="post" action="options.php">
      <?php settings_fields('nor_external_sites_options'); ?>

      <h2>Google Analytics 連携</h2>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="nor_ga_measurement_id">Measurement ID</label></th>
          <td>
            <input type="text" class="regular-text" id="nor_ga_measurement_id" name="nor_ga_measurement_id" value="<?php echo esc_attr($ga); ?>">
            <p class="description">GA4のMeasurement ID（G-XXXXXXXXXX）を入力してください。空欄の場合は無効化されます。</p>
          </td>
        </tr>
      </table>

      <hr>
      <h2>SNS 連携</h2>
      <p>各SNSの連携用URLを入力してください。<br>すべてのSNSで登録がない場合、JSON-LD Person.sameAsの出力もされません。</p>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="nor_social_google_url">Google</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_google_url" name="nor_social_google_url" value="<?php echo esc_attr($social_google); ?>">
            <p class="description">例：https://g.co/kgs/...</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_social_x_url">X</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_x_url" name="nor_social_x_url" value="<?php echo esc_attr($social_x); ?>">
            <p class="description">例：https://x.com/@ユーザー名</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_social_facebook_url">Facebook</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_facebook_url" name="nor_social_facebook_url" value="<?php echo esc_attr($social_facebook); ?>">
            <p class="description">例：https://www.facebook.com/ユーザー名</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_social_instagram_url">Instagram</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_instagram_url" name="nor_social_instagram_url" value="<?php echo esc_attr($social_instagram); ?>">
            <p class="description">例：https://www.instagram.com/ユーザー名</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_social_linkedin_url">LinkedIn</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_linkedin_url" name="nor_social_linkedin_url" value="<?php echo esc_attr($social_linkedin); ?>">
            <p class="description">例：https://www.linkedin.com/in/ユーザー名</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_social_github_url">GitHub</label></th>
          <td>
            <input type="url" class="regular-text" id="nor_social_github_url" name="nor_social_github_url" value="<?php echo esc_attr($social_github); ?>">
            <p class="description">例：https://github.com/ユーザー名</p>
          </td>
        </tr>
      </table>

      <?php submit_button('設定を保存'); ?>
    </form>
  </div>
  <?php
}

function nor_render_seo_llmo_settings_page(): void {
  if (!current_user_can('manage_options')) return;

  $env_type = function_exists('nor_get_environment_type') ? nor_get_environment_type() : 'production';
  $force_noindex_by_env = ($env_type !== 'production');
  $discourage_search = ((int) get_option('blog_public', 1) === 0);
  $force_noindex = ($force_noindex_by_env || $discourage_search);
  $site_name = trim((string) get_bloginfo('name'));
  $site_tagline = trim((string) get_bloginfo('description'));
  $default_title_preview = $site_name;
  if ($site_tagline !== '') {
    $default_title_preview = ($default_title_preview !== '')
      ? ($default_title_preview . ' | ' . $site_tagline)
      : $site_tagline;
  }
  $default_desc_ja = (string) get_option('nor_default_meta_desc_ja_override', '');
  $default_desc_en = (string) get_option('nor_default_meta_desc_en_override', '');
  $default_og_image = (string) get_option('nor_default_og_image_override', '');
  $home_title = (string) get_option('nor_home_meta_title_override', '');
  $home_desc_ja = (string) get_option('nor_home_meta_desc_ja_override', '');
  $home_desc_en = (string) get_option('nor_home_meta_desc_en_override', '');
  $home_canonical_locked = trailingslashit(home_url('/'));
  $home_og_image = (string) get_option('nor_home_og_image_override', '');
  $home_og_title = (string) get_option('nor_home_og_title_override', '');
  $home_use_default = ((string) get_option('nor_home_use_default_settings', '1') === '1');
  ?>
  <div class="wrap">
    <h1>SEO / LLMO</h1>
    <form method="post" action="options.php">
      <?php settings_fields('nor_head_meta_options'); ?>
      <?php if ($force_noindex): ?>
        <div class="notice notice-warning inline">
          <p>
            現在、全ページで <code>noindex, nofollow</code> が優先されています。
            <?php if ($force_noindex_by_env): ?>
              環境タイプが <code><?php echo esc_html($env_type); ?></code>（production 以外）のため強制されています。
            <?php endif; ?>
            <?php if ($discourage_search): ?>
              「設定 → 表示設定」の「検索エンジンがサイトをインデックスしないようにする」が有効です。
            <?php endif; ?>
            個別のrobots設定より全体条件が優先されます。
          </p>
        </div>
      <?php else: ?>
        <div class="notice notice-success inline"><p>現在、サイト全体のrobots設定はインデックス許可状態です。必要に応じて各ページの「robots（任意上書き）」で個別設定できます。</p></div>
      <?php endif; ?>

      <h2>デフォルト設定</h2>
      <p>サイト全体のデフォルト値です。個別ページやHome設定がある場合はそちらが優先されます。未入力の場合その項目のmetaは出力しません。<br>Title と OG Title は設定の一般にある「サイト名」と「キャッチフレーズ」を使用します、変更したい場合は一般の設定項目を変更してください。</p>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="nor_default_meta_title_preview">Title</label></th>
          <td>
            <input type="text" class="large-text" id="nor_default_meta_title_preview" value="<?php echo esc_attr($default_title_preview); ?>" disabled aria-disabled="true">
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_default_meta_desc_ja_override">Description（JA）</label></th>
          <td><input type="text" class="large-text" id="nor_default_meta_desc_ja_override" name="nor_default_meta_desc_ja_override" value="<?php echo esc_attr($default_desc_ja); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_default_meta_desc_en_override">Description（EN）</label></th>
          <td>
            <input type="text" class="large-text" id="nor_default_meta_desc_en_override" name="nor_default_meta_desc_en_override" value="<?php echo esc_attr($default_desc_en); ?>">
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_default_og_image_override">OG image</label></th>
          <td>
            <div style="display:flex; gap:8px; align-items:center;">
              <input type="text" class="large-text" id="nor_default_og_image_override" name="nor_default_og_image_override" value="<?php echo esc_attr($default_og_image); ?>">
              <button type="button" class="button nor-og-media-pick" data-target="nor_default_og_image_override">メディアから選択</button>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_default_og_title_preview">OG Title</label></th>
          <td>
            <input type="text" class="large-text" id="nor_default_og_title_preview" value="<?php echo esc_attr($default_title_preview); ?>" disabled aria-disabled="true">
          </td>
        </tr>
      </table>

      <hr>
      <h2>Home 設定</h2>
      <p>
        <label for="nor_home_use_default_settings">
          <input type="hidden" name="nor_home_use_default_settings" value="0">
          <input type="checkbox" id="nor_home_use_default_settings" name="nor_home_use_default_settings" value="1" <?php checked($home_use_default); ?>>
          デフォルト設定を Home 設定として使用する
        </label>
      </p>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="nor_home_meta_title_override">Title</label></th>
          <td><input type="text" class="large-text" id="nor_home_meta_title_override" name="nor_home_meta_title_override" value="<?php echo esc_attr($home_title); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_home_meta_desc_ja_override">Description（JA）</label></th>
          <td><input type="text" class="large-text" id="nor_home_meta_desc_ja_override" name="nor_home_meta_desc_ja_override" value="<?php echo esc_attr($home_desc_ja); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_home_meta_desc_en_override">Description（EN）</label></th>
          <td><input type="text" class="large-text" id="nor_home_meta_desc_en_override" name="nor_home_meta_desc_en_override" value="<?php echo esc_attr($home_desc_en); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_home_canonical_override">Canonical URL</label></th>
          <td>
            <input type="text" class="large-text" id="nor_home_canonical_override" value="<?php echo esc_attr($home_canonical_locked); ?>" disabled aria-disabled="true">
            <input type="hidden" name="nor_home_canonical_override" value="<?php echo esc_attr($home_canonical_locked); ?>">
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_home_og_image_override">OG image</label></th>
          <td>
            <div style="display:flex; gap:8px; align-items:center;">
              <input type="text" class="large-text" id="nor_home_og_image_override" name="nor_home_og_image_override" value="<?php echo esc_attr($home_og_image); ?>">
              <button type="button" class="button nor-og-media-pick" data-target="nor_home_og_image_override">メディアから選択</button>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_home_og_title_override">OG Title</label></th>
          <td><input type="text" class="large-text" id="nor_home_og_title_override" name="nor_home_og_title_override" value="<?php echo esc_attr($home_og_title); ?>"></td>
        </tr>
      </table>

      <?php submit_button('設定を保存'); ?>
    </form>
  </div>
  <script>
    (function () {
      function setInputValue(id, value) {
        var input = document.getElementById(id);
        if (!input) return;
        input.value = String(value || '');
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }

      function fillHomeFromDefaults() {
        var defaultTitle = document.getElementById('nor_default_meta_title_preview');
        var defaultDescJa = document.getElementById('nor_default_meta_desc_ja_override');
        var defaultDescEn = document.getElementById('nor_default_meta_desc_en_override');
        var defaultOgTitle = document.getElementById('nor_default_og_title_preview');

        setInputValue('nor_home_meta_title_override', defaultTitle ? defaultTitle.value : '');
        setInputValue('nor_home_meta_desc_ja_override', defaultDescJa ? defaultDescJa.value : '');
        setInputValue('nor_home_meta_desc_en_override', defaultDescEn ? defaultDescEn.value : '');
        setInputValue('nor_home_og_title_override', defaultOgTitle ? defaultOgTitle.value : (defaultTitle ? defaultTitle.value : ''));
      }

      var homeUseDefault = document.getElementById('nor_home_use_default_settings');
      var homeForm = homeUseDefault ? homeUseDefault.closest('form') : null;
      var syncDefaultIds = [
        'nor_default_meta_desc_ja_override',
        'nor_default_meta_desc_en_override'
      ];

      if (homeUseDefault && homeUseDefault.checked) {
        fillHomeFromDefaults();
      }

      if (homeUseDefault) {
        homeUseDefault.addEventListener('change', function () {
          if (homeUseDefault.checked) fillHomeFromDefaults();
        });
      }

      syncDefaultIds.forEach(function (id) {
        var input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', function () {
          if (homeUseDefault && homeUseDefault.checked) fillHomeFromDefaults();
        });
      });

      if (homeForm) {
        homeForm.addEventListener('submit', function () {
          if (homeUseDefault && homeUseDefault.checked) fillHomeFromDefaults();
        });
      }

      document.addEventListener('click', function (e) {
        var button = e.target.closest('.nor-og-media-pick');
        if (!button) return;
        e.preventDefault();

        var targetId = String(button.getAttribute('data-target') || '').trim();
        if (!targetId) return;

        if (typeof window.wp === 'undefined' || !window.wp.media) {
          window.alert('メディアライブラリを読み込めませんでした。ページを再読み込みして再度お試しください。');
          return;
        }

        var frame = window.wp.media({
          title: 'OG画像を選択',
          library: { type: 'image' },
          button: { text: 'この画像を使用' },
          multiple: false
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection');
          if (!selection || selection.length === 0) return;
          var media = selection.first().toJSON();
          if (!media || !media.url) return;

          var input = document.getElementById(targetId);
          if (!input) return;
          input.value = media.url;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        frame.open();
      });
    })();
  </script>
  <?php
}

function nor_render_works_settings_page(): void {
  if (!current_user_can('manage_options')) return;

  $env_type = function_exists('nor_get_environment_type') ? nor_get_environment_type() : 'production';
  $force_noindex_by_env = ($env_type !== 'production');
  $discourage_search = ((int) get_option('blog_public', 1) === 0);
  $force_noindex = ($force_noindex_by_env || $discourage_search);

  $desc_ja = (string) get_option('nor_works_archive_meta_desc_ja_override', '');
  $desc_en = (string) get_option('nor_works_archive_meta_desc_en_override', '');
  $og_image = (string) get_option('nor_works_archive_og_image_override', '');
  $og_title = trim((string) get_option('nor_works_archive_og_title_override', ''));
  $robots = (string) get_option('nor_works_archive_robots_override', '');
  $tagline = (string) get_option('nor_works_archive_tagline', '');
  $body_desc_ja = (string) get_option('nor_works_archive_desc_ja', '');
  $body_desc_en = (string) get_option('nor_works_archive_desc_en', '');
  $section_h2_ja = (string) get_option('nor_works_archive_section_h2_ja', '');
  $section_h2_en = (string) get_option('nor_works_archive_section_h2_en', '');
  $posts_per_page = nor_get_works_archive_per_page();

  // Align input behavior with fixed-page SEO meta box:
  // show the currently used value when override is empty.
  $default_desc_ja = trim((string) get_option('nor_default_meta_desc_ja_override', ''));
  $default_desc_en = trim((string) get_option('nor_default_meta_desc_en_override', ''));
  $desc_ja = trim((string) $desc_ja);
  $desc_en = trim((string) $desc_en);
  $body_desc_ja = trim((string) $body_desc_ja);
  $body_desc_en = trim((string) $body_desc_en);
  $desc_ja_input = ($desc_ja !== '') ? $desc_ja : (($body_desc_ja !== '') ? $body_desc_ja : $default_desc_ja);
  $desc_en_input = ($desc_en !== '') ? $desc_en : (($body_desc_en !== '') ? $body_desc_en : $default_desc_en);
  $site_name = trim((string) get_bloginfo('name'));
  $og_title_auto = 'Works';
  if ($site_name !== '') $og_title_auto .= ' | ' . $site_name;
  $og_title_input = ($og_title !== '') ? $og_title : $og_title_auto;
  $robots_default = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
  $robots_options = [
    '' => 'デフォルト（' . $robots_default . '）',
    $robots_default => $robots_default,
    'index, follow' => 'index, follow',
    'noindex, follow' => 'noindex, follow',
    'noindex, nofollow' => 'noindex, nofollow',
    'index, nofollow' => 'index, nofollow',
  ];
  ?>
  <div class="wrap">
    <h1>Works 設定</h1>
    <?php if ($force_noindex): ?>
      <div class="notice notice-warning inline">
        <p>
          現在、全ページで <code>noindex, nofollow</code> が優先されています。
          <?php if ($force_noindex_by_env): ?>
            環境タイプが <code><?php echo esc_html($env_type); ?></code>（production 以外）のため強制されています。
          <?php endif; ?>
          <?php if ($discourage_search): ?>
            「設定 → 表示設定」の「検索エンジンがサイトをインデックスしないようにする」が有効です。
          <?php endif; ?>
          個別のrobots設定より全体条件が優先されます。
        </p>
      </div>
    <?php endif; ?>
    <p>
      表示設定は <code>/works/page/{n}/</code> に適用されます。<br>
      一覧件数は <code>/works/page/{n}/</code>・<code>/archives/{YYYY}/</code>・カテゴリー/タグ/クライアントなどの一覧ページに適用されます。
    </p>
    <form method="post" action="options.php">
      <?php settings_fields('nor_works_archive_meta_options'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="nor_works_archive_tagline">タグライン</label></th>
          <td>
            <input type="text" class="large-text" id="nor_works_archive_tagline" name="nor_works_archive_tagline" value="<?php echo esc_attr($tagline); ?>">
            <p class="description">1行テキストで入力してください。</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_desc_ja">説明文（JA）</label></th>
          <td>
            <textarea class="large-text" id="nor_works_archive_desc_ja" name="nor_works_archive_desc_ja" rows="3"><?php echo esc_textarea($body_desc_ja); ?></textarea>
            <p class="description">改行で入力してください。改行は Desktop / Tablet のみで反映されます。</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_desc_en">説明文（EN）</label></th>
          <td>
            <textarea class="large-text" id="nor_works_archive_desc_en" name="nor_works_archive_desc_en" rows="3"><?php echo esc_textarea($body_desc_en); ?></textarea>
            <p class="description">改行で入力してください。改行は Desktop / Tablet のみで反映されます。</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_section_h2_ja">セクションタイトル（JA）</label></th>
          <td><input type="text" class="regular-text" id="nor_works_archive_section_h2_ja" name="nor_works_archive_section_h2_ja" value="<?php echo esc_attr($section_h2_ja); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_section_h2_en">セクションタイトル（EN）</label></th>
          <td><input type="text" class="regular-text" id="nor_works_archive_section_h2_en" name="nor_works_archive_section_h2_en" value="<?php echo esc_attr($section_h2_en); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_posts_per_page">一覧件数</label></th>
          <td>
            <input type="number" min="1" max="100" step="1" class="small-text" id="nor_works_archive_posts_per_page" name="nor_works_archive_posts_per_page" value="<?php echo esc_attr((string) $posts_per_page); ?>">
            <p class="description">1ページあたりの表示件数です。</p>
          </td>
        </tr>
        <tr><th colspan="2"><hr style="margin:16px 0;"></th></tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_meta_desc_ja_override">Description（JA）</label></th>
          <td><input type="text" class="large-text" id="nor_works_archive_meta_desc_ja_override" name="nor_works_archive_meta_desc_ja_override" value="<?php echo esc_attr($desc_ja_input); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_meta_desc_en_override">Description（EN）</label></th>
          <td><input type="text" class="large-text" id="nor_works_archive_meta_desc_en_override" name="nor_works_archive_meta_desc_en_override" value="<?php echo esc_attr($desc_en_input); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_og_image_override">OG image</label></th>
          <td>
            <div style="display:flex; gap:8px; align-items:center;">
              <input type="text" class="large-text" id="nor_works_archive_og_image_override" name="nor_works_archive_og_image_override" value="<?php echo esc_attr($og_image); ?>">
              <button type="button" class="button nor-og-media-pick" data-target="nor_works_archive_og_image_override">メディアから選択</button>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_og_title_override">OG Title</label></th>
          <td><input type="text" class="large-text" id="nor_works_archive_og_title_override" name="nor_works_archive_og_title_override" value="<?php echo esc_attr($og_title_input); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="nor_works_archive_robots_override">robots</label></th>
          <td>
            <select id="nor_works_archive_robots_override" name="nor_works_archive_robots_override" class="regular-text">
              <?php foreach ($robots_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($robots, $value); ?>><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>

      <?php submit_button('設定を保存'); ?>
    </form>
  </div>
  <script>
    (function () {
      document.addEventListener('click', function (e) {
        var button = e.target.closest('.nor-og-media-pick');
        if (!button) return;
        e.preventDefault();

        var targetId = String(button.getAttribute('data-target') || '').trim();
        if (!targetId) return;

        if (typeof window.wp === 'undefined' || !window.wp.media) {
          window.alert('メディアライブラリを読み込めませんでした。ページを再読み込みして再度お試しください。');
          return;
        }

        var frame = window.wp.media({
          title: 'OG画像を選択',
          library: { type: 'image' },
          button: { text: 'この画像を使用' },
          multiple: false
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection');
          if (!selection || selection.length === 0) return;
          var media = selection.first().toJSON();
          if (!media || !media.url) return;

          var input = document.getElementById(targetId);
          if (!input) return;
          input.value = media.url;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        frame.open();
      });
    })();
  </script>
  <?php
}

add_action('admin_menu', function () {
  add_options_page(
    '外部サイト連携',
    '外部サイト連携',
    'manage_options',
    'nor-external-sites',
    'nor_render_external_sites_settings_page'
  );

  // Keep legacy slug `nor-head-meta` for backward compatibility.
  add_options_page(
    'SEO / LLMO',
    'SEO / LLMO',
    'manage_options',
    'nor-head-meta',
    'nor_render_seo_llmo_settings_page'
  );

  add_submenu_page(
    'edit.php?post_type=works',
    'Works 設定',
    '設定',
    'manage_options',
    'nor-works-settings',
    'nor_render_works_settings_page'
  );
});

add_action('admin_enqueue_scripts', function ($hook) {
  $is_seo_settings_page = ($hook === 'settings_page_nor-head-meta')
    || ($hook === 'works_page_nor-works-settings')
    || (isset($_GET['page']) && in_array((string) $_GET['page'], ['nor-head-meta', 'nor-works-settings'], true));
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  $is_post_editor = in_array($hook, ['post.php', 'post-new.php'], true)
    && $screen
    && in_array((string) ($screen->post_type ?? ''), ['page', 'works'], true);
  if (!$is_seo_settings_page && !$is_post_editor) return;
  wp_enqueue_media();
  wp_enqueue_script('jquery');
});

// Keep core XML sitemaps enabled even when "Discourage search engines" is ON.
// Pre-release indexing control is handled separately via robots/noindex and Basic auth.
add_filter('wp_sitemaps_enabled', function ($enabled) {
  return true;
}, 999);

// Do not expose author/user sitemap in this site.
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
  if ($name === 'users') return false;
  return $provider;
}, 10, 2);

// Rewrite: reserve /clients/ pages (clients + children) so they don't get captured by the work_client taxonomy.
add_action('init', function () {
  // Parent page
  add_rewrite_rule('^clients/?$', 'index.php?pagename=clients', 'top');

  // Child pages under /clients/
  add_rewrite_rule('^clients/iot/?$', 'index.php?pagename=clients/iot', 'top');
  add_rewrite_rule('^clients/industries/?$', 'index.php?pagename=clients/industries', 'top');
}, 1);

// Redirect: /clients/ -> /clients/iot/
// Redirect: /works/  -> Home (Home is treated as page 1 for Works; /works/page/2/ starts from item 7)
add_action('template_redirect', function () {
  if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

  // Explicitly return 404 for disabled users sitemap URLs.
  // Some environments may otherwise resolve these to Home (200).
  $uri_path = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
  $uri_path = trim($uri_path, '/');
  if (preg_match('#^wp-sitemap-users-[0-9]+\.xml$#i', $uri_path)) {
    status_header(404);
    nocache_headers();
    $tpl = locate_template('404.php');
    if ($tpl) {
      include $tpl;
      exit;
    }
    wp_die('404 Not Found', '404 Not Found', ['response' => 404]);
  }

  // 1) /clients/ -> /clients/iot/
  // Request path without leading/trailing slashes (e.g. "clients", "clients/iot")
  $req = isset($GLOBALS['wp']) ? (string) $GLOBALS['wp']->request : '';
  $req = trim($req, '/');

  if ($req === 'clients') {
    wp_redirect(home_url('/clients/iot/'), 301);
    exit;
  }

  // 2) /works/ -> Home
  // Only redirect the archive root path "/works/" (non-paged).
  // Keep /works/page/{n}/ and /archives/{year}/ working.
  if ($req === 'works') {
    wp_safe_redirect(home_url('/'), 302);
    exit;
  }

  // 3) work_client archive: force masked slug URL when enabled.
  if (function_exists('is_tax') && is_tax('work_client')) {
    $term = get_queried_object();
    if ($term instanceof WP_Term) {
      $payload = nor_get_work_client_public_payload($term);
      $masked_slug = trim((string) ($payload['slug'] ?? ''));
      $is_masked = !empty($payload['is_masked']);
      if ($is_masked && $masked_slug !== '') {
        $target = home_url('/clients/' . rawurlencode($masked_slug) . '/');
        $target_path = trim((string) wp_parse_url($target, PHP_URL_PATH), '/');
        if ($target_path !== '' && $req !== $target_path) {
          $qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
          if ($qs !== '') {
            $target .= '?' . $qs;
          }
          wp_safe_redirect($target, 301);
          exit;
        }
      }
    }
  }

  // 4) /works/industries/{term}/ -> /clients/industries/#client-industry-{term}
  // Keep industry terms as internal master data, but retire the public industry taxonomy archive.
  // Match by request path first so we can redirect even if the term slug is missing/invalid.
  if (preg_match('#^works/industries(?:/([^/]+))?/?$#', $req, $m)) {
    $slug_raw = isset($m[1]) ? rawurldecode((string) $m[1]) : '';
    $slug = sanitize_title($slug_raw);
    $target = home_url('/clients/industries/');
    if ($slug !== '') $target .= '#client-industry-' . $slug;
    wp_safe_redirect($target, 301);
    exit;
  }

  // 5) /403/ -> theme 403 template
  if ($req === '403') {
    status_header(403);
    nocache_headers();
    $tpl = locate_template('403.php');
    if ($tpl) {
      include $tpl;
      exit;
    }
    wp_die('403 Forbidden', '403 Forbidden', ['response' => 403]);
  }

  // 6) /410/ -> theme 410 template
  if ($req === '410') {
    status_header(410);
    nocache_headers();
    $tpl = locate_template('410.php');
    if ($tpl) {
      include $tpl;
      exit;
    }
    wp_die('410 Gone', '410 Gone', ['response' => 410]);
  }

  // 7) /5xx/ -> theme 5xx template (503 for temporary server-side issue)
  if ($req === '5xx') {
    status_header(503);
    nocache_headers();
    $tpl = locate_template('5xx.php');
    if ($tpl) {
      include $tpl;
      exit;
    }
    wp_die('503 Service Unavailable', '503 Service Unavailable', ['response' => 503]);
  }
});

// Custom document titles for explicit error routes.
add_filter('pre_get_document_title', function ($title) {
  if (is_admin()) return $title;
  $req = isset($GLOBALS['wp']) ? trim((string) $GLOBALS['wp']->request, '/') : '';
  if ($req === '403') return '403 Forbidden | nør. Ryousuke Tamura Design Office';
  if ($req === '410') return '410 Gone | nør. Ryousuke Tamura Design Office';
  if ($req === '5xx') return '5xx Server Error | nør. Ryousuke Tamura Design Office';
  if (function_exists('is_tax') && is_tax('work_client')) {
    $term = get_queried_object();
    if ($term instanceof WP_Term) {
      $name = nor_get_term_public_name($term, (string) $term->name);
      if ($name !== '') {
        return $name . ' | nør. Ryousuke Tamura Design Office';
      }
    }
  }
  return $title;
});

// Register post types / taxonomies
add_action('init', function () {
  // Custom Post Type: works
  register_post_type('works', [
    'labels' => [
      // CPT label (top-level)
      'name'               => 'Works',
      'singular_name'      => 'Work',
      'menu_name'          => 'Works',
      'name_admin_bar'     => 'Work',

      // Submenu / screens
      'add_new'            => '投稿を追加',
      'add_new_item'       => '投稿を追加',
      'edit_item'          => '投稿を編集',
      'new_item'           => '新規投稿',
      'view_item'          => '投稿を表示',
      'search_items'       => '投稿を検索',
      'not_found'          => '投稿が見つかりません。',
      'not_found_in_trash' => 'ゴミ箱に投稿はありません。',
      'all_items'          => '投稿一覧',
    ],
    'public'        => true,
    'has_archive'   => true,
    // with_front: false — otherwise the sitewide permalink structure's leading
    // static segment (currently "/writings/") would be prepended to this slug.
    'rewrite'       => ['slug' => 'works', 'with_front' => false],
    'menu_position' => 20,
    'menu_icon'     => 'dashicons-portfolio',
    'supports'      => ['title', 'excerpt', 'thumbnail', 'revisions'],
    'show_in_rest'  => true,
  ]);

  // Register Works meta for the block editor (REST).
  // Without this, meta box values (e.g. nor_work_no) may not be included in publish/update requests,
  // and hard validation may not see the submitted values.
  $meta_args = [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'sanitize_text_field',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ];

  register_post_meta('works', 'nor_work_no', $meta_args);
  register_post_meta('works', 'nor_tagline', $meta_args);
  register_post_meta('works', 'nor_summary_en', [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'wp_kses_post',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ]);

  register_post_meta('works', 'nor_content_mode', $meta_args);
  register_post_meta('works', 'nor_gallery_ids', $meta_args);

  register_post_meta('works', 'nor_main_client_id', [
    'type'              => 'integer',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'absint',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ]);

  // Stored as comma-separated string ("1,2,3") in DB.
  register_post_meta('works', 'nor_end_client_ids', $meta_args);

  // Taxonomy: Categories (works)
  register_taxonomy('work_category', ['works'], [
    'labels' => [
      'name'              => 'カテゴリー',
      'singular_name'     => 'カテゴリー',
      'menu_name'         => 'カテゴリー',
      'all_items'         => 'カテゴリー一覧',
      'edit_item'         => 'カテゴリーを編集',
      'view_item'         => 'カテゴリーを表示',
      'update_item'       => 'カテゴリーを更新',
      'add_new_item'      => 'カテゴリーを追加',
      'new_item_name'     => '新規カテゴリー名',
      'search_items'      => 'カテゴリーを検索',
      'parent_item'       => '親カテゴリー',
      'parent_item_colon' => '親カテゴリー:',
    ],
    'public'       => true,
    'hierarchical' => true,
    'rewrite'      => ['slug' => 'categories', 'with_front' => false],
    'show_in_rest' => true,
    'show_ui'      => true,
  ]);

  // Taxonomy: Tags (works)  ※/tags/ 配下で運用（親子＝タググループ＋子タグ）
  register_taxonomy('work_tag', ['works'], [
    'labels' => [
      'name'              => 'タグ',
      'singular_name'     => 'タグ',
      'menu_name'         => 'タグ',
      'all_items'         => 'タグ一覧',
      'edit_item'         => 'タグを編集',
      'view_item'         => 'タグを表示',
      'update_item'       => 'タグを更新',
      'add_new_item'      => 'タグを追加',
      'new_item_name'     => '新規タグ名',
      'search_items'      => 'タグを検索',
      'parent_item'       => '親タグ',
      'parent_item_colon' => '親タグ:',
    ],
    'public'       => true,
    'hierarchical' => true,
    'rewrite'      => ['slug' => 'tags', 'hierarchical' => true, 'with_front' => false],
    'show_in_rest' => true,
    'show_ui'      => true,
  ]);

  // Taxonomy: Clients
  register_taxonomy('work_client', ['works'], [
    'labels'       => [
      'name'              => 'クライアント',
      'singular_name'     => 'クライアント',
      'menu_name'         => 'クライアント',
      'all_items'         => 'クライアント一覧',
      'edit_item'         => 'クライアントを編集',
      'view_item'         => 'クライアントを表示',
      'update_item'       => 'クライアントを更新',
      'add_new_item'      => 'クライアントを追加',
      'new_item_name'     => '新規クライアント名',
      'search_items'      => 'クライアントを検索',
    ],
    'public'       => true,
    'hierarchical' => false,
    'rewrite'      => ['slug' => 'clients', 'with_front' => false],
    'show_in_rest' => true,
    'show_ui'      => true,
  ]);

  // Taxonomy: Industries
  // NOTE: Industries are managed as terms, but are NOT directly assigned on each Work.
  // They are selected per Client (work_client meta: nor_industry).
  register_taxonomy('work_industry', ['works'], [
    'labels'       => [
      'name'          => '業種',
      'singular_name' => '業種',
      'menu_name'     => '業種',
      'all_items'     => '業種一覧',
      'edit_item'     => '業種を編集',
      'view_item'     => '業種を表示',
      'update_item'   => '業種を更新',
      'add_new_item'  => '業種を追加',
      'new_item_name' => '新規業種名',
      'search_items'  => '業種を検索',
    ],
    'public'       => true,
    'hierarchical' => false,
    'rewrite'      => ['slug' => 'works/industries', 'with_front' => false],
    'show_in_rest' => true,
    'show_ui'      => true,
    // Hide the taxonomy meta box on the Works edit screen.
    'meta_box_cb'  => false,
  ]);
});

// Ensure taxonomy-object bindings (safety for load order / editor UI)
add_action('init', function () {
  register_taxonomy_for_object_type('work_client', 'works');
  register_taxonomy_for_object_type('work_industry', 'works');
}, 20);

// Admin: hide legacy taxonomy meta boxes on Works edit screen (safety)
add_action('add_meta_boxes', function () {
  // Default meta box IDs:
  // - hierarchical taxonomy: {$tax}div
  // - non-hierarchical taxonomy: tagsdiv-{$tax}

  // Industries are selected per Client term meta; do not allow direct assignment on Works.
  remove_meta_box('tagsdiv-work_industry', 'works', 'side');
  remove_meta_box('work_industrydiv', 'works', 'side');

  // Clients are now managed via the custom meta boxes (Main / End).
  // Hide the legacy taxonomy metabox to avoid double-input.
  remove_meta_box('tagsdiv-work_client', 'works', 'side');
  remove_meta_box('work_clientdiv', 'works', 'side');
}, 20);

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
  $theme_uri = get_stylesheet_directory_uri();

  $asset_ver = static function (string $relative, string $fallback): string {
    $path = trailingslashit(get_stylesheet_directory()) . ltrim($relative, '/');
    $mtime = @filemtime($path);
    return $mtime ? (string) $mtime : $fallback;
  };
  $fallback_ver = (string) wp_get_theme()->get('Version');

  // CSS (tokens/base/ui)
  wp_enqueue_style('nor-tokens', $theme_uri . '/assets/css/nor.tokens.css', [], $asset_ver('assets/css/nor.tokens.css', $fallback_ver));
  wp_enqueue_style('nor-base',   $theme_uri . '/assets/css/nor.base.css',   ['nor-tokens'], $asset_ver('assets/css/nor.base.css', $fallback_ver));
  wp_enqueue_style('nor-ui',     $theme_uri . '/assets/css/nor.ui.css',     ['nor-base'], $asset_ver('assets/css/nor.ui.css', $fallback_ver));

  // JS
  wp_enqueue_script('nor-js', $theme_uri . '/assets/js/nor.js', [], $asset_ver('assets/js/nor.js', $fallback_ver), true);
});

// Admin: customize columns for Works list
add_filter('manage_works_posts_columns', function ($cols) {
  // 既存の列をベースに、差し込みたい順で組み替え
  $new = [];

  // チェックボックス
  $new['cb'] = $cols['cb'] ?? '';

  // タイトル
  $new['title'] = $cols['title'] ?? 'Title';

  // 追加列
  $new['work_content_mode'] = 'Content mode';
  $new['work_category'] = 'Category';
  $new['work_tag'] = 'Tags';

  // 日付（既存があれば最後へ）
  $new['date'] = $cols['date'] ?? 'Date';

  return $new;
});

/**
 * Admin: work_client term meta (nor_yomi)
 * - Adds "よみ" field to Add/Edit screens
 * - Saves to term meta key: nor_yomi
 */

// Add form (new term)
add_action('work_client_add_form_fields', function () {
  ?>
  <div class="form-field term-nor-desc-en-wrap">
    <label for="nor_desc_en">説明（EN）</label>
    <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="40" class="large-text"></textarea>
    <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
  </div>

  <div class="form-field term-nor-industry-wrap">
    <label for="nor_industry">業種</label>
    <?php
      $industries = get_terms([
        'taxonomy'   => 'work_industry',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
      ]);
    ?>
    <select name="nor_industry" id="nor_industry" class="postform">
      <option value="">—</option>
      <?php if (!is_wp_error($industries) && !empty($industries)) : ?>
        <?php foreach ($industries as $ind) : ?>
          <option value="<?php echo esc_attr((int) $ind->term_id); ?>"><?php echo esc_html($ind->name); ?></option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
    <p class="description">このクライアントに紐づく業種を1つ選択します。</p>
  </div>

  <div class="form-field term-nor-tagline-wrap">
    <label for="nor_tagline">タグライン</label>
    <input name="nor_tagline" id="nor_tagline" type="text" value="" class="regular-text" />
    <p class="description">タイトルの下に表示される短いタグラインです。</p>
  </div>

  <div class="form-field term-nor-yomi-wrap">
    <label for="nor_yomi">よみ</label>
    <input name="nor_yomi" id="nor_yomi" type="text" value="" />
    <p class="description">五十音インデックス用。例：さくらいんたーねっと / びっとすたー / おず</p>
  </div>

  <div class="form-field term-nor-mask-enabled-wrap">
    <label for="nor_mask_enabled">
      <input name="nor_mask_enabled" id="nor_mask_enabled" type="checkbox" value="1" />
      正式名称を伏せる
    </label>
    <p class="description">ONにすると、公開側では伏せ名称・伏せスラッグを優先表示します。OFFにしても入力値は保持されます。</p>
  </div>

  <div class="form-field term-nor-mask-name-wrap">
    <label for="nor_mask_name">伏せ名称</label>
    <input name="nor_mask_name" id="nor_mask_name" type="text" value="" class="regular-text" />
    <p class="description">公開時に表示する名称です。未入力時は正式名称を使います。</p>
  </div>

  <div class="form-field term-nor-mask-slug-wrap">
    <label for="nor_mask_slug">伏せスラッグ</label>
    <input name="nor_mask_slug" id="nor_mask_slug" type="text" value="" class="regular-text" />
    <p class="description">公開URL用スラッグ。英数字とハイフン推奨（例: client-001）。未入力時は伏せ名称から自動生成します。</p>
  </div>
  <?php
});

// Edit form (existing term)
add_action('work_client_edit_form_fields', function ($term) {
  $desc_en = get_term_meta($term->term_id, 'nor_desc_en', true);
  $desc_en = is_string($desc_en) ? $desc_en : '';

  $industry_id = get_term_meta($term->term_id, 'nor_industry', true);
  $industry_id = is_numeric($industry_id) ? (int) $industry_id : 0;

  $industries = get_terms([
    'taxonomy'   => 'work_industry',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);

  $tagline = get_term_meta($term->term_id, 'nor_tagline', true);
  $tagline = is_string($tagline) ? $tagline : '';

  $yomi = get_term_meta($term->term_id, 'nor_yomi', true);
  $yomi = is_string($yomi) ? $yomi : '';

  $mask_enabled = (nor_get_term_meta_text((int) $term->term_id, 'nor_mask_enabled') === '1');
  $mask_name = nor_get_term_meta_text((int) $term->term_id, 'nor_mask_name');
  $mask_slug = sanitize_title(nor_get_term_meta_text((int) $term->term_id, 'nor_mask_slug'));
  ?>
  <tr class="form-field term-nor-desc-en-wrap">
    <th scope="row"><label for="nor_desc_en">説明（EN）</label></th>
    <td>
      <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="50" class="large-text"><?php echo esc_textarea($desc_en); ?></textarea>
      <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
    </td>
  </tr>

  <tr class="form-field term-nor-industry-wrap">
    <th scope="row"><label for="nor_industry">業種</label></th>
    <td>
      <select name="nor_industry" id="nor_industry" class="postform">
        <option value="" <?php selected(0, $industry_id); ?>>—</option>
        <?php if (!is_wp_error($industries) && !empty($industries)) : ?>
          <?php foreach ($industries as $ind) : ?>
            <option value="<?php echo esc_attr((int) $ind->term_id); ?>" <?php selected((int) $ind->term_id, $industry_id); ?>><?php echo esc_html($ind->name); ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <p class="description">このクライアントに紐づく業種を1つ選択します。</p>
    </td>
  </tr>

  <tr class="form-field term-nor-tagline-wrap">
    <th scope="row"><label for="nor_tagline">タグライン</label></th>
    <td>
      <input name="nor_tagline" id="nor_tagline" type="text" value="<?php echo esc_attr($tagline); ?>" class="regular-text" />
      <p class="description">タイトルの下に表示される短いタグラインです。</p>
    </td>
  </tr>

  <tr class="form-field term-nor-yomi-wrap">
    <th scope="row"><label for="nor_yomi">よみ</label></th>
    <td>
      <input name="nor_yomi" id="nor_yomi" type="text" value="<?php echo esc_attr($yomi); ?>" class="regular-text" />
      <p class="description">五十音インデックス用。例：さくらいんたーねっと / びっとすたー / おず</p>
    </td>
  </tr>

  <tr class="form-field term-nor-mask-enabled-wrap">
    <th scope="row"><label for="nor_mask_enabled">正式名称を伏せる</label></th>
    <td>
      <label><input name="nor_mask_enabled" id="nor_mask_enabled" type="checkbox" value="1" <?php checked($mask_enabled); ?> /> 公開側では伏せ名称・伏せスラッグを優先する</label>
      <p class="description">ON時は、このクライアントの表示名とURLを伏せ値で置き換えます。OFFにしても入力値は保持されます。</p>
    </td>
  </tr>

  <tr class="form-field term-nor-mask-name-wrap">
    <th scope="row"><label for="nor_mask_name">伏せ名称</label></th>
    <td>
      <input name="nor_mask_name" id="nor_mask_name" type="text" value="<?php echo esc_attr($mask_name); ?>" class="regular-text" />
      <p class="description">公開時に表示する名称です。未入力時は正式名称を使います。</p>
    </td>
  </tr>

  <tr class="form-field term-nor-mask-slug-wrap">
    <th scope="row"><label for="nor_mask_slug">伏せスラッグ</label></th>
    <td>
      <input name="nor_mask_slug" id="nor_mask_slug" type="text" value="<?php echo esc_attr($mask_slug); ?>" class="regular-text" />
      <p class="description">公開URL用スラッグ。英数字とハイフン推奨（予約語: clients / iot / industries）。未入力時は伏せ名称から自動生成します。</p>
    </td>
  </tr>
  <?php
});


/**
 * Admin: work_client term meta (nor_tagline / nor_desc_en / nor_yomi)
 */

function nor_save_work_client_term_meta_from_post(int $term_id): void {
  if ($term_id <= 0) {
    return;
  }

  // Tagline
  if (isset($_POST['nor_tagline'])) {
    $raw = wp_unslash($_POST['nor_tagline']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_term_meta($term_id, 'nor_tagline');
    } else {
      update_term_meta($term_id, 'nor_tagline', $val);
    }
  }

  // Description (EN)
  if (isset($_POST['nor_desc_en'])) {
    $raw = wp_unslash($_POST['nor_desc_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_term_meta($term_id, 'nor_desc_en');
    } else {
      update_term_meta($term_id, 'nor_desc_en', $val);
    }
  }

  // Industry (single)
  if (isset($_POST['nor_industry'])) {
    $raw = wp_unslash($_POST['nor_industry']);
    $val = is_numeric($raw) ? (int) $raw : 0;
    if ($val <= 0) {
      delete_term_meta($term_id, 'nor_industry');
    } else {
      update_term_meta($term_id, 'nor_industry', $val);
    }
  }

  // Yomi
  if (isset($_POST['nor_yomi'])) {
    $raw = wp_unslash($_POST['nor_yomi']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_term_meta($term_id, 'nor_yomi');
    } else {
      update_term_meta($term_id, 'nor_yomi', $val);
    }
  }

  // Masking: public name / slug override
  $mask_enabled = (isset($_POST['nor_mask_enabled']) && (string) wp_unslash($_POST['nor_mask_enabled']) === '1');
  if (!$mask_enabled) {
    // Keep saved mask_name/mask_slug for future re-enable.
    delete_term_meta($term_id, 'nor_mask_enabled');
  } else {
    update_term_meta($term_id, 'nor_mask_enabled', '1');
  }

  // Save mask fields regardless of checkbox state.
  $mask_name = '';
  if (isset($_POST['nor_mask_name'])) {
    $mask_name = trim((string) sanitize_text_field((string) wp_unslash($_POST['nor_mask_name'])));
  }
  if ($mask_name === '') {
    delete_term_meta($term_id, 'nor_mask_name');
  } else {
    update_term_meta($term_id, 'nor_mask_name', $mask_name);
  }

  $mask_slug = '';
  if (isset($_POST['nor_mask_slug'])) {
    $mask_slug = sanitize_title((string) wp_unslash($_POST['nor_mask_slug']));
  }
  if ($mask_slug === '' && $mask_name !== '') {
    $mask_slug = sanitize_title($mask_name);
  }
  if ($mask_slug !== '' && !nor_is_work_client_public_slug_available($mask_slug, $term_id)) {
    $mask_slug = '';
  }
  if ($mask_slug === '') {
    delete_term_meta($term_id, 'nor_mask_slug');
  } else {
    update_term_meta($term_id, 'nor_mask_slug', $mask_slug);
  }
}

add_action('created_work_client', function ($term_id) {
  nor_save_work_client_term_meta_from_post((int) $term_id);
});

add_action('edited_work_client', function ($term_id) {
  nor_save_work_client_term_meta_from_post((int) $term_id);
});

/**
 * Admin: work_category term meta (nor_tagline)
 * - Adds "Tagline" field to Add/Edit screens
 * - Saves to term meta key: nor_tagline
 */

function nor_work_category_tagline_add_form_fields() {
  ?>
  <div class="form-field term-nor-tagline-wrap">
    <label for="nor_tagline">タグライン</label>
    <input name="nor_tagline" id="nor_tagline" type="text" value="" class="regular-text" />
    <p class="description">タイトルの下に表示される短いタグラインです。</p>
  </div>
  <?php
}
add_action('work_category_add_form_fields', 'nor_work_category_tagline_add_form_fields', 20);

function nor_work_category_tagline_edit_form_fields($term) {
  $value = get_term_meta($term->term_id, 'nor_tagline', true);
  $value = is_string($value) ? $value : '';
  ?>
  <tr class="form-field term-nor-tagline-wrap">
    <th scope="row"><label for="nor_tagline">タグライン</label></th>
    <td>
      <input name="nor_tagline" id="nor_tagline" type="text" value="<?php echo esc_attr($value); ?>" class="regular-text" />
      <p class="description">タイトルの下に表示される短いタグラインです。</p>
    </td>
  </tr>
  <?php
}
add_action('work_category_edit_form_fields', 'nor_work_category_tagline_edit_form_fields', 20);

// Save meta (created)
add_action('created_work_category', function ($term_id) {
  if (!isset($_POST['nor_tagline'])) return;
  $raw = wp_unslash($_POST['nor_tagline']);
  $val = sanitize_text_field($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_tagline');
    return;
  }
  update_term_meta($term_id, 'nor_tagline', $val);
});

// Save meta (edited)

add_action('edited_work_category', function ($term_id) {
  if (!isset($_POST['nor_tagline'])) return;
  $raw = wp_unslash($_POST['nor_tagline']);
  $val = sanitize_text_field($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_tagline');
    return;
  }
  update_term_meta($term_id, 'nor_tagline', $val);
});



/**
 * Admin: work_category term meta (nor_desc_en)
 * - Adds "Description (EN)" field to Add/Edit screens
 * - Saves to term meta key: nor_desc_en
 */

// Add form (new term)
add_action('work_category_add_form_fields', function () {
  ?>
  <div class="form-field term-nor-desc-en-wrap">
    <label for="nor_desc_en">説明（EN）</label>
    <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="40" class="large-text"></textarea>
    <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
  </div>
  <?php
});

// Edit form (existing term)
add_action('work_category_edit_form_fields', function ($term) {
  $value = get_term_meta($term->term_id, 'nor_desc_en', true);
  $value = is_string($value) ? $value : '';
  ?>
  <tr class="form-field term-nor-desc-en-wrap">
    <th scope="row"><label for="nor_desc_en">説明（EN）</label></th>
    <td>
      <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
      <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
    </td>
  </tr>
  <?php
});

// Save meta
add_action('created_work_category', function ($term_id) {
  if (!isset($_POST['nor_desc_en'])) return;
  $raw = wp_unslash($_POST['nor_desc_en']);
  $val = wp_kses_post($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_desc_en');
    return;
  }
  update_term_meta($term_id, 'nor_desc_en', $val);
});

add_action('edited_work_category', function ($term_id) {
  if (!isset($_POST['nor_desc_en'])) return;
  $raw = wp_unslash($_POST['nor_desc_en']);
  $val = wp_kses_post($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_desc_en');
    return;
  }
  update_term_meta($term_id, 'nor_desc_en', $val);
});

/**
 * Admin list tables: customize columns for Categories / Tags
 * Columns:
 * - 名前（並び替え） / タグライン / 説明JA / 説明EN / スラッグ / カウント
 */

$nor_admin_render_term_meta_plain = function ($val): string {
  $v = is_string($val) ? trim($val) : '';
  return ($v === '') ? '—' : esc_html($v);
};

$nor_admin_render_term_meta_multiline = function ($val): string {
  $v = is_string($val) ? trim($val) : '';
  if ($v === '') return '—';
  $v = wp_strip_all_tags($v);
  $v = str_replace(["\r\n", "\r"], "\n", $v);
  $v = preg_replace("/\n{3,}/", "\n\n", $v);
  return nl2br(esc_html($v));
};

 $nor_register_tax_admin_columns = function (string $tax) use ($nor_admin_render_term_meta_plain, $nor_admin_render_term_meta_multiline) {
  // Columns
  add_filter("manage_edit-{$tax}_columns", function ($cols) use ($tax) {
    // Keep checkbox if present
    $cb = $cols['cb'] ?? '';

    // Default (Categories)
    $base = [
      'cb'          => $cb,
      'name'        => '名前',
      'description' => '説明',
      'nor_desc_en' => '説明（EN）',
      'nor_tagline' => 'タグライン',
      'slug'        => 'スラッグ',
      'posts'       => 'カウント',
    ];

    // Tags only: show hierarchical parent column
    if ($tax === 'work_tag') {
      $base = [
        'cb'          => $cb,
        'name'        => '名前',
        'parent'      => '親タグ',
        'description' => '説明',
        'nor_desc_en' => '説明（EN）',
        'nor_tagline' => 'タグライン',
        'slug'        => 'スラッグ',
        'posts'       => 'カウント',
      ];
    }

    // Clients only: add Yomi and Industry columns and order as requested
    if ($tax === 'work_client') {
      $base = [
        'cb'           => $cb,
        'name'         => '名前',
        'slug'         => 'スラッグ',
        'description'  => '説明',
        'nor_desc_en'  => '説明（EN）',
        'nor_tagline'  => 'タグライン',
        'nor_industry' => '業種',
        'nor_yomi'     => 'よみ',
        'posts'        => 'カウント',
      ];
    }

    return $base;
  });

  // Values
  add_filter("manage_{$tax}_custom_column", function ($out, $column_name, $term_id) use ($nor_admin_render_term_meta_plain, $nor_admin_render_term_meta_multiline, $tax) {
    if ($column_name === 'parent' && $tax === 'work_tag') {
      $term = get_term((int) $term_id, $tax);
      if (!$term || is_wp_error($term) || empty($term->parent)) return '—';
      $parent = get_term((int) $term->parent, $tax);
      if (!$parent || is_wp_error($parent)) return '—';
      return esc_html($parent->name);
    }

    if ($column_name === 'nor_tagline') {
      return $nor_admin_render_term_meta_plain(get_term_meta($term_id, 'nor_tagline', true));
    }

    if ($column_name === 'nor_industry' && $tax === 'work_client') {
      $industry_id = get_term_meta($term_id, 'nor_industry', true);
      $industry_id = is_numeric($industry_id) ? (int) $industry_id : 0;
      if ($industry_id <= 0) return '—';

      $industry = get_term($industry_id, 'work_industry');
      if (!$industry || is_wp_error($industry)) return '—';
      return esc_html($industry->name);
    }

    if ($column_name === 'nor_desc_en') {
      return $nor_admin_render_term_meta_multiline(get_term_meta($term_id, 'nor_desc_en', true));
    }

    if ($column_name === 'nor_yomi') {
      return $nor_admin_render_term_meta_plain(get_term_meta($term_id, 'nor_yomi', true));
    }

    // default
    return $out;
  }, 10, 3);
};

// Apply to Categories + Tags + Clients
$nor_register_tax_admin_columns('work_category');
$nor_register_tax_admin_columns('work_tag');
$nor_register_tax_admin_columns('work_client');

/**
 * Admin: term meta (nor_desc_en) for tag-group taxonomies
 * - Adds "Description (EN)" field to Add/Edit screens
 * - Saves to term meta key: nor_desc_en
 *
 * Targets:
 * - work_document_type
 * - work_site_type
 * - work_role
 * - work_tool
 */

$nor_register_term_desc_en = function (string $tax, string $help_label) {
  // Add form (new term)
  add_action("{$tax}_add_form_fields", function () use ($help_label) {
    ?>
    <div class="form-field term-nor-desc-en-wrap">
      <label for="nor_desc_en">説明（EN）</label>
      <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="40" class="large-text"></textarea>
      <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
    </div>
    <?php
  });

  // Edit form (existing term)
  add_action("{$tax}_edit_form_fields", function ($term) use ($help_label) {
    $value = get_term_meta($term->term_id, 'nor_desc_en', true);
    $value = is_string($value) ? $value : '';
    ?>
    <tr class="form-field term-nor-desc-en-wrap">
      <th scope="row"><label for="nor_desc_en">説明（EN）</label></th>
      <td>
        <textarea name="nor_desc_en" id="nor_desc_en" rows="5" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">英語の説明です。日本語は組み込みの説明フィールドに入力します。</p>
      </td>
    </tr>
    <?php
  });

  // Save meta (created)
  add_action("created_{$tax}", function ($term_id) {
    if (!isset($_POST['nor_desc_en'])) return;
    $raw = wp_unslash($_POST['nor_desc_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_term_meta($term_id, 'nor_desc_en');
      return;
    }
    update_term_meta($term_id, 'nor_desc_en', $val);
  });

  // Save meta (edited)
  add_action("edited_{$tax}", function ($term_id) {
    if (!isset($_POST['nor_desc_en'])) return;
    $raw = wp_unslash($_POST['nor_desc_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_term_meta($term_id, 'nor_desc_en');
      return;
    }
    update_term_meta($term_id, 'nor_desc_en', $val);
  });
};

// Register for tag taxonomy
add_action('init', function () use ($nor_register_term_desc_en) {
  $nor_register_term_desc_en('work_tag', 'Tags');
}, 30);


/**
 * Admin: work_tag term meta (nor_tagline)
 * - Adds "Tagline" field to Add/Edit screens
 * - Saves to term meta key: nor_tagline
 */

function nor_work_tag_tagline_add_form_fields() {
  ?>
  <div class="form-field term-nor-tagline-wrap">
    <label for="nor_tagline">タグライン</label>
    <input name="nor_tagline" id="nor_tagline" type="text" value="" class="regular-text" />
    <p class="description">タイトルの下に表示される短いタグラインです。</p>
  </div>
  <?php
}
add_action('work_tag_add_form_fields', 'nor_work_tag_tagline_add_form_fields', 20);

function nor_work_tag_tagline_edit_form_fields($term) {
  $value = get_term_meta($term->term_id, 'nor_tagline', true);
  $value = is_string($value) ? $value : '';
  ?>
  <tr class="form-field term-nor-tagline-wrap">
    <th scope="row"><label for="nor_tagline">タグライン</label></th>
    <td>
      <input name="nor_tagline" id="nor_tagline" type="text" value="<?php echo esc_attr($value); ?>" class="regular-text" />
      <p class="description">タイトルの下に表示される短いタグラインです。</p>
    </td>
  </tr>
  <?php
}
add_action('work_tag_edit_form_fields', 'nor_work_tag_tagline_edit_form_fields', 20);

// Save meta (created)
add_action('created_work_tag', function ($term_id) {
  if (!isset($_POST['nor_tagline'])) return;
  $raw = wp_unslash($_POST['nor_tagline']);
  $val = sanitize_text_field($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_tagline');
    return;
  }
  update_term_meta($term_id, 'nor_tagline', $val);
});

// Save meta (edited)
add_action('edited_work_tag', function ($term_id) {
  if (!isset($_POST['nor_tagline'])) return;
  $raw = wp_unslash($_POST['nor_tagline']);
  $val = sanitize_text_field($raw);
  $val = trim($val);
  if ($val === '') {
    delete_term_meta($term_id, 'nor_tagline');
    return;
  }
  update_term_meta($term_id, 'nor_tagline', $val);
});

add_action('manage_works_posts_custom_column', function ($col, $post_id) {
  if ($col === 'work_content_mode') {
    $mode = get_post_meta($post_id, 'nor_content_mode', true);
    $mode = is_string($mode) ? trim($mode) : '';
    echo ($mode === 'full') ? 'Full-content' : 'Text-only';
    return;
  }

  if ($col === 'work_category') {
    $terms = get_the_terms($post_id, 'work_category');
    if (!empty($terms) && !is_wp_error($terms)) {
      echo esc_html($terms[0]->name); // まずは先頭1つだけ
    } else {
      echo '—';
    }
    return;
  }

  if ($col === 'work_tag') {
    $terms = get_the_terms($post_id, 'work_tag');
    if (!empty($terms) && !is_wp_error($terms)) {
      $names = array_map(fn($t) => $t->name, $terms);
      echo esc_html(implode(', ', $names));
    } else {
      echo '—';
    }
    return;
  }
}, 10, 2);

// 並び替え可能にする（任意：まずはcontent modeだけ）
add_filter('manage_edit-works_sortable_columns', function ($cols) {
  $cols['work_content_mode'] = 'work_content_mode';
  return $cols;
});

// Works archive paging
// - Home (front-page.php) shows the latest fixed count (treated as page 1).
// - /works/ is redirected to Home.
// - /works/page/2/ starts from item (home count + 1) and shows configurable per-page count.
add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query()) return;

  // Year archives (/archives/{year}/): configurable pagination without Works-archive offset logic.
  $nor_year = (int) $q->get('nor_year');
  if ($nor_year > 0) {
    $q->set('posts_per_page', nor_get_works_archive_per_page());
    $q->set('orderby', 'date');
    $q->set('order', 'DESC');
    $q->set('offset', 0);
    return;
  }

  // Works archive
  if ($q->is_post_type_archive('works')) {
    $q->set('orderby', 'date');
    $q->set('order', 'DESC');

    // Page 1 is Home. We keep /works/ redirected, so only paged archives matter here.
    $paged = (int) $q->get('paged');
    $home_count = nor_get_works_home_count();
    $per_page   = nor_get_works_archive_per_page();

    if ($paged < 2) {
      // If something bypasses template_redirect, keep it consistent.
      $q->set('posts_per_page', $home_count);
      return;
    }

    // /works/page/2/ => offset home_count
    // /works/page/3/ => offset home_count + per_page, ...
    $offset = $home_count + (($paged - 2) * $per_page);

    $q->set('posts_per_page', $per_page);
    $q->set('offset', $offset);
    return;
  }

  // Tax archives
  if ($q->is_tax([
    'work_category',
    'work_tag',
    'work_client',
    'work_industry',
  ])) {
    $q->set('posts_per_page', nor_get_works_archive_per_page());
    $q->set('orderby', 'date');
    $q->set('order', 'DESC');
  }
});

// Fix max_num_pages for the Works archive when using offset.
// We want the total page count to include the Home page as page 1.
// Total pages = 1 + ceil((total_works - home_count) / per_page)
// WordPress computes max_num_pages from found_posts/posts_per_page, so we fake found_posts accordingly.
add_filter('found_posts', function ($found, $q) {
  if (is_admin() || !$q->is_main_query()) return $found;
  if (!$q->is_post_type_archive('works')) return $found;
  if ((int) $q->get('nor_year') > 0) return $found;

  $paged = (int) $q->get('paged');
  if ($paged < 2) return $found;

  $total = nor_get_published_works_count();
  $home_count = nor_get_works_home_count();
  $per_page = nor_get_works_archive_per_page();

  // Make max pages equal to: 1 + ceil((total-home_count)/per_page)
  // For posts_per_page=per_page, setting found_posts to (total - home_count + per_page) yields that page count.
  return max(0, $total - $home_count + $per_page);
}, 10, 2);

// Archives (year): rewrite /archives/2026/ -> works + year=2026
add_filter('query_vars', function ($vars) {
  $vars[] = 'nor_year';
  return $vars;
});

add_action('init', function () {
  add_rewrite_rule('^archives/([0-9]{4})/?$', 'index.php?post_type=works&year=$matches[1]&nor_year=$matches[1]', 'top');
  add_rewrite_rule('^archives/([0-9]{4})/page/([0-9]{1,})/?$', 'index.php?post_type=works&year=$matches[1]&paged=$matches[2]&nor_year=$matches[1]', 'top');
});

// Writings list pagination: /writings/page/{n}/ -> pagename=writings + paged=n.
// WordPress's default page rewrite rule would otherwise resolve this to the
// `page` query var (the <!--nextpage--> content-splitting mechanism), not
// list pagination — this dedicated, top-priority rule takes precedence and
// does not affect the standard /writings/{slug}/ post permalink.
add_action('init', function () {
  add_rewrite_rule('^writings/page/([0-9]{1,})/?$', 'index.php?pagename=writings&paged=$matches[1]', 'top');
});

add_filter('template_include', function ($template) {
  $year = get_query_var('nor_year');
  if ($year) {
    $candidate = locate_template('archive-works-year.php');
    if ($candidate) return $candidate;
  }
  return $template;
});

add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
  // Default behavior
  if (!empty($item->current)) {
    $atts['aria-current'] = 'page';
    return $atts;
  }

  // Custom: keep "Clients" highlighted across its child pages (/clients/iot/, /clients/industries/, ...)
  if (!is_page()) return $atts;

  $clients_page = get_page_by_path('clients');
  $clients_id = ($clients_page instanceof WP_Post) ? (int) $clients_page->ID : 0;
  if ($clients_id <= 0) return $atts;

  $qo = get_queried_object();
  if (!($qo instanceof WP_Post)) return $atts;

  $qid = (int) $qo->ID;
  $anc = get_post_ancestors($qo);
  $is_clients_tree = ($qid === $clients_id) || (is_array($anc) && in_array($clients_id, $anc, true));
  if (!$is_clients_tree) return $atts;

  if (!isset($item->url)) return $atts;

  $item_url = (string) ($item->url ?? '');
  if ($item_url === '') return $atts;

  $item_path = wp_parse_url($item_url, PHP_URL_PATH);
  $item_path = is_string($item_path) ? untrailingslashit($item_path) : '';

  // Target any menu item that lives under /clients/ (e.g. /clients/, /clients/iot/, /clients/industries/)
  if ($item_path !== '' && (strpos($item_path . '/', '/clients/') === 0)) {
    $atts['aria-current'] = 'page';
  }

  return $atts;
}, 10, 3);

add_filter('nav_menu_css_class', function ($classes, $item, $args) {
  // Mirror the same logic as aria-current, but via CSS classes
  if (!is_page()) return $classes;

  $clients_page = get_page_by_path('clients');
  $clients_id = ($clients_page instanceof WP_Post) ? (int) $clients_page->ID : 0;
  if ($clients_id <= 0) return $classes;

  $qo = get_queried_object();
  if (!($qo instanceof WP_Post)) return $classes;

  $qid = (int) $qo->ID;
  $anc = get_post_ancestors($qo);
  $is_clients_tree = ($qid === $clients_id) || (is_array($anc) && in_array($clients_id, $anc, true));
  if (!$is_clients_tree) return $classes;

  if (!isset($item->url)) return $classes;

  $item_url = (string) ($item->url ?? '');
  if ($item_url === '') return $classes;

  $item_path = wp_parse_url($item_url, PHP_URL_PATH);
  $item_path = is_string($item_path) ? untrailingslashit($item_path) : '';

  if ($item_path !== '' && (strpos($item_path . '/', '/clients/') === 0)) {
    $classes[] = 'current-menu-item';
    $classes[] = 'current_page_item';
    $classes[] = 'current-menu-ancestor';
    $classes[] = 'current_page_ancestor';
  }

  return $classes;
}, 10, 3);

// Writing detail (post_type = post) is information-architecturally a child of
// the Writings page, but posts have no post_parent/ancestor relationship to
// pages in WP's data model, so core's own current-menu-ancestor logic never
// fires here. Identify the Writings menu item by its linked object (a Page),
// not by comparing URL strings.
add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
  if (!is_singular('post')) return $atts;
  if (!isset($item->object, $item->type, $item->object_id)) return $atts;
  if ($item->type !== 'post_type' || $item->object !== 'page') return $atts;

  $writings_page = get_page_by_path('writings');
  $writings_id = ($writings_page instanceof WP_Post) ? (int) $writings_page->ID : 0;
  if ($writings_id <= 0) return $atts;

  if ((int) $item->object_id === $writings_id) {
    $atts['aria-current'] = 'page';
  }

  return $atts;
}, 10, 3);

add_filter('nav_menu_css_class', function ($classes, $item, $args) {
  if (!is_singular('post')) return $classes;
  if (!isset($item->object, $item->type, $item->object_id)) return $classes;
  if ($item->type !== 'post_type' || $item->object !== 'page') return $classes;

  $writings_page = get_page_by_path('writings');
  $writings_id = ($writings_page instanceof WP_Post) ? (int) $writings_page->ID : 0;
  if ($writings_id <= 0) return $classes;

  if ((int) $item->object_id === $writings_id) {
    $classes[] = 'current-menu-item';
    $classes[] = 'current_page_item';
    $classes[] = 'current-menu-ancestor';
    $classes[] = 'current_page_ancestor';
  }

  return $classes;
}, 10, 3);

// NOTE:
// /categories/ and /tags/ are handled as normal WordPress Pages.
// - Page templates: page-categories.php, page-tags.php
// - Term archives:
//   - work_category terms: /categories/{term-slug}/ via taxonomy-work_category.php
//   - work_tag terms: /tags/{term-slug}/（子は /tags/{parent}/{child}/） via taxonomy-work_tag.php

/**
 * Admin: Page meta for landing pages
 * - Adds Tagline + Description (JP/EN) fields to the Page edit screen
 * - Saves to post meta keys:
 *   - nor_tagline
 *   - nor_desc_ja
 *   - nor_desc_en
 *
 * Target pages (by slug):
 * - categories
 * - tags
 * - clients
 * - iot
 * - industries
 */
add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;

  $slug = $post->post_name;
  $targets = ['categories', 'tags', 'archives', 'clients', 'iot', 'industries', 'search', 'about', 'policies', 'notes', 'faqs', 'contact', 'writings'];
  if (!in_array($slug, $targets, true)) return;

  add_meta_box(
    'nor_landing_page_copy',
    'ページタグライン／説明文（JA & EN）',
    function ($post) use ($slug) {
      $tagline = get_post_meta($post->ID, 'nor_tagline', true);
      $desc_ja = get_post_meta($post->ID, 'nor_desc_ja', true);
      $desc_en = get_post_meta($post->ID, 'nor_desc_en', true);

      $section_h2_ja = get_post_meta($post->ID, 'nor_section_h2_ja', true);
      $section_h2_en = get_post_meta($post->ID, 'nor_section_h2_en', true);

      $tagline = is_string($tagline) ? $tagline : '';
      $desc_ja = is_string($desc_ja) ? $desc_ja : '';
      $desc_en = is_string($desc_en) ? $desc_en : '';

      $section_h2_ja = is_string($section_h2_ja) ? $section_h2_ja : '';
      $section_h2_en = is_string($section_h2_en) ? $section_h2_en : '';

      wp_nonce_field('nor_landing_page_copy_save', 'nor_landing_page_copy_nonce');

      echo '<p class="description">AboutのHeroで使用されます。タグは使用せず、改行で入力してください。改行は <code>Desktop / Tablet</code> のみで反映され、<code>Mobile</code> では改行されません。</p>';

      echo '<p><label for="nor_tagline"><strong>タグライン</strong></label></p>';
      echo '<textarea name="nor_tagline" id="nor_tagline" rows="3" style="width:100%">' . esc_textarea($tagline) . '</textarea>';

      echo '<p style="margin-top:12px;"><label for="nor_desc_ja"><strong>説明文（JA）</strong></label></p>';
      echo '<textarea name="nor_desc_ja" id="nor_desc_ja" rows="5" style="width:100%">' . esc_textarea($desc_ja) . '</textarea>';

      echo '<p style="margin-top:12px;"><label for="nor_desc_en"><strong>説明文（EN）</strong></label></p>';
      echo '<textarea name="nor_desc_en" id="nor_desc_en" rows="5" style="width:100%">' . esc_textarea($desc_en) . '</textarea>';

      echo '<p style="margin-top:12px;"><label for="nor_section_h2_ja"><strong>セクションタイトル（JA）</strong></label></p>';
      echo '<input name="nor_section_h2_ja" id="nor_section_h2_ja" type="text" class="regular-text" value="' . esc_attr($section_h2_ja) . '" />';

      echo '<p style="margin-top:12px;"><label for="nor_section_h2_en"><strong>セクションタイトル（EN）</strong></label></p>';
      echo '<input name="nor_section_h2_en" id="nor_section_h2_en" type="text" class="regular-text" value="' . esc_attr($section_h2_en) . '" />';

      echo '<div class="description" style="margin-top:16px; padding:10px 12px; background-color:#f5f5f5; border:1px solid #c9c9c9;">';
      echo '<p style="margin:0 0 6px;">Stored as post meta: nor_tagline / nor_desc_ja / nor_desc_en</p>';
      echo '<p style="margin:0;">Stored as post meta: nor_section_h2_ja / nor_section_h2_en</p>';
      echo '</div>';
    },
    'page',
    'normal',
    'default'
  );
});

add_action('save_post_page', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $slug = get_post_field('post_name', $post_id);
  $targets = ['categories', 'tags', 'archives', 'clients', 'iot', 'industries', 'search', 'about', 'policies', 'notes', 'faqs', 'contact', 'writings'];
  if (!in_array($slug, $targets, true)) return;

  if (!isset($_POST['nor_landing_page_copy_nonce']) || !wp_verify_nonce($_POST['nor_landing_page_copy_nonce'], 'nor_landing_page_copy_save')) return;

  // Tagline
  if (isset($_POST['nor_tagline'])) {
    $raw = (string) wp_unslash($_POST['nor_tagline']);
    $val = nor_sanitize_inline_html($raw);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_tagline');
    } else {
      update_post_meta($post_id, 'nor_tagline', $val);
    }
  }

  // Description (JP)
  if (isset($_POST['nor_desc_ja'])) {
    $raw = (string) wp_unslash($_POST['nor_desc_ja']);
    $val = nor_sanitize_inline_html($raw);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_desc_ja');
    } else {
      update_post_meta($post_id, 'nor_desc_ja', $val);
    }
  }

  // Description (EN)
  if (isset($_POST['nor_desc_en'])) {
    $raw = (string) wp_unslash($_POST['nor_desc_en']);
    $val = nor_sanitize_inline_html($raw);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_desc_en');
    } else {
      update_post_meta($post_id, 'nor_desc_en', $val);
    }
  }

  // Section title (JA)
  if (isset($_POST['nor_section_h2_ja'])) {
    $raw = wp_unslash($_POST['nor_section_h2_ja']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_section_h2_ja');
    } else {
      update_post_meta($post_id, 'nor_section_h2_ja', $val);
    }
  }

  // Section title (EN)
  if (isset($_POST['nor_section_h2_en'])) {
    $raw = wp_unslash($_POST['nor_section_h2_en']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_section_h2_en');
    } else {
      update_post_meta($post_id, 'nor_section_h2_en', $val);
    }
  }
});

/**
 * Admin: SEO / LLMO meta (Page + Works)
 * - Per-entry override fields for head meta.
 * - Keys:
 *   - nor_meta_title_override
 *   - nor_meta_desc_ja_override
 *   - nor_meta_desc_en_override
 *   - nor_canonical_override
 *   - nor_og_image_override
 *   - nor_og_title_override
 *   - nor_robots_override
 */
function nor_seo_meta_target_page_slugs(): array {
  return ['categories', 'tags', 'archives', 'clients', 'iot', 'industries', 'search', 'about', 'policies', 'notes', 'faqs', 'contact', 'writings'];
}

function nor_is_seo_meta_target_post(WP_Post $post): bool {
  if ($post->post_type === 'works') return true;
  // Writings (standard post) get the same SEO / LLMO meta box as Works.
  if ($post->post_type === 'post') return true;
  if ($post->post_type !== 'page') return false;
  return in_array((string) $post->post_name, nor_seo_meta_target_page_slugs(), true);
}

function nor_seo_meta_normalize_url(string $raw): string {
  $raw = trim($raw);
  if ($raw === '') return '';

  $home_url_raw = (string) home_url('/');
  $home_parts = wp_parse_url($home_url_raw);
  $home_scheme = strtolower((string) ($home_parts['scheme'] ?? 'https'));
  if (!in_array($home_scheme, ['http', 'https'], true)) $home_scheme = 'https';
  $home_host = strtolower((string) ($home_parts['host'] ?? ''));

  if (strpos($raw, '//') === 0) {
    $raw = $home_scheme . ':' . $raw;
  } elseif (str_starts_with($raw, '/')) {
    $raw = home_url($raw);
  }

  if (!preg_match('#^https?://#i', $raw)) return '';

  $parts = wp_parse_url($raw);
  if (!is_array($parts)) return '';

  $scheme = strtolower((string) ($parts['scheme'] ?? $home_scheme));
  if (!in_array($scheme, ['http', 'https'], true)) return '';

  $host = strtolower((string) ($parts['host'] ?? ''));
  if ($host === '') return '';

  $port = isset($parts['port']) ? (int) $parts['port'] : 0;
  $path = (string) ($parts['path'] ?? '/');
  if ($path === '') $path = '/';
  $path = '/' . ltrim($path, '/');
  $path = (string) preg_replace('#/{2,}#', '/', $path);

  $is_home_host = ($home_host !== '' && strcasecmp($host, $home_host) === 0);
  if ($is_home_host) {
    // Keep same scheme as site URL for internal canonical consistency.
    $scheme = $home_scheme;

    // For site pages, prefer trailing slash except obvious file paths.
    $base = basename($path);
    $looks_file = (bool) preg_match('/\.[a-z0-9]{1,8}$/i', $base);
    if ($path !== '/' && !$looks_file) {
      $path = trailingslashit(untrailingslashit($path));
    }
  }

  $normalized = $scheme . '://' . $host;
  if ($port > 0) {
    $is_default_port = (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443));
    if (!$is_default_port) {
      $normalized .= ':' . $port;
    }
  }
  $normalized .= $path;

  return (string) esc_url_raw($normalized);
}

function nor_render_seo_meta_box(WP_Post $post): void {
  if (!nor_is_seo_meta_target_post($post)) return;
  $env_type = function_exists('nor_get_environment_type') ? nor_get_environment_type() : 'production';
  $force_noindex_by_env = ($env_type !== 'production');
  $discourage_search = ((int) get_option('blog_public', 1) === 0);
  $force_noindex = ($force_noindex_by_env || $discourage_search);

  $title_override = get_post_meta($post->ID, 'nor_meta_title_override', true);
  $desc_ja_override = get_post_meta($post->ID, 'nor_meta_desc_ja_override', true);
  $desc_en_override = get_post_meta($post->ID, 'nor_meta_desc_en_override', true);
  $canonical_override = get_post_meta($post->ID, 'nor_canonical_override', true);
  $og_image_override = get_post_meta($post->ID, 'nor_og_image_override', true);
  $og_title_override = get_post_meta($post->ID, 'nor_og_title_override', true);
  $robots_override = get_post_meta($post->ID, 'nor_robots_override', true);

  $title_override = is_string($title_override) ? trim($title_override) : '';
  $desc_ja_override = is_string($desc_ja_override) ? trim($desc_ja_override) : '';
  $desc_en_override = is_string($desc_en_override) ? trim($desc_en_override) : '';
  $canonical_override = is_string($canonical_override) ? trim($canonical_override) : '';
  $og_image_override = is_string($og_image_override) ? trim($og_image_override) : '';
  $og_title_override = is_string($og_title_override) ? trim($og_title_override) : '';
  $robots_override = is_string($robots_override) ? trim($robots_override) : '';

  $is_works = ($post->post_type === 'works');
  // Writings (post) share the exact same "rich" SEO treatment as Works
  // (featured-image OG, excerpt+nor_summary_en descriptions, reflect button).
  $is_writing = ($post->post_type === 'post');
  $seo_rich = ($is_works || $is_writing);
  $has_featured = $seo_rich ? has_post_thumbnail($post->ID) : false;
  $has_custom_og = (trim($og_image_override) !== '');
  $default_og_image = trim((string) get_option('nor_default_og_image_override', ''));
  $featured_og = '';
  if ($seo_rich && $has_featured) {
    $thumb = get_the_post_thumbnail_url($post->ID, 'full');
    if (is_string($thumb) && $thumb !== '') $featured_og = $thumb;
  }
  $uses_default_og = (!$has_custom_og && $featured_og === '');

  $normalize_meta_line = static function (string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $text = wp_strip_all_tags($text);
    $text = (string) preg_replace('/\s+/u', ' ', $text);
    return trim($text);
  };

  $site_name_for_title = 'nør. Ryousuke Tamura Design Office';
  $current_name = trim((string) wp_strip_all_tags((string) get_the_title($post->ID)));
  $seo_tagline = nor_inline_html_to_plain_text((string) get_post_meta($post->ID, 'nor_tagline', true));
  $title_head = $current_name;
  if ($seo_tagline !== '') {
    if ($title_head === '') {
      $title_head = $seo_tagline;
    } elseif (strcasecmp($seo_tagline, $title_head) !== 0) {
      $title_head = $title_head . ' - ' . $seo_tagline;
    }
  }

  // Common rule for all three: {Title}[ - {Tagline}][ | {section label}] | {tail}.
  // Works/Writings insert a section label ("Works"/"Writings"); Pages insert none.
  // Title and OG Title are built independently (OG Title's tail is always the
  // short "nør." brand mark instead of the full site name).
  $section_label = $is_works ? 'Works' : ($is_writing ? 'Writings' : '');
  $title_auto = implode(' | ', array_filter([$title_head, $section_label, $site_name_for_title], fn($s) => $s !== ''));
  $og_title_auto = implode(' | ', array_filter([$title_head, $section_label, 'nør.'], fn($s) => $s !== ''));
  $title_input = ($title_override !== '') ? $title_override : $title_auto;

  $default_desc_ja = $normalize_meta_line((string) get_option('nor_default_meta_desc_ja_override', ''));
  $default_desc_en = $normalize_meta_line((string) get_option('nor_default_meta_desc_en_override', ''));

  $desc_ja_base = '';
  $desc_en_base = '';
  if ($seo_rich) {
    $excerpt = get_the_excerpt($post->ID);
    $desc_ja_base = is_string($excerpt) ? $excerpt : '';
    $desc_en_base = (string) get_post_meta($post->ID, 'nor_summary_en', true);
  } else {
    $desc_ja_base = (string) get_post_meta($post->ID, 'nor_desc_ja', true);
    $desc_en_base = (string) get_post_meta($post->ID, 'nor_desc_en', true);
  }
  $desc_ja_base = $normalize_meta_line((string) $desc_ja_base);
  $desc_en_base = $normalize_meta_line((string) $desc_en_base);

  $desc_ja_override = $normalize_meta_line((string) $desc_ja_override);
  $desc_en_override = $normalize_meta_line((string) $desc_en_override);
  $desc_ja_input = ($desc_ja_override !== '') ? $desc_ja_override : (($desc_ja_base !== '') ? $desc_ja_base : $default_desc_ja);
  $desc_en_input = ($desc_en_override !== '') ? $desc_en_override : (($desc_en_base !== '') ? $desc_en_base : $default_desc_en);

  $canonical_auto = get_permalink($post->ID);
  $canonical_auto = is_string($canonical_auto) ? trim($canonical_auto) : '';
  if ($canonical_auto !== '' && function_exists('nor_seo_meta_normalize_url')) {
    $normalized = (string) nor_seo_meta_normalize_url($canonical_auto);
    if ($normalized !== '') $canonical_auto = $normalized;
  }
  $canonical_input = ($canonical_override !== '') ? $canonical_override : $canonical_auto;

  $og_image_input = $og_image_override;
  if ($og_image_input === '') {
    if ($featured_og !== '') {
      $og_image_input = $featured_og;
    } elseif ($default_og_image !== '') {
      $og_image_input = $default_og_image;
    }
  }
  // OG Title has its own auto value (ends in "nør.") for all three post types and
  // does not fall back through Title's own override, since they're generated
  // independently of one another.
  $og_title_input = ($og_title_override !== '') ? $og_title_override : $og_title_auto;

  wp_nonce_field('nor_seo_meta_save', 'nor_seo_meta_nonce');

  echo '<p style="margin-top:12px;"><label for="nor_meta_title_override"><strong>Title</strong></label></p>';
  echo '<input name="nor_meta_title_override" id="nor_meta_title_override" type="text" class="widefat" value="' . esc_attr($title_input) . '" />';

  echo '<p style="margin-top:12px;"><label for="nor_meta_desc_ja_override"><strong>Description（JA）</strong></label></p>';
  echo '<textarea name="nor_meta_desc_ja_override" id="nor_meta_desc_ja_override" rows="3" style="width:100%">' . esc_textarea($desc_ja_input) . '</textarea>';

  echo '<p style="margin-top:12px;"><label for="nor_meta_desc_en_override"><strong>Description（EN）</strong></label></p>';
  echo '<textarea name="nor_meta_desc_en_override" id="nor_meta_desc_en_override" rows="3" style="width:100%">' . esc_textarea($desc_en_input) . '</textarea>';
  // Shown for every SEO/LLMO target post type (Works, Writings, and every
  // target-slug page) — nor_render_seo_meta_box() is already gated to these
  // above, so this is unconditional rather than re-checking $seo_rich.
  if (true) {
    echo '<p style="margin-top:10px;">';
    echo '<button type="button" class="button" id="nor_seo_apply_from_body">本文情報を反映</button>';
    echo '<span id="nor_seo_apply_from_body_status" class="description" style="margin-left:8px;"></span>';
    echo '</p>';
    echo '<p class="description" style="margin-top:4px;">Title / Description（JA・EN）/ OG Title を本文入力欄の現在値から反映します。</p>';
  }

  echo '<p style="margin-top:12px;"><label for="nor_canonical_override"><strong>Canonical URL</strong></label></p>';
  echo '<input name="nor_canonical_override" id="nor_canonical_override" type="text" class="widefat" value="' . esc_attr($canonical_input) . '" />';

  echo '<p style="margin-top:12px;"><label for="nor_og_image_override"><strong>OG image</strong></label></p>';
  echo '<div style="display:flex; gap:8px; align-items:center;">';
  echo '<input name="nor_og_image_override" id="nor_og_image_override" type="text" class="widefat" value="' . esc_attr($og_image_input) . '" />';
  echo '<button type="button" class="button nor-og-media-pick" data-target="nor_og_image_override">メディアから選択</button>';
  echo '</div>';
  if ($uses_default_og) {
    echo '<p id="nor_og_image_default_warn" style="margin-top:6px; color:#b32d2e;"><strong>OG Image が個別設定されていません。現在はデフォルト画像が使用されます。</strong></p>';
  } elseif (!$has_custom_og && $seo_rich && $has_featured) {
    $og_source_label = $is_works ? 'Works' : 'Writing';
    echo '<p class="description" style="margin-top:6px;">この' . esc_html($og_source_label) . 'はアイキャッチ画像をOG画像として使用します。</p>';
  } else {
    echo '<p class="description" style="margin-top:6px;">個別設定したOG画像が優先されます。</p>';
  }

  echo '<p style="margin-top:12px;"><label for="nor_og_title_override"><strong>OG Title</strong></label></p>';
  echo '<input name="nor_og_title_override" id="nor_og_title_override" type="text" class="widefat" value="' . esc_attr($og_title_input) . '" />';

  $robots_default = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
  $robots_options = [
    '' => 'デフォルト（' . $robots_default . '）',
    $robots_default => $robots_default,
    'index, follow' => 'index, follow',
    'noindex, follow' => 'noindex, follow',
    'noindex, nofollow' => 'noindex, nofollow',
    'index, nofollow' => 'index, nofollow',
  ];

  echo '<p style="margin-top:12px;"><label for="nor_robots_override"><strong>robots</strong></label></p>';
  echo '<select name="nor_robots_override" id="nor_robots_override" class="widefat">';
  foreach ($robots_options as $value => $label) {
    echo '<option value="' . esc_attr($value) . '" ' . selected($robots_override, $value, false) . '>' . esc_html($label) . '</option>';
  }
  echo '</select>';
  if ($force_noindex) {
    echo '<p style="margin-top:6px; color:#b32d2e;"><strong>現在、共通設定により <code>noindex, nofollow</code> が強制されています。</strong></p>';
    if ($force_noindex_by_env) {
      echo '<p class="description" style="margin-top:4px;">環境タイプ: <code>' . esc_html($env_type) . '</code>（production 以外）</p>';
    }
    if ($discourage_search) {
      echo '<p class="description" style="margin-top:4px;">設定 → 表示設定の「検索エンジンがサイトをインデックスしないようにする」が有効です。</p>';
    }
  } else {
    echo '<p class="description" style="margin-top:6px;">全体条件（環境タイプ / 表示設定）で noindex が有効な場合、ここでの設定より全体条件が優先されます。</p>';
  }

  echo '<script>
    (function () {
      if (window.norPostOgMediaPickerInit) return;
      window.norPostOgMediaPickerInit = true;

      function refreshOgWarningState() {
        var input = document.getElementById("nor_og_image_override");
        var warn = document.getElementById("nor_og_image_default_warn");
        if (!input || !warn) return;

        var hasValue = String(input.value || "").trim() !== "";
        warn.style.display = hasValue ? "none" : "";
      }

      document.addEventListener("input", function (e) {
        if (e.target && e.target.id === "nor_og_image_override") refreshOgWarningState();
      });
      document.addEventListener("change", function (e) {
        if (e.target && e.target.id === "nor_og_image_override") refreshOgWarningState();
      });
      document.addEventListener("submit", function (e) {
        var form = e.target;
        if (!form || form.id !== "post") return;
        setTimeout(refreshOgWarningState, 200);
      });

      document.addEventListener("click", function (e) {
        var t = e.target;
        if (!t || !t.closest) return;
        var saveBtn = t.closest("#publish, #save-post, .editor-post-publish-button, .editor-post-save-draft");
        if (!saveBtn) return;
        setTimeout(refreshOgWarningState, 200);
        setTimeout(refreshOgWarningState, 900);
      });

      if (window.wp && window.wp.data && typeof window.wp.data.select === "function" && typeof window.wp.data.subscribe === "function") {
        var selectEditor = null;
        try {
          selectEditor = window.wp.data.select("core/editor");
        } catch (err) {
          selectEditor = null;
        }
        if (selectEditor && typeof selectEditor.isSavingPost === "function") {
          var wasSaving = false;
          window.wp.data.subscribe(function () {
            var isSaving = false;
            var isAutosaving = false;
            try {
              isSaving = !!selectEditor.isSavingPost();
              isAutosaving = (typeof selectEditor.isAutosavingPost === "function") ? !!selectEditor.isAutosavingPost() : false;
            } catch (err) {
              return;
            }
            if (wasSaving && !isSaving && !isAutosaving) {
              refreshOgWarningState();
            }
            wasSaving = isSaving;
          });
        }
      }

      document.addEventListener("click", function (e) {
        var button = e.target.closest(".nor-og-media-pick");
        if (!button) return;
        e.preventDefault();

        var targetId = String(button.getAttribute("data-target") || "").trim();
        if (!targetId) return;

        if (typeof window.wp === "undefined" || !window.wp.media) {
          window.alert("メディアライブラリを読み込めませんでした。ページを再読み込みして再度お試しください。");
          return;
        }

        var frame = window.wp.media({
          title: "OG画像を選択",
          library: { type: "image" },
          button: { text: "この画像を使用" },
          multiple: false
        });

        frame.on("select", function () {
          var selection = frame.state().get("selection");
          if (!selection || selection.length === 0) return;
          var media = selection.first().toJSON();
          if (!media || !media.url) return;

          var input = document.getElementById(targetId);
          if (!input) return;
          input.value = media.url;
          input.dispatchEvent(new Event("input", { bubbles: true }));
          input.dispatchEvent(new Event("change", { bubbles: true }));
          refreshOgWarningState();
        });

        frame.open();
      });
    })();
  </script>';

  // Shown for every SEO/LLMO target post type (see note above the button).
  if (true) {
    $seo_section_label = $is_works ? 'Works' : ($is_writing ? 'Writings' : 'Page');
    echo '<script>
      (function () {
        if (window.norWorksSeoReflectInit) return;
        window.norWorksSeoReflectInit = true;

        var norSeoSectionLabel = ' . wp_json_encode($seo_section_label) . ';

        function normalizeMetaLine(text) {
          var s = String(text || "").trim();
          if (!s) return "";
          s = s.replace(/<[^>]*>/g, " ");
          s = s.replace(/\\s+/g, " ").trim();
          return s;
        }

        function getEditorSelector() {
          if (!window.wp || !window.wp.data || typeof window.wp.data.select !== "function") return null;
          try {
            return window.wp.data.select("core/editor");
          } catch (err) {
            return null;
          }
        }

        function getEditedPostAttribute(key) {
          var selector = getEditorSelector();
          if (!selector || typeof selector.getEditedPostAttribute !== "function") return "";
          try {
            var v = selector.getEditedPostAttribute(key);
            if (typeof v === "string") return v;
            if (v && typeof v === "object") {
              if (typeof v.raw === "string") return v.raw;
              if (typeof v.rendered === "string") return v.rendered;
            }
          } catch (err) {}
          return "";
        }

        function getEditedMetaValue(key) {
          var selector = getEditorSelector();
          if (!selector || typeof selector.getEditedPostAttribute !== "function") return "";
          try {
            var meta = selector.getEditedPostAttribute("meta");
            if (meta && Object.prototype.hasOwnProperty.call(meta, key)) {
              return String(meta[key] || "");
            }
          } catch (err) {}
          return "";
        }

        function getInputValue(id) {
          var el = document.getElementById(id);
          return el ? String(el.value || "") : "";
        }

        function pickFirstNonEmpty(values) {
          for (var i = 0; i < values.length; i++) {
            var s = String(values[i] || "").trim();
            if (s !== "") return s;
          }
          return "";
        }

        function getWorksTitle() {
          return pickFirstNonEmpty([
            getInputValue("title"),
            getEditedPostAttribute("title")
          ]);
        }

        function getWorksExcerptJa() {
          if (norSeoSectionLabel === "Page") {
            // Pages: Description (JA) comes from nor_desc_ja, not the excerpt.
            return pickFirstNonEmpty([
              getInputValue("nor_desc_ja"),
              getEditedMetaValue("nor_desc_ja")
            ]);
          }
          return pickFirstNonEmpty([
            getInputValue("excerpt"),
            getEditedPostAttribute("excerpt")
          ]);
        }

        function getWorksSummaryEn() {
          if (norSeoSectionLabel === "Page") {
            // Pages: Description (EN) comes from nor_desc_en, not nor_summary_en.
            return pickFirstNonEmpty([
              getInputValue("nor_desc_en"),
              getEditedMetaValue("nor_desc_en")
            ]);
          }
          return pickFirstNonEmpty([
            getInputValue("nor_summary_en"),
            getEditedMetaValue("nor_summary_en")
          ]);
        }

        function getWorksTagline() {
          return pickFirstNonEmpty([
            getInputValue("nor_tagline"),
            getEditedMetaValue("nor_tagline")
          ]);
        }

        function pushUnique(segments, value) {
          var v = String(value || "").trim();
          if (!v) return;
          var key = v.toLowerCase();
          for (var i = 0; i < segments.length; i++) {
            if (String(segments[i] || "").toLowerCase() === key) return;
          }
          segments.push(v);
        }

        function buildSeoTitle(tailLabel) {
          // Common rule for all three: {Title}[ - {Tagline}][ | {section label}] | {tail}.
          // Works/Writings insert a section label; Pages insert none.
          var currentName = normalizeMetaLine(getWorksTitle());
          var tagline = normalizeMetaLine(getWorksTagline());
          var head = currentName;
          if (tagline) {
            if (!head) {
              head = tagline;
            } else if (tagline.toLowerCase() !== head.toLowerCase()) {
              head = head + " - " + tagline;
            }
          }

          var sectionLabel = (norSeoSectionLabel === "Page") ? "" : norSeoSectionLabel;

          var segments = [];
          pushUnique(segments, head);
          pushUnique(segments, sectionLabel);
          pushUnique(segments, tailLabel);
          return segments.join(" | ");
        }

        function buildWorksSeoTitle() {
          return buildSeoTitle("nør. Ryousuke Tamura Design Office");
        }

        function buildWorksSeoOgTitle() {
          return buildSeoTitle("nør.");
        }

        function setFieldValue(id, value) {
          var el = document.getElementById(id);
          if (!el) return false;
          el.value = String(value || "");
          el.dispatchEvent(new Event("input", { bubbles: true }));
          el.dispatchEvent(new Event("change", { bubbles: true }));
          return true;
        }

        function setStatus(text) {
          var el = document.getElementById("nor_seo_apply_from_body_status");
          if (!el) return;
          el.textContent = text;
        }

        function readSamplePermalinkUrl() {
          var el = document.getElementById("sample-permalink");
          if (!el) return "";

          if (el.tagName && String(el.tagName).toUpperCase() === "A" && el.href) {
            return String(el.href || "");
          }

          if (typeof el.querySelector === "function") {
            var a = el.querySelector("a[href]");
            if (a && a.href) return String(a.href || "");
          }

          var text = String(el.textContent || "").trim();
          if (/^https?:\/\//i.test(text)) return text;
          return "";
        }

        function normalizeCanonicalCandidate(raw) {
          var s = String(raw || "").trim();
          if (!s) return "";

          var u;
          try {
            u = new URL(s, window.location.origin);
          } catch (err) {
            return "";
          }

          if (!/^https?:$/.test(String(u.protocol || ""))) return "";

          var p = String(u.pathname || "");
          if (/\/wp-admin(?:\/|$)/.test(p)) return "";
          if (/\/wp-login\.php$/i.test(p)) return "";
          if (/\/xmlrpc\.php$/i.test(p)) return "";

          u.hash = "";
          return String(u.href || "");
        }

        function isDisallowedCanonical(raw) {
          var s = String(raw || "").trim();
          if (!s) return false;
          var u;
          try {
            u = new URL(s, window.location.origin);
          } catch (err) {
            return false;
          }
          var p = String(u.pathname || "");
          if (/\/wp-admin(?:\/|$)/.test(p)) return true;
          if (/\/wp-login\.php$/i.test(p)) return true;
          if (/\/xmlrpc\.php$/i.test(p)) return true;
          return false;
        }

        function getCanonicalAuto() {
          var candidates = [
            readSamplePermalinkUrl(),
            getEditedPostAttribute("link")
          ];
          for (var i = 0; i < candidates.length; i++) {
            var normalized = normalizeCanonicalCandidate(candidates[i]);
            if (normalized !== "") return normalized;
          }
          return "";
        }

        document.addEventListener("click", function (e) {
          var button = e.target.closest("#nor_seo_apply_from_body");
          if (!button) return;
          e.preventDefault();

          var applied = 0;
          var title = normalizeMetaLine(buildWorksSeoTitle());
          var ogTitle = normalizeMetaLine(buildWorksSeoOgTitle());
          var descJa = normalizeMetaLine(getWorksExcerptJa());
          var descEn = normalizeMetaLine(getWorksSummaryEn());
          var canonical = normalizeMetaLine(getCanonicalAuto());
          var currentCanonical = normalizeMetaLine(getInputValue("nor_canonical_override"));

          if (title !== "") {
            if (setFieldValue("nor_meta_title_override", title)) applied++;
          }
          if (ogTitle !== "") {
            if (setFieldValue("nor_og_title_override", ogTitle)) applied++;
          }
          if (descJa !== "") {
            if (setFieldValue("nor_meta_desc_ja_override", descJa)) applied++;
          }
          if (descEn !== "") {
            if (setFieldValue("nor_meta_desc_en_override", descEn)) applied++;
          }
          if (canonical !== "") {
            if (setFieldValue("nor_canonical_override", canonical)) applied++;
          } else if (isDisallowedCanonical(currentCanonical)) {
            if (setFieldValue("nor_canonical_override", "")) applied++;
          }

          if (applied > 0) {
            setStatus("本文情報を反映しました。");
          } else {
            setStatus("反映元の本文情報が空のため、変更はありません。");
          }
        });
      })();
    </script>';
  }
}

add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;
  if (!nor_is_seo_meta_target_post($post)) return;

  add_meta_box(
    'nor_seo_meta',
    'SEO / LLMO',
    'nor_render_seo_meta_box',
    'page',
    'side',
    'default'
  );
});

add_action('add_meta_boxes_works', function ($post) {
  if (!$post instanceof WP_Post) return;
  if (!nor_is_seo_meta_target_post($post)) return;

  add_meta_box(
    'nor_seo_meta',
    'SEO / LLMO',
    'nor_render_seo_meta_box',
    'works',
    'side',
    'default'
  );
});

// Writings (standard post): same SEO / LLMO meta box as Works, same render/save.
add_action('add_meta_boxes_post', function ($post) {
  if (!$post instanceof WP_Post) return;
  if (!nor_is_seo_meta_target_post($post)) return;

  add_meta_box(
    'nor_seo_meta',
    'SEO / LLMO',
    'nor_render_seo_meta_box',
    'post',
    'side',
    'default'
  );
});

add_action('save_post', function ($post_id, $post) {
  if (!$post instanceof WP_Post) return;
  if (!nor_is_seo_meta_target_post($post)) return;

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;
  if (!current_user_can('edit_post', $post_id)) return;

  if (!isset($_POST['nor_seo_meta_nonce'])) return;
  if (!wp_verify_nonce((string) $_POST['nor_seo_meta_nonce'], 'nor_seo_meta_save')) return;

  $save_text = static function (int $pid, string $key, $raw): void {
    $val = sanitize_text_field((string) wp_unslash($raw));
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($pid, $key);
    } else {
      update_post_meta($pid, $key, $val);
    }
  };

  $save_textarea = static function (int $pid, string $key, $raw): void {
    $val = sanitize_textarea_field((string) wp_unslash($raw));
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($pid, $key);
    } else {
      update_post_meta($pid, $key, $val);
    }
  };

  if (isset($_POST['nor_meta_title_override'])) {
    $save_text($post_id, 'nor_meta_title_override', $_POST['nor_meta_title_override']);
  }
  if (isset($_POST['nor_meta_desc_ja_override'])) {
    $save_textarea($post_id, 'nor_meta_desc_ja_override', $_POST['nor_meta_desc_ja_override']);
  }
  if (isset($_POST['nor_meta_desc_en_override'])) {
    $save_textarea($post_id, 'nor_meta_desc_en_override', $_POST['nor_meta_desc_en_override']);
  }
  if (isset($_POST['nor_og_title_override'])) {
    $save_text($post_id, 'nor_og_title_override', $_POST['nor_og_title_override']);
  }

  if (isset($_POST['nor_canonical_override'])) {
    $raw = (string) wp_unslash($_POST['nor_canonical_override']);
    $val = nor_seo_meta_normalize_url($raw);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_canonical_override');
    } else {
      update_post_meta($post_id, 'nor_canonical_override', $val);
    }
  }

  if (isset($_POST['nor_og_image_override'])) {
    $raw = (string) wp_unslash($_POST['nor_og_image_override']);
    $val = nor_seo_meta_normalize_url($raw);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_og_image_override');
    } else {
      update_post_meta($post_id, 'nor_og_image_override', $val);
    }
  }

  if (isset($_POST['nor_robots_override'])) {
    $raw = trim((string) wp_unslash($_POST['nor_robots_override']));
    $robots_default = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $allowed = [
      '',
      $robots_default,
      'index, follow',
      'noindex, follow',
      'noindex, nofollow',
      'index, nofollow',
    ];
    if (!in_array($raw, $allowed, true) || $raw === '') {
      delete_post_meta($post_id, 'nor_robots_override');
    } else {
      update_post_meta($post_id, 'nor_robots_override', $raw);
    }
  }
}, 10, 2);

/**
 * Admin: About page section copy
 * - Adds dedicated fields for About body sections.
 * - Stores values in post meta:
 *   - nor_about_*
 */
add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;
  if ((string) $post->post_name !== 'about') return;

  $render_fields = function ($post, array $fields, string $desc = ''): void {
    if (!$post instanceof WP_Post) return;

    wp_nonce_field('nor_about_page_copy_save', 'nor_about_page_copy_nonce');
    if ($desc !== '') {
      echo '<p class="description">' . esc_html($desc) . '</p>';
    }

    foreach ($fields as $f) {
      $key = isset($f['key']) ? (string) $f['key'] : '';
      $label = isset($f['label']) ? (string) $f['label'] : '';
      $rows = isset($f['rows']) ? (int) $f['rows'] : 3;
      if ($key === '') continue;

      $val = get_post_meta($post->ID, $key, true);
      $val = is_string($val) ? $val : '';

      echo '<p style="margin-top:12px;"><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label></p>';
      echo '<textarea name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" rows="' . esc_attr((string) max(2, $rows)) . '" style="width:100%">' . esc_textarea($val) . '</textarea>';
    }
  };

  add_meta_box(
    'nor_about_summary_copy',
    'Summary',
    function ($post) use ($render_fields) {
      $render_fields($post, [
        ['key' => 'nor_about_summary_lead_ja', 'label' => 'リード文（JA）', 'rows' => 3],
        ['key' => 'nor_about_summary_lead_en', 'label' => 'リード文（EN）', 'rows' => 3],
      ]);
    },
    'page',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_about_structure_copy',
    'Structure',
    function ($post) use ($render_fields) {
      $render_fields($post, [
        ['key' => 'nor_about_structure_lead_ja', 'label' => 'リード文（JA）', 'rows' => 3],
        ['key' => 'nor_about_structure_lead_en', 'label' => 'リード文（EN）', 'rows' => 3],
      ]);
    },
    'page',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_about_sectors_copy',
    'Served Sectors',
    function ($post) use ($render_fields) {
      $render_fields($post, [
        ['key' => 'nor_about_sectors_lead_ja', 'label' => 'リード文（JA）', 'rows' => 3],
        ['key' => 'nor_about_sectors_lead_en', 'label' => 'リード文（EN）', 'rows' => 3],
        ['key' => 'nor_about_sectors_note_ja', 'label' => '注釈（JA）', 'rows' => 3],
        ['key' => 'nor_about_sectors_note_en', 'label' => '注釈（EN）', 'rows' => 3],
      ]);
    },
    'page',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_about_profile_copy',
    'Short Profile',
    function ($post) use ($render_fields) {
      $render_fields($post, [
        ['key' => 'nor_about_profile_name_ja', 'label' => 'サイト名（JA）', 'rows' => 2],
        ['key' => 'nor_about_profile_name_en', 'label' => 'サイト名（EN）', 'rows' => 2],
        ['key' => 'nor_about_profile_body_ja', 'label' => '略歴（JA）', 'rows' => 6],
        ['key' => 'nor_about_profile_body_en', 'label' => '略歴（EN）', 'rows' => 6],
      ]);
    },
    'page',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_about_stance_copy',
    'Stance',
    function ($post) use ($render_fields) {
      $render_fields($post, [
        ['key' => 'nor_about_stance_lead_ja', 'label' => 'コンセプト（JA）', 'rows' => 2],
        ['key' => 'nor_about_stance_lead_en', 'label' => 'コンセプト（EN）', 'rows' => 2],
        ['key' => 'nor_about_stance_body_ja', 'label' => 'ストーリー（JA）', 'rows' => 6],
        ['key' => 'nor_about_stance_body_en', 'label' => 'ストーリー（EN）', 'rows' => 6],
      ]);
    },
    'page',
    'normal',
    'default'
  );
});

add_action('save_post_page', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $slug = get_post_field('post_name', $post_id);
  if ((string) $slug !== 'about') return;

  if (!isset($_POST['nor_about_page_copy_nonce']) || !wp_verify_nonce($_POST['nor_about_page_copy_nonce'], 'nor_about_page_copy_save')) return;

  $keys = [
    'nor_about_summary_lead_ja',
    'nor_about_summary_lead_en',
    'nor_about_structure_lead_ja',
    'nor_about_structure_lead_en',
    'nor_about_sectors_lead_ja',
    'nor_about_sectors_lead_en',
    'nor_about_sectors_note_ja',
    'nor_about_sectors_note_en',
    'nor_about_profile_name_ja',
    'nor_about_profile_name_en',
    'nor_about_profile_body_ja',
    'nor_about_profile_body_en',
    'nor_about_stance_lead_ja',
    'nor_about_stance_lead_en',
    'nor_about_stance_body_ja',
    'nor_about_stance_body_en',
  ];

  foreach ($keys as $key) {
    if (!isset($_POST[$key])) continue;
    $raw = wp_unslash($_POST[$key]);
    $val = nor_sanitize_about_rich_html((string) $raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, $key);
    } else {
      update_post_meta($post_id, $key, $val);
    }
  }
});

/**
 * Admin: Works meta
 * - Tagline: nor_tagline (string)
 * - Summary (EN): nor_summary_en (string)
 * - Content mode: nor_content_mode (text|full)
 * - Gallery (sub images): nor_gallery_ids (array of attachment IDs)
 */

/**
 * Works: Number (required, unique)
 * - Stored in post meta: nor_work_no
 * - Used for the card index like "#001".
 */
function nor_normalize_work_no($raw): string {
  $s = is_string($raw) ? trim($raw) : '';
  // keep digits only
  $s = preg_replace('/[^0-9]/', '', $s);
  if ($s === '') return '';
  // normalize to integer string (remove leading zeros)
  $n = (int) $s;
  if ($n <= 0) return '';
  return (string) $n;
}

function nor_find_work_id_by_work_no(string $work_no, int $exclude_post_id = 0): int {
  $work_no = nor_normalize_work_no($work_no);
  if ($work_no === '') return 0;

  // NOTE:
  // Older posts may have stored values like "020" or non-normalized strings.
  // We therefore fetch candidate IDs by meta_key existence and compare after normalization in PHP.
  $q = new WP_Query([
    'post_type'      => 'works',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'meta_query'     => [[
      'key'     => 'nor_work_no',
      'compare' => 'EXISTS',
    ]],
  ]);

  $ids = is_array($q->posts) ? $q->posts : [];
  wp_reset_postdata();

  foreach ($ids as $id) {
    $id = (int) $id;
    if ($id <= 0) continue;
    if ($exclude_post_id > 0 && $id === (int) $exclude_post_id) continue;

    $stored = get_post_meta($id, 'nor_work_no', true);
    $stored = nor_normalize_work_no($stored);

    if ($stored !== '' && $stored === $work_no) {
      return $id;
    }
  }

  return 0;
}

function nor_append_work_admin_notice(int $post_id, string $line): void {
  if ($post_id <= 0) return;
  $key = 'nor_work_admin_notice_' . $post_id;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';

  $lines = [];
  if ($existing !== '') {
    $lines = preg_split("/\r\n|\r|\n/", $existing);
    $lines = array_filter(array_map('trim', (array) $lines));
  }

  $line = trim($line);
  if ($line === '') return;
  if (!in_array($line, $lines, true)) {
    $lines[] = $line;
  }

  if (empty($lines)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $lines), 5 * MINUTE_IN_SECONDS);
}

function nor_remove_work_admin_notice_lines(int $post_id, callable $keep_line): void {
  if ($post_id <= 0) return;
  $key = 'nor_work_admin_notice_' . $post_id;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';
  if ($existing === '') return;

  $lines = preg_split("/\r\n|\r|\n/", $existing);
  $lines = array_filter(array_map('trim', (array) $lines));

  $filtered = [];
  foreach ($lines as $l) {
    if ($l === '') continue;
    if ($keep_line($l)) $filtered[] = $l;
  }

  if (empty($filtered)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $filtered), 5 * MINUTE_IN_SECONDS);
}

function nor_clear_work_no_admin_notices(int $post_id): void {
  nor_remove_work_admin_notice_lines($post_id, function (string $line): bool {
    // Remove ONLY work-no related notices so they don't persist when the number changes.
    // Examples:
    // - 採番が未入力のため…
    // - 採番が「記事ID: xxx」と重複…
    return (strpos($line, '採番が') !== 0);
  });
}

function nor_append_work_admin_notice_for_user(string $line): void {
  $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
  if ($uid <= 0) return;

  $key = 'nor_work_admin_notice_user_' . $uid;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';

  $lines = [];
  if ($existing !== '') {
    $lines = preg_split("/\r\n|\r|\n/", $existing);
    $lines = array_filter(array_map('trim', (array) $lines));
  }

  $line = trim($line);
  if ($line === '') return;
  if (!in_array($line, $lines, true)) {
    $lines[] = $line;
  }

  if (empty($lines)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $lines), 5 * MINUTE_IN_SECONDS);
}

function nor_get_submitted_work_no(array $postarr): string {
  // Classic editor / meta box submit
  if (isset($_POST['nor_work_no'])) {
    return nor_normalize_work_no(wp_unslash($_POST['nor_work_no']));
  }

  // Block editor (REST) may pass meta via meta_input
  if (isset($postarr['meta_input']) && is_array($postarr['meta_input']) && array_key_exists('nor_work_no', $postarr['meta_input'])) {
    return nor_normalize_work_no($postarr['meta_input']['nor_work_no']);
  }

  // Some paths may use `meta`.
  if (isset($postarr['meta']) && is_array($postarr['meta']) && array_key_exists('nor_work_no', $postarr['meta'])) {
    return nor_normalize_work_no($postarr['meta']['nor_work_no']);
  }

  return '';
}

/**
 * Works: Main/End client (meta-driven)
 * - Main client (single): nor_main_client_id (term_id of work_client)
 * - End clients (multi): nor_end_client_ids (comma-separated term_ids of work_client)
 *
 * Note:
 * - work_client taxonomy remains the source of truth for Client terms.
 * - We sync taxonomy assignments (work_client) = main + end so term archives keep working.
 */
function nor_get_all_client_terms(): array {
  $terms = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);
  if (is_wp_error($terms) || !is_array($terms)) return [];
  return array_values(array_filter($terms, fn($t) => $t instanceof WP_Term));
}

function nor_parse_id_list($raw): array {
  if (is_array($raw)) {
    $ids = array_map('intval', $raw);
  } else {
    $s = is_string($raw) ? trim($raw) : '';
    $ids = ($s === '') ? [] : array_map('intval', array_map('trim', explode(',', $s)));
  }
  $ids = array_values(array_filter($ids, fn($n) => is_int($n) ? $n > 0 : (int)$n > 0));
  // unique keep order
  $seen = [];
  $out = [];
  foreach ($ids as $id) {
    $id = (int) $id;
    if ($id <= 0) continue;
    if (isset($seen[$id])) continue;
    $seen[$id] = true;
    $out[] = $id;
  }
  return $out;
}

/**
 * Normalize a term list into WP_Term[] with optional limit.
 *
 * @param mixed $terms
 * @param int   $limit 0 means no limit.
 * @return WP_Term[]
 */
function nor_limit_wp_terms($terms, int $limit = 0): array {
  if (empty($terms) || is_wp_error($terms) || !is_array($terms)) return [];
  $out = [];
  foreach ($terms as $t) {
    if (!$t instanceof WP_Term) continue;
    $out[] = $t;
    if ($limit > 0 && count($out) >= $limit) break;
  }
  return $out;
}

/**
 * Build grouped work_tag terms for card rendering.
 *
 * @param int  $post_id
 * @param bool $include_other Include "_other" bucket when true.
 * @return array{
 *   document-types: WP_Term[],
 *   site-types: WP_Term[],
 *   roles: WP_Term[],
 *   tools: WP_Term[],
 *   _other?: WP_Term[]
 * }
 */
function nor_get_work_tag_groups(int $post_id, bool $include_other = false): array {
  $groups = [
    'document-types' => [],
    'site-types'     => [],
    'roles'          => [],
    'tools'          => [],
  ];
  if ($include_other) $groups['_other'] = [];

  if ($post_id <= 0) return $groups;

  $tags = get_the_terms($post_id, 'work_tag');
  if (empty($tags) || is_wp_error($tags) || !is_array($tags)) return $groups;

  foreach ($tags as $t) {
    if (!$t instanceof WP_Term) continue;

    $root = $t;
    while ($root instanceof WP_Term && !empty($root->parent)) {
      $p = get_term((int) $root->parent, 'work_tag');
      if (!$p || is_wp_error($p) || !$p instanceof WP_Term) break;
      $root = $p;
    }

    $root_slug = ($root instanceof WP_Term) ? (string) $root->slug : '';
    if ($root instanceof WP_Term && (int) $t->term_id === (int) $root->term_id) continue;

    if (isset($groups[$root_slug])) {
      $groups[$root_slug][] = $t;
    } elseif ($include_other) {
      $groups['_other'][] = $t;
    }
  }

  foreach ($groups as $k => $arr) {
    usort($arr, function ($a, $b) {
      $an = $a instanceof WP_Term ? (string) $a->name : '';
      $bn = $b instanceof WP_Term ? (string) $b->name : '';
      return strcmp($an, $bn);
    });
    $groups[$k] = $arr;
  }

  return $groups;
}

/**
 * Resolve clients for Works card.
 *
 * @param int    $post_id
 * @param int    $limit
 * @param string $mode "meta" | "taxonomy"
 * @return WP_Term[]
 */
function nor_get_work_clients_for_card(int $post_id, int $limit = 4, string $mode = 'meta'): array {
  if ($post_id <= 0) return [];
  if ($limit < 1) $limit = 4;

  if ($mode === 'taxonomy') {
    return nor_limit_wp_terms(get_the_terms($post_id, 'work_client'), $limit);
  }

  // Meta-first mode:
  // - Main client: nor_main_client_id
  // - End clients: nor_end_client_ids
  // - Fallback to taxonomy assignment order when main is missing.
  $main_client_id = (int) get_post_meta($post_id, 'nor_main_client_id', true);
  $end_ids = nor_parse_id_list(get_post_meta($post_id, 'nor_end_client_ids', true));

  $main_client = null;
  if ($main_client_id > 0) {
    $main_term = get_term($main_client_id, 'work_client');
    if ($main_term && !is_wp_error($main_term) && $main_term instanceof WP_Term) {
      $main_client = $main_term;
    }
  }

  $end_clients = [];
  foreach ($end_ids as $cid) {
    $cid = (int) $cid;
    if ($cid <= 0) continue;
    if ($main_client instanceof WP_Term && (int) $main_client->term_id === $cid) continue;
    $t = get_term($cid, 'work_client');
    if ($t && !is_wp_error($t) && $t instanceof WP_Term) {
      $end_clients[] = $t;
    }
  }

  $clients = [];
  if ($main_client instanceof WP_Term) {
    $clients[] = $main_client;
    if (!empty($end_clients)) {
      $clients = array_merge($clients, $end_clients);
    }
  } else {
    // Fallback: preserve the original taxonomy assignment order.
    $clients = nor_limit_wp_terms(get_the_terms($post_id, 'work_client'), $limit);
  }

  return nor_limit_wp_terms($clients, $limit);
}

/**
 * Resolve industries for Works card from first client.
 *
 * @param int       $post_id
 * @param WP_Term[] $clients
 * @param bool      $legacy_fallback If true, fallback to legacy work_industry assignment.
 * @return WP_Term[]
 */
function nor_get_work_industries_for_card(int $post_id, array $clients, bool $legacy_fallback = true): array {
  $first_client = null;
  if (!empty($clients) && isset($clients[0]) && $clients[0] instanceof WP_Term) {
    $first_client = $clients[0];
  }

  if ($first_client instanceof WP_Term) {
    $iid = (int) get_term_meta((int) $first_client->term_id, 'nor_industry', true);
    if ($iid > 0) {
      $it = get_term($iid, 'work_industry');
      if ($it && !is_wp_error($it) && $it instanceof WP_Term) {
        return [$it];
      }
    }
  }

  if (!$legacy_fallback) return [];

  return nor_limit_wp_terms(get_the_terms($post_id, 'work_industry'), 1);
}

/**
 * Build canonical card data for one Works post.
 *
 * @param int   $post_id
 * @param array $opts
 * @return array{
 *   post_id:int,
 *   permalink:string,
 *   title:string,
 *   excerpt:string,
 *   published:string,
 *   published_dt:string,
 *   is_new:bool,
 *   category:?WP_Term,
 *   content_mode:string,
 *   tag_groups:array,
 *   doc_types:WP_Term[],
 *   site_types:WP_Term[],
 *   roles:WP_Term[],
 *   tools:WP_Term[],
 *   clients:WP_Term[],
 *   industries:WP_Term[]
 * }
 */
function nor_get_work_card_data(int $post_id, array $opts = []): array {
  $defaults = [
    'client_mode'              => 'meta',     // "meta" | "taxonomy"
    'clients_limit'            => 4,
    'legacy_industry_fallback' => true,
    'include_tag_other'        => false,
  ];
  $o = wp_parse_args($opts, $defaults);

  $post_id = max(0, (int) $post_id);
  $post = ($post_id > 0) ? get_post($post_id) : null;

  $published = get_the_date('Y-m-d', $post_id);
  $published_dt = get_the_date('c', $post_id);
  $published_ts = (int) get_post_time('U', false, $post_id);
  $is_new = nor_is_recent_timestamp((int) $published_ts, 30);

  $cats = nor_limit_wp_terms(get_the_terms($post_id, 'work_category'), 1);
  $category = isset($cats[0]) && $cats[0] instanceof WP_Term ? $cats[0] : null;

  $tag_groups = nor_get_work_tag_groups($post_id, !empty($o['include_tag_other']));

  $client_mode = ((string) $o['client_mode'] === 'taxonomy') ? 'taxonomy' : 'meta';
  $clients_limit = (int) $o['clients_limit'];
  if ($clients_limit < 1) $clients_limit = 4;
  $clients = nor_get_work_clients_for_card($post_id, $clients_limit, $client_mode);
  $industries = nor_get_work_industries_for_card($post_id, $clients, !empty($o['legacy_industry_fallback']));

  $mode_raw = get_post_meta($post_id, 'nor_content_mode', true);
  $mode_norm = strtolower(trim((string) $mode_raw));
  $is_full = in_array($mode_norm, ['full-content', 'fullcontent', 'full'], true);
  $content_mode = $is_full ? 'Full-content' : 'Text-only';

  $excerpt = get_the_excerpt($post_id);
  $excerpt = is_string($excerpt) ? trim($excerpt) : '';
  if ($excerpt === '') {
    $content = ($post instanceof WP_Post) ? (string) $post->post_content : '';
    $excerpt = wp_trim_words(wp_strip_all_tags($content), 40, '…');
  }
  $excerpt = function_exists('nor_get_work_public_text')
    ? nor_get_work_public_text($post_id, (string) $excerpt)
    : (string) $excerpt;

  $title_raw = (string) get_the_title($post_id);
  $title_public = function_exists('nor_get_work_public_title')
    ? nor_get_work_public_title($post_id, $title_raw)
    : $title_raw;

  return [
    'post_id'      => $post_id,
    'permalink'    => (string) get_permalink($post_id),
    'title'        => (string) $title_public,
    'excerpt'      => (string) $excerpt,
    'published'    => is_string($published) ? $published : '',
    'published_dt' => is_string($published_dt) ? $published_dt : '',
    'is_new'       => (bool) $is_new,
    'category'     => $category,
    'content_mode' => $content_mode,
    'tag_groups'   => $tag_groups,
    'doc_types'    => isset($tag_groups['document-types']) && is_array($tag_groups['document-types']) ? $tag_groups['document-types'] : [],
    'site_types'   => isset($tag_groups['site-types']) && is_array($tag_groups['site-types']) ? $tag_groups['site-types'] : [],
    'roles'        => isset($tag_groups['roles']) && is_array($tag_groups['roles']) ? $tag_groups['roles'] : [],
    'tools'        => isset($tag_groups['tools']) && is_array($tag_groups['tools']) ? $tag_groups['tools'] : [],
    'clients'      => $clients,
    'industries'   => $industries,
  ];
}

function nor_sync_work_client_terms(int $post_id, int $main_id, array $end_ids): void {
  $all = [];
  if ($main_id > 0) $all[] = (int) $main_id;
  foreach ($end_ids as $id) {
    $id = (int) $id;
    if ($id > 0 && $id !== (int) $main_id) $all[] = $id;
  }
  // unique keep order
  $all = nor_parse_id_list($all);
  // If no main, do not overwrite taxonomy (safety)
  if (empty($all)) return;
  wp_set_object_terms($post_id, $all, 'work_client', false);
}

function nor_migrate_clients_if_needed(int $post_id): void {
  $main = get_post_meta($post_id, 'nor_main_client_id', true);
  $main = is_numeric($main) ? (int) $main : 0;

  $ends = get_post_meta($post_id, 'nor_end_client_ids', true);
  $end_ids = nor_parse_id_list($ends);

  // Already migrated
  if ($main > 0) {
    nor_sync_work_client_terms($post_id, $main, $end_ids);
    return;
  }

  // Migrate from existing taxonomy assignments
  $clients = get_the_terms($post_id, 'work_client');
  if (empty($clients) || is_wp_error($clients)) return;
  $clients = array_values(array_filter($clients, fn($t) => $t instanceof WP_Term));
  if (empty($clients)) return;

  // Use the first term returned by WP as main; remaining as end
  $main_term = $clients[0];
  $main_id = (int) $main_term->term_id;
  $rest = [];
  foreach ($clients as $idx => $t) {
    if ($idx === 0) continue;
    $rest[] = (int) $t->term_id;
  }

  update_post_meta($post_id, 'nor_main_client_id', $main_id);
  if (!empty($rest)) {
    update_post_meta($post_id, 'nor_end_client_ids', implode(',', nor_parse_id_list($rest)));
  } else {
    delete_post_meta($post_id, 'nor_end_client_ids');
  }

  nor_sync_work_client_terms($post_id, $main_id, $rest);
}

add_action('add_meta_boxes_works', function () {
  // 0) 採番（必須・ユニーク）
  add_meta_box(
    'nor_work_no',
    '採番（必須）',
    function (WP_Post $post) {
      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      $work_no = get_post_meta($post->ID, 'nor_work_no', true);
      $work_no = is_string($work_no) ? trim($work_no) : '';

      echo '<input name="nor_work_no" id="nor_work_no" type="text" inputmode="numeric" pattern="[0-9]*" class="regular-text" value="' . esc_attr($work_no) . '" />';
      echo '<p class="description" style="margin-top:6px;">カードの # 表示に使う連番です（数字のみ）。未入力では公開できません。既存と重複すると警告します。</p>';
    },
    'works',
    'normal',
    'high'
  );

  // 1) タグライン
  add_meta_box(
    'nor_work_tagline',
    'タグライン',
    function (WP_Post $post) {
      $tagline = get_post_meta($post->ID, 'nor_tagline', true);
      $tagline = is_string($tagline) ? $tagline : '';

      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      echo '<input name="nor_tagline" id="nor_tagline" type="text" class="regular-text" value="' . esc_attr($tagline) . '" />';
      echo '<p class="description" style="margin-top:6px;">Works個別のタグラインを入力します。タイトル下に表示されます。</p>';
    },
    'works',
    'normal',
    'default'
  );

  // 2) 説明（EN）
  add_meta_box(
    'nor_work_summary_en',
    '説明（EN）',
    function (WP_Post $post) {
      $summary_en = get_post_meta($post->ID, 'nor_summary_en', true);
      $summary_en = is_string($summary_en) ? $summary_en : '';

      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      echo '<textarea name="nor_summary_en" id="nor_summary_en" rows="4" style="width:100%">' . esc_textarea($summary_en) . '</textarea>';
      echo '<p class="description" style="margin-top:6px;">説明（JA）は標準の抜粋を使用し、説明（EN）はカスタムフィールドで保存しています。</p>';
    },
    'works',
    'normal',
    'default'
  );

  // 3) コンテンツモード
  add_meta_box(
    'nor_work_content_mode',
    'コンテンツモード',
    function (WP_Post $post) {
      $content_mode = get_post_meta($post->ID, 'nor_content_mode', true);
      $content_mode = is_string($content_mode) ? $content_mode : '';
      $content_mode = ($content_mode === 'full') ? 'full' : 'text';

      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      echo '<fieldset style="margin:0; padding:0; border:0;">';
      echo '<label style="margin-right:14px;"><input type="radio" name="nor_content_mode" value="text" ' . checked('text', $content_mode, false) . ' /> Text-only</label>';
      echo '<label><input type="radio" name="nor_content_mode" value="full" ' . checked('full', $content_mode, false) . ' /> Full-content</label>';
      echo '<div class="description" style="margin-top:8px;">';
      echo '<p style="margin:0;">メイン画像は WordPress 標準のアイキャッチ画像を使用します。<br>Full-content の時は、メイン画像とサブ画像を2枚以上を想定します。<br>満たしていない場合は保存後に警告を表示します。';
      echo '</div>';
      echo '</fieldset>';
    },
    'works',
    'normal',
    'default'
  );

  // 4) サブ画像（Full-contentの時だけ表示）
  add_meta_box(
    'nor_work_gallery',
    'サブ画像',
    function (WP_Post $post) {
      $content_mode = get_post_meta($post->ID, 'nor_content_mode', true);
      $content_mode = is_string($content_mode) ? $content_mode : '';
      $content_mode = ($content_mode === 'full') ? 'full' : 'text';

      $gallery_ids = get_post_meta($post->ID, 'nor_gallery_ids', true);

      // Stored as comma-separated IDs in DB for simplicity; normalize to string for the hidden input
      if (is_array($gallery_ids)) {
        $gallery_ids = array_map('intval', $gallery_ids);
        $gallery_ids = implode(',', array_filter($gallery_ids));
      } elseif (!is_string($gallery_ids)) {
        $gallery_ids = '';
      }

      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      // $gallery_style = ($content_mode === 'full') ? '' : 'display:none;'; // (DELETE this line)

      // New block for gallery wrapper
      $is_full = ($content_mode === 'full');
      $disabled_attr  = $is_full ? '' : ' disabled';
      $disabled_class = $is_full ? '' : ' is-disabled';

      echo '<div id="nor_gallery_block" class="nor-work-meta-block' . esc_attr($disabled_class) . '">';
      echo '<input type="hidden" name="nor_gallery_ids" id="nor_gallery_ids" value="' . esc_attr($gallery_ids) . '" />';
      // New block for buttons
      echo '<p style="margin:0 0 10px;">';
      echo '<button type="button" class="button" id="nor_gallery_pick"' . $disabled_attr . '>サブ画像を選択</button> ';
      echo '<button type="button" class="button" id="nor_gallery_clear"' . $disabled_attr . '>解除</button>';
      echo '</p>';
      echo '<div id="nor_gallery_preview" style="display:flex;flex-wrap:wrap;gap:8px;">';

      // Preview thumbnails
      $ids = array_filter(array_map('intval', explode(',', (string) $gallery_ids)));
      if (!empty($ids)) {
        foreach ($ids as $aid) {
          $thumb = wp_get_attachment_image($aid, 'thumbnail', false, ['style' => 'width:80px;height:auto;border:1px solid #ccd0d4;']);
          if ($thumb) {
            echo '<div data-aid="' . esc_attr($aid) . '">' . $thumb . '</div>';
          }
        }
      }

      echo '</div>';
      echo '<div class="description" style="margin-top:8px;">';
      echo '<p style="margin:0;">キャプションはメディアライブラリで画像ごとに設定しています。<br>キャプションが日本語のキャプション。キャプション（EN）はカスタムフィールドで保存しています。';
      echo '</div>';
      echo '</div>';
    },
    'works',
    'normal',
    'default'
  );

  // 5) Client（必須・1つ）
  add_meta_box(
    'nor_work_main_client',
    'メインクライアント',
    function (WP_Post $post) {
      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      $main = get_post_meta($post->ID, 'nor_main_client_id', true);
      $main = is_numeric($main) ? (int) $main : 0;

      $terms = nor_get_all_client_terms();

      echo '<select name="nor_main_client_id" id="nor_main_client_id" class="postform" style="max-width:100%;">';
      echo '<option value="">—</option>';
      foreach ($terms as $t) {
        $tid = (int) $t->term_id;
        echo '<option value="' . esc_attr($tid) . '" ' . selected($tid, $main, false) . '>' . esc_html($t->name) . '</option>';
      }
      echo '</select>';
      echo '<p class="description" style="margin-top:6px;">クライアントを選択します。業種は選択したクライアントの業種が表示されます。</p>';
    },
    'works',
    'normal',
    'default'
  );

  // 6) End clients & partners（任意・複数）
  add_meta_box(
    'nor_work_end_clients',
    'エンドクライアント',
    function (WP_Post $post) {
      // Shared nonce for all Works meta boxes
      wp_nonce_field('nor_work_meta_save', 'nor_work_meta_nonce');

      $main = get_post_meta($post->ID, 'nor_main_client_id', true);
      $main = is_numeric($main) ? (int) $main : 0;

      $raw = get_post_meta($post->ID, 'nor_end_client_ids', true);
      $end_ids = nor_parse_id_list($raw);

      $terms = nor_get_all_client_terms();

      echo '<div style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow:auto;border:1px solid #ccd0d4;padding:10px;background:#fff;">';
      if (empty($terms)) {
        echo '<p style="margin:0;">—</p>';
      } else {
        foreach ($terms as $t) {
          $tid = (int) $t->term_id;
          $checked = in_array($tid, $end_ids, true);
          $disabled = ($main > 0 && $tid === $main);
          echo '<label style="display:flex;gap:8px;align-items:flex-start;">';
          echo '<input type="checkbox" name="nor_end_client_ids[]" value="' . esc_attr($tid) . '" ' . checked(true, $checked, false) . ($disabled ? ' disabled' : '') . ' />';
          echo '<span>' . esc_html($t->name) . ($disabled ? '（メイン）' : '') . '</span>';
          echo '</label>';
        }
      }
      echo '</div>';
      echo '<p class="description" style="margin-top:6px;">エンドクライアントを複数選択できます。メインクライアントと同一のクライアントは選択できません。</p>';
    },
    'works',
    'normal',
    'default'
  );
});

add_action('save_post_works', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  if (!isset($_POST['nor_work_meta_nonce']) || !wp_verify_nonce($_POST['nor_work_meta_nonce'], 'nor_work_meta_save')) return;

  // If wp_insert_post_data stored user-scoped notices (new post has no ID yet),
  // migrate them to this post-scoped notice so they appear on the edit screen.
  $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
  if ($uid > 0) {
    $user_key = 'nor_work_admin_notice_user_' . $uid;
    $user_msg = get_transient($user_key);
    if (is_string($user_msg) && trim($user_msg) !== '') {
      $lines = preg_split("/\r\n|\r|\n/", (string) $user_msg);
      $lines = array_filter(array_map('trim', (array) $lines));
      foreach ($lines as $l) {
        nor_append_work_admin_notice((int) $post_id, (string) $l);
      }
      delete_transient($user_key);
    }
  }

  // Work no (required, unique) - store as normalized integer string
  if (isset($_POST['nor_work_no'])) {
    $raw = wp_unslash($_POST['nor_work_no']);
    $val = nor_normalize_work_no($raw);

    if ($val === '') {
      delete_post_meta($post_id, 'nor_work_no');
    } else {
      update_post_meta($post_id, 'nor_work_no', $val);
    }
  }

  // Tagline
  if (isset($_POST['nor_tagline'])) {
    $raw = wp_unslash($_POST['nor_tagline']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_tagline');
    } else {
      update_post_meta($post_id, 'nor_tagline', $val);
    }
  }

  // Summary (EN)
  if (isset($_POST['nor_summary_en'])) {
    $raw = wp_unslash($_POST['nor_summary_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_summary_en');
    } else {
      update_post_meta($post_id, 'nor_summary_en', $val);
    }
  }

  // Content mode
  if (isset($_POST['nor_content_mode'])) {
    $raw = (string) wp_unslash($_POST['nor_content_mode']);
    $val = ($raw === 'full') ? 'full' : 'text';
    update_post_meta($post_id, 'nor_content_mode', $val);
  }

  // Gallery IDs (comma-separated)
  if (isset($_POST['nor_gallery_ids'])) {
    $raw = (string) wp_unslash($_POST['nor_gallery_ids']);
    $raw = preg_replace('/[^0-9,]/', '', $raw);
    $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
    $ids = array_unique($ids);

    if (empty($ids)) {
      delete_post_meta($post_id, 'nor_gallery_ids');
    } else {
      // Store as comma-separated string to keep it easy to edit/debug
      update_post_meta($post_id, 'nor_gallery_ids', implode(',', $ids));
    }
  }

  // Main / End clients (meta)
  // - Main: single term_id
  // - End: array of term_ids (checkboxes)
  $main_id = 0;
  if (isset($_POST['nor_main_client_id'])) {
    $raw = wp_unslash($_POST['nor_main_client_id']);
    $main_id = is_numeric($raw) ? (int) $raw : 0;
    if ($main_id <= 0) {
      delete_post_meta($post_id, 'nor_main_client_id');
    } else {
      update_post_meta($post_id, 'nor_main_client_id', $main_id);
    }
  }

  // End clients are optional. When nothing is checked, treat as empty and clear meta.
  $end_ids_raw = isset($_POST['nor_end_client_ids']) ? wp_unslash($_POST['nor_end_client_ids']) : [];
  $end_ids = nor_parse_id_list($end_ids_raw);
  // Never store main in end list
  $end_ids = array_values(array_filter($end_ids, fn($id) => (int) $id > 0 && (int) $id !== (int) $main_id));
  if (empty($end_ids)) {
    delete_post_meta($post_id, 'nor_end_client_ids');
  } else {
    update_post_meta($post_id, 'nor_end_client_ids', implode(',', $end_ids));
  }

  // Sync taxonomy assignments so client term archives continue to work.
  if ($main_id > 0) {
    nor_sync_work_client_terms((int) $post_id, (int) $main_id, $end_ids);
  }
});

/**
 * Admin: Works hard validation (required / unique fields)
 * - If required fields are missing or duplicate, force status to draft and show an error notice.
 *
 * Rules (hard-stop):
 * - Title is required
 * - Work No (nor_work_no) is required and must be unique
 */
add_filter('wp_insert_post_data', function ($data, $postarr) {
  // Run for admin saves including block editor (REST). Skip only ajax-like contexts.
  if (!is_admin() || wp_doing_ajax()) return $data;
  if (!isset($data['post_type']) || $data['post_type'] !== 'works') return $data;

  // Allow trash/delete actions to proceed without validation.
  // Without this, attempts to move invalid test posts to Trash can be forced back to draft.
  $next_status = isset($data['post_status']) ? (string) $data['post_status'] : '';
  $req_action  = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
  $req_action2 = isset($_REQUEST['action2']) ? (string) $_REQUEST['action2'] : '';

  if (
    $next_status === 'trash' ||
    in_array($req_action, ['trash', 'delete', 'bulk-trash', 'bulk-delete', 'untrash'], true) ||
    in_array($req_action2, ['trash', 'delete', 'bulk-trash', 'bulk-delete', 'untrash'], true)
  ) {
    return $data;
  }

  /**
   * Skip validation for auto-draft.
   * Opening "Add New Work" creates an auto-draft internally.
   * We must not warn/block on that initial auto-draft creation.
   */
  if ($next_status === 'auto-draft') {
    return $data;
  }

  $pid = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;

  // Clear stale work-no notices before re-validating (otherwise old duplicate messages remain).
  if ($pid > 0) {
    nor_clear_work_no_admin_notices($pid);
  }

  // Collect hard-stop errors so we can show multiple notices at once.
  $hard_errors = [];

  $title = isset($data['post_title']) ? trim((string) $data['post_title']) : '';
  if ($title === '') {
    $hard_errors[] = 'タイトルが未入力のため、公開せず下書きとして保存しました。';
  }

  // Work No: required + unique (supports classic + block editor)
  $work_no = nor_get_submitted_work_no((array) $postarr);

  if ($work_no === '') {
    // If the field wasn't in the submission (some editor flows), fall back to stored meta.
    if ($pid > 0) {
      $stored = get_post_meta($pid, 'nor_work_no', true);
      $stored = nor_normalize_work_no($stored);
      if ($stored !== '') {
        $work_no = $stored;
      }
    }
  }

  if ($work_no === '') {
    // Make the reason explicit: we saved as draft because work-no is required.
    $hard_errors[] = '採番が未入力のため、公開せず下書きとして保存しました。';
  } else {
    $dup_id = nor_find_work_id_by_work_no($work_no, $pid);
    if ($dup_id > 0) {
      $hard_errors[] = '採番が「記事ID: ' . $dup_id . '」と重複しているため、公開せず下書きとして保存しました。';
    }
  }

  // If any hard-stop errors exist, force draft and attach all notices.
  if (!empty($hard_errors)) {
    $data['post_status'] = 'draft';

    foreach ($hard_errors as $msg) {
      if ($pid > 0) {
        nor_append_work_admin_notice($pid, (string) $msg);
      } else {
        nor_append_work_admin_notice_for_user((string) $msg);
      }
    }

    return $data;
  }

  return $data;
}, 10, 2);


add_action('admin_notices', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen) return;
  if (($screen->post_type ?? '') !== 'works') return;
  if (!in_array((string) ($screen->base ?? ''), ['post', 'post-new'], true)) return;

  $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
  if ($post_id <= 0) {
    // New post screen: show user-scoped notices (no post ID yet)
    $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
    if ($uid <= 0) return;

    $user_key = 'nor_work_admin_notice_user_' . $uid;
    $msg = get_transient($user_key);
    if (!is_string($msg) || trim($msg) === '') return;

    $lines = preg_split("/\r\n|\r|\n/", (string) $msg);
    $lines = array_filter(array_map('trim', (array) $lines));

    echo '<div class="notice notice-warning is-dismissible">';
    if (count($lines) <= 1) {
      echo '<p>' . esc_html($msg) . '</p>';
    } else {
      echo '<ul style="margin:0.5em 0 0.5em 1.2em; list-style:disc;">';
      foreach ($lines as $l) {
        echo '<li>' . esc_html($l) . '</li>';
      }
      echo '</ul>';
    }
    echo '</div>';
    // One-shot notice
    delete_transient($user_key);
    return;
  }

  $msg = get_transient('nor_work_admin_notice_' . $post_id);
  if (!is_string($msg) || trim($msg) === '') return;

  $lines = preg_split("/\r\n|\r|\n/", (string) $msg);
  $lines = array_filter(array_map('trim', (array) $lines));

  echo '<div class="notice notice-warning is-dismissible">';
  if (count($lines) <= 1) {
    echo '<p>' . esc_html($msg) . '</p>';
  } else {
    echo '<ul style="margin:0.5em 0 0.5em 1.2em; list-style:disc;">';
    foreach ($lines as $l) {
      echo '<li>' . esc_html($l) . '</li>';
    }
    echo '</ul>';
  }
  echo '</div>';
  // One-shot notices (prevents stale/accumulating messages across saves)
  delete_transient('nor_work_admin_notice_' . $post_id);
});

/**
 * Admin dashboard notice:
 * Show warning when Notes page has not been updated for 14+ days.
 */
add_action('admin_notices', function () {
  if (!is_admin()) return;
  if (!current_user_can('edit_pages')) return;

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen) return;
  if ((string) ($screen->base ?? '') !== 'dashboard') return;

  $notes = get_page_by_path('notes');
  if (!$notes instanceof WP_Post) return;
  if ((string) $notes->post_status === 'trash') return;

  $last_modified_ts = (int) get_post_modified_time('U', true, $notes->ID);
  if ($last_modified_ts <= 0) return;

  $elapsed_days = (int) floor((time() - $last_modified_ts) / DAY_IN_SECONDS);
  if ($elapsed_days < 14) return;

  echo '<div class="notice notice-warning"><p>NotesページのDiagnosticsが、前回の更新から14日以上経過しています</p></div>';
});

/**
 * Admin: Works validation notice (required fields / taxonomy rules)
 * - Shows non-blocking warnings after saving.
 *
 * Rules:
 * - Category must be selected
 * - Client must be selected
 * - Excerpt (Summary JA) must be filled
 * - Slug must not be empty
 * - Tagline (nor_tagline) must be filled
 * - Summary (EN) (nor_summary_en) must be filled
 * - Content mode must be explicitly selected (nor_content_mode must be set)
 * - Tags group rules (work_tag):
 *   - Types must be selected (either Document types OR Site types)
 *   - Document types and Site types must NOT coexist
 *   - Roles must be selected
 *   - Tools must be selected
 */
add_action('save_post_works', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  // Only on normal editor save requests.
  if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

  $warnings = [];

  // 1) Slug
  $slug = get_post_field('post_name', $post_id);
  $slug = is_string($slug) ? trim($slug) : '';
  if ($slug === '') {
    $warnings[] = 'スラッグが入力されていません。';
  }

  // 2) Tagline
  $tagline = get_post_meta($post_id, 'nor_tagline', true);
  $tagline = is_string($tagline) ? trim($tagline) : '';
  if ($tagline === '') {
    $warnings[] = 'タグラインが入力されていません。';
  }

  // 3) Page description (JA) via Excerpt
  $excerpt = get_post_field('post_excerpt', $post_id);
  $excerpt = is_string($excerpt) ? trim($excerpt) : '';
  if ($excerpt === '') {
    $warnings[] = 'ページ説明（JA）が入力されていません。抜粋欄に入力してください。';
  }

  // 4) Page description (EN)
  $summary_en = get_post_meta($post_id, 'nor_summary_en', true);
  $summary_en = is_string($summary_en) ? trim($summary_en) : '';
  if ($summary_en === '') {
    $warnings[] = 'ページ説明（EN）が入力されていません。';
  }

  // 5) Content mode (must be stored)
  $content_mode = get_post_meta($post_id, 'nor_content_mode', true);
  $content_mode = is_string($content_mode) ? trim($content_mode) : '';
  if ($content_mode === '') {
    $warnings[] = 'コンテンツモードが選択されていません。';
  }

  // 6) Full-content visuals count (featured + gallery >= 3)
  if ($content_mode === 'full') {
    $has_featured = has_post_thumbnail($post_id);

    $raw_gallery = get_post_meta($post_id, 'nor_gallery_ids', true);
    $ids = [];
    if (is_string($raw_gallery) && $raw_gallery !== '') {
      $ids = array_values(array_filter(array_map('intval', explode(',', $raw_gallery))));
    } elseif (is_array($raw_gallery)) {
      $ids = array_values(array_filter(array_map('intval', $raw_gallery)));
    }

    $total_visuals = ($has_featured ? 1 : 0) + count($ids);
    if ($total_visuals < 3) {
      $warnings[] = 'コンテンツモードが Full-content の場合、アイキャッチ画像とサブ画像で合わせて3枚以上を登録してください。';
    }
  }

  // 7) Category
  $cats = get_the_terms($post_id, 'work_category');
  if (empty($cats) || is_wp_error($cats)) {
    $warnings[] = 'カテゴリーが選択されていません。';
  }

  // 8) Main client
  $main_client_id = get_post_meta($post_id, 'nor_main_client_id', true);
  $main_client_id = is_numeric($main_client_id) ? (int) $main_client_id : 0;
  if ($main_client_id <= 0) {
    $warnings[] = 'メインクライアントが選択されていません。';
  }

  // --- Tags group rules ---
  $tags = get_the_terms($post_id, 'work_tag');
  if (!empty($tags) && !is_wp_error($tags)) {
    // Resolve group roots by slug (preferred) and name (fallback)
    $root_doc  = get_term_by('slug', 'document-types', 'work_tag');
    if (!$root_doc)  $root_doc  = get_term_by('name', 'Document types', 'work_tag');

    $root_site = get_term_by('slug', 'site-types', 'work_tag');
    if (!$root_site) $root_site = get_term_by('name', 'Site types', 'work_tag');

    $root_roles = get_term_by('slug', 'roles', 'work_tag');
    if (!$root_roles) $root_roles = get_term_by('name', 'Roles', 'work_tag');

    $root_tools = get_term_by('slug', 'tools', 'work_tag');
    if (!$root_tools) $root_tools = get_term_by('name', 'Tools', 'work_tag');

    $root_ids = [
      'doc'   => ($root_doc instanceof WP_Term) ? (int) $root_doc->term_id : 0,
      'site'  => ($root_site instanceof WP_Term) ? (int) $root_site->term_id : 0,
      'roles' => ($root_roles instanceof WP_Term) ? (int) $root_roles->term_id : 0,
      'tools' => ($root_tools instanceof WP_Term) ? (int) $root_tools->term_id : 0,
    ];

    $has = [
      'doc'   => false,
      'site'  => false,
      'roles' => false,
      'tools' => false,
    ];

    // Determine each selected tag's top-level root
    foreach ($tags as $t) {
      if (!($t instanceof WP_Term)) continue;

      $cur = $t;
      $guard = 0;
      while ($cur instanceof WP_Term && (int) $cur->parent > 0 && $guard < 12) {
        $cur = get_term((int) $cur->parent, 'work_tag');
        $guard++;
      }

      $root_id = ($cur instanceof WP_Term) ? (int) $cur->term_id : 0;
      if ($root_id > 0) {
        if ($root_ids['doc'] > 0 && $root_id === $root_ids['doc']) $has['doc'] = true;
        if ($root_ids['site'] > 0 && $root_id === $root_ids['site']) $has['site'] = true;
        if ($root_ids['roles'] > 0 && $root_id === $root_ids['roles']) $has['roles'] = true;
        if ($root_ids['tools'] > 0 && $root_id === $root_ids['tools']) $has['tools'] = true;
      }
    }

    // Types required: doc OR site
    if (!$has['doc'] && !$has['site']) {
      $warnings[] = 'タグの「Document types」か「Site types」のどちらかを選択してください。';
    }

    // Doc and Site must not coexist
    if ($has['doc'] && $has['site']) {
      $warnings[] = 'タグの「Document types」か「Site types」は共存できません。';
    }

    // Roles required
    if (!$has['roles']) {
      $warnings[] = 'タグの「Roles」が選択されていません。';
    }

    // Tools required
    if (!$has['tools']) {
      $warnings[] = 'タグの「Tools」が選択されていません。';
    }
  } else {
    // No tags at all => all tag-group requirements fail
    $warnings[] = 'タグの「Document types」か「Site types」のどちらかを選択してください。';
    $warnings[] = 'タグの「Roles」が選択されていません。';
    $warnings[] = 'タグの「Tools」が選択されていません。';
  }

  // --- Merge with existing notice (e.g., 3-visuals warning) ---
  $key = 'nor_work_admin_notice_' . $post_id;
  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';

  if (empty($warnings)) {
    if ($existing === '') {
      delete_transient($key);
    }
    return;
  }

  $msg = implode("\n", $warnings);
  if ($existing !== '') {
    $existing_lines = preg_split("/\r\n|\r|\n/", $existing);
    $existing_lines = array_filter(array_map('trim', (array) $existing_lines));

    $new_lines = preg_split("/\r\n|\r|\n/", $msg);
    $new_lines = array_filter(array_map('trim', (array) $new_lines));

    $merged = $existing_lines;
    foreach ($new_lines as $l) {
      if (!in_array($l, $merged, true)) $merged[] = $l;
    }
    $msg = implode("\n", $merged);
  }

  set_transient($key, $msg, 5 * MINUTE_IN_SECONDS);
}, 30);

// Admin JS for Works gallery picker
add_action('admin_enqueue_scripts', function ($hook) {
  // Only on Works edit screens
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || ($screen->post_type ?? '') !== 'works') return;
  if (!in_array((string) ($screen->base ?? ''), ['post', 'post-new'], true)) return;

  wp_enqueue_media();
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-sortable');

  $js = <<<'JS'
(function($){
  function renderPreview(ids){
    var $wrap = $('#nor_gallery_preview');
    if (!$wrap.length) return;
    $wrap.empty();
    if (!ids || !ids.length) return;

    ids.forEach(function(id){
      if (!id) return;
      wp.media.attachment(id).fetch().then(function(){
        var att = wp.media.attachment(id);
        var url = att.get('sizes') && att.get('sizes').thumbnail ? att.get('sizes').thumbnail.url : att.get('url');
        if (!url) return;
        var $img = $('<img/>', {src: url, alt: '', style: 'width:80px;height:auto;border:1px solid #ccd0d4;'});
        $wrap.append($('<div/>', {'data-aid': id}).append($img));
      });
    });
    // Enable manual ordering
    $wrap.sortable({
      items: '> div[data-aid]',
      tolerance: 'pointer',
      update: function(){
        var ordered = [];
        $wrap.find('div[data-aid]').each(function(){
          var id = parseInt($(this).attr('data-aid'), 10) || 0;
          if (id) ordered.push(id);
        });
        $('#nor_gallery_ids').val(ordered.join(','));
      }
    });
    // Reflect current mode on sortable state
    var mode = $('input[name="nor_content_mode"]:checked').val();
    if (mode !== 'full') {
      $wrap.sortable('disable');
    }
  }

  function getIds(){
    var raw = String($('#nor_gallery_ids').val() || '').trim();
    if (!raw) return [];
    return raw.split(',').map(function(x){ return parseInt(x,10) || 0; }).filter(Boolean);
  }

  function setIds(ids){
    ids = (ids || []).map(function(x){ return parseInt(x,10) || 0; }).filter(Boolean);
    // unique, keep order
    var seen = {};
    var ordered = [];
    ids.forEach(function(x){
      if (!x) return;
      if (seen[x]) return;
      seen[x] = true;
      ordered.push(x);
    });
    ids = ordered;
    $('#nor_gallery_ids').val(ids.join(','));
    renderPreview(ids);
  }

  function toggleGalleryBlock(){
    var mode = $('input[name="nor_content_mode"]:checked').val();
    var isFull = (mode === 'full');

    var $blk = $('#nor_gallery_block');
    if (!$blk.length) return;

    // Always show the block, but disable interaction for Text-only
    $blk.show();

    $('#nor_gallery_pick, #nor_gallery_clear').prop('disabled', !isFull);

    var $wrap = $('#nor_gallery_preview');
    if ($wrap.length) {
      if ($wrap.data('ui-sortable')) {
        if (isFull) {
          $wrap.sortable('enable');
          $blk.removeClass('is-disabled');
          $wrap.css('opacity', '');
        } else {
          $wrap.sortable('disable');
          $blk.addClass('is-disabled');
          $wrap.css('opacity', '0.6');
        }
      } else {
        // If sortable not initialized yet, just reflect visual state
        if (isFull) {
          $blk.removeClass('is-disabled');
          $wrap.css('opacity', '');
        } else {
          $blk.addClass('is-disabled');
          $wrap.css('opacity', '0.6');
        }
      }
    }
  }

  $(function(){
    // initial preview
    setIds(getIds());

    // initial visibility
    toggleGalleryBlock();

    // react to mode changes
    $(document).on('change', 'input[name="nor_content_mode"]', function(){
      toggleGalleryBlock();
    });

    var frame;
    $('#nor_gallery_pick').on('click', function(e){
      e.preventDefault();
      var mode = $('input[name="nor_content_mode"]:checked').val();
      if (mode !== 'full') return;

      if (frame) { frame.open(); return; }

      frame = wp.media({
        title: 'Select gallery images',
        library: { type: 'image' },
        button: { text: 'Use selected images' },
        multiple: true
      });

      frame.on('open', function(){
        // Preselect existing
        var selection = frame.state().get('selection');
        selection.reset();
        selection.multiple = true;
        getIds().forEach(function(id){
          var att = wp.media.attachment(id);
          att.fetch();
          selection.add(att);
        });
      });

      frame.on('select', function(){
        var selection = frame.state().get('selection');
        var ids = [];
        selection.each(function(att){
          var id = parseInt(att.get('id'), 10) || 0;
          if (id) ids.push(id);
        });
        setIds(ids);
      });

      frame.open();
    });

    $('#nor_gallery_clear').on('click', function(e){
      e.preventDefault();
      var mode = $('input[name="nor_content_mode"]:checked').val();
      if (mode !== 'full') return;
      setIds([]);
    });
  });
})(jQuery);
JS;

  // Attach to jQuery handle (already enqueued)
  wp_add_inline_script('jquery', $js, 'after');
});

/**
 * Admin: Attachment (image) caption EN
 * - Caption (JA): attachment caption (WP standard)
 * - Caption (EN): attachment meta nor_caption_en
 */

add_filter('attachment_fields_to_edit', function ($fields, $post) {
  if (!($post instanceof WP_Post)) return $fields;
  if (strpos((string) $post->post_mime_type, 'image/') !== 0) return $fields;

  $val = get_post_meta($post->ID, 'nor_caption_en', true);
  $val = is_string($val) ? $val : '';

  $fields['nor_caption_en'] = [
    'label' => 'キャプション（EN）',
    'input' => 'textarea',
    'value' => $val,
    'helps' => 'この画像に対する英語のキャプション。日本語のキャプションは標準のキャプションを使用します。',
  ];

  return $fields;
}, 10, 2);

add_filter('attachment_fields_to_save', function ($post, $attachment) {
  if (!isset($post['ID'])) return $post;
  $id = (int) $post['ID'];

  if (isset($attachment['nor_caption_en'])) {
    $raw = wp_unslash($attachment['nor_caption_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($id, 'nor_caption_en');
    } else {
      update_post_meta($id, 'nor_caption_en', $val);
    }
  }

  return $post;
}, 10, 2);

// Auto-migrate legacy Client selections (work_client terms) into meta-driven Main/End fields.
add_action('save_post_works', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

  nor_migrate_clients_if_needed((int) $post_id);
}, 40);

/**
 * Policies page builder
 * - Meta keys:
 *   - nor_policies_last_updated (string)
 *   - nor_policies_groups (array)
 */
if (!function_exists('nor_policies_default_groups')) {
  function nor_policies_default_groups(): array {
    return [
      [
        'group_id' => 'policies-publishing-and-rights',
        'group_title_en' => 'Publishing & Rights',
        'group_name_ja' => '公開と権利',
        'items' => [],
      ],
      [
        'group_id' => 'policies-group-operations-and-security',
        'group_title_en' => 'Operations & Security',
        'group_name_ja' => '運用とセキュリティ',
        'items' => [],
      ],
      [
        'group_id' => 'policies-group-legal-framework',
        'group_title_en' => 'Legal Framework',
        'group_name_ja' => '法的枠組み',
        'items' => [],
      ],
    ];
  }
}

if (!function_exists('nor_policies_allowed_html')) {
  function nor_policies_allowed_html(): array {
    // Align with About-style sanitization (post content baseline),
    // then explicitly allow attributes we rely on in Policies.
    $allowed = wp_kses_allowed_html('post');

    $allowed['a']['target'] = true;
    $allowed['a']['rel'] = true;
    $allowed['a']['class'] = true;

    $allowed['abbr']['title'] = true;
    $allowed['code']['class'] = true;
    $allowed['p']['class'] = true;
    $allowed['p']['lang'] = true;
    $allowed['span']['class'] = true;
    $allowed['span']['lang'] = true;
    $allowed['div']['class'] = true;
    $allowed['div']['lang'] = true;
    $allowed['br']['class'] = true;
    $allowed['li']['class'] = true;

    if (!isset($allowed['time']) || !is_array($allowed['time'])) {
      $allowed['time'] = [];
    }
    $allowed['time']['datetime'] = true;
    $allowed['time']['class'] = true;

    return $allowed;
  }
}

if (!function_exists('nor_policies_sanitize_rich_text')) {
  function nor_policies_rewrite_asset_links(string $html): string {
    $html = (string) $html;
    if ($html === '' || strpos($html, 'href="/"') === false) return $html;

    $asset_map = [
      'nor.tokens.css' => '/assets/css/nor.tokens.css',
      'nor.base.css'   => '/assets/css/nor.base.css',
      'nor.ui.css'     => '/assets/css/nor.ui.css',
      'nor.js'         => '/assets/js/nor.js',
    ];

    return (string) preg_replace_callback(
      '/<a(?P<pre>[^>]*?)\shref=(?P<q>[\'"])\/(?P=q)(?P<post>[^>]*)>\s*<code>\s*(?P<file>nor\.(?:tokens|base|ui)\.css|nor\.js)\s*<\/code>\s*<\/a>/i',
      static function (array $m) use ($asset_map): string {
        $file = isset($m['file']) ? strtolower((string) $m['file']) : '';
        $path = $asset_map[$file] ?? '/';
        $pre = isset($m['pre']) ? (string) $m['pre'] : '';
        $post = isset($m['post']) ? (string) $m['post'] : '';
        $q = isset($m['q']) ? (string) $m['q'] : '"';

        return '<a' . $pre . ' href=' . $q . $path . $q . $post . '><code>' . $file . '</code></a>';
      },
      $html
    );
  }

  function nor_policies_sanitize_rich_text(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    $raw = wp_check_invalid_utf8($raw, true);
    $safe = trim(wp_kses($raw, nor_policies_allowed_html()));
    if ($safe === '') return '';
    return trim(nor_policies_rewrite_asset_links($safe));
  }
}

if (!function_exists('nor_policies_encode_rich_storage')) {
  function nor_policies_encode_rich_storage(string $raw): string {
    $raw = (string) $raw;
    if ($raw === '') return '';
    $b64url = nor_policies_base64url_encode($raw);
    if ($b64url === '') return '';
    return 'b64:' . $b64url;
  }
}

if (!function_exists('nor_policies_base64url_encode')) {
  function nor_policies_base64url_encode(string $raw): string {
    $raw = (string) $raw;
    if ($raw === '') return '';
    $b64 = base64_encode($raw);
    if (!is_string($b64) || $b64 === '') return '';
    return rtrim(strtr($b64, '+/', '-_'), '=');
  }
}

if (!function_exists('nor_policies_decode_rich_storage')) {
  function nor_policies_decode_rich_storage(string $raw): string {
    $raw = (string) $raw;
    if ($raw === '') return '';
    if (strncmp($raw, 'b64:', 4) !== 0) return $raw;

    $payload = substr($raw, 4);
    if ($payload === false || $payload === '') return '';

    $normalized = strtr($payload, '-_', '+/');
    $pad = strlen($normalized) % 4;
    if ($pad > 0) {
      $normalized .= str_repeat('=', 4 - $pad);
    }

    $decoded = base64_decode($normalized, true);
    return is_string($decoded) ? $decoded : '';
  }
}

if (!function_exists('nor_policies_normalize_anchor_id')) {
  function nor_policies_normalize_anchor_id(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    $raw = ltrim($raw, '#');

    if ($raw === '' && $fallback !== '') {
      $raw = ltrim(trim($fallback), '#');
    }

    $id = sanitize_title($raw);

    if ($id === '' && $fallback !== '') {
      $id = sanitize_title(ltrim(trim($fallback), '#'));
    }

    return $id;
  }
}

if (!function_exists('nor_policies_get_groups')) {
  function nor_policies_get_groups(int $post_id, bool $for_editor = false): array {
    $defaults = nor_policies_default_groups();
    $saved = get_post_meta($post_id, 'nor_policies_groups', true);
    $saved = is_array($saved) ? $saved : [];

    $source_groups = $saved;
    if (empty($source_groups)) {
      $source_groups = $defaults;
    }

    $groups = [];

    foreach ($source_groups as $g_idx => $src) {
      if (!is_array($src)) continue;

      $def = isset($defaults[$g_idx]) && is_array($defaults[$g_idx])
        ? $defaults[$g_idx]
        : [
          'group_id' => 'policies-group-' . ((int) $g_idx + 1),
          'group_title_en' => '',
          'group_name_ja' => '',
        ];

      if ($for_editor) {
        $group_id = nor_policies_normalize_anchor_id((string) ($src['group_id'] ?? ''), '');
      } else {
        $group_id = nor_policies_normalize_anchor_id((string) ($src['group_id'] ?? $def['group_id']), (string) $def['group_id']);
      }

      $group_title_en = sanitize_text_field((string) ($src['group_title_en'] ?? $def['group_title_en']));
      if ($group_title_en === '') $group_title_en = (string) $def['group_title_en'];

      $group_name_ja = sanitize_text_field((string) ($src['group_name_ja'] ?? $def['group_name_ja']));
      if ($group_name_ja === '') $group_name_ja = (string) $def['group_name_ja'];

      $items = [];
      $src_items = isset($src['items']) && is_array($src['items']) ? $src['items'] : [];

      foreach ($src_items as $item_key => $row) {
        if (!is_array($row)) continue;

        $title_en = sanitize_text_field((string) ($row['title_en'] ?? ''));

        if ($for_editor) {
          $anchor = nor_policies_normalize_anchor_id((string) ($row['anchor'] ?? ''), '');
        } else {
          $fallback_anchor = $title_en !== ''
            ? 'policies-heading-' . sanitize_title($title_en)
            : ('policies-heading-item-' . ((int) count($items) + 1));
          $anchor = nor_policies_normalize_anchor_id((string) ($row['anchor'] ?? ''), $fallback_anchor);
        }

        $column = isset($row['column']) ? (int) $row['column'] : 0;
        if ($column < 1 || $column > 3) $column = 0;

        $position = isset($row['position']) ? (int) $row['position'] : 0;
        if ($position < 0) $position = 0;

        $summary_ja_raw = nor_policies_decode_rich_storage((string) ($row['summary_ja'] ?? ''));
        $summary_en_raw = nor_policies_decode_rich_storage((string) ($row['summary_en'] ?? ''));
        $body_ja_raw = nor_policies_decode_rich_storage((string) ($row['body_ja'] ?? ''));
        $body_en_raw = nor_policies_decode_rich_storage((string) ($row['body_en'] ?? ''));

        if ($for_editor) {
          // Keep raw values in admin so authors can continue editing
          // even if markup is temporarily incomplete.
          $summary_ja = $summary_ja_raw;
          $summary_en = $summary_en_raw;
          $body_ja = $body_ja_raw;
          $body_en = $body_en_raw;
        } else {
          $summary_ja = nor_policies_sanitize_rich_text($summary_ja_raw);
          $summary_en = nor_policies_sanitize_rich_text($summary_en_raw);
          $body_ja = nor_policies_sanitize_rich_text($body_ja_raw);
          $body_en = nor_policies_sanitize_rich_text($body_en_raw);
        }

        $is_empty = ($title_en === '' && $summary_ja === '' && $summary_en === '' && $body_ja === '' && $body_en === '');
        if ($is_empty && !$for_editor) continue;

        $safe_key = is_string($item_key) ? sanitize_key((string) $item_key) : '';
        if ($safe_key === '') {
          $safe_key = 'item_' . ((int) count($items) + 1);
        }

        $items[$safe_key] = [
          'title_en' => $title_en,
          'anchor' => $anchor,
          'column' => $column,
          'position' => $position,
          'summary_ja' => $summary_ja,
          'summary_en' => $summary_en,
          'body_ja' => $body_ja,
          'body_en' => $body_en,
        ];
      }

      $groups[] = [
        'group_id' => $group_id,
        'group_title_en' => $group_title_en,
        'group_name_ja' => $group_name_ja,
        'items' => $items,
      ];
    }

    return $groups;
  }
}

add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;
  if ((string) $post->post_name !== 'policies') return;

  add_meta_box(
    'nor_policies_page_updated',
    '更新日',
    function ($post) {
      if (!$post instanceof WP_Post) return;

      $updated = get_post_meta($post->ID, 'nor_policies_last_updated', true);
      $updated = is_string($updated) ? $updated : '';

      wp_nonce_field('nor_policies_page_save', 'nor_policies_page_nonce');
      ?>
      <p style="margin:0 0 4px;"><label for="nor_policies_last_updated"><strong>Last updated</strong></label></p>
      <p class="description" style="margin:0 0 8px;">ポリシーの改定・最終更新日を「YYYY-MM-DD」で入力してください。</p>
      <input
        id="nor_policies_last_updated"
        name="nor_policies_last_updated"
        type="text"
        class="regular-text"
        value="<?php echo esc_attr($updated); ?>"
        placeholder="YYYY-MM-DD"
        inputmode="numeric"
        pattern="\d{4}-\d{2}-\d{2}"
        aria-describedby="nor-policies-last-updated-warn"
      />
      <p
        id="nor-policies-last-updated-warn"
        class="description"
        style="margin-top:4px; color:#b32d2e; display:none;"
      >更新日が未入力です。</p>
      <script>
      (function () {
        var input = document.getElementById('nor_policies_last_updated');
        var warn = document.getElementById('nor-policies-last-updated-warn');
        if (!input || !warn) return;

        var toggleWarn = function () {
          var empty = (input.value || '').trim() === '';
          warn.style.display = empty ? 'block' : 'none';
          input.setAttribute('aria-invalid', empty ? 'true' : 'false');
        };

        toggleWarn();
        input.addEventListener('input', toggleWarn);
        input.addEventListener('change', toggleWarn);

        var form = document.getElementById('post') || input.closest('form');
        if (form) {
          form.addEventListener('submit', toggleWarn);
        }
      })();
      </script>
      <?php
    },
    'page',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_policies_page_builder',
    'ポリシーグループ',
    function ($post) {
      if (!$post instanceof WP_Post) return;

      $groups = nor_policies_get_groups((int) $post->ID, true);

      wp_nonce_field('nor_policies_page_save', 'nor_policies_page_nonce');

      $render_item = function (int $group_index, string $item_key, array $item): void {
        $title_en = isset($item['title_en']) ? (string) $item['title_en'] : '';
        $anchor = isset($item['anchor']) ? (string) $item['anchor'] : '';
        $anchor = $anchor !== '' ? '#' . ltrim($anchor, '#') : '';

        $column = isset($item['column']) ? (int) $item['column'] : 0;
        if ($column < 1 || $column > 3) $column = 0;

        $position = isset($item['position']) ? (int) $item['position'] : 0;
        if ($position < 0) $position = 0;

        $summary_ja = isset($item['summary_ja']) ? (string) $item['summary_ja'] : '';
        $summary_en = isset($item['summary_en']) ? (string) $item['summary_en'] : '';
        $body_ja = isset($item['body_ja']) ? (string) $item['body_ja'] : '';
        $body_en = isset($item['body_en']) ? (string) $item['body_en'] : '';
        $summary_ja_b64 = nor_policies_base64url_encode($summary_ja);
        $summary_en_b64 = nor_policies_base64url_encode($summary_en);
        $body_ja_b64 = nor_policies_base64url_encode($body_ja);
        $body_en_b64 = nor_policies_base64url_encode($body_en);

        $base = 'nor_policies_groups[' . $group_index . '][items][' . $item_key . ']';
        ?>
        <div class="nor-policies-item" data-item-key="<?php echo esc_attr($item_key); ?>" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">
          <p style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin:0 0 8px;">
            <strong>ポリシー</strong>
            <button type="button" class="button-link-delete nor-policies-remove-item">このポリシーを削除</button>
          </p>

          <p style="margin:8px 0 4px;"><label><strong>ポリシータイトル（EN）</strong></label></p>
          <input type="text" style="width:100%" data-field="title_en" name="<?php echo esc_attr($base . '[title_en]'); ?>" value="<?php echo esc_attr($title_en); ?>" />

          <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>
          <input type="text" style="width:100%" data-field="anchor" name="<?php echo esc_attr($base . '[anchor]'); ?>" value="<?php echo esc_attr($anchor); ?>" placeholder="#policies-heading-example" required />

          <div style="display:flex; gap:12px; margin-top:8px;">
            <div style="flex:1 1 auto; min-width:0;">
              <p style="margin:0 0 4px;"><label><strong>配置カラム</strong></label></p>
              <select style="width:100%" data-field="column" name="<?php echo esc_attr($base . '[column]'); ?>">
                <option value="0"<?php selected($column, 0); ?>>自動</option>
                <option value="1"<?php selected($column, 1); ?>>1カラム目</option>
                <option value="2"<?php selected($column, 2); ?>>2カラム目</option>
                <option value="3"<?php selected($column, 3); ?>>3カラム目</option>
              </select>
            </div>
            <div style="width:160px;">
              <p style="margin:0 0 4px;"><label><strong>表示順</strong></label></p>
              <input type="number" min="0" step="1" style="width:100%" data-field="position" name="<?php echo esc_attr($base . '[position]'); ?>" value="<?php echo esc_attr((string) $position); ?>" />
            </div>
          </div>

          <p style="margin:8px 0 4px;"><label><strong>概要（JA）</strong></label></p>
          <textarea rows="3" style="width:100%" class="nor-policies-rich" data-rich-field="summary_ja"><?php echo esc_textarea($summary_ja); ?></textarea>
          <input type="hidden" class="nor-policies-rich-b64" data-rich-field="summary_ja" name="<?php echo esc_attr($base . '[summary_ja_b64]'); ?>" value="<?php echo esc_attr($summary_ja_b64); ?>" />

          <p style="margin:8px 0 4px;"><label><strong>概要（EN）</strong></label></p>
          <textarea rows="3" style="width:100%" class="nor-policies-rich" data-rich-field="summary_en"><?php echo esc_textarea($summary_en); ?></textarea>
          <input type="hidden" class="nor-policies-rich-b64" data-rich-field="summary_en" name="<?php echo esc_attr($base . '[summary_en_b64]'); ?>" value="<?php echo esc_attr($summary_en_b64); ?>" />

          <p style="margin:8px 0 4px;"><label><strong>本文（JA）</strong></label></p>
          <textarea rows="7" style="width:100%" class="nor-policies-rich" data-rich-field="body_ja"><?php echo esc_textarea($body_ja); ?></textarea>
          <input type="hidden" class="nor-policies-rich-b64" data-rich-field="body_ja" name="<?php echo esc_attr($base . '[body_ja_b64]'); ?>" value="<?php echo esc_attr($body_ja_b64); ?>" />

          <p style="margin:8px 0 4px;"><label><strong>本文（EN）</strong></label></p>
          <textarea rows="7" style="width:100%" class="nor-policies-rich" data-rich-field="body_en"><?php echo esc_textarea($body_en); ?></textarea>
          <input type="hidden" class="nor-policies-rich-b64" data-rich-field="body_en" name="<?php echo esc_attr($base . '[body_en_b64]'); ?>" value="<?php echo esc_attr($body_en_b64); ?>" />
        </div>
        <?php
      };

      ?>
      <div id="nor-policies-editor">
        <p class="description">ポリシーグループを追加し、グループの中に各ポリシーを入力できます。</p>
        <div class="nor-policies-groups">
          <?php foreach ($groups as $g_idx => $group) : ?>
            <?php
              $group_id = isset($group['group_id']) ? (string) $group['group_id'] : '';
              $group_title_en = isset($group['group_title_en']) ? (string) $group['group_title_en'] : '';
              $group_name_ja = isset($group['group_name_ja']) ? (string) $group['group_name_ja'] : '';
              $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
            ?>
            <section class="nor-policies-group" data-group-index="<?php echo esc_attr((string) $g_idx); ?>" style="margin-top:20px; padding:12px; border:1px solid #c9c9c9; background:#f8f9fa;">
              <p style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin:0 0 8px;">
                <strong class="nor-policies-group-heading">グループ <?php echo esc_html((string) ($g_idx + 1)); ?></strong>
                <button type="button" class="button-link-delete nor-policies-remove-group">このグループを削除</button>
              </p>
              <input type="hidden" data-group-field="group_id" name="<?php echo esc_attr('nor_policies_groups[' . $g_idx . '][group_id]'); ?>" value="<?php echo esc_attr($group_id); ?>" />

              <p style="margin:8px 0 4px;"><label><strong>グループ名（JA）</strong></label></p>
              <input type="text" style="width:100%" data-group-field="group_name_ja" name="<?php echo esc_attr('nor_policies_groups[' . $g_idx . '][group_name_ja]'); ?>" value="<?php echo esc_attr($group_name_ja); ?>" />

              <p style="margin:8px 0 4px;"><label><strong>グループ名（EN）</strong></label></p>
              <input type="text" style="width:100%" data-group-field="group_title_en" name="<?php echo esc_attr('nor_policies_groups[' . $g_idx . '][group_title_en]'); ?>" value="<?php echo esc_attr($group_title_en); ?>" />

              <div class="nor-policies-items" data-group-index="<?php echo esc_attr((string) $g_idx); ?>">
                <?php foreach ($items as $item_key => $item) : ?>
                  <?php $render_item((int) $g_idx, (string) $item_key, (array) $item); ?>
                <?php endforeach; ?>
              </div>

              <p style="margin-top:10px;"><button type="button" class="button nor-policies-add-item" data-group-index="<?php echo esc_attr((string) $g_idx); ?>">ポリシーを追加</button></p>
            </section>
          <?php endforeach; ?>
        </div>
        <p style="margin-top:12px;"><button type="button" class="button button-primary nor-policies-add-group">グループを追加</button></p>
      </div>

      <script>
      (function(){
        var root = document.getElementById('nor-policies-editor');
        if (!root) return;

        function newItemKey() {
          return 'new_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
        }

        function utf8ToBase64Url(str) {
          var utf8 = encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function (_, p1) {
            return String.fromCharCode(parseInt(p1, 16));
          });
          var b64 = window.btoa(utf8);
          return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
        }

        function syncRichEncoded(card) {
          if (!card) return;
          var textareas = card.querySelectorAll('textarea.nor-policies-rich[data-rich-field]');
          for (var i = 0; i < textareas.length; i++) {
            var ta = textareas[i];
            var field = ta.getAttribute('data-rich-field') || '';
            if (!field) continue;
            var hidden = card.querySelector('input.nor-policies-rich-b64[data-rich-field="' + field + '"]');
            if (!hidden) continue;
            hidden.value = utf8ToBase64Url(ta.value || '');
          }
        }

        function normalizeIdValue(raw) {
          var s = String(raw || '').trim();
          s = s.replace(/^#+/, '').toLowerCase();
          s = s.replace(/\s+/g, '-');
          s = s.replace(/[^a-z0-9\-_]+/g, '-');
          s = s.replace(/-+/g, '-');
          s = s.replace(/^-+/, '').replace(/-+$/, '');
          return s;
        }

        function ensureIdWarn(input) {
          if (!input || !input.parentNode) return null;
          var next = input.nextElementSibling;
          if (next && next.classList && next.classList.contains('nor-policies-id-warn')) return next;

          var warn = document.createElement('p');
          warn.className = 'description nor-policies-id-warn';
          warn.style.marginTop = '4px';
          warn.style.color = '#b32d2e';
          warn.style.display = 'none';
          input.insertAdjacentElement('afterend', warn);
          return warn;
        }

        function validatePolicyIds() {
          var idInputs = root.querySelectorAll('.nor-policies-item input[data-field="anchor"]');
          var counts = {};
          var normalized = [];

          for (var i = 0; i < idInputs.length; i++) {
            var input = idInputs[i];
            var raw = String(input.value || '').trim();
            var norm = normalizeIdValue(raw);
            normalized.push(norm);
            if (raw !== '' && norm !== '') {
              counts[norm] = (counts[norm] || 0) + 1;
            }
          }

          for (var j = 0; j < idInputs.length; j++) {
            var current = idInputs[j];
            var currentNorm = normalized[j] || '';
            var msg = '';

            if (currentNorm === '') {
              msg = 'IDが未入力です。';
            } else if (currentNorm !== '' && (counts[currentNorm] || 0) > 1) {
              msg = 'IDが重複しています。';
            }

            var warn = ensureIdWarn(current);
            if (warn) {
              warn.textContent = msg;
              warn.style.display = msg ? 'block' : 'none';
            }
            current.setAttribute('aria-invalid', msg ? 'true' : 'false');
          }
        }

        function buildItem(groupIndex, itemKey) {
          var base = 'nor_policies_groups[' + groupIndex + '][items][' + itemKey + ']';
          return ''
            + '<div class="nor-policies-item" data-item-key="' + itemKey + '" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">'
            + '  <p style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin:0 0 8px;"><strong>ポリシー</strong> <button type="button" class="button-link-delete nor-policies-remove-item">このポリシーを削除</button></p>'
            + '  <p style="margin:8px 0 4px;"><label><strong>ポリシータイトル（EN）</strong></label></p>'
            + '  <input type="text" style="width:100%" data-field="title_en" name="' + base + '[title_en]" value="" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>'
            + '  <input type="text" style="width:100%" data-field="anchor" name="' + base + '[anchor]" value="" placeholder="#policies-heading-example" required />'
            + '  <div style="display:flex; gap:12px; margin-top:8px;">'
            + '    <div style="flex:1 1 auto; min-width:0;">'
            + '      <p style="margin:0 0 4px;"><label><strong>配置カラム</strong></label></p>'
            + '      <select style="width:100%" data-field="column" name="' + base + '[column]">'
            + '        <option value="0" selected>自動</option>'
            + '        <option value="1">1カラム目</option>'
            + '        <option value="2">2カラム目</option>'
            + '        <option value="3">3カラム目</option>'
            + '      </select>'
            + '    </div>'
            + '    <div style="width:160px;">'
            + '      <p style="margin:0 0 4px;"><label><strong>表示順</strong></label></p>'
            + '      <input type="number" min="0" step="1" style="width:100%" data-field="position" name="' + base + '[position]" value="0" />'
            + '    </div>'
            + '  </div>'
            + '  <p style="margin:8px 0 4px;"><label><strong>概要（JA）</strong></label></p>'
            + '  <textarea rows="3" style="width:100%" class="nor-policies-rich" data-rich-field="summary_ja"></textarea>'
            + '  <input type="hidden" class="nor-policies-rich-b64" data-rich-field="summary_ja" name="' + base + '[summary_ja_b64]" value="" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>概要（EN）</strong></label></p>'
            + '  <textarea rows="3" style="width:100%" class="nor-policies-rich" data-rich-field="summary_en"></textarea>'
            + '  <input type="hidden" class="nor-policies-rich-b64" data-rich-field="summary_en" name="' + base + '[summary_en_b64]" value="" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>本文（JA）</strong></label></p>'
            + '  <textarea rows="7" style="width:100%" class="nor-policies-rich" data-rich-field="body_ja"></textarea>'
            + '  <input type="hidden" class="nor-policies-rich-b64" data-rich-field="body_ja" name="' + base + '[body_ja_b64]" value="" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>本文（EN）</strong></label></p>'
            + '  <textarea rows="7" style="width:100%" class="nor-policies-rich" data-rich-field="body_en"></textarea>'
            + '  <input type="hidden" class="nor-policies-rich-b64" data-rich-field="body_en" name="' + base + '[body_en_b64]" value="" />'
            + '</div>';
        }

        function buildGroup(groupIndex) {
          var groupBase = 'nor_policies_groups[' + groupIndex + ']';
          var groupLabel = 'グループ ' + (groupIndex + 1);
          var defaultGroupId = 'policies-group-' + (groupIndex + 1);
          return ''
            + '<section class="nor-policies-group" data-group-index="' + groupIndex + '" style="margin-top:20px; padding:12px; border:1px solid #c9c9c9; background:#f8f9fa;">'
            + '  <p style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin:0 0 8px;">'
            + '    <strong class="nor-policies-group-heading">' + groupLabel + '</strong>'
            + '    <button type="button" class="button-link-delete nor-policies-remove-group">このグループを削除</button>'
            + '  </p>'
            + '  <input type="hidden" data-group-field="group_id" name="' + groupBase + '[group_id]" value="' + defaultGroupId + '" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>グループ名（JA）</strong></label></p>'
            + '  <input type="text" style="width:100%" data-group-field="group_name_ja" name="' + groupBase + '[group_name_ja]" value="" />'
            + '  <p style="margin:8px 0 4px;"><label><strong>グループ名（EN）</strong></label></p>'
            + '  <input type="text" style="width:100%" data-group-field="group_title_en" name="' + groupBase + '[group_title_en]" value="" />'
            + '  <div class="nor-policies-items" data-group-index="' + groupIndex + '"></div>'
            + '  <p style="margin-top:10px;"><button type="button" class="button nor-policies-add-item" data-group-index="' + groupIndex + '">ポリシーを追加</button></p>'
            + '</section>';
        }

        function setGroupFieldNames(section, groupIndex) {
          var groupBase = 'nor_policies_groups[' + groupIndex + ']';
          var groupFields = section.querySelectorAll('[data-group-field]');
          for (var i = 0; i < groupFields.length; i++) {
            var el = groupFields[i];
            var field = el.getAttribute('data-group-field') || '';
            if (!field) continue;
            el.name = groupBase + '[' + field + ']';
            if (field === 'group_id' && !el.value) {
              el.value = 'policies-group-' + (groupIndex + 1);
            }
          }
        }

        function setItemFieldNames(card, groupIndex) {
          var itemKey = card.getAttribute('data-item-key') || '';
          if (!itemKey) {
            itemKey = newItemKey();
            card.setAttribute('data-item-key', itemKey);
          }

          var base = 'nor_policies_groups[' + groupIndex + '][items][' + itemKey + ']';

          var fieldEls = card.querySelectorAll('[data-field]');
          for (var i = 0; i < fieldEls.length; i++) {
            var el = fieldEls[i];
            var field = el.getAttribute('data-field') || '';
            if (!field) continue;
            el.name = base + '[' + field + ']';
          }

          var richHidden = card.querySelectorAll('input.nor-policies-rich-b64[data-rich-field]');
          for (var j = 0; j < richHidden.length; j++) {
            var hidden = richHidden[j];
            var richField = hidden.getAttribute('data-rich-field') || '';
            if (!richField) continue;
            hidden.name = base + '[' + richField + '_b64]';
          }
        }

        function reindexGroups() {
          var groupsWrap = root.querySelector('.nor-policies-groups');
          if (!groupsWrap) return;

          var sections = groupsWrap.querySelectorAll('.nor-policies-group');
          for (var s = 0; s < sections.length; s++) {
            var section = sections[s];
            section.setAttribute('data-group-index', String(s));

            var heading = section.querySelector('.nor-policies-group-heading');
            if (heading) {
              heading.textContent = 'グループ ' + (s + 1);
            }

            setGroupFieldNames(section, s);

            var list = section.querySelector('.nor-policies-items');
            if (list) {
              list.setAttribute('data-group-index', String(s));
            }

            var addBtn = section.querySelector('.nor-policies-add-item');
            if (addBtn) {
              addBtn.setAttribute('data-group-index', String(s));
            }

            var cards = section.querySelectorAll('.nor-policies-item');
            for (var c = 0; c < cards.length; c++) {
              setItemFieldNames(cards[c], s);
            }
          }

          validatePolicyIds();
        }

        root.addEventListener('click', function (event) {
          var addGroupBtn = event.target.closest('.nor-policies-add-group');
          if (addGroupBtn) {
            event.preventDefault();
            var groupsWrap = root.querySelector('.nor-policies-groups');
            if (!groupsWrap) return;
            var sectionCount = groupsWrap.querySelectorAll('.nor-policies-group').length;
            groupsWrap.insertAdjacentHTML('beforeend', buildGroup(sectionCount));
            reindexGroups();
            return;
          }

          var removeGroupBtn = event.target.closest('.nor-policies-remove-group');
          if (removeGroupBtn) {
            event.preventDefault();
            var section = removeGroupBtn.closest('.nor-policies-group');
            if (section) section.remove();
            reindexGroups();
            return;
          }

          var addBtn = event.target.closest('.nor-policies-add-item');
          if (addBtn) {
            event.preventDefault();
            var groupIndex = addBtn.getAttribute('data-group-index');
            var list = root.querySelector('.nor-policies-items[data-group-index="' + groupIndex + '"]');
            if (!list) return;

            var itemKey = newItemKey();
            list.insertAdjacentHTML('beforeend', buildItem(groupIndex, itemKey));
            var newCards = list.querySelectorAll('.nor-policies-item');
            if (newCards.length > 0) {
              setItemFieldNames(newCards[newCards.length - 1], Number(groupIndex));
              syncRichEncoded(newCards[newCards.length - 1]);
            }
            reindexGroups();
            return;
          }

          var removeBtn = event.target.closest('.nor-policies-remove-item');
          if (removeBtn) {
            event.preventDefault();
            var card = removeBtn.closest('.nor-policies-item');
            if (card) card.remove();
            reindexGroups();
          }
        });

        root.addEventListener('input', function (event) {
          var idInput = event.target.closest('input[data-field="anchor"]');
          if (idInput) {
            validatePolicyIds();
          }

          var ta = event.target.closest('textarea.nor-policies-rich');
          if (!ta) return;
          var card = ta.closest('.nor-policies-item');
          syncRichEncoded(card);
        });

        root.addEventListener('change', function (event) {
          var idInput = event.target.closest('input[data-field="anchor"]');
          if (idInput) {
            validatePolicyIds();
          }

          var ta = event.target.closest('textarea.nor-policies-rich');
          if (!ta) return;
          var card = ta.closest('.nor-policies-item');
          syncRichEncoded(card);
        });

        reindexGroups();
        var initialCards = root.querySelectorAll('.nor-policies-item');
        for (var ic = 0; ic < initialCards.length; ic++) {
          syncRichEncoded(initialCards[ic]);
        }
        validatePolicyIds();

        var form = document.getElementById('post') || root.closest('form') || document.querySelector('form#post');
        if (form) {
          form.addEventListener('submit', function () {
            reindexGroups();
            var cards = root.querySelectorAll('.nor-policies-item');
            for (var i = 0; i < cards.length; i++) {
              syncRichEncoded(cards[i]);
            }
            validatePolicyIds();
          });
        }
      })();
      </script>
      <?php
    },
    'page',
    'normal',
    'default'
  );
});

add_action('save_post_page', function ($post_id) {
  $slug = get_post_field('post_name', $post_id);
  if ((string) $slug !== 'policies') return;

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $nonce_ok = false;
  if (isset($_POST['nor_policies_page_nonce'])) {
    $nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['nor_policies_page_nonce']), 'nor_policies_page_save');
  }

  $core_nonce_ok = false;
  if (isset($_POST['_wpnonce'])) {
    $core_nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['_wpnonce']), 'update-post_' . (int) $post_id);
  }

  if (!$nonce_ok && !$core_nonce_ok) return;

  $has_policy_last_updated = isset($_POST['nor_policies_last_updated']);
  $has_policy_groups = isset($_POST['nor_policies_groups']) && is_array($_POST['nor_policies_groups']);
  if (!$has_policy_last_updated && !$has_policy_groups) return;

  if ($has_policy_last_updated) {
    $updated = wp_unslash($_POST['nor_policies_last_updated']);
    $updated = sanitize_text_field((string) $updated);
    $updated = trim($updated);

    if ($updated === '') {
      delete_post_meta($post_id, 'nor_policies_last_updated');
    } else {
      update_post_meta($post_id, 'nor_policies_last_updated', $updated);
    }
  }

  if (!$has_policy_groups) return;

  $existing_groups = get_post_meta($post_id, 'nor_policies_groups', true);
  $existing_groups = is_array($existing_groups) ? $existing_groups : [];
  $raw_groups = $_POST['nor_policies_groups'];

  $groups = [];
  $group_seq = 0;

  foreach ($raw_groups as $g_idx => $row) {
    if (!is_array($row)) continue;
    $group_seq++;

    $existing_row = (isset($existing_groups[$g_idx]) && is_array($existing_groups[$g_idx])) ? $existing_groups[$g_idx] : [];
    $existing_items = isset($existing_row['items']) && is_array($existing_row['items']) ? $existing_row['items'] : [];

    $group_name_ja_raw = isset($row['group_name_ja']) ? (string) $row['group_name_ja'] : (string) ($existing_row['group_name_ja'] ?? '');
    $group_name_ja = sanitize_text_field(wp_unslash($group_name_ja_raw));

    $group_title_en_raw = isset($row['group_title_en']) ? (string) $row['group_title_en'] : (string) ($existing_row['group_title_en'] ?? '');
    $group_title_en = sanitize_text_field(wp_unslash($group_title_en_raw));

    $fallback_group_id = $group_title_en !== ''
      ? ('policies-group-' . sanitize_title($group_title_en))
      : ('policies-group-' . $group_seq);
    $group_id_raw = isset($row['group_id']) ? (string) $row['group_id'] : (string) ($existing_row['group_id'] ?? '');
    $group_id = nor_policies_normalize_anchor_id(wp_unslash($group_id_raw), $fallback_group_id);

    $clean_items = [];
    $raw_items = isset($row['items']) && is_array($row['items']) ? $row['items'] : [];

    $read_rich_input = static function (array $item_row, string $field, string $fallback = ''): string {
      $b64_key = $field . '_b64';
      if (array_key_exists($b64_key, $item_row)) {
        $b64_raw = (string) $item_row[$b64_key];
        $b64 = trim((string) wp_unslash($b64_raw));
        if ($b64 === '') return '';

        // Accept both classic Base64 and URL-safe Base64 payloads.
        $normalized = str_replace(' ', '+', $b64);
        $normalized = strtr($normalized, '-_', '+/');
        $pad = strlen($normalized) % 4;
        if ($pad > 0) {
          $normalized .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($normalized, true);
        if (is_string($decoded)) return $decoded;

        return '';
      }

      if (array_key_exists($field, $item_row)) {
        return (string) wp_unslash((string) $item_row[$field]);
      }

      return $fallback;
    };

    foreach ($raw_items as $item_key => $item_row) {
      if (!is_array($item_row)) continue;

      $existing_item = [];
      if (array_key_exists($item_key, $existing_items) && is_array($existing_items[$item_key])) {
        $existing_item = $existing_items[$item_key];
      } elseif (is_string($item_key)) {
        $item_key_safe = sanitize_key($item_key);
        if ($item_key_safe !== '' && array_key_exists($item_key_safe, $existing_items) && is_array($existing_items[$item_key_safe])) {
          $existing_item = $existing_items[$item_key_safe];
        }
      }

      $item_id = is_string($item_key) ? sanitize_key($item_key) : '';
      if ($item_id === '') {
        $item_id = 'item_' . ((int) count($clean_items) + 1);
      }
      while (isset($clean_items[$item_id])) {
        $item_id .= '_x';
      }

      $title_en_raw = array_key_exists('title_en', $item_row) ? (string) $item_row['title_en'] : (string) ($existing_item['title_en'] ?? '');
      $title_en = sanitize_text_field(wp_unslash($title_en_raw));

      $anchor_raw = array_key_exists('anchor', $item_row) ? (string) $item_row['anchor'] : (string) ($existing_item['anchor'] ?? '');
      $anchor = nor_policies_normalize_anchor_id(wp_unslash($anchor_raw), '');

      $column_raw = array_key_exists('column', $item_row) ? (string) $item_row['column'] : (string) ($existing_item['column'] ?? '0');
      $column = (int) sanitize_text_field(wp_unslash($column_raw));
      if ($column < 1 || $column > 3) $column = 0;

      $position_raw = array_key_exists('position', $item_row) ? (string) $item_row['position'] : (string) ($existing_item['position'] ?? '0');
      $position = (int) sanitize_text_field(wp_unslash($position_raw));
      if ($position < 0) $position = 0;

      $summary_ja_raw = $read_rich_input($item_row, 'summary_ja', nor_policies_decode_rich_storage((string) ($existing_item['summary_ja'] ?? '')));
      $summary_en_raw = $read_rich_input($item_row, 'summary_en', nor_policies_decode_rich_storage((string) ($existing_item['summary_en'] ?? '')));
      $body_ja_raw = $read_rich_input($item_row, 'body_ja', nor_policies_decode_rich_storage((string) ($existing_item['body_ja'] ?? '')));
      $body_en_raw = $read_rich_input($item_row, 'body_en', nor_policies_decode_rich_storage((string) ($existing_item['body_en'] ?? '')));

      // Store raw values and sanitize on render.
      $summary_ja = trim((string) $summary_ja_raw);
      $summary_en = trim((string) $summary_en_raw);
      $body_ja = trim((string) $body_ja_raw);
      $body_en = trim((string) $body_en_raw);

      $is_empty = ($title_en === '' && $summary_ja === '' && $summary_en === '' && $body_ja === '' && $body_en === '');
      if ($is_empty) continue;

      $clean_items[$item_id] = [
        'title_en' => $title_en,
        'anchor' => $anchor,
        'column' => $column,
        'position' => $position,
        // Store rich blocks as encoded text to avoid environment-specific tag stripping.
        'summary_ja' => nor_policies_encode_rich_storage($summary_ja),
        'summary_en' => nor_policies_encode_rich_storage($summary_en),
        'body_ja' => nor_policies_encode_rich_storage($body_ja),
        'body_en' => nor_policies_encode_rich_storage($body_en),
      ];
    }

    $is_group_empty = ($group_title_en === '' && $group_name_ja === '' && empty($clean_items));
    if ($is_group_empty) continue;

    $groups[] = [
      'group_id' => $group_id,
      'group_title_en' => $group_title_en,
      'group_name_ja' => $group_name_ja,
      'items' => $clean_items,
    ];
  }

  update_post_meta($post_id, 'nor_policies_groups', $groups);
  delete_post_meta($post_id, '_nor_policies_debug_last');
});

/**
 * Notes page builder
 * - Meta key:
 *   - nor_notes_majors (array)
 */
if (!function_exists('nor_notes_default_majors')) {
  function nor_notes_default_majors(): array {
    return [
      ['major_id' => 'major-heading-structure', 'major_title_en' => 'Structure', 'summary_ja' => '', 'summary_en' => '', 'middles' => []],
      ['major_id' => 'major-heading-specification', 'major_title_en' => 'Specification', 'summary_ja' => '', 'summary_en' => '', 'middles' => []],
      ['major_id' => 'major-heading-design-system', 'major_title_en' => 'Design System', 'summary_ja' => '', 'summary_en' => '', 'middles' => []],
      ['major_id' => 'major-heading-diagnostics', 'major_title_en' => 'Diagnostics', 'summary_ja' => '', 'summary_en' => '', 'middles' => []],
      ['major_id' => 'major-heading-attributions', 'major_title_en' => 'Attributions', 'summary_ja' => '', 'summary_en' => '', 'middles' => []],
    ];
  }
}

if (!function_exists('nor_notes_allowed_html')) {
  function nor_notes_allowed_html(): array {
    if (function_exists('nor_policies_allowed_html')) {
      return nor_policies_allowed_html();
    }

    $allowed = wp_kses_allowed_html('post');
    $allowed['a']['target'] = true;
    $allowed['a']['rel'] = true;
    $allowed['a']['class'] = true;
    $allowed['abbr']['title'] = true;
    $allowed['code']['class'] = true;
    $allowed['br']['class'] = true;
    $allowed['span']['class'] = true;
    $allowed['span']['lang'] = true;
    $allowed['div']['class'] = true;
    $allowed['div']['lang'] = true;
    $allowed['li']['class'] = true;
    if (!isset($allowed['time']) || !is_array($allowed['time'])) $allowed['time'] = [];
    $allowed['time']['datetime'] = true;
    $allowed['time']['class'] = true;
    return $allowed;
  }
}

if (!function_exists('nor_notes_sanitize_rich_text')) {
  function nor_notes_sanitize_rich_text(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    $raw = wp_check_invalid_utf8($raw, true);
    return trim(wp_kses($raw, nor_notes_allowed_html()));
  }
}

if (!function_exists('nor_notes_base64url_encode')) {
  function nor_notes_base64url_encode(string $raw): string {
    if (function_exists('nor_policies_base64url_encode')) {
      return nor_policies_base64url_encode($raw);
    }
    $raw = (string) $raw;
    if ($raw === '') return '';
    $b64 = base64_encode($raw);
    if (!is_string($b64) || $b64 === '') return '';
    return rtrim(strtr($b64, '+/', '-_'), '=');
  }
}

if (!function_exists('nor_notes_encode_rich_storage')) {
  function nor_notes_encode_rich_storage(string $raw): string {
    if (function_exists('nor_policies_encode_rich_storage')) {
      return nor_policies_encode_rich_storage($raw);
    }
    $raw = (string) $raw;
    if ($raw === '') return '';
    $b64url = nor_notes_base64url_encode($raw);
    if ($b64url === '') return '';
    return 'b64:' . $b64url;
  }
}

if (!function_exists('nor_notes_decode_rich_storage')) {
  function nor_notes_decode_rich_storage(string $raw): string {
    if (function_exists('nor_policies_decode_rich_storage')) {
      return nor_policies_decode_rich_storage($raw);
    }
    $raw = (string) $raw;
    if ($raw === '') return '';
    if (strncmp($raw, 'b64:', 4) !== 0) return $raw;

    $payload = substr($raw, 4);
    if ($payload === false || $payload === '') return '';

    $normalized = strtr($payload, '-_', '+/');
    $pad = strlen($normalized) % 4;
    if ($pad > 0) $normalized .= str_repeat('=', 4 - $pad);
    $decoded = base64_decode($normalized, true);
    return is_string($decoded) ? $decoded : '';
  }
}

if (!function_exists('nor_notes_normalize_anchor_id')) {
  function nor_notes_normalize_anchor_id(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    $raw = ltrim($raw, '#');
    if ($raw === '' && $fallback !== '') $raw = ltrim(trim($fallback), '#');
    $id = sanitize_title($raw);
    if ($id === '' && $fallback !== '') $id = sanitize_title(ltrim(trim($fallback), '#'));
    return $id;
  }
}

if (!function_exists('nor_notes_get_majors')) {
  function nor_notes_get_majors(int $post_id, bool $for_editor = false): array {
    $defaults = nor_notes_default_majors();
    $saved = get_post_meta($post_id, 'nor_notes_majors', true);
    $saved = is_array($saved) ? array_values($saved) : [];
    $source = !empty($saved) ? $saved : ($for_editor ? $defaults : []);

    $majors = [];

    foreach ($source as $m_idx => $major_row) {
      if (!is_array($major_row)) continue;

      $def = isset($defaults[$m_idx]) && is_array($defaults[$m_idx])
        ? $defaults[$m_idx]
        : ['major_id' => 'major-heading-section-' . ((int) $m_idx + 1), 'major_title_en' => 'Section ' . ((int) $m_idx + 1), 'summary_ja' => '', 'summary_en' => '', 'middles' => []];

      $major_title_en = sanitize_text_field((string) ($major_row['major_title_en'] ?? $def['major_title_en']));
      if ($major_title_en === '') $major_title_en = (string) $def['major_title_en'];

      $fallback_major_id = (string) ($def['major_id'] ?? ('major-heading-section-' . ((int) $m_idx + 1)));
      if ($major_title_en !== '') {
        $fallback_major_id = 'major-heading-' . sanitize_title($major_title_en);
      }
      if ($for_editor) {
        $major_id = nor_notes_normalize_anchor_id((string) ($major_row['major_id'] ?? ''), '');
      } else {
        $major_id = nor_notes_normalize_anchor_id((string) ($major_row['major_id'] ?? $fallback_major_id), $fallback_major_id);
      }

      $summary_ja_raw = nor_notes_decode_rich_storage((string) ($major_row['summary_ja'] ?? ''));
      $summary_en_raw = nor_notes_decode_rich_storage((string) ($major_row['summary_en'] ?? ''));
      $summary_ja = $for_editor ? $summary_ja_raw : nor_notes_sanitize_rich_text($summary_ja_raw);
      $summary_en = $for_editor ? $summary_en_raw : nor_notes_sanitize_rich_text($summary_en_raw);

      $middles = [];
      $middle_rows = isset($major_row['middles']) && is_array($major_row['middles']) ? array_values($major_row['middles']) : [];

      foreach ($middle_rows as $md_idx => $middle_row) {
        if (!is_array($middle_row)) continue;

        $middle_title_en = sanitize_text_field((string) ($middle_row['middle_title_en'] ?? ''));
        if ($for_editor) {
          $middle_id = nor_notes_normalize_anchor_id((string) ($middle_row['middle_id'] ?? ''), '');
        } else {
          $middle_title_for_id = $middle_title_en !== '' ? $middle_title_en : ('group-' . ((int) $md_idx + 1));
          $fallback_middle_id = 'middle-heading-' . sanitize_title($middle_title_for_id);
          $middle_id = nor_notes_normalize_anchor_id((string) ($middle_row['middle_id'] ?? $fallback_middle_id), $fallback_middle_id);
        }

        $items = [];
        $item_rows = isset($middle_row['items']) && is_array($middle_row['items']) ? array_values($middle_row['items']) : [];
        foreach ($item_rows as $item_row) {
          if (!is_array($item_row)) continue;

          $label_raw = nor_notes_decode_rich_storage((string) ($item_row['label'] ?? ''));
          $desc_ja_raw = nor_notes_decode_rich_storage((string) ($item_row['desc_ja'] ?? ''));
          $desc_en_raw = nor_notes_decode_rich_storage((string) ($item_row['desc_en'] ?? ''));

          $label = $for_editor ? $label_raw : nor_notes_sanitize_rich_text($label_raw);
          $desc_ja = $for_editor ? $desc_ja_raw : nor_notes_sanitize_rich_text($desc_ja_raw);
          $desc_en = $for_editor ? $desc_en_raw : nor_notes_sanitize_rich_text($desc_en_raw);

          $is_item_empty = ($label === '' && $desc_ja === '' && $desc_en === '');
          if ($is_item_empty && !$for_editor) continue;

          $items[] = [
            'label' => $label,
            'desc_ja' => $desc_ja,
            'desc_en' => $desc_en,
          ];
        }

        $is_middle_empty = ($middle_title_en === '' && empty($items));
        if ($is_middle_empty && !$for_editor) continue;

        $middles[] = [
          'middle_id' => $middle_id,
          'middle_title_en' => $middle_title_en,
          'items' => $items,
        ];
      }

      $is_major_empty = ($major_title_en === '' && $summary_ja === '' && $summary_en === '' && empty($middles));
      if ($is_major_empty && !$for_editor) continue;

      $majors[] = [
        'major_id' => $major_id,
        'major_title_en' => $major_title_en,
        'summary_ja' => $summary_ja,
        'summary_en' => $summary_en,
        'middles' => $middles,
      ];
    }

    return $majors;
  }
}

add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;
  if ((string) $post->post_name !== 'notes') return;

  add_meta_box(
    'nor_notes_page_builder',
    'Notes構成',
    function ($post) {
      if (!$post instanceof WP_Post) return;
      wp_nonce_field('nor_notes_page_save', 'nor_notes_page_nonce');

      $majors = nor_notes_get_majors((int) $post->ID, true);

      $render_item = function (int $m_idx, int $md_idx, int $it_idx, array $item): void {
        $label = isset($item['label']) ? (string) $item['label'] : '';
        $desc_ja = isset($item['desc_ja']) ? (string) $item['desc_ja'] : '';
        $desc_en = isset($item['desc_en']) ? (string) $item['desc_en'] : '';
        $base = 'nor_notes_majors[' . $m_idx . '][middles][' . $md_idx . '][items][' . $it_idx . ']';
        ?>
        <div class="nor-notes-item" data-item-index="<?php echo esc_attr((string) $it_idx); ?>" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">
          <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
            <strong class="nor-notes-item-heading">小分類 <?php echo esc_html((string) ($it_idx + 1)); ?></strong>
            <span style="display:flex; gap:8px; align-items:center;">
              <button type="button" class="button-link nor-notes-move-item-up">↑ 上へ</button>
              <button type="button" class="button-link nor-notes-move-item-down">↓ 下へ</button>
              <button type="button" class="button-link-delete nor-notes-remove-item">削除</button>
            </span>
          </p>

          <p style="margin:8px 0 4px;"><label><strong>小分類</strong></label></p>
          <div class="nor-notes-rich-field">
            <textarea rows="2" style="width:100%" class="nor-notes-rich" data-rich-field="label"><?php echo esc_textarea($label); ?></textarea>
            <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="label" name="<?php echo esc_attr($base . '[label_b64]'); ?>" value="<?php echo esc_attr(nor_notes_base64url_encode($label)); ?>" />
          </div>

          <p style="margin:8px 0 4px;"><label><strong>説明文（JA）</strong></label></p>
          <div class="nor-notes-rich-field">
            <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="desc_ja"><?php echo esc_textarea($desc_ja); ?></textarea>
            <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="desc_ja" name="<?php echo esc_attr($base . '[desc_ja_b64]'); ?>" value="<?php echo esc_attr(nor_notes_base64url_encode($desc_ja)); ?>" />
          </div>

          <p style="margin:8px 0 4px;"><label><strong>説明文（EN）</strong></label></p>
          <div class="nor-notes-rich-field">
            <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="desc_en"><?php echo esc_textarea($desc_en); ?></textarea>
            <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="desc_en" name="<?php echo esc_attr($base . '[desc_en_b64]'); ?>" value="<?php echo esc_attr(nor_notes_base64url_encode($desc_en)); ?>" />
          </div>
        </div>
        <?php
      };

      $render_middle = function (int $m_idx, int $md_idx, array $middle) use ($render_item): void {
        $middle_title_en = isset($middle['middle_title_en']) ? (string) $middle['middle_title_en'] : '';
        $middle_id = isset($middle['middle_id']) ? (string) $middle['middle_id'] : '';
        $base = 'nor_notes_majors[' . $m_idx . '][middles][' . $md_idx . ']';
        $items = isset($middle['items']) && is_array($middle['items']) ? $middle['items'] : [];
        ?>
        <section class="nor-notes-middle" data-middle-index="<?php echo esc_attr((string) $md_idx); ?>" style="margin-top:12px; padding:12px; border:1px solid #c9c9c9; background:#fff;">
          <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
            <strong class="nor-notes-middle-heading">中分類 <?php echo esc_html((string) ($md_idx + 1)); ?></strong>
            <span style="display:flex; gap:8px; align-items:center;">
              <button type="button" class="button-link nor-notes-move-middle-up">↑ 上へ</button>
              <button type="button" class="button-link nor-notes-move-middle-down">↓ 下へ</button>
              <button type="button" class="button-link-delete nor-notes-remove-middle">削除</button>
            </span>
          </p>

          <p style="margin:8px 0 4px;"><label><strong>中分類タイトル（EN）</strong></label></p>
          <input type="text" style="width:100%" data-middle-field="middle_title_en" name="<?php echo esc_attr($base . '[middle_title_en]'); ?>" value="<?php echo esc_attr($middle_title_en); ?>" />

          <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>
          <input type="text" style="width:100%" data-middle-field="middle_id" name="<?php echo esc_attr($base . '[middle_id]'); ?>" value="<?php echo esc_attr($middle_id); ?>" placeholder="#middle-heading-example" required />

          <div class="nor-notes-items" data-major-index="<?php echo esc_attr((string) $m_idx); ?>" data-middle-index="<?php echo esc_attr((string) $md_idx); ?>">
            <?php foreach ($items as $it_idx => $item) : ?>
              <?php $render_item($m_idx, $md_idx, (int) $it_idx, is_array($item) ? $item : []); ?>
            <?php endforeach; ?>
          </div>

          <p style="margin-top:10px;"><button type="button" class="button nor-notes-add-item" data-major-index="<?php echo esc_attr((string) $m_idx); ?>" data-middle-index="<?php echo esc_attr((string) $md_idx); ?>">小分類を追加</button></p>
        </section>
        <?php
      };
      ?>
      <div id="nor-notes-editor">
        <p class="description">大分類・中分類・小分類を追加し、各階層で上下移動・削除できます。</p>

        <div class="nor-notes-majors">
          <?php foreach ($majors as $m_idx => $major) : ?>
            <?php
              $major = is_array($major) ? $major : [];
              $major_title_en = isset($major['major_title_en']) ? (string) $major['major_title_en'] : '';
              $major_id = isset($major['major_id']) ? (string) $major['major_id'] : '';
              $summary_ja = isset($major['summary_ja']) ? (string) $major['summary_ja'] : '';
              $summary_en = isset($major['summary_en']) ? (string) $major['summary_en'] : '';
              $middles = isset($major['middles']) && is_array($major['middles']) ? $major['middles'] : [];
              $major_base = 'nor_notes_majors[' . $m_idx . ']';
            ?>
            <section class="nor-notes-major" data-major-index="<?php echo esc_attr((string) $m_idx); ?>" style="margin-top:20px; padding:12px; border:1px solid #c9c9c9; background:#f8f9fa;">
              <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
                <strong class="nor-notes-major-heading">大分類 <?php echo esc_html((string) ($m_idx + 1)); ?></strong>
                <span style="display:flex; gap:8px; align-items:center;">
                  <button type="button" class="button-link nor-notes-move-major-up">↑ 上へ</button>
                  <button type="button" class="button-link nor-notes-move-major-down">↓ 下へ</button>
                  <button type="button" class="button-link-delete nor-notes-remove-major">削除</button>
                </span>
              </p>

              <p style="margin:8px 0 4px;"><label><strong>大分類タイトル（EN）</strong></label></p>
              <input type="text" style="width:100%" data-major-field="major_title_en" name="<?php echo esc_attr($major_base . '[major_title_en]'); ?>" value="<?php echo esc_attr($major_title_en); ?>" />

              <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>
              <input type="text" style="width:100%" data-major-field="major_id" name="<?php echo esc_attr($major_base . '[major_id]'); ?>" value="<?php echo esc_attr($major_id); ?>" placeholder="#major-heading-example" required />

              <p style="margin:8px 0 4px;"><label><strong>概要文（JA）</strong></label></p>
              <div class="nor-notes-rich-field">
                <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="summary_ja"><?php echo esc_textarea($summary_ja); ?></textarea>
                <input type="hidden" class="nor-notes-rich-b64" data-major-rich-field="summary_ja" name="<?php echo esc_attr($major_base . '[summary_ja_b64]'); ?>" value="<?php echo esc_attr(nor_notes_base64url_encode($summary_ja)); ?>" />
              </div>

              <p style="margin:8px 0 4px;"><label><strong>概要文（EN）</strong></label></p>
              <div class="nor-notes-rich-field">
                <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="summary_en"><?php echo esc_textarea($summary_en); ?></textarea>
                <input type="hidden" class="nor-notes-rich-b64" data-major-rich-field="summary_en" name="<?php echo esc_attr($major_base . '[summary_en_b64]'); ?>" value="<?php echo esc_attr(nor_notes_base64url_encode($summary_en)); ?>" />
              </div>

              <div class="nor-notes-middles" data-major-index="<?php echo esc_attr((string) $m_idx); ?>">
                <?php foreach ($middles as $md_idx => $middle) : ?>
                  <?php $render_middle((int) $m_idx, (int) $md_idx, is_array($middle) ? $middle : []); ?>
                <?php endforeach; ?>
              </div>

              <p style="margin-top:10px;"><button type="button" class="button nor-notes-add-middle" data-major-index="<?php echo esc_attr((string) $m_idx); ?>">中分類を追加</button></p>
            </section>
          <?php endforeach; ?>
        </div>

        <p style="margin-top:12px;"><button type="button" class="button button-primary nor-notes-add-major">大分類を追加</button></p>

        <template id="nor-notes-major-template">
          <section class="nor-notes-major" data-major-index="">
            <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
              <strong class="nor-notes-major-heading">大分類</strong>
              <span style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="button-link nor-notes-move-major-up">↑ 上へ</button>
                <button type="button" class="button-link nor-notes-move-major-down">↓ 下へ</button>
                <button type="button" class="button-link-delete nor-notes-remove-major">削除</button>
              </span>
            </p>
            <p style="margin:8px 0 4px;"><label><strong>大分類タイトル（EN）</strong></label></p>
            <input type="text" style="width:100%" data-major-field="major_title_en" value="" />
            <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>
            <input type="text" style="width:100%" data-major-field="major_id" value="" placeholder="#major-heading-example" required />
            <p style="margin:8px 0 4px;"><label><strong>概要文（JA）</strong></label></p>
            <div class="nor-notes-rich-field">
              <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="summary_ja"></textarea>
              <input type="hidden" class="nor-notes-rich-b64" data-major-rich-field="summary_ja" value="" />
            </div>
            <p style="margin:8px 0 4px;"><label><strong>概要文（EN）</strong></label></p>
            <div class="nor-notes-rich-field">
              <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="summary_en"></textarea>
              <input type="hidden" class="nor-notes-rich-b64" data-major-rich-field="summary_en" value="" />
            </div>
            <div class="nor-notes-middles" data-major-index=""></div>
            <p style="margin-top:10px;"><button type="button" class="button nor-notes-add-middle" data-major-index="">中分類を追加</button></p>
          </section>
        </template>

        <template id="nor-notes-middle-template">
          <section class="nor-notes-middle" data-middle-index="" style="margin-top:12px; padding:12px; border:1px solid #c9c9c9; background:#fff;">
            <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
              <strong class="nor-notes-middle-heading">中分類</strong>
              <span style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="button-link nor-notes-move-middle-up">↑ 上へ</button>
                <button type="button" class="button-link nor-notes-move-middle-down">↓ 下へ</button>
                <button type="button" class="button-link-delete nor-notes-remove-middle">削除</button>
              </span>
            </p>
            <p style="margin:8px 0 4px;"><label><strong>中分類タイトル（EN）</strong></label></p>
            <input type="text" style="width:100%" data-middle-field="middle_title_en" value="" />
            <p style="margin:8px 0 4px;"><label><strong>ID</strong></label></p>
            <input type="text" style="width:100%" data-middle-field="middle_id" value="" placeholder="#middle-heading-example" required />
            <div class="nor-notes-items" data-major-index="" data-middle-index=""></div>
            <p style="margin-top:10px;"><button type="button" class="button nor-notes-add-item" data-major-index="" data-middle-index="">小分類を追加</button></p>
          </section>
        </template>

        <template id="nor-notes-item-template">
          <div class="nor-notes-item" data-item-index="" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">
            <p style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin:0 0 8px;">
              <strong class="nor-notes-item-heading">小分類</strong>
              <span style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="button-link nor-notes-move-item-up">↑ 上へ</button>
                <button type="button" class="button-link nor-notes-move-item-down">↓ 下へ</button>
                <button type="button" class="button-link-delete nor-notes-remove-item">削除</button>
              </span>
            </p>
            <p style="margin:8px 0 4px;"><label><strong>小分類</strong></label></p>
            <div class="nor-notes-rich-field">
              <textarea rows="2" style="width:100%" class="nor-notes-rich" data-rich-field="label"></textarea>
              <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="label" value="" />
            </div>
            <p style="margin:8px 0 4px;"><label><strong>説明文（JA）</strong></label></p>
            <div class="nor-notes-rich-field">
              <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="desc_ja"></textarea>
              <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="desc_ja" value="" />
            </div>
            <p style="margin:8px 0 4px;"><label><strong>説明文（EN）</strong></label></p>
            <div class="nor-notes-rich-field">
              <textarea rows="3" style="width:100%" class="nor-notes-rich" data-rich-field="desc_en"></textarea>
              <input type="hidden" class="nor-notes-rich-b64" data-item-rich-field="desc_en" value="" />
            </div>
          </div>
        </template>
      </div>

      <script>
      (function () {
        var root = document.getElementById('nor-notes-editor');
        if (!root) return;

        var majorsWrap = root.querySelector('.nor-notes-majors');
        if (!majorsWrap) return;

        var majorTpl = document.getElementById('nor-notes-major-template');
        var middleTpl = document.getElementById('nor-notes-middle-template');
        var itemTpl = document.getElementById('nor-notes-item-template');
        if (!majorTpl || !middleTpl || !itemTpl) return;

        function encodeBase64Url(str) {
          var input = String(str || '');
          if (!input) return '';
          try {
            var utf8 = unescape(encodeURIComponent(input));
            var b64 = btoa(utf8);
            return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
          } catch (e) {
            return '';
          }
        }

        function syncRichEncoded(scope) {
          var container = scope || root;
          var textareas = container.querySelectorAll('textarea.nor-notes-rich[data-rich-field]');
          for (var i = 0; i < textareas.length; i++) {
            var ta = textareas[i];
            var field = ta.getAttribute('data-rich-field') || '';
            if (!field) continue;
            var wrap = ta.closest('.nor-notes-rich-field');
            if (!wrap) continue;
            var hidden = wrap.querySelector('input.nor-notes-rich-b64[data-major-rich-field="' + field + '"], input.nor-notes-rich-b64[data-item-rich-field="' + field + '"]');
            if (!hidden) continue;
            hidden.value = encodeBase64Url(ta.value || '');
          }
        }

        function normalizeIdValue(raw) {
          var s = String(raw || '').trim();
          s = s.replace(/^#+/, '').toLowerCase();
          s = s.replace(/\s+/g, '-');
          s = s.replace(/[^a-z0-9\-_]+/g, '-');
          s = s.replace(/-+/g, '-');
          s = s.replace(/^-+/, '').replace(/-+$/, '');
          return s;
        }

        function ensureIdWarn(input) {
          if (!input || !input.parentNode) return null;
          var next = input.nextElementSibling;
          if (next && next.classList && next.classList.contains('nor-notes-id-warn')) return next;

          var warn = document.createElement('p');
          warn.className = 'description nor-notes-id-warn';
          warn.style.marginTop = '4px';
          warn.style.color = '#b32d2e';
          warn.style.display = 'none';
          input.insertAdjacentElement('afterend', warn);
          return warn;
        }

        function validateNoteIds() {
          var idInputs = root.querySelectorAll(
            'input[data-major-field="major_id"], input[data-middle-field="middle_id"]'
          );
          var counts = {};
          var normalized = [];

          for (var i = 0; i < idInputs.length; i++) {
            var input = idInputs[i];
            var raw = String(input.value || '').trim();
            var norm = normalizeIdValue(raw);
            normalized.push(norm);
            if (raw !== '' && norm !== '') {
              counts[norm] = (counts[norm] || 0) + 1;
            }
          }

          for (var j = 0; j < idInputs.length; j++) {
            var current = idInputs[j];
            var currentNorm = normalized[j] || '';
            var msg = '';

            if (currentNorm === '') {
              msg = 'IDが未入力です。';
            } else if (currentNorm !== '' && (counts[currentNorm] || 0) > 1) {
              msg = 'IDが重複しています。';
            }

            var warn = ensureIdWarn(current);
            if (warn) {
              warn.textContent = msg;
              warn.style.display = msg ? 'block' : 'none';
            }
            current.setAttribute('aria-invalid', msg ? 'true' : 'false');
          }
        }

        function cloneTemplate(tpl) {
          var node = tpl.content.firstElementChild;
          if (!node) return null;
          return node.cloneNode(true);
        }

        function setMajorFieldNames(majorEl, majorIndex) {
          var majorBase = 'nor_notes_majors[' + majorIndex + ']';

          var majorFields = majorEl.querySelectorAll('[data-major-field]');
          for (var i = 0; i < majorFields.length; i++) {
            var fieldEl = majorFields[i];
            var field = fieldEl.getAttribute('data-major-field') || '';
            if (!field) continue;
            fieldEl.name = majorBase + '[' + field + ']';
          }

          var majorRich = majorEl.querySelectorAll('input.nor-notes-rich-b64[data-major-rich-field]');
          for (var r = 0; r < majorRich.length; r++) {
            var richEl = majorRich[r];
            var richField = richEl.getAttribute('data-major-rich-field') || '';
            if (!richField) continue;
            richEl.name = majorBase + '[' + richField + '_b64]';
          }
        }

        function setMiddleFieldNames(middleEl, majorIndex, middleIndex) {
          var middleBase = 'nor_notes_majors[' + majorIndex + '][middles][' + middleIndex + ']';
          var middleFields = middleEl.querySelectorAll('[data-middle-field]');
          for (var i = 0; i < middleFields.length; i++) {
            var fieldEl = middleFields[i];
            var field = fieldEl.getAttribute('data-middle-field') || '';
            if (!field) continue;
            fieldEl.name = middleBase + '[' + field + ']';
          }
        }

        function setItemFieldNames(itemEl, majorIndex, middleIndex, itemIndex) {
          var itemBase = 'nor_notes_majors[' + majorIndex + '][middles][' + middleIndex + '][items][' + itemIndex + ']';
          var itemRich = itemEl.querySelectorAll('input.nor-notes-rich-b64[data-item-rich-field]');
          for (var i = 0; i < itemRich.length; i++) {
            var richEl = itemRich[i];
            var richField = richEl.getAttribute('data-item-rich-field') || '';
            if (!richField) continue;
            richEl.name = itemBase + '[' + richField + '_b64]';
          }
        }

        function reindexAll() {
          var majors = majorsWrap.querySelectorAll('.nor-notes-major');
          for (var m = 0; m < majors.length; m++) {
            var majorEl = majors[m];
            majorEl.setAttribute('data-major-index', String(m));

            var majorHeading = majorEl.querySelector('.nor-notes-major-heading');
            if (majorHeading) majorHeading.textContent = '大分類 ' + (m + 1);

            setMajorFieldNames(majorEl, m);

            var middlesWrap = majorEl.querySelector('.nor-notes-middles');
            if (middlesWrap) middlesWrap.setAttribute('data-major-index', String(m));

            var addMiddleBtn = majorEl.querySelector('.nor-notes-add-middle');
            if (addMiddleBtn) addMiddleBtn.setAttribute('data-major-index', String(m));

            var middles = majorEl.querySelectorAll('.nor-notes-middle');
            for (var md = 0; md < middles.length; md++) {
              var middleEl = middles[md];
              middleEl.setAttribute('data-middle-index', String(md));

              var middleHeading = middleEl.querySelector('.nor-notes-middle-heading');
              if (middleHeading) middleHeading.textContent = '中分類 ' + (md + 1);

              setMiddleFieldNames(middleEl, m, md);

              var itemsWrap = middleEl.querySelector('.nor-notes-items');
              if (itemsWrap) {
                itemsWrap.setAttribute('data-major-index', String(m));
                itemsWrap.setAttribute('data-middle-index', String(md));
              }

              var addItemBtn = middleEl.querySelector('.nor-notes-add-item');
              if (addItemBtn) {
                addItemBtn.setAttribute('data-major-index', String(m));
                addItemBtn.setAttribute('data-middle-index', String(md));
              }

              var items = middleEl.querySelectorAll('.nor-notes-item');
              for (var it = 0; it < items.length; it++) {
                var itemEl = items[it];
                itemEl.setAttribute('data-item-index', String(it));

                var itemHeading = itemEl.querySelector('.nor-notes-item-heading');
                if (itemHeading) itemHeading.textContent = '小分類 ' + (it + 1);

                setItemFieldNames(itemEl, m, md, it);
              }
            }
          }

          validateNoteIds();
        }

        function moveElement(el, direction) {
          if (!el || !el.parentNode) return;
          if (direction < 0) {
            var prev = el.previousElementSibling;
            if (prev) el.parentNode.insertBefore(el, prev);
            return;
          }
          var next = el.nextElementSibling;
          if (next) el.parentNode.insertBefore(next, el);
        }

        root.addEventListener('click', function (event) {
          var addMajor = event.target.closest('.nor-notes-add-major');
          if (addMajor) {
            event.preventDefault();
            var majorEl = cloneTemplate(majorTpl);
            if (!majorEl) return;
            majorEl.style.marginTop = '20px';
            majorEl.style.padding = '12px';
            majorEl.style.border = '1px solid #c9c9c9';
            majorEl.style.background = '#f8f9fa';
            majorsWrap.appendChild(majorEl);
            reindexAll();
            syncRichEncoded(majorEl);
            return;
          }

          var removeMajor = event.target.closest('.nor-notes-remove-major');
          if (removeMajor) {
            event.preventDefault();
            var majorToRemove = removeMajor.closest('.nor-notes-major');
            if (majorToRemove) majorToRemove.remove();
            reindexAll();
            return;
          }

          var moveMajorUp = event.target.closest('.nor-notes-move-major-up');
          if (moveMajorUp) {
            event.preventDefault();
            moveElement(moveMajorUp.closest('.nor-notes-major'), -1);
            reindexAll();
            return;
          }

          var moveMajorDown = event.target.closest('.nor-notes-move-major-down');
          if (moveMajorDown) {
            event.preventDefault();
            moveElement(moveMajorDown.closest('.nor-notes-major'), 1);
            reindexAll();
            return;
          }

          var addMiddle = event.target.closest('.nor-notes-add-middle');
          if (addMiddle) {
            event.preventDefault();
            var majorIndex = addMiddle.getAttribute('data-major-index') || '';
            var middleWrap = root.querySelector('.nor-notes-middles[data-major-index="' + majorIndex + '"]');
            if (!middleWrap) return;
            var middleEl = cloneTemplate(middleTpl);
            if (!middleEl) return;
            middleWrap.appendChild(middleEl);
            reindexAll();
            syncRichEncoded(middleEl);
            return;
          }

          var removeMiddle = event.target.closest('.nor-notes-remove-middle');
          if (removeMiddle) {
            event.preventDefault();
            var middleToRemove = removeMiddle.closest('.nor-notes-middle');
            if (middleToRemove) middleToRemove.remove();
            reindexAll();
            return;
          }

          var moveMiddleUp = event.target.closest('.nor-notes-move-middle-up');
          if (moveMiddleUp) {
            event.preventDefault();
            moveElement(moveMiddleUp.closest('.nor-notes-middle'), -1);
            reindexAll();
            return;
          }

          var moveMiddleDown = event.target.closest('.nor-notes-move-middle-down');
          if (moveMiddleDown) {
            event.preventDefault();
            moveElement(moveMiddleDown.closest('.nor-notes-middle'), 1);
            reindexAll();
            return;
          }

          var addItem = event.target.closest('.nor-notes-add-item');
          if (addItem) {
            event.preventDefault();
            var maj = addItem.getAttribute('data-major-index') || '';
            var mid = addItem.getAttribute('data-middle-index') || '';
            var itemWrap = root.querySelector('.nor-notes-items[data-major-index="' + maj + '"][data-middle-index="' + mid + '"]');
            if (!itemWrap) return;
            var itemEl = cloneTemplate(itemTpl);
            if (!itemEl) return;
            itemWrap.appendChild(itemEl);
            reindexAll();
            syncRichEncoded(itemEl);
            return;
          }

          var removeItem = event.target.closest('.nor-notes-remove-item');
          if (removeItem) {
            event.preventDefault();
            var itemToRemove = removeItem.closest('.nor-notes-item');
            if (itemToRemove) itemToRemove.remove();
            reindexAll();
            return;
          }

          var moveItemUp = event.target.closest('.nor-notes-move-item-up');
          if (moveItemUp) {
            event.preventDefault();
            moveElement(moveItemUp.closest('.nor-notes-item'), -1);
            reindexAll();
            return;
          }

          var moveItemDown = event.target.closest('.nor-notes-move-item-down');
          if (moveItemDown) {
            event.preventDefault();
            moveElement(moveItemDown.closest('.nor-notes-item'), 1);
            reindexAll();
          }
        });

        root.addEventListener('input', function (event) {
          var idInput = event.target.closest('input[data-major-field="major_id"], input[data-middle-field="middle_id"]');
          if (idInput) {
            validateNoteIds();
          }

          var ta = event.target.closest('textarea.nor-notes-rich');
          if (!ta) return;
          syncRichEncoded(ta.closest('.nor-notes-rich-field') || ta.closest('.nor-notes-item') || ta.closest('.nor-notes-major') || root);
        });

        root.addEventListener('change', function (event) {
          var idInput = event.target.closest('input[data-major-field="major_id"], input[data-middle-field="middle_id"]');
          if (idInput) {
            validateNoteIds();
          }

          var ta = event.target.closest('textarea.nor-notes-rich');
          if (!ta) return;
          syncRichEncoded(ta.closest('.nor-notes-rich-field') || ta.closest('.nor-notes-item') || ta.closest('.nor-notes-major') || root);
        });

        reindexAll();
        syncRichEncoded(root);
        validateNoteIds();

        var form = document.getElementById('post') || root.closest('form') || document.querySelector('form#post');
        if (form) {
          form.addEventListener('submit', function () {
            reindexAll();
            syncRichEncoded(root);
            validateNoteIds();
          });
        }
      })();
      </script>
      <?php
    },
    'page',
    'normal',
    'default'
  );
});

add_action('save_post_page', function ($post_id) {
  $slug = get_post_field('post_name', $post_id);
  if ((string) $slug !== 'notes') return;

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $nonce_ok = false;
  if (isset($_POST['nor_notes_page_nonce'])) {
    $nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['nor_notes_page_nonce']), 'nor_notes_page_save');
  }

  $core_nonce_ok = false;
  if (isset($_POST['_wpnonce'])) {
    $core_nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['_wpnonce']), 'update-post_' . (int) $post_id);
  }

  if (!$nonce_ok && !$core_nonce_ok) return;

  $has_majors = isset($_POST['nor_notes_majors']) && is_array($_POST['nor_notes_majors']);
  if (!$has_majors) return;

  $existing_majors = get_post_meta($post_id, 'nor_notes_majors', true);
  $existing_majors = is_array($existing_majors) ? array_values($existing_majors) : [];

  $read_rich_input = static function (array $row, string $field, string $fallback = ''): string {
    $b64_key = $field . '_b64';
    if (array_key_exists($b64_key, $row)) {
      $b64_raw = trim((string) wp_unslash((string) $row[$b64_key]));
      if ($b64_raw === '') return '';

      $normalized = str_replace(' ', '+', $b64_raw);
      $normalized = strtr($normalized, '-_', '+/');
      $pad = strlen($normalized) % 4;
      if ($pad > 0) $normalized .= str_repeat('=', 4 - $pad);
      $decoded = base64_decode($normalized, true);
      if (is_string($decoded)) return $decoded;
      return '';
    }

    if (array_key_exists($field, $row)) {
      return (string) wp_unslash((string) $row[$field]);
    }

    return $fallback;
  };

  $raw_majors = array_values($_POST['nor_notes_majors']);
  $majors = [];

  foreach ($raw_majors as $m_idx => $major_row) {
    if (!is_array($major_row)) continue;

    $existing_major = isset($existing_majors[$m_idx]) && is_array($existing_majors[$m_idx]) ? $existing_majors[$m_idx] : [];

    $major_title_en_raw = array_key_exists('major_title_en', $major_row) ? (string) $major_row['major_title_en'] : (string) ($existing_major['major_title_en'] ?? '');
    $major_title_en = sanitize_text_field(wp_unslash($major_title_en_raw));

    $major_id_raw = array_key_exists('major_id', $major_row) ? (string) $major_row['major_id'] : (string) ($existing_major['major_id'] ?? '');
    $major_id = nor_notes_normalize_anchor_id(wp_unslash($major_id_raw), '');

    $summary_ja_raw = $read_rich_input($major_row, 'summary_ja', nor_notes_decode_rich_storage((string) ($existing_major['summary_ja'] ?? '')));
    $summary_en_raw = $read_rich_input($major_row, 'summary_en', nor_notes_decode_rich_storage((string) ($existing_major['summary_en'] ?? '')));
    $summary_ja = trim((string) $summary_ja_raw);
    $summary_en = trim((string) $summary_en_raw);

    $raw_middles = isset($major_row['middles']) && is_array($major_row['middles']) ? array_values($major_row['middles']) : [];
    $existing_middles = isset($existing_major['middles']) && is_array($existing_major['middles']) ? array_values($existing_major['middles']) : [];
    $middles = [];

    foreach ($raw_middles as $md_idx => $middle_row) {
      if (!is_array($middle_row)) continue;

      $existing_middle = isset($existing_middles[$md_idx]) && is_array($existing_middles[$md_idx]) ? $existing_middles[$md_idx] : [];

      $middle_title_en_raw = array_key_exists('middle_title_en', $middle_row) ? (string) $middle_row['middle_title_en'] : (string) ($existing_middle['middle_title_en'] ?? '');
      $middle_title_en = sanitize_text_field(wp_unslash($middle_title_en_raw));

      $middle_id_raw = array_key_exists('middle_id', $middle_row) ? (string) $middle_row['middle_id'] : (string) ($existing_middle['middle_id'] ?? '');
      $middle_id = nor_notes_normalize_anchor_id(wp_unslash($middle_id_raw), '');

      $raw_items = isset($middle_row['items']) && is_array($middle_row['items']) ? array_values($middle_row['items']) : [];
      $existing_items = isset($existing_middle['items']) && is_array($existing_middle['items']) ? array_values($existing_middle['items']) : [];
      $items = [];

      foreach ($raw_items as $it_idx => $item_row) {
        if (!is_array($item_row)) continue;
        $existing_item = isset($existing_items[$it_idx]) && is_array($existing_items[$it_idx]) ? $existing_items[$it_idx] : [];

        $label_raw = $read_rich_input($item_row, 'label', nor_notes_decode_rich_storage((string) ($existing_item['label'] ?? '')));
        $desc_ja_raw = $read_rich_input($item_row, 'desc_ja', nor_notes_decode_rich_storage((string) ($existing_item['desc_ja'] ?? '')));
        $desc_en_raw = $read_rich_input($item_row, 'desc_en', nor_notes_decode_rich_storage((string) ($existing_item['desc_en'] ?? '')));

        $label = trim((string) $label_raw);
        $desc_ja = trim((string) $desc_ja_raw);
        $desc_en = trim((string) $desc_en_raw);

        $is_item_empty = ($label === '' && $desc_ja === '' && $desc_en === '');
        if ($is_item_empty) continue;

        $items[] = [
          'label' => nor_notes_encode_rich_storage($label),
          'desc_ja' => nor_notes_encode_rich_storage($desc_ja),
          'desc_en' => nor_notes_encode_rich_storage($desc_en),
        ];
      }

      $is_middle_empty = ($middle_title_en === '' && empty($items));
      if ($is_middle_empty) continue;

      $middles[] = [
        'middle_id' => $middle_id,
        'middle_title_en' => $middle_title_en,
        'items' => $items,
      ];
    }

    $is_major_empty = ($major_title_en === '' && $summary_ja === '' && $summary_en === '' && empty($middles));
    if ($is_major_empty) continue;

    $majors[] = [
      'major_id' => $major_id,
      'major_title_en' => $major_title_en,
      'summary_ja' => nor_notes_encode_rich_storage($summary_ja),
      'summary_en' => nor_notes_encode_rich_storage($summary_en),
      'middles' => $middles,
    ];
  }

  update_post_meta($post_id, 'nor_notes_majors', $majors);
});

/**
 * FAQs page content helpers
 * - Used by page-faqs.php rendering
 * - Used by header.php FAQPage mainEntity fallback (when page content is empty)
 */
if (!function_exists('nor_faqs_default_sections')) {
  function nor_faqs_default_sections(): array {
    $categories_url = esc_url(home_url('/categories/'));
    $tags_url = esc_url(home_url('/tags/'));
    $archives_url = esc_url(home_url('/archives/'));
    $clients_url = esc_url(home_url('/clients/'));
    $policies_url = esc_url(home_url('/policies/'));
    $policies_takedown_url = esc_url(home_url('/policies/#policies-heading-takedown'));
    $policies_licensing_url = esc_url(home_url('/policies/#policies-heading-licensing-reuse'));
    $policies_accessibility_url = esc_url(home_url('/policies/#policies-heading-accessibility'));
    $contact_url = esc_url(home_url('/contact/'));

    return [
      [
        'title_en' => 'About nør. & this site',
        'title_ja' => 'nør. とここ',
        'qas' => [
          [
            'q_ja' => 'このサイトは何ですか？',
            'q_en' => 'What is this site?',
            'a_ja' => 'デザイン実務の記録を体系的に公開するアーカイブです。<time datetime="2013">2013年</time>以降の案件を中心に掲載しています。',
            'a_en' => 'It&rsquo;s an archival record of design work, primarily covering projects since <time datetime="2013">2013</time>.',
          ],
          [
            'q_ja' => '画像が表示されない記録があるのはなぜ？',
            'q_en' => 'Why do some entries have no images?',
            'a_ja' => '画像は許諾時のみ掲載します。許諾がない場合はテキスト記録として保存します。',
            'a_en' => 'Visuals appear only when permission is granted; otherwise entries remain text-only.',
          ],
          [
            'q_ja' => '掲載の更新頻度は？',
            'q_en' => 'How often is the site updated?',
            'a_ja' => '不定期です。新規案件の追加や整理完了時に随時更新します。',
            'a_en' => 'On an irregular basis &mdash; whenever new work is added or reorganized.',
          ],
        ],
      ],
      [
        'title_en' => 'Classification & Search',
        'title_ja' => '分類と検索',
        'qas' => [
          [
            'q_ja' => '作品はどのように分類されていますか？',
            'q_en' => 'How are works classified?',
            'a_ja' => '「<a href="' . $categories_url . '"><i>Categories</i></a>」＝制作分野、「<a href="' . $tags_url . '"><i>Tags</i></a>」＝媒体・役割・使用ツール、「<a href="' . $archives_url . '"><i>Archives</i></a>」＝年別、「<a href="' . $clients_url . '"><i>Clients</i></a>」＝社名と業種で整理します。',
            'a_en' => '“<a href="' . $categories_url . '"><i>Categories</i></a>” = disciplines; “<a href="' . $tags_url . '"><i>Tags</i></a>” = medium/roles/tools; “<a href="' . $archives_url . '"><i>Archives</i></a>” = by year; “<a href="' . $clients_url . '"><i>Clients</i></a>” = name and industry.',
          ],
          [
            'q_ja' => '「<i>Industry</i>」の基準は？',
            'q_en' => 'What defines “<i>Industry</i>”?',
            'a_ja' => '日本標準産業分類（大分類）に準拠して索引します。',
            'a_en' => 'Indexing follows <abbr title="Japan Standard Industrial Classification">JSIC</abbr> large divisions.',
          ],
          [
            'q_ja' => '検索は何を対象にしていますか？',
            'q_en' => 'What does search cover?',
            'a_ja' => 'キーワード検索のみです。主に <code>project</code>／<code>page</code> が対象です（下書きは除外します）。',
            'a_en' => 'Keyword search only, targeting <code>project</code>s and <code>page</code>s (drafts excluded).',
          ],
        ],
      ],
      [
        'title_en' => 'Clients & Corrections',
        'title_ja' => '表記と削除',
        'qas' => [
          [
            'q_ja' => '社名の表記基準は？',
            'q_en' => 'How are client names written?',
            'a_ja' => '可能な限り正式名称で記載し、ふりがな・英字表記を付与します。',
            'a_en' => 'nør. uses official client names where possible, adding kana and Latin forms.',
          ],
          [
            'q_ja' => '表記の誤りや非掲載の要望は？',
            'q_en' => 'How to request a correction or removal?',
            'a_ja' => '「<a href="' . $policies_takedown_url . '"><i>Policies（Takedown）</i></a>」を御確認のうえ、必要事項を添えて「<a href="' . $contact_url . '"><i>Contact</i></a>」から御連絡ください。',
            'a_en' => 'See “<a href="' . $policies_takedown_url . '"><i>Policies (Takedown)</i></a>”, then contact nør. via “<a href="' . $contact_url . '"><i>Contact</i></a>” with the required details.',
          ],
          [
            'q_ja' => '掲載クライアントの選定基準は？',
            'q_en' => 'What are the inclusion criteria for clients?',
            'a_ja' => 'nør. が実際に関与した案件の取引先に限定し、公表可能な事実関係が確認できる場合のみ掲載します。',
            'a_en' => 'nør. lists only clients from projects it worked on, and only when verifiable public facts are available.',
          ],
        ],
      ],
      [
        'title_en' => 'Publication, Rights & Reuse',
        'title_ja' => '掲載と利用',
        'qas' => [
          [
            'q_ja' => '記事や画像を引用・再利用できますか？',
            'q_en' => 'May I quote or reuse content?',
            'a_ja' => 'テキストの引用は出典明記で可能です。画像・ロゴ等は事前許諾が必要です。詳細は「<a href="' . $policies_url . '"><i>Policies</i></a>」を御確認ください。',
            'a_en' => 'Text quotations are allowed with attribution. Images and logos require prior permission &mdash; see “<a href="' . $policies_url . '"><i>Policies</i></a>” for details.',
          ],
          [
            'q_ja' => '公開アセット（<abbr title="Cascading Style Sheets">CSS</abbr>/<abbr title="JavaScript">JS</abbr>）の扱いは？',
            'q_en' => 'How can I use the public <abbr title="Cascading Style Sheets">CSS</abbr>/<abbr title="JavaScript">JS</abbr> assets?',
            'a_ja' => '目的を問わず利用可能ですが、無保証（<code>as-is</code>）です。詳細は「<a href="' . $policies_licensing_url . '"><i>Policies（Content Licensing &amp; Reuse）</i></a>」を御参照ください。',
            'a_en' => 'They can be used for any purpose <code>as-is</code>, without warranty. See “<a href="' . $policies_licensing_url . '"><i>Policies (Content Licensing &amp; Reuse)</i></a>”.',
          ],
          [
            'q_ja' => 'Figma（トークン/コンポーネント）の扱いは？',
            'q_en' => 'How can I use the Figma tokens/components?',
            'a_ja' => '公開範囲での閲覧・複製は可能です。転用は条件付き・無保証です。詳細は「<a href="' . $policies_licensing_url . '"><i>Policies（Content Licensing &amp; Reuse）</i></a>」を御参照ください。',
            'a_en' => 'Where published, they may be viewed and duplicated; reuse is conditional and <code>as-is</code>. See “<a href="' . $policies_licensing_url . '"><i>Policies (Content Licensing &amp; Reuse)</i></a>”.',
          ],
        ],
      ],
      [
        'title_en' => 'Technical & Access',
        'title_ja' => '技術と通信',
        'qas' => [
          [
            'q_ja' => '対応ブラウザは？',
            'q_en' => 'Which browsers are supported?',
            'a_ja' => '主に Chrome と Safari の最新安定版で検証しています。他の主要ブラウザでも動作を想定していますが、個別検証は限定的で、Internet Explorer には対応していません。',
            'a_en' => 'nør. primarily tests on the latest stable versions of Chrome and Safari. Other major browsers are expected to work but are not tested exhaustively; Internet Explorer is not supported.',
          ],
          [
            'q_ja' => 'アクセシビリティ方針は？',
            'q_en' => 'What&rsquo;s your accessibility stance?',
            'a_ja' => 'レイアウトと表現を優先しつつ、可能な範囲で <abbr title="Web Content Accessibility Guidelines">WCAG 2.1 AA</abbr> を参照し、ダークモード等でコントラストを確保します。詳細は「<a href="' . $policies_accessibility_url . '"><i>Policies（Accessibility）</i></a>」を御確認ください。',
            'a_en' => 'nør. prioritizes layout and expression while referencing <abbr title="Web Content Accessibility Guidelines">WCAG 2.1 AA</abbr> where feasible, and uses options such as dark mode to help ensure contrast. See “<a href="' . $policies_accessibility_url . '"><i>Policies (Accessibility)</i></a>”.',
          ],
          [
            'q_ja' => 'クッキーや解析は使っていますか？',
            'q_en' => 'Do you use cookies or tracking?',
            'a_ja' => '動作に必要なクッキーと、Google Analytics による第一者解析用クッキーを使用します。第三者広告トラッキングは行っていません。',
            'a_en' => 'nør. uses cookies required for basic functionality and first-party analytics cookies via Google Analytics. nør. does not use third-party advertising trackers.',
          ],
        ],
      ],
      [
        'title_en' => 'Inquiries & Collaboration',
        'title_ja' => '依頼や連絡',
        'qas' => [
          [
            'q_ja' => '新規の制作依頼は可能ですか？',
            'q_en' => 'Can we commission new work?',
            'a_ja' => '本サイト経由では受け付けていません。広告・営業を目的としたサイトではありません。',
            'a_en' => 'We do not accept commissions via this site; it is not intended for advertising.',
          ],
          [
            'q_ja' => '自社案件の掲載を依頼できますか？',
            'q_en' => 'Can we ask to list our project?',
            'a_ja' => 'nør. が関与した案件のみ対象です。許諾や素材の可否を含め、個別にご相談ください。',
            'a_en' => 'Only projects involving nør. are eligible. nør. will confirm permissions and materials case-by-case.',
          ],
          [
            'q_ja' => '連絡方法は？',
            'q_en' => 'How can we get in touch?',
            'a_ja' => '既に面識のある方は「<a href="' . $contact_url . '"><i>Contact</i></a>」を御利用ください。初回連絡は対面・ご紹介のみ承ります。',
            'a_en' => 'If we already know each other, please use “<a href="' . $contact_url . '"><i>Contact</i></a>”. First-time inquiries are accepted in person or via referral only.',
          ],
        ],
      ],
      [
        'title_en' => 'Language & Legal',
        'title_ja' => '言語と法律',
        'qas' => [
          [
            'q_ja' => '日本語と英語で内容が異なる場合は？',
            'q_en' => 'Which language prevails?',
            'a_ja' => '矛盾が生じた場合は日本語版を優先します。',
            'a_en' => 'If any discrepancy exists, the Japanese text prevails.',
          ],
          [
            'q_ja' => 'さらに詳しい規定はどこに？',
            'q_en' => 'Where can I read the full policies?',
            'a_ja' => '「<a href="' . $policies_url . '"><i>Policies</i></a>」に掲載しています（掲載ポリシー、著作権・商標、再利用、Cookie／Privacy など）。',
            'a_en' => 'See “<a href="' . $policies_url . '"><i>Policies</i></a>” (publication, copyright/trademarks, reuse, cookie/privacy, etc.).',
          ],
          [
            'q_ja' => '準拠法と裁判管轄は？',
            'q_en' => 'What law and jurisdiction apply?',
            'a_ja' => '「<a href="' . $policies_url . '"><i>Policies</i></a>」に掲載しています。法的効果を強く期待していませんが、運用上の指針として定義しています。',
            'a_en' => 'They are listed in “<a href="' . $policies_url . '"><i>Policies</i></a>”. The provisions are not primarily intended as a binding legal instrument, but as operational guidance.',
          ],
        ],
      ],
    ];
  }
}

if (!function_exists('nor_faqs_allowed_html')) {
  function nor_faqs_allowed_html(): array {
    $allowed = wp_kses_allowed_html('post');
    $allowed['a'] = ['href' => true, 'target' => true, 'rel' => true, 'title' => true, 'class' => true];
    $allowed['abbr'] = ['title' => true, 'class' => true];
    $allowed['time'] = ['datetime' => true, 'class' => true];
    $allowed['span'] = ['class' => true];
    $allowed['code'] = ['class' => true];
    return $allowed;
  }
}

if (!function_exists('nor_faqs_get_sections')) {
  function nor_faqs_get_sections(int $post_id): array {
    $defaults = nor_faqs_default_sections();
    if ($post_id <= 0) return $defaults;

    $saved = get_post_meta($post_id, 'nor_faqs_sections', true);
    $has_saved = metadata_exists('post', $post_id, 'nor_faqs_sections');
    if (!$has_saved) return $defaults;
    if (!is_array($saved)) return $defaults;
    if (empty($saved)) return [];

    $allowed_html = nor_faqs_allowed_html();
    $sanitize_inline = static function ($raw) use ($allowed_html): string {
      $text = trim((string) $raw);
      if ($text === '') return '';
      return trim((string) wp_kses($text, $allowed_html));
    };
    $sanitize_text = static function ($raw): string {
      return trim(sanitize_text_field((string) $raw));
    };

    $sections = [];
    foreach (array_values($saved) as $s_idx => $section) {
      if (!is_array($section)) continue;

      $title_en = $sanitize_text($section['title_en'] ?? '');
      $title_ja = $sanitize_text($section['title_ja'] ?? '');
      $raw_qas = isset($section['qas']) && is_array($section['qas']) ? array_values($section['qas']) : [];
      $qas = [];

      foreach ($raw_qas as $qa) {
        if (!is_array($qa)) continue;
        $q_ja = $sanitize_inline($qa['q_ja'] ?? '');
        $q_en = $sanitize_inline($qa['q_en'] ?? '');
        $a_ja = $sanitize_inline($qa['a_ja'] ?? '');
        $a_en = $sanitize_inline($qa['a_en'] ?? '');

        if (($q_ja === '' && $q_en === '') || ($a_ja === '' && $a_en === '')) continue;
        $qas[] = [
          'q_ja' => $q_ja,
          'q_en' => $q_en,
          'a_ja' => $a_ja,
          'a_en' => $a_en,
        ];
      }

      if ($title_en === '' && $title_ja === '' && empty($qas)) continue;
      if ($title_en === '') $title_en = 'Section ' . ((int) $s_idx + 1);
      if ($title_ja === '') $title_ja = $title_en;

      $sections[] = [
        'title_en' => $title_en,
        'title_ja' => $title_ja,
        'qas' => $qas,
      ];
    }

    if (empty($sections)) return $defaults;
    return $sections;
  }
}

if (!function_exists('nor_faqs_build_main_entities')) {
  function nor_faqs_build_main_entities(array $sections): array {
    $normalize_plain = static function (string $raw): string {
      $text = html_entity_decode((string) wp_strip_all_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $text = trim((string) preg_replace('/\s+/u', ' ', $text));
      return $text;
    };

    $entities = [];
    foreach ($sections as $section) {
      if (!is_array($section)) continue;
      $qas = isset($section['qas']) && is_array($section['qas']) ? $section['qas'] : [];
      foreach ($qas as $qa) {
        if (!is_array($qa)) continue;

        $q_ja = $normalize_plain((string) ($qa['q_ja'] ?? ''));
        $a_ja = $normalize_plain((string) ($qa['a_ja'] ?? ''));
        $q_en = $normalize_plain((string) ($qa['q_en'] ?? ''));
        $a_en = $normalize_plain((string) ($qa['a_en'] ?? ''));

        if ($q_ja !== '' && $a_ja !== '') {
          $entities[] = [
            '@type' => 'Question',
            'name' => $q_ja,
            'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $a_ja,
            ],
          ];
        }
        if ($q_en !== '' && $a_en !== '') {
          $entities[] = [
            '@type' => 'Question',
            'name' => $q_en,
            'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $a_en,
            ],
          ];
        }
      }
    }

    return array_values($entities);
  }
}

add_action('add_meta_boxes_page', function ($post) {
  if (!$post instanceof WP_Post) return;
  if ((string) $post->post_name !== 'faqs') return;

  add_meta_box(
    'nor_faqs_page_builder',
    'FAQs',
    function ($post) {
      if (!$post instanceof WP_Post) return;

      $sections = function_exists('nor_faqs_get_sections')
        ? nor_faqs_get_sections((int) $post->ID)
        : [];
      if (!is_array($sections)) $sections = [];

      wp_nonce_field('nor_faqs_page_save', 'nor_faqs_page_nonce');
      ?>
      <p class="description">カテゴリ（セクション）とQ/Aを追加・削除・並び替えできます。</p>

      <div id="nor-faqs-editor">
        <div class="nor-faqs-sections">
          <?php foreach ($sections as $s_idx => $section) : ?>
            <?php
              if (!is_array($section)) continue;
              $title_en = isset($section['title_en']) ? (string) $section['title_en'] : '';
              $title_ja = isset($section['title_ja']) ? (string) $section['title_ja'] : '';
              $qas = isset($section['qas']) && is_array($section['qas']) ? array_values($section['qas']) : [];
            ?>
            <section class="nor-faqs-section" data-section-index="<?php echo esc_attr((string) $s_idx); ?>" style="margin-top:16px; padding:12px; border:1px solid #ccd0d4; background:#f8f9fa;">
              <header style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:10px;">
                <strong class="nor-faqs-section-heading">カテゴリ <?php echo esc_html((string) ((int) $s_idx + 1)); ?></strong>
                <span>
                  <button type="button" class="button-link nor-faqs-move-section-up">↑ 上へ</button>
                  <button type="button" class="button-link nor-faqs-move-section-down">↓ 下へ</button>
                  <button type="button" class="button-link-delete nor-faqs-remove-section">削除</button>
                </span>
              </header>

              <p style="margin:0 0 8px;">
                <label><strong>カテゴリ名（EN）</strong></label>
                <input type="text" style="width:100%;" data-section-field="title_en" name="<?php echo esc_attr('nor_faqs_sections[' . $s_idx . '][title_en]'); ?>" value="<?php echo esc_attr($title_en); ?>" />
              </p>
              <p style="margin:0 0 8px;">
                <label><strong>カテゴリ名（JA）</strong></label>
                <input type="text" style="width:100%;" data-section-field="title_ja" name="<?php echo esc_attr('nor_faqs_sections[' . $s_idx . '][title_ja]'); ?>" value="<?php echo esc_attr($title_ja); ?>" />
              </p>

              <div class="nor-faqs-qas" data-section-index="<?php echo esc_attr((string) $s_idx); ?>">
                <?php foreach ($qas as $q_idx => $qa) : ?>
                  <?php
                    if (!is_array($qa)) continue;
                    $q_ja = isset($qa['q_ja']) ? (string) $qa['q_ja'] : '';
                    $q_en = isset($qa['q_en']) ? (string) $qa['q_en'] : '';
                    $a_ja = isset($qa['a_ja']) ? (string) $qa['a_ja'] : '';
                    $a_en = isset($qa['a_en']) ? (string) $qa['a_en'] : '';
                    $base = 'nor_faqs_sections[' . $s_idx . '][qas][' . $q_idx . ']';
                  ?>
                  <article class="nor-faqs-qa" data-qa-index="<?php echo esc_attr((string) $q_idx); ?>" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">
                    <header style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:10px;">
                      <strong class="nor-faqs-qa-heading">Q/A <?php echo esc_html((string) ((int) $q_idx + 1)); ?></strong>
                      <span>
                        <button type="button" class="button-link nor-faqs-move-qa-up">↑ 上へ</button>
                        <button type="button" class="button-link nor-faqs-move-qa-down">↓ 下へ</button>
                        <button type="button" class="button-link-delete nor-faqs-remove-qa">削除</button>
                      </span>
                    </header>

                    <p style="margin:0 0 8px;">
                      <label><strong>Question（JA）</strong></label>
                      <textarea rows="2" style="width:100%;" data-qa-field="q_ja" name="<?php echo esc_attr($base . '[q_ja]'); ?>"><?php echo esc_textarea($q_ja); ?></textarea>
                    </p>
                    <p style="margin:0 0 8px;">
                      <label><strong>Question（EN）</strong></label>
                      <textarea rows="2" style="width:100%;" data-qa-field="q_en" name="<?php echo esc_attr($base . '[q_en]'); ?>"><?php echo esc_textarea($q_en); ?></textarea>
                    </p>
                    <p style="margin:0 0 8px;">
                      <label><strong>Answer（JA）</strong></label>
                      <textarea rows="3" style="width:100%;" data-qa-field="a_ja" name="<?php echo esc_attr($base . '[a_ja]'); ?>"><?php echo esc_textarea($a_ja); ?></textarea>
                    </p>
                    <p style="margin:0;">
                      <label><strong>Answer（EN）</strong></label>
                      <textarea rows="3" style="width:100%;" data-qa-field="a_en" name="<?php echo esc_attr($base . '[a_en]'); ?>"><?php echo esc_textarea($a_en); ?></textarea>
                    </p>
                  </article>
                <?php endforeach; ?>
              </div>

              <p style="margin:10px 0 0;">
                <button type="button" class="button nor-faqs-add-qa" data-section-index="<?php echo esc_attr((string) $s_idx); ?>">Q/Aを追加</button>
              </p>
            </section>
          <?php endforeach; ?>
        </div>

        <p style="margin-top:12px;">
          <button type="button" class="button button-primary nor-faqs-add-section">カテゴリを追加</button>
        </p>

        <template id="nor-faqs-section-template">
          <section class="nor-faqs-section" data-section-index="" style="margin-top:16px; padding:12px; border:1px solid #ccd0d4; background:#f8f9fa;">
            <header style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:10px;">
              <strong class="nor-faqs-section-heading">カテゴリ</strong>
              <span>
                <button type="button" class="button-link nor-faqs-move-section-up">↑ 上へ</button>
                <button type="button" class="button-link nor-faqs-move-section-down">↓ 下へ</button>
                <button type="button" class="button-link-delete nor-faqs-remove-section">削除</button>
              </span>
            </header>

            <p style="margin:0 0 8px;">
              <label><strong>カテゴリ名（EN）</strong></label>
              <input type="text" style="width:100%;" data-section-field="title_en" value="" />
            </p>
            <p style="margin:0 0 8px;">
              <label><strong>カテゴリ名（JA）</strong></label>
              <input type="text" style="width:100%;" data-section-field="title_ja" value="" />
            </p>

            <div class="nor-faqs-qas" data-section-index=""></div>
            <p style="margin:10px 0 0;">
              <button type="button" class="button nor-faqs-add-qa" data-section-index="">Q/Aを追加</button>
            </p>
          </section>
        </template>

        <template id="nor-faqs-qa-template">
          <article class="nor-faqs-qa" data-qa-index="" style="margin-top:12px; padding:12px; border:1px solid #d0d7de; background:#fff;">
            <header style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:10px;">
              <strong class="nor-faqs-qa-heading">Q/A</strong>
              <span>
                <button type="button" class="button-link nor-faqs-move-qa-up">↑ 上へ</button>
                <button type="button" class="button-link nor-faqs-move-qa-down">↓ 下へ</button>
                <button type="button" class="button-link-delete nor-faqs-remove-qa">削除</button>
              </span>
            </header>

            <p style="margin:0 0 8px;">
              <label><strong>Question（JA）</strong></label>
              <textarea rows="2" style="width:100%;" data-qa-field="q_ja"></textarea>
            </p>
            <p style="margin:0 0 8px;">
              <label><strong>Question（EN）</strong></label>
              <textarea rows="2" style="width:100%;" data-qa-field="q_en"></textarea>
            </p>
            <p style="margin:0 0 8px;">
              <label><strong>Answer（JA）</strong></label>
              <textarea rows="3" style="width:100%;" data-qa-field="a_ja"></textarea>
            </p>
            <p style="margin:0;">
              <label><strong>Answer（EN）</strong></label>
              <textarea rows="3" style="width:100%;" data-qa-field="a_en"></textarea>
            </p>
          </article>
        </template>
      </div>

      <script>
      (function () {
        var root = document.getElementById('nor-faqs-editor');
        if (!root) return;

        var sectionsWrap = root.querySelector('.nor-faqs-sections');
        if (!sectionsWrap) return;

        var sectionTpl = document.getElementById('nor-faqs-section-template');
        var qaTpl = document.getElementById('nor-faqs-qa-template');

        function cloneTemplate(tpl) {
          if (!tpl || !tpl.content) return null;
          var node = tpl.content.firstElementChild;
          if (!node) return null;
          return node.cloneNode(true);
        }

        function moveElement(el, direction) {
          if (!el || !el.parentNode) return;
          if (direction < 0) {
            var prev = el.previousElementSibling;
            if (prev) el.parentNode.insertBefore(el, prev);
          } else if (direction > 0) {
            var next = el.nextElementSibling;
            if (next) el.parentNode.insertBefore(next, el);
          }
        }

        function reindexAll() {
          var sections = sectionsWrap.querySelectorAll('.nor-faqs-section');
          for (var s = 0; s < sections.length; s++) {
            var section = sections[s];
            section.setAttribute('data-section-index', String(s));

            var sectionHeading = section.querySelector('.nor-faqs-section-heading');
            if (sectionHeading) sectionHeading.textContent = 'カテゴリ ' + String(s + 1);

            var sectionFields = section.querySelectorAll('[data-section-field]');
            for (var i = 0; i < sectionFields.length; i++) {
              var sf = sectionFields[i];
              var key = sf.getAttribute('data-section-field');
              if (!key) continue;
              sf.name = 'nor_faqs_sections[' + s + '][' + key + ']';
            }

            var qasWrap = section.querySelector('.nor-faqs-qas');
            if (!qasWrap) continue;
            qasWrap.setAttribute('data-section-index', String(s));

            var addQa = section.querySelector('.nor-faqs-add-qa');
            if (addQa) addQa.setAttribute('data-section-index', String(s));

            var qas = qasWrap.querySelectorAll('.nor-faqs-qa');
            for (var q = 0; q < qas.length; q++) {
              var qa = qas[q];
              qa.setAttribute('data-qa-index', String(q));
              var qaHeading = qa.querySelector('.nor-faqs-qa-heading');
              if (qaHeading) qaHeading.textContent = 'Q/A ' + String(q + 1);

              var qaFields = qa.querySelectorAll('[data-qa-field]');
              for (var k = 0; k < qaFields.length; k++) {
                var qf = qaFields[k];
                var field = qf.getAttribute('data-qa-field');
                if (!field) continue;
                qf.name = 'nor_faqs_sections[' + s + '][qas][' + q + '][' + field + ']';
              }
            }
          }
        }

        root.addEventListener('click', function (event) {
          var addSection = event.target.closest('.nor-faqs-add-section');
          if (addSection) {
            event.preventDefault();
            var sectionEl = cloneTemplate(sectionTpl);
            if (!sectionEl) return;
            sectionsWrap.appendChild(sectionEl);
            reindexAll();
            return;
          }

          var removeSection = event.target.closest('.nor-faqs-remove-section');
          if (removeSection) {
            event.preventDefault();
            var sectionToRemove = removeSection.closest('.nor-faqs-section');
            if (sectionToRemove) sectionToRemove.remove();
            reindexAll();
            return;
          }

          var moveSectionUp = event.target.closest('.nor-faqs-move-section-up');
          if (moveSectionUp) {
            event.preventDefault();
            moveElement(moveSectionUp.closest('.nor-faqs-section'), -1);
            reindexAll();
            return;
          }

          var moveSectionDown = event.target.closest('.nor-faqs-move-section-down');
          if (moveSectionDown) {
            event.preventDefault();
            moveElement(moveSectionDown.closest('.nor-faqs-section'), 1);
            reindexAll();
            return;
          }

          var addQa = event.target.closest('.nor-faqs-add-qa');
          if (addQa) {
            event.preventDefault();
            var sectionIndex = addQa.getAttribute('data-section-index');
            var qasWrap = root.querySelector('.nor-faqs-qas[data-section-index="' + sectionIndex + '"]');
            if (!qasWrap) return;
            var qaEl = cloneTemplate(qaTpl);
            if (!qaEl) return;
            qasWrap.appendChild(qaEl);
            reindexAll();
            return;
          }

          var removeQa = event.target.closest('.nor-faqs-remove-qa');
          if (removeQa) {
            event.preventDefault();
            var qaToRemove = removeQa.closest('.nor-faqs-qa');
            if (qaToRemove) qaToRemove.remove();
            reindexAll();
            return;
          }

          var moveQaUp = event.target.closest('.nor-faqs-move-qa-up');
          if (moveQaUp) {
            event.preventDefault();
            moveElement(moveQaUp.closest('.nor-faqs-qa'), -1);
            reindexAll();
            return;
          }

          var moveQaDown = event.target.closest('.nor-faqs-move-qa-down');
          if (moveQaDown) {
            event.preventDefault();
            moveElement(moveQaDown.closest('.nor-faqs-qa'), 1);
            reindexAll();
          }
        });

        var form = document.getElementById('post') || root.closest('form');
        if (form) {
          form.addEventListener('submit', function () {
            reindexAll();
          });
        }

        reindexAll();
      })();
      </script>
      <?php
    },
    'page',
    'normal',
    'default'
  );
});

add_action('save_post_page', function ($post_id) {
  $slug = get_post_field('post_name', $post_id);
  if ((string) $slug !== 'faqs') return;

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $nonce_ok = false;
  if (isset($_POST['nor_faqs_page_nonce'])) {
    $nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['nor_faqs_page_nonce']), 'nor_faqs_page_save');
  }

  $core_nonce_ok = false;
  if (isset($_POST['_wpnonce'])) {
    $core_nonce_ok = (bool) wp_verify_nonce((string) wp_unslash($_POST['_wpnonce']), 'update-post_' . (int) $post_id);
  }

  if (!$nonce_ok && !$core_nonce_ok) return;

  $has_sections = isset($_POST['nor_faqs_sections']) && is_array($_POST['nor_faqs_sections']);
  if (!$has_sections) return;

  $allowed_html = function_exists('nor_faqs_allowed_html')
    ? nor_faqs_allowed_html()
    : wp_kses_allowed_html('post');

  $sanitize_inline = static function ($raw) use ($allowed_html): string {
    $text = trim((string) wp_unslash((string) $raw));
    if ($text === '') return '';
    return trim((string) wp_kses($text, $allowed_html));
  };
  $sanitize_title = static function ($raw): string {
    return trim(sanitize_text_field((string) wp_unslash((string) $raw)));
  };

  $sections = [];
  $raw_sections = array_values($_POST['nor_faqs_sections']);

  foreach ($raw_sections as $section_row) {
    if (!is_array($section_row)) continue;

    $title_en = $sanitize_title($section_row['title_en'] ?? '');
    $title_ja = $sanitize_title($section_row['title_ja'] ?? '');

    $raw_qas = isset($section_row['qas']) && is_array($section_row['qas']) ? array_values($section_row['qas']) : [];
    $qas = [];

    foreach ($raw_qas as $qa_row) {
      if (!is_array($qa_row)) continue;

      $q_ja = $sanitize_inline($qa_row['q_ja'] ?? '');
      $q_en = $sanitize_inline($qa_row['q_en'] ?? '');
      $a_ja = $sanitize_inline($qa_row['a_ja'] ?? '');
      $a_en = $sanitize_inline($qa_row['a_en'] ?? '');

      if (($q_ja === '' && $q_en === '') || ($a_ja === '' && $a_en === '')) continue;

      $qas[] = [
        'q_ja' => $q_ja,
        'q_en' => $q_en,
        'a_ja' => $a_ja,
        'a_en' => $a_en,
      ];
    }

    if ($title_en === '' && $title_ja === '' && empty($qas)) continue;
    if ($title_en === '') $title_en = wp_strip_all_tags($title_ja);
    if ($title_ja === '') $title_ja = wp_strip_all_tags($title_en);

    $sections[] = [
      'title_en' => $title_en,
      'title_ja' => $title_ja,
      'qas' => $qas,
    ];
  }

  update_post_meta($post_id, 'nor_faqs_sections', $sections);
});

/**
 * Contact: settings, inbox, and form handling
 */
if (!function_exists('nor_contact_purpose_options')) {
  function nor_contact_purpose_options(): array {
    return [
      'publication' => ['ja' => '掲載・訂正の依頼', 'en' => 'Publication or Correction'],
      'takedown'    => ['ja' => '削除依頼', 'en' => 'Takedown'],
      'rights'      => ['ja' => '権利・商標など', 'en' => 'Rights & Trademarks'],
      'technical'   => ['ja' => '技術的不具合', 'en' => 'Technical Issue'],
      'other'       => ['ja' => 'その他（返信できない場合あり）', 'en' => 'Other (reply not guaranteed)'],
    ];
  }
}

if (!function_exists('nor_contact_status_options')) {
  function nor_contact_status_options(): array {
    return [
      'new'     => '未対応',
      'hold'    => '保留',
      'done'    => '完了',
      'spam'    => 'スパム',
    ];
  }
}

if (!function_exists('nor_contact_sanitize_multiline_emails')) {
  function nor_contact_sanitize_multiline_emails($raw): string {
    $text = trim((string) wp_unslash((string) $raw));
    if ($text === '') return '';

    $parts = preg_split('/[\s,;]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts)) return '';

    $emails = [];
    foreach ($parts as $part) {
      $email = sanitize_email((string) $part);
      if ($email === '' || !is_email($email)) continue;
      $emails[] = strtolower($email);
    }

    $emails = array_values(array_unique($emails));
    return implode("\n", $emails);
  }
}

if (!function_exists('nor_contact_sanitize_email')) {
  function nor_contact_sanitize_email($raw): string {
    $email = sanitize_email((string) wp_unslash((string) $raw));
    if ($email === '' || !is_email($email)) return '';
    return strtolower($email);
  }
}

if (!function_exists('nor_contact_sanitize_positive_months')) {
  function nor_contact_sanitize_positive_months($raw): int {
    $v = (int) $raw;
    if ($v < 1) $v = 1;
    if ($v > 120) $v = 120;
    return $v;
  }
}

if (!function_exists('nor_contact_started_signature')) {
  function nor_contact_started_signature(int $started_at): string {
    return hash_hmac('sha256', (string) $started_at, wp_salt('nonce'));
  }
}

if (!function_exists('nor_contact_normalize_url_tokens')) {
  function nor_contact_normalize_url_tokens(string $value): array {
    $parts = preg_split('/[\s,]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts)) return [];

    $tokens = [];
    foreach ($parts as $part) {
      $token = trim((string) $part);
      if ($token === '') continue;
      $token = (string) preg_replace('/[)\]}>、。，．,]+$/u', '', $token);
      $token = trim($token);
      if ($token === '') continue;
      $tokens[] = $token;
    }

    return array_values($tokens);
  }
}

if (!function_exists('nor_contact_prepare_url_for_validation')) {
  function nor_contact_prepare_url_for_validation(string $value): string {
    return trim($value);
  }
}

if (!function_exists('nor_contact_is_valid_url')) {
  function nor_contact_is_valid_url(string $value): bool {
    $prepared = nor_contact_prepare_url_for_validation($value);
    if ($prepared === '') return false;

    $parts = wp_parse_url($prepared);
    if (!is_array($parts)) return false;
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) return false;
    $host = trim((string) ($parts['host'] ?? ''));
    return $host !== '';
  }
}

if (!function_exists('nor_contact_default_notify_email')) {
  function nor_contact_default_notify_email(): string {
    $fallback = sanitize_email('account@not-or.jp');
    if ($fallback !== '' && is_email($fallback)) {
      return strtolower($fallback);
    }

    $admin_email = sanitize_email((string) get_option('admin_email', ''));
    if ($admin_email !== '' && is_email($admin_email)) {
      return strtolower($admin_email);
    }

    return '';
  }
}

if (!function_exists('nor_contact_primary_notify_email')) {
  function nor_contact_primary_notify_email(): string {
    $primary = nor_contact_sanitize_email((string) get_option('nor_contact_notify_primary_email', ''));
    if ($primary !== '') return $primary;
    return nor_contact_default_notify_email();
  }
}

if (!function_exists('nor_contact_get_notification_recipients')) {
  function nor_contact_get_notification_recipients(): array {
    $recipients = [];

    $primary = nor_contact_primary_notify_email();
    if ($primary !== '') $recipients[] = $primary;

    $extra_raw = (string) get_option('nor_contact_notify_extra_emails', '');
    $extra = nor_contact_sanitize_multiline_emails($extra_raw);
    if ($extra !== '') {
      $parts = preg_split('/[\s,;]+/u', $extra, -1, PREG_SPLIT_NO_EMPTY);
      if (is_array($parts)) {
        foreach ($parts as $part) {
          $email = sanitize_email((string) $part);
          if ($email === '' || !is_email($email)) continue;
          $recipients[] = strtolower($email);
        }
      }
    }

    return array_values(array_unique($recipients));
  }
}

if (!function_exists('nor_contact_replace_reply_placeholders')) {
  function nor_contact_replace_reply_placeholders(string $text, array $context): string {
    $name = trim((string) ($context['name'] ?? ''));
    $organization = trim((string) ($context['organization'] ?? ''));
    $email = trim((string) ($context['email'] ?? ''));
    $urls_raw = $context['urls'] ?? [];
    $details = trim((string) ($context['details'] ?? ''));
    $purpose_ja = trim((string) ($context['purpose_ja'] ?? ''));
    $purpose_en = trim((string) ($context['purpose_en'] ?? ''));
    $site_name = trim((string) get_bloginfo('name'));
    $site_url = trailingslashit(home_url('/'));

    $urls = [];
    if (is_array($urls_raw)) {
      foreach ($urls_raw as $u) {
        $v = trim((string) $u);
        if ($v !== '') $urls[] = $v;
      }
    } else {
      $v = trim((string) $urls_raw);
      if ($v !== '') $urls[] = $v;
    }
    $urls_text = !empty($urls) ? implode("\n", $urls) : '（未入力）';
    $details_text = ($details !== '') ? $details : '（未入力）';
    $email_text = ($email !== '') ? $email : '（未入力）';

    return strtr($text, [
      '{name}' => $name,
      '{organization}' => $organization,
      '{email}' => $email_text,
      '{urls}' => $urls_text,
      '{details}' => $details_text,
      '{purpose_ja}' => $purpose_ja,
      '{purpose_en}' => $purpose_en,
      '{site_name}' => $site_name,
      '{site_url}' => $site_url,
    ]);
  }
}

if (!function_exists('nor_contact_get_return_url')) {
  function nor_contact_get_return_url(): string {
    $page = get_page_by_path('contact');
    if ($page instanceof WP_Post) {
      $url = get_permalink($page);
      if (is_string($url) && $url !== '') return $url;
    }
    return home_url('/contact/');
  }
}

if (!function_exists('nor_contact_redirect_with_status')) {
  function nor_contact_redirect_with_status(string $status): void {
    $base = nor_contact_get_return_url();
    $url = add_query_arg(['contact_status' => $status], $base);
    wp_safe_redirect($url, 303);
    exit;
  }
}

if (!function_exists('nor_contact_get_remote_ip')) {
  function nor_contact_get_remote_ip(): string {
    $raw = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
    if ($raw === '') return '';
    if (filter_var($raw, FILTER_VALIDATE_IP) === false) return '';
    return $raw;
  }
}

if (!function_exists('nor_contact_rate_limit_exceeded')) {
  function nor_contact_rate_limit_exceeded(string $ip, int $limit = 5): bool {
    $key = 'nor_contact_rate_' . md5($ip !== '' ? $ip : 'unknown');
    $now = time();

    $bucket = get_transient($key);
    if (!is_array($bucket)) $bucket = [];

    $bucket = array_values(array_filter($bucket, static function ($ts) use ($now): bool {
      return is_int($ts) && $ts > ($now - HOUR_IN_SECONDS);
    }));

    if (count($bucket) >= $limit) {
      set_transient($key, $bucket, HOUR_IN_SECONDS + 60);
      return true;
    }

    $bucket[] = $now;
    set_transient($key, $bucket, HOUR_IN_SECONDS + 60);
    return false;
  }
}

if (!function_exists('nor_contact_send_admin_notification')) {
  function nor_contact_send_admin_notification(int $post_id, array $payload): void {
    $recipients = nor_contact_get_notification_recipients();
    if (empty($recipients)) return;

    $purpose_ja = trim((string) ($payload['purpose_ja'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));
    $organization = trim((string) ($payload['organization'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $urls = isset($payload['urls']) && is_array($payload['urls']) ? $payload['urls'] : [];
    $details = trim((string) ($payload['details'] ?? ''));
    $ip = trim((string) ($payload['ip'] ?? ''));
    $ua = trim((string) ($payload['ua'] ?? ''));

    $subject = '[nør] Contact: ' . ($purpose_ja !== '' ? $purpose_ja : 'Inquiry');
    if ($name !== '') $subject .= ' / ' . $name;

    $edit_url = admin_url('post.php?post=' . $post_id . '&action=edit');

    $lines = [
      'Contact フォームから新しい問い合わせを受信しました。',
      '',
      '管理画面: ' . $edit_url,
      '',
      '用件: ' . $purpose_ja,
      'お名前: ' . $name,
      '御所属: ' . $organization,
      'メール: ' . $email,
      '対象URL:',
    ];
    if (!empty($urls)) {
      foreach ($urls as $url) {
        $lines[] = '- ' . (string) $url;
      }
    } else {
      $lines[] = '- (なし)';
    }
    $lines[] = '';
    $lines[] = '詳細:';
    $lines[] = ($details !== '') ? $details : '(なし)';
    $lines[] = '';
    $lines[] = '送信元IP: ' . ($ip !== '' ? $ip : '(取得不可)');
    $lines[] = 'User-Agent: ' . ($ua !== '' ? $ua : '(取得不可)');

    $body = implode("\n", $lines);
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if ($email !== '' && is_email($email)) {
      $safe_name = trim((string) preg_replace('/[\r\n]+/u', ' ', $name));
      if ($safe_name === '') $safe_name = $email;
      $headers[] = 'Reply-To: ' . $safe_name . ' <' . $email . '>';
    }

    wp_mail($recipients, $subject, $body, $headers);
  }
}

if (!function_exists('nor_contact_send_auto_reply')) {
  function nor_contact_send_auto_reply(array $payload): void {
    $enabled = ((string) get_option('nor_contact_auto_reply_enabled', '1') === '1');
    if (!$enabled) return;

    $to = trim((string) ($payload['email'] ?? ''));
    if ($to === '' || !is_email($to)) return;

    $subject = trim((string) get_option('nor_contact_auto_reply_subject_ja', 'お問い合わせありがとうございます | nør. Ryousuke Tamura Design Office'));
    $body = trim((string) get_option('nor_contact_auto_reply_body_ja', "お問い合わせありがとうございます。\n内容を確認のうえ、必要に応じて返信いたします。\n\nName: {name}\nOrganization: {organization}\nPurpose: {purpose_ja}\n\n{site_name}\n{site_url}"));

    if ($subject === '') $subject = 'お問い合わせありがとうございます | nør. Ryousuke Tamura Design Office';
    if ($body === '') return;

    $body = nor_contact_replace_reply_placeholders($body, $payload);
    $subject = nor_contact_replace_reply_placeholders($subject, $payload);

    $reply_to = nor_contact_primary_notify_email();
    $site_name = trim((string) get_bloginfo('name'));
    if ($site_name === '') $site_name = 'nør.';

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($reply_to !== '' && is_email($reply_to)) {
      $headers[] = 'Reply-To: ' . $site_name . ' <' . $reply_to . '>';
    }

    wp_mail($to, $subject, $body, $headers);
  }
}

if (!function_exists('nor_contact_get_mail_failed_logs')) {
  function nor_contact_get_mail_failed_logs(): array {
    $logs = get_option('nor_contact_mail_failed_logs', []);
    if (!is_array($logs)) return [];

    $normalized = [];
    foreach ($logs as $row) {
      if (!is_array($row)) continue;
      $normalized[] = [
        'time_gmt' => sanitize_text_field((string) ($row['time_gmt'] ?? '')),
        'to'       => sanitize_text_field((string) ($row['to'] ?? '')),
        'subject'  => sanitize_text_field((string) ($row['subject'] ?? '')),
        'error'    => sanitize_text_field((string) ($row['error'] ?? '')),
      ];
    }

    return $normalized;
  }
}

if (!function_exists('nor_contact_append_mail_failed_log')) {
  function nor_contact_append_mail_failed_log(array $row): void {
    $logs = nor_contact_get_mail_failed_logs();
    $logs[] = [
      'time_gmt' => sanitize_text_field((string) ($row['time_gmt'] ?? '')),
      'to'       => sanitize_text_field((string) ($row['to'] ?? '')),
      'subject'  => sanitize_text_field((string) ($row['subject'] ?? '')),
      'error'    => sanitize_text_field((string) ($row['error'] ?? '')),
    ];

    if (count($logs) > 200) {
      $logs = array_slice($logs, -200);
    }

    update_option('nor_contact_mail_failed_logs', $logs, false);
  }
}

add_action('wp_mail_failed', function ($error): void {
  if (!$error instanceof WP_Error) return;

  $data = $error->get_error_data();
  if (!is_array($data)) $data = [];

  $to = '';
  if (isset($data['to'])) {
    if (is_array($data['to'])) {
      $list = array_values(array_filter(array_map(static function ($v): string {
        return trim((string) $v);
      }, $data['to']), static function ($v): bool {
        return $v !== '';
      }));
      $to = implode(', ', $list);
    } else {
      $to = trim((string) $data['to']);
    }
  }

  $subject = isset($data['subject']) ? trim((string) $data['subject']) : '';
  $messages = $error->get_error_messages();
  $error_text = !empty($messages) ? implode(' | ', array_map('strval', $messages)) : 'Unknown wp_mail_failed error';

  nor_contact_append_mail_failed_log([
    'time_gmt' => gmdate('c'),
    'to'       => $to,
    'subject'  => $subject,
    'error'    => $error_text,
  ]);
}, 10, 1);

add_action('admin_init', function () {
  register_setting('nor_contact_settings_options', 'nor_contact_notify_primary_email', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_contact_sanitize_email',
    'default'           => '',
  ]);
  register_setting('nor_contact_settings_options', 'nor_contact_notify_extra_emails', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_contact_sanitize_multiline_emails',
    'default'           => '',
  ]);
  register_setting('nor_contact_settings_options', 'nor_contact_auto_reply_enabled', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_checkbox_flag',
    'default'           => '1',
  ]);
  register_setting('nor_contact_settings_options', 'nor_contact_auto_reply_subject_ja', [
    'type'              => 'string',
    'sanitize_callback' => 'nor_sanitize_head_text',
    'default'           => 'お問い合わせありがとうございます | nør. Ryousuke Tamura Design Office',
  ]);
  register_setting('nor_contact_settings_options', 'nor_contact_auto_reply_body_ja', [
    'type'              => 'string',
    'sanitize_callback' => 'sanitize_textarea_field',
    'default'           => "お問い合わせありがとうございます。\n内容を確認のうえ、必要に応じて返信いたします。\n\nName: {name}\nOrganization: {organization}\nPurpose: {purpose_ja}\n\n{site_name}\n{site_url}",
  ]);
  register_setting('nor_contact_settings_options', 'nor_contact_retention_months', [
    'type'              => 'integer',
    'sanitize_callback' => 'nor_contact_sanitize_positive_months',
    'default'           => 24,
  ]);
});

if (!function_exists('nor_render_contact_settings_page')) {
  function nor_render_contact_settings_page(): void {
    if (!current_user_can('manage_options')) return;

    $notify_primary = (string) get_option('nor_contact_notify_primary_email', '');
    $notify_extra = (string) get_option('nor_contact_notify_extra_emails', '');
    $auto_reply_enabled = ((string) get_option('nor_contact_auto_reply_enabled', '1') === '1');
    $subject_ja = (string) get_option('nor_contact_auto_reply_subject_ja', 'お問い合わせありがとうございます | nør. Ryousuke Tamura Design Office');
    $body_ja = (string) get_option('nor_contact_auto_reply_body_ja', "お問い合わせありがとうございます。\n内容を確認のうえ、必要に応じて返信いたします。\n\nName: {name}\nOrganization: {organization}\nPurpose: {purpose_ja}\n\n{site_name}\n{site_url}");
    $retention = (int) get_option('nor_contact_retention_months', 24);
    if ($retention < 1) $retention = 1;
    if ($retention > 120) $retention = 120;
    $mail_log_cleared = isset($_GET['mail_log_cleared'])
      ? sanitize_key((string) wp_unslash($_GET['mail_log_cleared']))
      : '';
    $mail_logs = array_reverse(nor_contact_get_mail_failed_logs());
    $mail_logs = array_slice($mail_logs, 0, 20);
    ?>
    <div class="wrap">
      <h1>Contact</h1>
      <?php if ($mail_log_cleared === '1') : ?>
        <div class="notice notice-success is-dismissible"><p>送信失敗ログをクリアしました。</p></div>
      <?php endif; ?>
      <form method="post" action="options.php">
        <?php settings_fields('nor_contact_settings_options'); ?>

        <h2>通知アドレス</h2>
        <p>通知アドレスが未入力の場合は、管理者メールアドレス「account@not-or.jp」に通知されます。</p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="nor_contact_notify_primary_email">メールアドレス</label></th>
            <td>
              <input type="email" class="regular-text" id="nor_contact_notify_primary_email" name="nor_contact_notify_primary_email" value="<?php echo esc_attr($notify_primary); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="nor_contact_notify_extra_emails">サブメールアドレス</label></th>
            <td>
              <textarea class="large-text" rows="3" id="nor_contact_notify_extra_emails" name="nor_contact_notify_extra_emails"><?php echo esc_textarea($notify_extra); ?></textarea>
              <p class="description">改行またはカンマ区切りで複数指定できます。</p>
            </td>
          </tr>
        </table>

        <hr>
        <h2>自動返信</h2>
        <p>
          <label for="nor_contact_auto_reply_enabled">
            <input type="hidden" name="nor_contact_auto_reply_enabled" value="0">
            <input type="checkbox" id="nor_contact_auto_reply_enabled" name="nor_contact_auto_reply_enabled" value="1" <?php checked($auto_reply_enabled); ?>>
            送信者へ自動返信を送る
          </label>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="nor_contact_auto_reply_subject_ja">件名</label></th>
            <td><input type="text" class="large-text" id="nor_contact_auto_reply_subject_ja" name="nor_contact_auto_reply_subject_ja" value="<?php echo esc_attr($subject_ja); ?>"></td>
          </tr>
          <tr>
            <th scope="row"><label for="nor_contact_auto_reply_body_ja">本文</label></th>
            <td><textarea class="large-text" rows="8" id="nor_contact_auto_reply_body_ja" name="nor_contact_auto_reply_body_ja"><?php echo esc_textarea($body_ja); ?></textarea></td>
          </tr>
        </table>
        <p class="description">利用可能プレースホルダー: <code>{name}</code> <code>{organization}</code> <code>{email}</code> <code>{urls}</code> <code>{details}</code> <code>{purpose_ja}</code> <code>{site_name}</code> <code>{site_url}</code></p>

        <hr>
        <h2>保持期間</h2>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="nor_contact_retention_months">指定期間（ヶ月）</label></th>
            <td>
              <input type="number" min="1" max="120" class="small-text" id="nor_contact_retention_months" name="nor_contact_retention_months" value="<?php echo (int) $retention; ?>">
              <p class="description">保存済みの Contactメール を自動削除する保持期間です、ポリシー上、24ヶ月保持する設定です。</p>
            </td>
          </tr>
        </table>

        <hr>
        <h2>送信失敗ログ</h2>
        <p><code>wp_mail_failed</code> を記録しています。最新20件を表示します。</p>
        <?php if (empty($mail_logs)) : ?>
          <p>送信失敗ログはありません。</p>
        <?php else : ?>
          <table class="widefat striped" role="presentation">
            <thead>
              <tr>
                <th style="width: 22%;">日時（GMT）</th>
                <th style="width: 20%;">宛先</th>
                <th style="width: 28%;">件名</th>
                <th>エラー</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mail_logs as $row) : ?>
                <tr>
                  <td><code><?php echo esc_html((string) ($row['time_gmt'] ?? '')); ?></code></td>
                  <td><?php echo esc_html((string) ($row['to'] ?? '')); ?></td>
                  <td><?php echo esc_html((string) ($row['subject'] ?? '')); ?></td>
                  <td><?php echo esc_html((string) ($row['error'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <?php submit_button('設定を保存'); ?>
      </form>

      <?php if (!empty($mail_logs)) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
          <?php wp_nonce_field('nor_contact_clear_mail_failed_logs'); ?>
          <input type="hidden" name="action" value="nor_contact_clear_mail_failed_logs">
          <?php submit_button('送信失敗ログをクリア', 'delete', 'submit', false); ?>
        </form>
      <?php endif; ?>
    </div>
    <?php
  }
}

add_action('admin_menu', function () {
  add_options_page(
    'Contact',
    'Contact',
    'manage_options',
    'nor-contact-settings',
    'nor_render_contact_settings_page'
  );
});

add_action('admin_post_nor_contact_clear_mail_failed_logs', function (): void {
  if (!current_user_can('manage_options')) {
    wp_die('Forbidden', 403);
  }

  check_admin_referer('nor_contact_clear_mail_failed_logs');
  update_option('nor_contact_mail_failed_logs', [], false);

  $target = add_query_arg([
    'page'             => 'nor-contact-settings',
    'mail_log_cleared' => '1',
  ], admin_url('options-general.php'));

  wp_safe_redirect($target);
  exit;
});

add_action('init', function () {
  register_post_type('nor_contact', [
    'labels' => [
      'name'               => 'Contact',
      'singular_name'      => 'Contact',
      'menu_name'          => 'Contact',
      'all_items'          => '受信一覧',
      'edit_item'          => 'Contact を確認',
      'view_item'          => 'お問い合わせを表示',
      'search_items'       => 'お問い合わせを検索',
      'not_found'          => 'お問い合わせはありません。',
      'not_found_in_trash' => 'ゴミ箱にお問い合わせはありません。',
      'add_new'            => '新規追加',
    ],
    'public'              => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'menu_icon'           => 'dashicons-email-alt',
    'menu_position'       => 24,
    'supports'            => ['title'],
    'capability_type'     => 'post',
    'map_meta_cap'        => true,
    'exclude_from_search' => true,
    'publicly_queryable'  => false,
    'show_in_admin_bar'   => false,
    'show_in_rest'        => false,
  ]);
}, 11);

add_action('admin_menu', function () {
  remove_submenu_page('edit.php?post_type=nor_contact', 'post-new.php?post_type=nor_contact');
}, 99);

add_action('admin_init', function () {
  if (!is_admin()) return;
  global $pagenow;
  if ($pagenow !== 'post-new.php') return;
  $post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
  if ($post_type !== 'nor_contact') return;
  wp_safe_redirect(admin_url('edit.php?post_type=nor_contact'));
  exit;
});

if (!function_exists('nor_contact_set_admin_notice')) {
  function nor_contact_set_admin_notice(string $type, string $message): void {
    $user_id = get_current_user_id();
    if ($user_id <= 0) return;
    set_transient('nor_contact_admin_notice_' . $user_id, [
      'type' => ($type === 'error') ? 'error' : 'success',
      'message' => trim($message),
    ], 120);
  }
}

if (!function_exists('nor_contact_send_manual_reply')) {
  function nor_contact_send_manual_reply(int $post_id, string $subject, string $body): bool {
    $to = trim((string) get_post_meta($post_id, 'nor_contact_email', true));
    if ($to === '' || !is_email($to)) return false;

    $subject = trim($subject);
    $body = trim($body);
    if ($subject === '' || $body === '') return false;

    $reply_to = nor_contact_primary_notify_email();

    $site_name = trim((string) get_bloginfo('name'));
    if ($site_name === '') $site_name = 'nør.';

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($reply_to !== '' && is_email($reply_to)) {
      $headers[] = 'Reply-To: ' . $site_name . ' <' . $reply_to . '>';
    }

    return (bool) wp_mail($to, $subject, $body, $headers);
  }
}

if (!function_exists('nor_contact_get_manual_reply_template')) {
  function nor_contact_get_manual_reply_template(array $context): array {
    $name = trim((string) ($context['name'] ?? ''));
    $purpose = trim((string) ($context['purpose'] ?? ''));
    $urls_raw = trim((string) ($context['urls'] ?? ''));
    $purpose_options = nor_contact_purpose_options();
    $purpose_ja = isset($purpose_options[$purpose]['ja']) ? (string) $purpose_options[$purpose]['ja'] : 'お問い合わせ';

    $subject_map = [
      'publication' => '掲載・訂正のご依頼について',
      'takedown'    => '削除依頼について',
      'rights'      => '権利・商標に関するご連絡について',
      'technical'   => '技術的不具合のご報告について',
      'other'       => 'お問い合わせありがとうございます',
    ];

    $subject_tail = isset($subject_map[$purpose]) ? (string) $subject_map[$purpose] : 'お問い合わせありがとうございます';
    if ($name !== '') {
      $subject = 'Re: nør. より ' . $name . ' 様 / ' . $subject_tail;
    } else {
      $subject = 'Re: nør. より ' . $subject_tail;
    }

    $lead_map = [
      'publication' => '掲載・訂正のご依頼内容を確認し、必要に応じて事実確認のうえ対応いたします。',
      'takedown'    => '削除依頼の内容を確認し、権利・契約・公開範囲に照らして対応いたします。',
      'rights'      => '権利・商標に関するご連絡内容を確認し、必要な範囲で対応いたします。',
      'technical'   => '技術的不具合のご報告を確認し、再現確認のうえ対応いたします。',
      'other'       => 'お問い合わせ内容を確認し、必要に応じてご連絡いたします。',
    ];
    $lead = isset($lead_map[$purpose]) ? (string) $lead_map[$purpose] : 'お問い合わせ内容を確認し、必要に応じてご連絡いたします。';

    $lines = [];
    $lines[] = ($name !== '') ? ($name . ' 様') : 'お問い合わせいただいた方へ';
    $lines[] = '';
    $lines[] = 'このたびは nør. へご連絡いただき、ありがとうございます。';
    $lines[] = '用件: ' . $purpose_ja;
    $lines[] = $lead;

    if ($urls_raw !== '') {
      $urls = preg_split('/\R/u', $urls_raw, -1, PREG_SPLIT_NO_EMPTY);
      if (is_array($urls) && !empty($urls)) {
        $lines[] = '';
        $lines[] = '対象URL';
        foreach ($urls as $u) {
          $url = trim((string) $u);
          if ($url === '') continue;
          $lines[] = '- ' . $url;
        }
      }
    }

    $lines[] = '';
    $lines[] = '内容の確認後、必要に応じてあらためてご連絡いたします。';
    $lines[] = '';
    $lines[] = 'よろしくお願いいたします。';
    $lines[] = '';
    $lines[] = '--';
    $lines[] = 'nør. Ryousuke Tamura Design Office';
    $lines[] = 'https://not-or.jp/';

    return [
      'subject' => $subject,
      'body'    => implode("\n", $lines),
    ];
  }
}

add_action('admin_notices', function () {
  if (!is_admin()) return;
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || (string) ($screen->id ?? '') !== 'nor_contact') return;

  $user_id = get_current_user_id();
  if ($user_id <= 0) return;
  $key = 'nor_contact_admin_notice_' . $user_id;
  $notice = get_transient($key);
  if (!is_array($notice)) return;
  delete_transient($key);

  $type = ((string) ($notice['type'] ?? 'success') === 'error') ? 'error' : 'success';
  $message = trim((string) ($notice['message'] ?? ''));
  if ($message === '') return;
  ?>
  <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible"><p><?php echo esc_html($message); ?></p></div>
  <?php
});

add_action('add_meta_boxes_nor_contact', function () {
  add_meta_box(
    'nor_contact_detail_box',
    'お問い合わせ内容',
    function (WP_Post $post): void {
      $purpose = trim((string) get_post_meta($post->ID, 'nor_contact_purpose', true));
      $name = trim((string) get_post_meta($post->ID, 'nor_contact_name', true));
      $organization = trim((string) get_post_meta($post->ID, 'nor_contact_organization', true));
      $email = trim((string) get_post_meta($post->ID, 'nor_contact_email', true));
      $urls = trim((string) get_post_meta($post->ID, 'nor_contact_urls', true));
      $details = trim((string) get_post_meta($post->ID, 'nor_contact_details', true));
      $submitted_gmt = trim((string) get_post_meta($post->ID, 'nor_contact_submitted_at_gmt', true));
      $ua = trim((string) get_post_meta($post->ID, 'nor_contact_user_agent', true));
      $ip_hash = trim((string) get_post_meta($post->ID, 'nor_contact_ip_hash', true));

      $purpose_options = nor_contact_purpose_options();
      $purpose_ja = isset($purpose_options[$purpose]['ja']) ? (string) $purpose_options[$purpose]['ja'] : $purpose;
      $purpose_en = isset($purpose_options[$purpose]['en']) ? (string) $purpose_options[$purpose]['en'] : '';

      $submitted_label = '';
      if ($submitted_gmt !== '') {
        $ts = strtotime($submitted_gmt);
        if ($ts !== false) {
          $submitted_label = wp_date('Y-m-d H:i:s', $ts);
        }
      }

      $urls_list = [];
      if ($urls !== '') {
        $rows = preg_split('/\R/u', $urls, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($rows)) $urls_list = array_values($rows);
      }
      ?>
      <table class="widefat striped" style="margin-top:8px;">
        <tbody>
          <tr><th style="width:160px;">受信日時</th><td><?php echo esc_html($submitted_label !== '' ? $submitted_label : get_the_date('Y-m-d H:i:s', $post)); ?></td></tr>
          <tr><th>用件</th><td><strong><?php echo esc_html($purpose_ja); ?></strong><?php if ($purpose_en !== '') : ?> <span style="opacity:.7;">(<?php echo esc_html($purpose_en); ?>)</span><?php endif; ?></td></tr>
          <tr><th>お名前</th><td><?php echo esc_html($name); ?></td></tr>
          <tr><th>御所属</th><td><?php echo esc_html($organization); ?></td></tr>
          <tr><th>メール</th><td><?php if ($email !== '') : ?><a href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?></td></tr>
          <tr>
            <th>対象URL</th>
            <td>
              <?php if (!empty($urls_list)) : ?>
                <ul style="margin:0; padding-left:1.2em;">
                  <?php foreach ($urls_list as $u) : ?>
                    <li><a href="<?php echo esc_url($u); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($u); ?></a></li>
                  <?php endforeach; ?>
                </ul>
              <?php else : ?>
                <span>（なし）</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr><th>詳細</th><td style="white-space:pre-wrap;"><?php echo esc_html($details !== '' ? $details : '（なし）'); ?></td></tr>
          <tr><th>送信元IP（ハッシュ）</th><td><code><?php echo esc_html($ip_hash !== '' ? $ip_hash : '（記録なし）'); ?></code></td></tr>
          <tr><th>User-Agent</th><td style="word-break:break-all;"><code><?php echo esc_html($ua !== '' ? $ua : '（記録なし）'); ?></code></td></tr>
        </tbody>
      </table>
      <?php
    },
    'nor_contact',
    'normal',
    'default'
  );

  add_meta_box(
    'nor_contact_status_box',
    '対応管理',
    function (WP_Post $post): void {
      $status = trim((string) get_post_meta($post->ID, 'nor_contact_status', true));
      if ($status === '') $status = 'new';
      $purpose = trim((string) get_post_meta($post->ID, 'nor_contact_purpose', true));
      $note = (string) get_post_meta($post->ID, 'nor_contact_admin_note', true);
      $email = trim((string) get_post_meta($post->ID, 'nor_contact_email', true));
      $name = trim((string) get_post_meta($post->ID, 'nor_contact_name', true));
      $urls = trim((string) get_post_meta($post->ID, 'nor_contact_urls', true));
      $status_options = nor_contact_status_options();
      $reply_logs = get_post_meta($post->ID, 'nor_contact_reply_logs', true);
      if (!is_array($reply_logs)) $reply_logs = [];

      $template = nor_contact_get_manual_reply_template([
        'name'    => $name,
        'purpose' => $purpose,
        'urls'    => $urls,
      ]);
      $default_subject = trim((string) ($template['subject'] ?? ''));
      if ($default_subject === '') {
        $default_subject = 'Re: nør. より お問い合わせありがとうございます';
        if ($name !== '') $default_subject = 'Re: nør. より ' . $name . ' 様 / お問い合わせありがとうございます';
      }
      $default_body = trim((string) ($template['body'] ?? ''));

      wp_nonce_field('nor_contact_admin_save', 'nor_contact_admin_nonce');
      ?>
      <p>
        <label for="nor_contact_status"><strong>ステータス</strong></label><br>
        <select id="nor_contact_status" name="nor_contact_status" style="width:100%;">
          <?php foreach ($status_options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
      <p>
        <label for="nor_contact_admin_note"><strong>管理メモ</strong></label>
        <textarea id="nor_contact_admin_note" name="nor_contact_admin_note" rows="6" style="width:100%;"><?php echo esc_textarea((string) $note); ?></textarea>
      </p>
      <hr>
      <p><strong>返信メール送信</strong></p>
      <p style="margin-top:-6px; color:#50575e;">宛先: <?php echo esc_html($email !== '' ? $email : '（メール未登録）'); ?></p>
      <p>
        <label for="nor_contact_reply_subject">件名</label>
        <input type="text" id="nor_contact_reply_subject" name="nor_contact_reply_subject" value="<?php echo esc_attr($default_subject); ?>" style="width:100%;">
      </p>
      <p>
        <label for="nor_contact_reply_body">本文</label>
        <textarea id="nor_contact_reply_body" name="nor_contact_reply_body" rows="10" style="width:100%;"><?php echo esc_textarea($default_body); ?></textarea>
      </p>
      <p>
        <button type="submit" class="button button-secondary" name="nor_contact_send_reply" value="1">返信メールを送信</button>
      </p>
      <?php if (!empty($reply_logs)) : ?>
        <details style="margin-top:12px;">
          <summary>送信履歴</summary>
          <ul style="margin-top:8px; padding-left:1.2em;">
            <?php foreach (array_reverse($reply_logs) as $log) : ?>
              <?php
                if (!is_array($log)) continue;
                $sent_at = trim((string) ($log['sent_at'] ?? ''));
                $sent_subject = trim((string) ($log['subject'] ?? ''));
                $sent_by = trim((string) ($log['sent_by'] ?? ''));
              ?>
              <li>
                <?php echo esc_html($sent_at); ?>
                <?php if ($sent_subject !== '') : ?> / <?php echo esc_html($sent_subject); ?><?php endif; ?>
                <?php if ($sent_by !== '') : ?> / by <?php echo esc_html($sent_by); ?><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
      <?php
    },
    'nor_contact',
    'side',
    'default'
  );
});

add_action('save_post_nor_contact', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (!isset($_POST['nor_contact_admin_nonce'])) return;
  if (!wp_verify_nonce((string) wp_unslash($_POST['nor_contact_admin_nonce']), 'nor_contact_admin_save')) return;

  $status_options = nor_contact_status_options();
  $status = isset($_POST['nor_contact_status']) ? sanitize_key((string) wp_unslash($_POST['nor_contact_status'])) : '';
  if (!isset($status_options[$status])) $status = 'new';

  $note = isset($_POST['nor_contact_admin_note'])
    ? sanitize_textarea_field((string) wp_unslash($_POST['nor_contact_admin_note']))
    : '';

  update_post_meta($post_id, 'nor_contact_status', $status);
  update_post_meta($post_id, 'nor_contact_admin_note', trim($note));

  $send_reply = isset($_POST['nor_contact_send_reply']) && ((string) wp_unslash($_POST['nor_contact_send_reply']) === '1');
  if (!$send_reply) return;

  $subject = isset($_POST['nor_contact_reply_subject'])
    ? sanitize_text_field((string) wp_unslash($_POST['nor_contact_reply_subject']))
    : '';
  $body = isset($_POST['nor_contact_reply_body'])
    ? sanitize_textarea_field((string) wp_unslash($_POST['nor_contact_reply_body']))
    : '';

  if (trim($subject) === '' || trim($body) === '') {
    nor_contact_set_admin_notice('error', '返信メールは件名と本文の両方が必要です。');
    return;
  }

  $sent = nor_contact_send_manual_reply((int) $post_id, $subject, $body);
  if (!$sent) {
    nor_contact_set_admin_notice('error', '返信メールの送信に失敗しました。メールアドレス設定とサーバー送信設定を確認してください。');
    return;
  }

  $logs = get_post_meta($post_id, 'nor_contact_reply_logs', true);
  if (!is_array($logs)) $logs = [];
  $current_user = wp_get_current_user();
  $logs[] = [
    'sent_at' => wp_date('Y-m-d H:i:s'),
    'subject' => trim($subject),
    'sent_by' => ($current_user instanceof WP_User) ? (string) $current_user->user_login : '',
  ];
  if (count($logs) > 30) {
    $logs = array_slice($logs, -30);
  }
  update_post_meta($post_id, 'nor_contact_reply_logs', $logs);

  nor_contact_set_admin_notice('success', '返信メールを送信しました。');
});

add_filter('manage_nor_contact_posts_columns', function ($columns) {
  return [
    'cb'                 => $columns['cb'] ?? '',
    'title'              => '問い合わせ',
    'nor_contact_status' => 'ステータス',
    'nor_contact_purpose'=> '用件',
    'nor_contact_name'   => 'お名前 / 御所属',
    'nor_contact_email'  => 'メール',
    'date'               => '受信日時',
  ];
});

add_action('manage_nor_contact_posts_custom_column', function ($column, $post_id) {
  if ($column === 'nor_contact_status') {
    $status = trim((string) get_post_meta($post_id, 'nor_contact_status', true));
    if ($status === '') $status = 'new';
    $labels = nor_contact_status_options();
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    echo esc_html($label);
    return;
  }

  if ($column === 'nor_contact_purpose') {
    $purpose = trim((string) get_post_meta($post_id, 'nor_contact_purpose', true));
    $options = nor_contact_purpose_options();
    $label = isset($options[$purpose]['ja']) ? (string) $options[$purpose]['ja'] : $purpose;
    echo esc_html($label);
    return;
  }

  if ($column === 'nor_contact_name') {
    $name = trim((string) get_post_meta($post_id, 'nor_contact_name', true));
    $org = trim((string) get_post_meta($post_id, 'nor_contact_organization', true));
    echo esc_html($name);
    if ($org !== '') {
      echo '<br><span style="opacity:.7;">' . esc_html($org) . '</span>';
    }
    return;
  }

  if ($column === 'nor_contact_email') {
    $email = trim((string) get_post_meta($post_id, 'nor_contact_email', true));
    if ($email !== '') {
      echo '<a href="' . esc_url('mailto:' . $email) . '">' . esc_html($email) . '</a>';
    }
    return;
  }
}, 10, 2);

if (!function_exists('nor_contact_get_admin_list_filter')) {
  function nor_contact_get_admin_list_filter(string $key): string {
    if (!isset($_GET[$key])) return '';
    return sanitize_key((string) wp_unslash($_GET[$key]));
  }
}

add_action('restrict_manage_posts', function ($post_type): void {
  if ((string) $post_type !== 'nor_contact') return;

  $status_options = nor_contact_status_options();
  $purpose_options = nor_contact_purpose_options();

  $selected_status = nor_contact_get_admin_list_filter('nor_contact_filter_status');
  $selected_purpose = nor_contact_get_admin_list_filter('nor_contact_filter_purpose');

  echo '<select name="nor_contact_filter_status">';
  echo '<option value="">すべてのステータス</option>';
  foreach ($status_options as $value => $label) {
    printf(
      '<option value="%s"%s>%s</option>',
      esc_attr($value),
      selected($selected_status, (string) $value, false),
      esc_html((string) $label)
    );
  }
  echo '</select>';

  echo '<select name="nor_contact_filter_purpose">';
  echo '<option value="">すべての用件</option>';
  foreach ($purpose_options as $value => $labels) {
    $label = isset($labels['ja']) ? (string) $labels['ja'] : (string) $value;
    printf(
      '<option value="%s"%s>%s</option>',
      esc_attr($value),
      selected($selected_purpose, (string) $value, false),
      esc_html($label)
    );
  }
  echo '</select>';

  $export_url = add_query_arg([
    'post_type'                 => 'nor_contact',
    'nor_contact_export_csv'    => '1',
    'nor_contact_filter_status' => $selected_status,
    'nor_contact_filter_purpose'=> $selected_purpose,
  ], admin_url('edit.php'));

  printf(
    '<a class="button" href="%s" style="margin-left:8px;">CSV出力</a>',
    esc_url($export_url)
  );
}, 10, 1);

add_action('pre_get_posts', function ($query): void {
  if (!is_admin() || !($query instanceof WP_Query) || !$query->is_main_query()) return;

  $post_type = $query->get('post_type');
  if ($post_type !== 'nor_contact') return;

  $status = nor_contact_get_admin_list_filter('nor_contact_filter_status');
  $purpose = nor_contact_get_admin_list_filter('nor_contact_filter_purpose');

  $meta_query = $query->get('meta_query');
  if (!is_array($meta_query)) $meta_query = [];

  $status_options = nor_contact_status_options();
  if ($status !== '' && isset($status_options[$status])) {
    $meta_query[] = [
      'key'   => 'nor_contact_status',
      'value' => $status,
    ];
  }

  $purpose_options = nor_contact_purpose_options();
  if ($purpose !== '' && isset($purpose_options[$purpose])) {
    $meta_query[] = [
      'key'   => 'nor_contact_purpose',
      'value' => $purpose,
    ];
  }

  if (!empty($meta_query)) {
    $query->set('meta_query', $meta_query);
  }
}, 20, 1);

add_action('load-edit.php', function (): void {
  if (!is_admin()) return;

  $post_type = isset($_GET['post_type']) ? sanitize_key((string) wp_unslash($_GET['post_type'])) : 'post';
  if ($post_type !== 'nor_contact') return;

  $export = isset($_GET['nor_contact_export_csv']) ? sanitize_key((string) wp_unslash($_GET['nor_contact_export_csv'])) : '';
  if ($export !== '1') return;

  if (!current_user_can('edit_posts')) {
    wp_die('Forbidden', 403);
  }

  $status = nor_contact_get_admin_list_filter('nor_contact_filter_status');
  $purpose = nor_contact_get_admin_list_filter('nor_contact_filter_purpose');

  $args = [
    'post_type'           => 'nor_contact',
    'post_status'         => ['private'],
    'posts_per_page'      => -1,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'fields'              => 'ids',
    'no_found_rows'       => true,
    'ignore_sticky_posts' => true,
  ];

  $meta_query = [];
  $status_options = nor_contact_status_options();
  if ($status !== '' && isset($status_options[$status])) {
    $meta_query[] = [
      'key'   => 'nor_contact_status',
      'value' => $status,
    ];
  }

  $purpose_options = nor_contact_purpose_options();
  if ($purpose !== '' && isset($purpose_options[$purpose])) {
    $meta_query[] = [
      'key'   => 'nor_contact_purpose',
      'value' => $purpose,
    ];
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
  }

  $query = new WP_Query($args);

  nocache_headers();
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="contact-' . gmdate('Ymd-His') . '.csv"');

  $out = fopen('php://output', 'w');
  if ($out === false) exit;

  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, [
    '受信日時',
    'ステータス',
    '用件',
    'お名前',
    '御所属',
    'メールアドレス',
    '対象URL',
    '詳細',
  ]);

  foreach ($query->posts as $post_id) {
    $status_value = trim((string) get_post_meta((int) $post_id, 'nor_contact_status', true));
    if ($status_value === '') $status_value = 'new';
    $status_label = isset($status_options[$status_value]) ? (string) $status_options[$status_value] : $status_value;

    $purpose_value = trim((string) get_post_meta((int) $post_id, 'nor_contact_purpose', true));
    $purpose_label = isset($purpose_options[$purpose_value]['ja']) ? (string) $purpose_options[$purpose_value]['ja'] : $purpose_value;

    $submitted_gmt = trim((string) get_post_meta((int) $post_id, 'nor_contact_submitted_at_gmt', true));
    $submitted_at = '';
    if ($submitted_gmt !== '') {
      $ts = strtotime($submitted_gmt);
      if ($ts !== false) {
        $submitted_at = wp_date('Y-m-d H:i:s', $ts);
      }
    }
    if ($submitted_at === '') {
      $submitted_at = get_the_date('Y-m-d H:i:s', (int) $post_id);
    }

    $name = trim((string) get_post_meta((int) $post_id, 'nor_contact_name', true));
    $organization = trim((string) get_post_meta((int) $post_id, 'nor_contact_organization', true));
    $email = trim((string) get_post_meta((int) $post_id, 'nor_contact_email', true));
    $urls = trim((string) get_post_meta((int) $post_id, 'nor_contact_urls', true));
    $details = trim((string) get_post_meta((int) $post_id, 'nor_contact_details', true));

    if ($urls !== '') {
      $urls = str_replace(["\r\n", "\r", "\n"], ' | ', $urls);
    }

    fputcsv($out, [
      $submitted_at,
      $status_label,
      $purpose_label,
      $name,
      $organization,
      $email,
      $urls,
      $details,
    ]);
  }

  fclose($out);
  exit;
});

add_action('admin_post_nopriv_nor_contact_submit', 'nor_handle_contact_submit');
add_action('admin_post_nor_contact_submit', 'nor_handle_contact_submit');

if (!function_exists('nor_handle_contact_submit')) {
  function nor_handle_contact_submit(): void {
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';
    if ($request_method !== 'POST') {
      nor_contact_redirect_with_status('error');
    }

    $nonce = isset($_POST['nor_contact_nonce']) ? (string) wp_unslash($_POST['nor_contact_nonce']) : '';
    if (!wp_verify_nonce($nonce, 'nor_contact_submit')) {
      nor_contact_redirect_with_status('error');
    }

    $honeypot = isset($_POST['_nor_contact_website']) ? trim((string) wp_unslash($_POST['_nor_contact_website'])) : '';
    if ($honeypot !== '') {
      nor_contact_redirect_with_status('spam');
    }

    $started_at = isset($_POST['_nor_contact_started_at']) ? (int) $_POST['_nor_contact_started_at'] : 0;
    $started_sig = isset($_POST['_nor_contact_started_sig']) ? (string) wp_unslash($_POST['_nor_contact_started_sig']) : '';
    $now = time();
    $expected_sig = nor_contact_started_signature($started_at);
    $sig_ok = ($started_sig !== '' && hash_equals($expected_sig, $started_sig));
    $elapsed = ($started_at > 0) ? ($now - $started_at) : -1;
    if (!$sig_ok || $elapsed < 3 || $elapsed > (2 * HOUR_IN_SECONDS)) {
      nor_contact_redirect_with_status('spam');
    }

    $ip = nor_contact_get_remote_ip();
    if (nor_contact_rate_limit_exceeded($ip, 5)) {
      nor_contact_redirect_with_status('spam');
    }

    $purpose = isset($_POST['purpose']) ? sanitize_key((string) wp_unslash($_POST['purpose'])) : '';
    $name = isset($_POST['name']) ? sanitize_text_field((string) wp_unslash($_POST['name'])) : '';
    $organization = isset($_POST['organization']) ? sanitize_text_field((string) wp_unslash($_POST['organization'])) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) wp_unslash($_POST['email'])) : '';
    $urls_raw = isset($_POST['urls']) ? trim((string) wp_unslash($_POST['urls'])) : '';
    $details = isset($_POST['details']) ? sanitize_textarea_field((string) wp_unslash($_POST['details'])) : '';

    $purpose_options = nor_contact_purpose_options();
    if (!isset($purpose_options[$purpose])) {
      nor_contact_redirect_with_status('error');
    }

    if ($name === '' || $organization === '' || $email === '' || !is_email($email)) {
      nor_contact_redirect_with_status('error');
    }

    $url_tokens = nor_contact_normalize_url_tokens($urls_raw);
    if (empty($url_tokens)) {
      nor_contact_redirect_with_status('error');
    }

    $normalized_urls = [];
    foreach ($url_tokens as $token) {
      if (!nor_contact_is_valid_url($token)) {
        nor_contact_redirect_with_status('error');
      }
      $prepared = nor_contact_prepare_url_for_validation($token);
      $normalized_urls[] = esc_url_raw($prepared);
    }
    $normalized_urls = array_values(array_filter($normalized_urls, static function ($url): bool {
      return is_string($url) && trim($url) !== '';
    }));
    if (empty($normalized_urls)) {
      nor_contact_redirect_with_status('error');
    }

    $details_len = function_exists('mb_strlen')
      ? (int) mb_strlen($details, 'UTF-8')
      : strlen($details);
    if ($details_len > 400) {
      $details = function_exists('mb_substr')
        ? (string) mb_substr($details, 0, 400, 'UTF-8')
        : substr($details, 0, 400);
    }

    $to_lower = static function (string $value): string {
      return function_exists('mb_strtolower')
        ? (string) mb_strtolower($value, 'UTF-8')
        : strtolower($value);
    };

    $dedupe_material = implode('|', [
      strtolower($purpose),
      $to_lower(trim($name)),
      $to_lower(trim($organization)),
      strtolower(trim($email)),
      implode('|', $normalized_urls),
      trim($details),
    ]);
    $dedupe_key = 'nor_contact_dup_' . md5($dedupe_material);
    if ((string) get_transient($dedupe_key) === '1') {
      nor_contact_redirect_with_status('duplicate');
    }

    $purpose_ja = (string) $purpose_options[$purpose]['ja'];
    $purpose_en = (string) $purpose_options[$purpose]['en'];
    $title = sprintf('[%s] %s / %s', wp_date('Y-m-d H:i:s'), $purpose_ja, $name);

    $inserted = wp_insert_post([
      'post_type'   => 'nor_contact',
      'post_status' => 'private',
      'post_title'  => $title,
    ], true);

    if (is_wp_error($inserted) || (int) $inserted <= 0) {
      nor_contact_redirect_with_status('error');
    }
    $post_id = (int) $inserted;

    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) $_SERVER['HTTP_USER_AGENT']) : '';
    $ip_hash = ($ip !== '') ? hash('sha256', $ip . '|' . wp_salt('auth')) : '';

    update_post_meta($post_id, 'nor_contact_purpose', $purpose);
    update_post_meta($post_id, 'nor_contact_name', $name);
    update_post_meta($post_id, 'nor_contact_organization', $organization);
    update_post_meta($post_id, 'nor_contact_email', $email);
    update_post_meta($post_id, 'nor_contact_urls', implode("\n", $normalized_urls));
    update_post_meta($post_id, 'nor_contact_details', trim($details));
    update_post_meta($post_id, 'nor_contact_status', 'new');
    update_post_meta($post_id, 'nor_contact_submitted_at_gmt', gmdate('c'));
    update_post_meta($post_id, 'nor_contact_user_agent', $ua);
    update_post_meta($post_id, 'nor_contact_ip_hash', $ip_hash);

    $payload = [
      'purpose'      => $purpose,
      'purpose_ja'   => $purpose_ja,
      'purpose_en'   => $purpose_en,
      'name'         => $name,
      'organization' => $organization,
      'email'        => $email,
      'urls'         => $normalized_urls,
      'details'      => trim($details),
      'ip'           => $ip,
      'ua'           => $ua,
    ];

    nor_contact_send_admin_notification($post_id, $payload);
    nor_contact_send_auto_reply($payload);

    set_transient($dedupe_key, '1', 10 * MINUTE_IN_SECONDS);
    nor_contact_redirect_with_status('success');
  }
}

add_action('init', function () {
  if (wp_next_scheduled('nor_contact_cleanup_event')) return;
  wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'nor_contact_cleanup_event');
});

add_action('nor_contact_cleanup_event', function () {
  $months = (int) get_option('nor_contact_retention_months', 24);
  if ($months < 1) $months = 1;
  if ($months > 120) $months = 120;

  $cutoff_ts = strtotime('-' . $months . ' months');
  if ($cutoff_ts === false) return;
  $cutoff_date = gmdate('Y-m-d H:i:s', $cutoff_ts);

  do {
    $query = new WP_Query([
      'post_type'              => 'nor_contact',
      'post_status'            => ['private'],
      'posts_per_page'         => 100,
      'fields'                 => 'ids',
      'orderby'                => 'date',
      'order'                  => 'ASC',
      'no_found_rows'          => true,
      'ignore_sticky_posts'    => true,
      'date_query'             => [
        [
          'before'    => $cutoff_date,
          'inclusive' => true,
        ],
      ],
    ]);

    if (empty($query->posts) || !is_array($query->posts)) {
      break;
    }

    foreach ($query->posts as $post_id) {
      wp_delete_post((int) $post_id, true);
    }
  } while (!empty($query->posts));
});

/**
 * ========================================
 * Writings (standard post) — Admin fields, validation, notices
 * ========================================
 * Mirrors the Works admin pattern (meta box shape, sanitize, save,
 * required/unique numbering, draft-force validation, transient notices),
 * independently implemented for post_type = 'post'. Works' own functions,
 * meta boxes, save handlers, and notices are not touched or shared.
 *
 * Meta keys:
 * - nor_writing_no                (required, unique)
 * - nor_tagline                   (tagline)
 * - nor_summary_en                (Summary EN / SEO description source, future use)
 * - nor_writing_body_summary_en   (English Summary block at the end of the article body)
 */

// Register Writings meta for the block editor (REST). Without this, meta box values
// (e.g. nor_writing_no) may not be included in publish/update requests, and hard
// validation may not see the submitted values.
add_action('init', function () {
  $writing_meta_args = [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'sanitize_text_field',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ];

  register_post_meta('post', 'nor_writing_no', $writing_meta_args);
  register_post_meta('post', 'nor_tagline', $writing_meta_args);

  register_post_meta('post', 'nor_summary_en', [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'wp_kses_post',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ]);

  register_post_meta('post', 'nor_writing_body_summary_en', [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'wp_kses_post',
    'auth_callback'     => function () { return current_user_can('edit_posts'); },
  ]);
});

/**
 * Writings: numbering helpers (mirrors nor_normalize_work_no / nor_find_work_id_by_work_no)
 */
function nor_normalize_writing_no($raw): string {
  $s = is_string($raw) ? trim($raw) : '';
  // keep digits only
  $s = preg_replace('/[^0-9]/', '', $s);
  if ($s === '') return '';
  // normalize to integer string (remove leading zeros)
  $n = (int) $s;
  if ($n <= 0) return '';
  return (string) $n;
}

function nor_find_writing_id_by_writing_no(string $writing_no, int $exclude_post_id = 0): int {
  $writing_no = nor_normalize_writing_no($writing_no);
  if ($writing_no === '') return 0;

  // NOTE:
  // Older posts may have stored values like "020" or non-normalized strings.
  // We therefore fetch candidate IDs by meta_key existence and compare after normalization in PHP.
  $q = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'meta_query'     => [[
      'key'     => 'nor_writing_no',
      'compare' => 'EXISTS',
    ]],
  ]);

  $ids = is_array($q->posts) ? $q->posts : [];
  wp_reset_postdata();

  foreach ($ids as $id) {
    $id = (int) $id;
    if ($id <= 0) continue;
    if ($exclude_post_id > 0 && $id === (int) $exclude_post_id) continue;

    $stored = get_post_meta($id, 'nor_writing_no', true);
    $stored = nor_normalize_writing_no($stored);

    if ($stored !== '' && $stored === $writing_no) {
      return $id;
    }
  }

  return 0;
}

/**
 * Writings: admin notice helpers (mirrors nor_append_work_admin_notice and friends)
 * Transient keys are namespaced separately from Works (nor_writing_admin_notice_*)
 * so the two systems never collide.
 */
function nor_append_writing_admin_notice(int $post_id, string $line): void {
  if ($post_id <= 0) return;
  $key = 'nor_writing_admin_notice_' . $post_id;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';

  $lines = [];
  if ($existing !== '') {
    $lines = preg_split("/\r\n|\r|\n/", $existing);
    $lines = array_filter(array_map('trim', (array) $lines));
  }

  $line = trim($line);
  if ($line === '') return;
  if (!in_array($line, $lines, true)) {
    $lines[] = $line;
  }

  if (empty($lines)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $lines), 5 * MINUTE_IN_SECONDS);
}

function nor_remove_writing_admin_notice_lines(int $post_id, callable $keep_line): void {
  if ($post_id <= 0) return;
  $key = 'nor_writing_admin_notice_' . $post_id;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';
  if ($existing === '') return;

  $lines = preg_split("/\r\n|\r|\n/", $existing);
  $lines = array_filter(array_map('trim', (array) $lines));

  $filtered = [];
  foreach ($lines as $l) {
    if ($l === '') continue;
    if ($keep_line($l)) $filtered[] = $l;
  }

  if (empty($filtered)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $filtered), 5 * MINUTE_IN_SECONDS);
}

function nor_clear_writing_no_admin_notices(int $post_id): void {
  nor_remove_writing_admin_notice_lines($post_id, function (string $line): bool {
    // Remove ONLY writing-no related notices so they don't persist when the number changes.
    return (strpos($line, '採番が') !== 0);
  });
}

function nor_append_writing_admin_notice_for_user(string $line): void {
  $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
  if ($uid <= 0) return;

  $key = 'nor_writing_admin_notice_user_' . $uid;

  $existing = get_transient($key);
  $existing = is_string($existing) ? trim($existing) : '';

  $lines = [];
  if ($existing !== '') {
    $lines = preg_split("/\r\n|\r|\n/", $existing);
    $lines = array_filter(array_map('trim', (array) $lines));
  }

  $line = trim($line);
  if ($line === '') return;
  if (!in_array($line, $lines, true)) {
    $lines[] = $line;
  }

  if (empty($lines)) {
    delete_transient($key);
    return;
  }

  set_transient($key, implode("\n", $lines), 5 * MINUTE_IN_SECONDS);
}

function nor_get_submitted_writing_no(array $postarr): string {
  // Classic editor / meta box submit
  if (isset($_POST['nor_writing_no'])) {
    return nor_normalize_writing_no(wp_unslash($_POST['nor_writing_no']));
  }

  // Block editor (REST) may pass meta via meta_input
  if (isset($postarr['meta_input']) && is_array($postarr['meta_input']) && array_key_exists('nor_writing_no', $postarr['meta_input'])) {
    return nor_normalize_writing_no($postarr['meta_input']['nor_writing_no']);
  }

  // Some paths may use `meta`.
  if (isset($postarr['meta']) && is_array($postarr['meta']) && array_key_exists('nor_writing_no', $postarr['meta'])) {
    return nor_normalize_writing_no($postarr['meta']['nor_writing_no']);
  }

  return '';
}

add_action('add_meta_boxes_post', function () {
  // 0) 採番（必須・ユニーク）
  add_meta_box(
    'nor_writing_no',
    '採番（必須）',
    function (WP_Post $post) {
      // Shared nonce for all Writings meta boxes
      wp_nonce_field('nor_writing_meta_save', 'nor_writing_meta_nonce');

      $writing_no = get_post_meta($post->ID, 'nor_writing_no', true);
      $writing_no = is_string($writing_no) ? trim($writing_no) : '';

      echo '<input name="nor_writing_no" id="nor_writing_no" type="text" inputmode="numeric" pattern="[0-9]*" class="regular-text" value="' . esc_attr($writing_no) . '" />';
      echo '<p class="description" style="margin-top:6px;">カードの # 表示に使う連番です（数字のみ）。未入力では公開できません。既存と重複すると警告します。</p>';
    },
    'post',
    'normal',
    'high'
  );

  // 1) タグライン
  add_meta_box(
    'nor_writing_tagline',
    'タグライン',
    function (WP_Post $post) {
      $tagline = get_post_meta($post->ID, 'nor_tagline', true);
      $tagline = is_string($tagline) ? $tagline : '';

      // Shared nonce for all Writings meta boxes
      wp_nonce_field('nor_writing_meta_save', 'nor_writing_meta_nonce');

      echo '<input name="nor_tagline" id="nor_tagline" type="text" class="regular-text" value="' . esc_attr($tagline) . '" />';
      echo '<p class="description" style="margin-top:6px;">Writings個別のタグラインを入力します。タイトル下に表示されます。</p>';
    },
    'post',
    'normal',
    'default'
  );

  // 2) 説明（EN）
  add_meta_box(
    'nor_writing_summary_en',
    '説明（EN）',
    function (WP_Post $post) {
      $summary_en = get_post_meta($post->ID, 'nor_summary_en', true);
      $summary_en = is_string($summary_en) ? $summary_en : '';

      // Shared nonce for all Writings meta boxes
      wp_nonce_field('nor_writing_meta_save', 'nor_writing_meta_nonce');

      echo '<textarea name="nor_summary_en" id="nor_summary_en" rows="4" style="width:100%">' . esc_textarea($summary_en) . '</textarea>';
      echo '<p class="description" style="margin-top:6px;">説明（JA）は標準の抜粋を使用し、説明（EN）はカスタムフィールドで保存しています。</p>';
    },
    'post',
    'normal',
    'default'
  );

  // 3) 本文英語要約
  add_meta_box(
    'nor_writing_body_summary_en',
    '本文英語要約',
    function (WP_Post $post) {
      $body_summary_en = get_post_meta($post->ID, 'nor_writing_body_summary_en', true);
      $body_summary_en = is_string($body_summary_en) ? $body_summary_en : '';

      // Shared nonce for all Writings meta boxes
      wp_nonce_field('nor_writing_meta_save', 'nor_writing_meta_nonce');

      echo '<textarea name="nor_writing_body_summary_en" id="nor_writing_body_summary_en" rows="8" style="width:100%">' . esc_textarea($body_summary_en) . '</textarea>';
      echo '<p class="description" style="margin-top:6px;">Writings詳細ページの本文末尾に表示する English Summary（複数段落可）です。SEO description用の説明（EN）とは別物です。</p>';
    },
    'post',
    'normal',
    'default'
  );
});

add_action('save_post_post', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;
  if (!current_user_can('edit_post', $post_id)) return;

  if (!isset($_POST['nor_writing_meta_nonce']) || !wp_verify_nonce($_POST['nor_writing_meta_nonce'], 'nor_writing_meta_save')) return;

  // If wp_insert_post_data stored user-scoped notices (new post has no ID yet),
  // migrate them to this post-scoped notice so they appear on the edit screen.
  $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
  if ($uid > 0) {
    $user_key = 'nor_writing_admin_notice_user_' . $uid;
    $user_msg = get_transient($user_key);
    if (is_string($user_msg) && trim($user_msg) !== '') {
      $lines = preg_split("/\r\n|\r|\n/", (string) $user_msg);
      $lines = array_filter(array_map('trim', (array) $lines));
      foreach ($lines as $l) {
        nor_append_writing_admin_notice((int) $post_id, (string) $l);
      }
      delete_transient($user_key);
    }
  }

  // Writing no (required, unique) - store as normalized integer string
  if (isset($_POST['nor_writing_no'])) {
    $raw = wp_unslash($_POST['nor_writing_no']);
    $val = nor_normalize_writing_no($raw);

    if ($val === '') {
      delete_post_meta($post_id, 'nor_writing_no');
    } else {
      update_post_meta($post_id, 'nor_writing_no', $val);
    }
  }

  // Tagline
  if (isset($_POST['nor_tagline'])) {
    $raw = wp_unslash($_POST['nor_tagline']);
    $val = sanitize_text_field($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_tagline');
    } else {
      update_post_meta($post_id, 'nor_tagline', $val);
    }
  }

  // Summary (EN)
  if (isset($_POST['nor_summary_en'])) {
    $raw = wp_unslash($_POST['nor_summary_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_summary_en');
    } else {
      update_post_meta($post_id, 'nor_summary_en', $val);
    }
  }

  // Body summary (EN)
  if (isset($_POST['nor_writing_body_summary_en'])) {
    $raw = wp_unslash($_POST['nor_writing_body_summary_en']);
    $val = wp_kses_post($raw);
    $val = trim($val);
    if ($val === '') {
      delete_post_meta($post_id, 'nor_writing_body_summary_en');
    } else {
      update_post_meta($post_id, 'nor_writing_body_summary_en', $val);
    }
  }
});

/**
 * Admin: Writings (post) hard validation (required / unique fields)
 * - If required fields are missing or duplicate, force status to draft and show an error notice.
 *
 * Rules (hard-stop):
 * - Writing No (nor_writing_no) is required and must be unique
 *
 * NOTE: Unlike Works, Title is intentionally NOT hard-required here (out of this round's scope).
 */
add_filter('wp_insert_post_data', function ($data, $postarr) {
  // Run for admin saves including block editor (REST). Skip only ajax-like contexts.
  if (!is_admin() || wp_doing_ajax()) return $data;
  if (!isset($data['post_type']) || $data['post_type'] !== 'post') return $data;

  // Allow trash/delete actions to proceed without validation.
  $next_status = isset($data['post_status']) ? (string) $data['post_status'] : '';
  $req_action  = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
  $req_action2 = isset($_REQUEST['action2']) ? (string) $_REQUEST['action2'] : '';

  if (
    $next_status === 'trash' ||
    in_array($req_action, ['trash', 'delete', 'bulk-trash', 'bulk-delete', 'untrash'], true) ||
    in_array($req_action2, ['trash', 'delete', 'bulk-trash', 'bulk-delete', 'untrash'], true)
  ) {
    return $data;
  }

  /**
   * Skip validation for auto-draft.
   * Opening "Add New" creates an auto-draft internally.
   * We must not warn/block on that initial auto-draft creation.
   */
  if ($next_status === 'auto-draft') {
    return $data;
  }

  $pid = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;

  // Clear stale writing-no notices before re-validating (otherwise old duplicate messages remain).
  if ($pid > 0) {
    nor_clear_writing_no_admin_notices($pid);
  }

  // Collect hard-stop errors so we can show multiple notices at once.
  $hard_errors = [];

  // Writing No: required + unique (supports classic + block editor)
  $writing_no = nor_get_submitted_writing_no((array) $postarr);

  if ($writing_no === '') {
    // If the field wasn't in the submission (some editor flows), fall back to stored meta.
    if ($pid > 0) {
      $stored = get_post_meta($pid, 'nor_writing_no', true);
      $stored = nor_normalize_writing_no($stored);
      if ($stored !== '') {
        $writing_no = $stored;
      }
    }
  }

  if ($writing_no === '') {
    // Make the reason explicit: we saved as draft because writing-no is required.
    $hard_errors[] = '採番が未入力のため、公開せず下書きとして保存しました。';
  } else {
    $dup_id = nor_find_writing_id_by_writing_no($writing_no, $pid);
    if ($dup_id > 0) {
      $hard_errors[] = '採番が「記事ID: ' . $dup_id . '」と重複しているため、公開せず下書きとして保存しました。';
    }
  }

  // If any hard-stop errors exist, force draft and attach all notices.
  if (!empty($hard_errors)) {
    $data['post_status'] = 'draft';

    foreach ($hard_errors as $msg) {
      if ($pid > 0) {
        nor_append_writing_admin_notice($pid, (string) $msg);
      } else {
        nor_append_writing_admin_notice_for_user((string) $msg);
      }
    }

    return $data;
  }

  return $data;
}, 10, 2);

add_action('admin_notices', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen) return;
  if (($screen->post_type ?? '') !== 'post') return;
  if (!in_array((string) ($screen->base ?? ''), ['post', 'post-new'], true)) return;

  $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
  if ($post_id <= 0) {
    // New post screen: show user-scoped notices (no post ID yet)
    $uid = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
    if ($uid <= 0) return;

    $user_key = 'nor_writing_admin_notice_user_' . $uid;
    $msg = get_transient($user_key);
    if (!is_string($msg) || trim($msg) === '') return;

    $lines = preg_split("/\r\n|\r|\n/", (string) $msg);
    $lines = array_filter(array_map('trim', (array) $lines));

    echo '<div class="notice notice-warning is-dismissible">';
    if (count($lines) <= 1) {
      echo '<p>' . esc_html($msg) . '</p>';
    } else {
      echo '<ul style="margin:0.5em 0 0.5em 1.2em; list-style:disc;">';
      foreach ($lines as $l) {
        echo '<li>' . esc_html($l) . '</li>';
      }
      echo '</ul>';
    }
    echo '</div>';
    // One-shot notice
    delete_transient($user_key);
    return;
  }

  $msg = get_transient('nor_writing_admin_notice_' . $post_id);
  if (!is_string($msg) || trim($msg) === '') return;

  $lines = preg_split("/\r\n|\r|\n/", (string) $msg);
  $lines = array_filter(array_map('trim', (array) $lines));

  echo '<div class="notice notice-warning is-dismissible">';
  if (count($lines) <= 1) {
    echo '<p>' . esc_html($msg) . '</p>';
  } else {
    echo '<ul style="margin:0.5em 0 0.5em 1.2em; list-style:disc;">';
    foreach ($lines as $l) {
      echo '<li>' . esc_html($l) . '</li>';
    }
    echo '</ul>';
  }
  echo '</div>';
  // One-shot notices (prevents stale/accumulating messages across saves)
  delete_transient('nor_writing_admin_notice_' . $post_id);
});

/**
 * ========================================
 * Writings (post) & Pages (page) — Classic Editor (Block Editor disabled)
 * ========================================
 * Works never declares 'editor' support in register_post_type('works', ...)
 * above, so use_block_editor_for_post_type() already returns false for it
 * automatically (WordPress core requires 'editor' support before it will ever
 * use the block editor) — Works simply has no body-content editor at all,
 * classic or block, which is why its edit screen is already "classic".
 *
 * Writings and Pages both need a real body editor (post_content), so that same
 * "just omit editor support" trick would remove the editor entirely, which is
 * not what we want here. Instead we use the officially documented
 * use_block_editor_for_post_type filter to keep the classic TinyMCE editor
 * while turning off the block editor, for 'post' and 'page' only. nør. runs a
 * CMS-style admin (theme-driven page structure, meta boxes for tagline/copy/
 * SEO-LLMO/etc.), so every editable post type is now the same classic, vertical
 * meta-box UI.
 *
 * show_in_rest and register_post_meta are left untouched: the REST API stays
 * available for both post types, only the block-editor UI is turned off.
 */
add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
  if (in_array($post_type, ['post', 'page'], true)) return false;
  return $use_block_editor;
}, 10, 2);

/**
 * Writings (post) — restrict the Classic Editor's heading dropdown to H4-H6.
 *
 * single-post.php's own template already owns H1 (Hero title), H2 (visually-
 * hidden section heading) and H3 (visually-hidden "Writing entry"), so post
 * body content must never introduce another H1-H3. tiny_mce_before_init has
 * no $post_type argument (unlike use_block_editor_for_post_type above), so the
 * edit-screen post type is read via get_current_screen() instead. This only
 * narrows the Visual tab's quick-format menu — the Text tab and saved content
 * are unaffected, and 'page' / 'works' are untouched.
 */
add_filter('tiny_mce_before_init', function ($settings) {
  if (!is_admin() || !function_exists('get_current_screen')) {
    return $settings;
  }

  $screen = get_current_screen();

  if (
    !$screen
    || $screen->base !== 'post'
    || $screen->post_type !== 'post'
  ) {
    return $settings;
  }

  $settings['block_formats'] =
    'Paragraph=p;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre';

  return $settings;
});
