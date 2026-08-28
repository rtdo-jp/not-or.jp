<?php
/**
 * Template part: Pages common hero
 *
 * Args:
 * - count (int)
 * - unit (string)
 * - title_html (string) : pre-escaped HTML for the title
 * - title (string)      : plain text title (fallback)
 * - h1_attrs (array) : optional attributes for the H1 tag (e.g. ['data-tighten-slot' => '3.2'])
 * - tagline (string)
 * - tagline_fallback (string)
 * - desc_ja (string)
 * - desc_en (string)
 * - desc_fallback (string) : fallback text for empty desc_ja/desc_en (default `—`)
 * - desc_ja_html (string) : pre-escaped HTML for JA description (optional)
 * - desc_en_html (string) : pre-escaped HTML for EN description (optional)
 * - specific_tag ('div'|'hgroup')
 * - widget (string) : 'none' | 'tabs' | 'search'
 * - nav_list (array) : [{label,url,current(bool)}] (used when widget='tabs')
 * - nav_list_aria (string) (used when widget='tabs')
 * - search_form (array) : optional settings to render search widget (used when widget='search')
 *   - state_html (string) : pre-escaped HTML rendered after the form (optional)
 * - updated_label_html (string) : pre-escaped HTML for the “Last updated” line (optional)
 * - updated_date (string) : date string for the updated time (optional, e.g. `2025-12-01`)
 * - updated_datetime (string) : datetime string for the updated time (optional, e.g. ISO 8601)
 * - updated_prefix (string) : prefix label before the time (default `Last updated:`)
 */

$args = isset($args) && is_array($args) ? $args : [];
$args = wp_parse_args($args, [
  'count' => 0,
  'unit'  => '',
  'title_html' => '',
  'title' => '',
  'h1_attrs' => [],
  'tagline' => '',
  'tagline_fallback' => '—',
  'desc_ja' => '',
  'desc_en' => '',
  'desc_fallback' => '—',
  'desc_ja_html' => '',
  'desc_en_html' => '',
  'specific_tag' => 'div',
  // Tabs (Clients pages)
  'widget'        => 'tabs',
  'nav_list_aria' => 'Client views',
  'nav_list'      => [
    [
      'label'   => 'Index of Terms',
      'url'     => home_url('/clients/iot/'),
      'current' => false,
    ],
    [
      'label'   => 'Industries',
      'url'     => home_url('/clients/industries/'),
      'current' => true,
    ],
  ],
  'widget' => 'none',
  'nav_list' => [],
  'nav_list_aria' => '',
  'search_form' => null,
  'breadcrumbs' => [],
  'updated_label_html' => '',
  'updated_date' => '',
  'updated_datetime' => '',
  'updated_prefix' => 'Last updated:',
]);

$count = (int) $args['count'];
$unit  = is_string($args['unit']) ? $args['unit'] : '';

$title_html = is_string($args['title_html']) ? $args['title_html'] : '';
$title_plain = is_string($args['title']) ? trim($args['title']) : '';

$tagline = is_string($args['tagline']) ? trim($args['tagline']) : '';
$tagline_fallback = is_string($args['tagline_fallback']) ? $args['tagline_fallback'] : '—';

// Optional: wrap plain-title text with an inline tag (e.g. for Work titles).
// NOTE: Prefer `title_html` when you need markup such as <cite>.
$title_wrap = isset($args['title_wrap']) && is_string($args['title_wrap']) ? strtolower(trim($args['title_wrap'])) : '';
if (!in_array($title_wrap, ['', 'cite'], true)) $title_wrap = '';

// Optional: attributes for the H1 tag (e.g. data-tighten-slot).
// Keys/values are escaped.
$h1_attrs = is_array($args['h1_attrs'] ?? null) ? $args['h1_attrs'] : [];
$h1_attr_html = '';
if (!empty($h1_attrs)) {
  foreach ($h1_attrs as $k => $v) {
    if (!is_string($k) || $k === '') continue;
    if (is_bool($v)) {
      if ($v) {
        $h1_attr_html .= ' ' . esc_attr($k);
      }
      continue;
    }
    if (!is_scalar($v)) continue;
    $h1_attr_html .= ' ' . esc_attr($k) . '="' . esc_attr((string) $v) . '"';
  }
}

$desc_ja = is_string($args['desc_ja']) ? trim($args['desc_ja']) : '';
$desc_en = is_string($args['desc_en']) ? trim($args['desc_en']) : '';
$desc_fallback = isset($args['desc_fallback']) && is_string($args['desc_fallback']) ? $args['desc_fallback'] : '—';

$desc_ja_html = is_string($args['desc_ja_html']) ? trim($args['desc_ja_html']) : '';
$desc_en_html = is_string($args['desc_en_html']) ? trim($args['desc_en_html']) : '';

// Normalize wrapper tag for the title/tagline block.
// Accept only 'div' or 'hgroup' (default: 'div').
$specific_tag_raw = is_string($args['specific_tag']) ? strtolower(trim($args['specific_tag'])) : '';
$specific_tag = in_array($specific_tag_raw, ['div', 'hgroup'], true) ? $specific_tag_raw : 'div';

$widget = is_string($args['widget']) ? strtolower(trim($args['widget'])) : 'none';
if (!in_array($widget, ['none', 'tabs', 'search'], true)) $widget = 'none';

// Tabs nav (widget='tabs')
// Primary keys: nav_list / nav_list_aria
// Back-compat aliases: tabs / tabs_aria (used by some templates)
$nav_list = is_array($args['nav_list'] ?? null) ? $args['nav_list'] : [];
if (empty($nav_list) && is_array($args['tabs'] ?? null)) {
  $nav_list = $args['tabs'];
}

$nav_list_aria = is_string($args['nav_list_aria'] ?? null) ? trim($args['nav_list_aria']) : '';
if ($nav_list_aria === '' && is_string($args['tabs_aria'] ?? null)) {
  $nav_list_aria = trim((string) $args['tabs_aria']);
}

$breadcrumbs = is_array($args['breadcrumbs']) ? $args['breadcrumbs'] : [];

$updated_label_html = is_string($args['updated_label_html']) ? trim($args['updated_label_html']) : '';
$updated_date = is_string($args['updated_date']) ? trim($args['updated_date']) : '';
$updated_datetime = is_string($args['updated_datetime']) ? trim($args['updated_datetime']) : '';
$updated_prefix = is_string($args['updated_prefix']) ? trim($args['updated_prefix']) : 'Last updated:';
if ($updated_datetime === '' && $updated_date !== '') {
  // If only a date is provided, use it as the datetime.
  $updated_datetime = $updated_date;
}


// Helpers
$render_inline = static function (string $s, string $fallback = ''): string {
  return nor_render_inline_html($s, $fallback);
};

// `*_html` args are treated as pre-escaped HTML (caller responsibility).
$render_html = function (string $html): string {
  $html = trim($html);
  if ($html === '') return '';
  return $html;
};
// Pre-resolve title markup to avoid whitespace-only text nodes around <h1>.
$title_resolved_html = '';
if ($title_html !== '') {
  $title_resolved_html = $title_html; // pre-escaped HTML
} else {
  if ($title_plain !== '' && $title_wrap === 'cite') {
    $title_resolved_html = '<cite>' . esc_html($title_plain) . '</cite>';
  } else {
    $title_resolved_html = esc_html($title_plain);
  }
}
?>
<section class="hero">
  <div class="inner">
    <div class="identity">
      <div class="name">
        <p class="ja" lang="ja">田村綾佑デザイン事務所</p>
        <p class="en" lang="en">Ryousuke Tamura <br>Design Office</p>
        <p class="author">nør.</p>
      </div>
      <div class="stats">
        <p class="pair">
          <data class="count" value="<?php echo esc_attr($count); ?>"><?php echo esc_html($count); ?></data>
          <span class="unit"><?php echo esc_html($unit); ?></span>
        </p>
        <p class="permission">Visuals appear only <br>with client permission. <br>Otherwise, entries remain <br>as <strong>text — never lost</strong>.</p>
      </div>
    </div>

    <div class="localize">
      <<?php echo $specific_tag; ?> class="specific">
        <h1<?php echo $h1_attr_html; ?>><?php echo $title_resolved_html; ?></h1>
        <p class="tagline"><?php echo $render_inline($tagline, $tagline_fallback); ?></p>
      </<?php echo $specific_tag; ?>>
      <div class="textpair">
        <p class="ja" lang="ja"><?php echo ($desc_ja_html !== '') ? $render_html($desc_ja_html) : $render_inline($desc_ja, $desc_fallback); ?></p>
        <p class="en" lang="en"><?php echo ($desc_en_html !== '') ? $render_html($desc_en_html) : $render_inline($desc_en, $desc_fallback); ?></p>
      </div>
<?php if ($widget === 'tabs' && !empty($nav_list)) : ?>
<?php
  echo '      <ul class="nav-list"' . (($nav_list_aria !== '') ? ' aria-label="' . esc_attr($nav_list_aria) . '"' : '') . ">\n";
  foreach ($nav_list as $item) {
    $label = isset($item['label']) && is_string($item['label']) ? $item['label'] : '';
    $url   = isset($item['url']) && is_string($item['url']) ? $item['url'] : '';
    $is_current = !empty($item['current']);
    echo '        <li><a href="' . esc_url($url) . '"' . ($is_current ? ' aria-current="page"' : '') . '>' . esc_html($label) . "</a></li>\n";
  }
  echo "      </ul>\n";
?>
<?php endif; ?>
<?php
  if ($widget === 'search' && is_array($args['search_form'])) :
    $sf = wp_parse_args($args['search_form'], [
      'action' => home_url('/search/'),
      'query_param' => 'q',
      'input_id' => 'keyword',
      'placeholder' => 'キーワード、社名、タグ… / keyword, client, tag…',
      'state_html' => '',
      'state_indent' => 8,
    ]);
    $qp = (string) $sf['query_param'];
    $qv = '';
    if (isset($_GET[$qp]) && is_string($_GET[$qp])) {
      $qv = (string) $_GET[$qp];
    }
?>
      <div class="search-area">
        <form action="<?php echo esc_url($sf['action']); ?>" method="get" role="search">
          <div class="field">
            <label class="visually-hidden" for="<?php echo esc_attr($sf['input_id']); ?>">Search keyword</label>
            <div class="control">
              <input type="search" id="<?php echo esc_attr($sf['input_id']); ?>" name="<?php echo esc_attr($sf['query_param']); ?>" value="<?php echo esc_attr($qv); ?>" placeholder="<?php echo esc_attr($sf['placeholder']); ?>">
              <button class="btn btn-clear" type="button" aria-label="Clear"><span class="btn-glyph" aria-hidden="true">✕</span></button>
              <button class="btn btn-search" type="submit" aria-label="Search"><span class="btn-glyph" aria-hidden="true">🔍</span></button>
            </div>
          </div>
        </form>
<?php
        $state_html = isset($sf['state_html']) && is_string($sf['state_html']) ? (string) $sf['state_html'] : '';
        $state_html = ltrim($state_html, "\r\n");
        $state_html = rtrim($state_html, "\r\n");
        if ($state_html !== '') {
          // Normalize embedded HTML indentation before aligning to the search-area block.
          // Use the first non-empty line as the baseline so the top line always aligns.
          $lines = preg_split('/\R/u', $state_html);
          $first_indent = null;
          foreach ($lines as $line) {
            if (trim($line) === '') continue;
            if (preg_match('/^[ \t]+/', $line, $m)) {
              $first_indent = strlen($m[0]);
            } else {
              $first_indent = 0;
            }
            break;
          }
          if ($first_indent !== null && $first_indent > 0) {
            $state_html = (string) preg_replace('/^[ \t]{0,' . (int) $first_indent . '}/m', '', $state_html);
          }
          $state_indent = isset($sf['state_indent']) ? max(0, (int) $sf['state_indent']) : 8;
          $state_prefix = str_repeat(' ', $state_indent);
          echo (string) preg_replace('/^(?=.*\S)/m', $state_prefix, $state_html) . "\n"; // pre-escaped HTML
        }
?>
      </div>
<?php endif; ?>
<?php if ($updated_label_html !== '') : ?>
      <?php echo $updated_label_html; // pre-escaped HTML ?>
<?php elseif ($updated_date !== '' || $updated_datetime !== '') : ?>
      <p class="updated-label"><?php echo esc_html($updated_prefix !== '' ? $updated_prefix : 'Last updated:'); ?> <time datetime="<?php echo esc_attr($updated_datetime !== '' ? $updated_datetime : $updated_date); ?>" class="value"><?php echo esc_html($updated_date !== '' ? $updated_date : $updated_datetime); ?></time></p>
<?php endif; ?>
    </div>
  </div>
<?php
  // Breadcrumbs
  // Delegate rendering to a dedicated partial for reuse.
  ob_start();
  get_template_part('template-parts/hero/breadcrumbs', null, [
    'breadcrumbs' => $breadcrumbs,
    'aria_label'  => 'Breadcrumb',
  ]);
  $hero_breadcrumbs_html = trim((string) ob_get_clean());
  if ($hero_breadcrumbs_html !== '') {
    echo "\n" . (string) preg_replace('/^(?=.*\S)/m', '  ', $hero_breadcrumbs_html) . "\n";
  }
?>
</section>
