<?php
/**
 * Pagination (List)
 *
 * Usage:
 *   get_template_part('template-parts/pagination/pagination-list', null, [
 *     'current'        => 1,
 *     'total'          => 10,
 *     'first_url'      => home_url('/'),
 *     'prev_url'       => '',
 *     'next_url'       => home_url('/works/page/2/'),
 *     'last_url'       => home_url('/works/page/10/'),
 *     'page_urls'      => [ 1 => home_url('/'), 2 => home_url('/works/page/2/') ],
 *     'aria_label'     => 'Works pagination',
 *     'next_rel'       => 'next',
 *     'prev_rel'       => 'prev',
 *     // Optional:
 *     // - Home (front-page) should keep First/Prev disabled
 *     // - Pages (archives) should show First/Prev when URLs exist
 *     'show_first_prev' => true,
 *     'show_next_last'  => true,
 *   ]);
 */

// $args is provided by get_template_part(..., ..., $args)
$args = isset($args) && is_array($args) ? $args : [];

// NOTE:
// This partial must behave like the original front-page.php pager.
// If caller passes no args, we compute the Home pager values here.

$computed = false;
if (empty($args)) {
  $works_count = nor_get_published_works_count();

  // Pagination policy:
  // - Page 1 is Home (/): 6 latest works
  // - Page 2+ are /works/page/{n}/ : 12 works per page
  $home_url  = home_url('/');
  $works_url = get_post_type_archive_link('works');

  $home_per_page    = nor_get_works_home_count();
  $archive_per_page = nor_get_works_archive_per_page();

  $total = max(0, (int) $works_count);
  $remaining = max(0, $total - $home_per_page);
  $archive_pages = ($archive_per_page > 0) ? (int) ceil($remaining / $archive_per_page) : 0;
  $total_pages = 1 + max(0, $archive_pages);
  if ($total_pages < 1) $total_pages = 1;

  // Page URLs
  $p2 = trailingslashit($works_url) . 'page/2/';
  $last = ($total_pages <= 1)
    ? $home_url
    : trailingslashit($works_url) . 'page/' . $total_pages . '/';

  $p_last2 = ($total_pages >= 3) ? trailingslashit($works_url) . 'page/' . ($total_pages - 2) . '/' : '';
  $p_last1 = ($total_pages >= 2) ? trailingslashit($works_url) . 'page/' . ($total_pages - 1) . '/' : '';

  // Build page_urls map (only what the UI renders)
  $page_urls = [];
  for ($i = 1; $i <= $total_pages; $i++) {
    if ($i === 1) {
      $page_urls[1] = $home_url;
      continue;
    }
    $page_urls[$i] = trailingslashit($works_url) . 'page/' . $i . '/';
  }

  $args = [
    'current'    => 1,
    'total'      => $total_pages,
    'first_url'  => $home_url,
    'prev_url'   => '',
    'next_url'   => ($total_pages >= 2) ? $p2 : '',
    'last_url'   => ($total_pages >= 2) ? $last : '',
    'page_urls'  => $page_urls,
    'aria_label' => 'Works pagination',
    'next_rel'   => 'next',
    'prev_rel'   => 'prev',
    // Home mock behavior: First/Prev are always disabled
    'show_first_prev' => false,
    // Next/Last visibility (keep true by default)
    'show_next_last'  => true,
  ];
  $computed = true;
}

$defaults = [
  'current'    => 1,
  'total'      => 1,
  'first_url'  => '',
  'prev_url'   => '',
  'next_url'   => '',
  'last_url'   => '',
  // map: page_number(int) => url(string)
  'page_urls'  => [],
  'aria_label' => 'Works pagination',
  // rel attr for next/prev (set empty string to omit)
  'next_rel'   => 'next',
  'prev_rel'   => 'prev',
  // If omitted, Home (computed) disables First/Prev; Pages (caller-provided) enables them.
  'show_first_prev' => null,
  // Next/Last visibility (keep true by default)
  'show_next_last'  => true,
];

$a = wp_parse_args($args, $defaults);

$current = (int) $a['current'];
$total   = (int) $a['total'];
if ($current < 1) $current = 1;
if ($total < 1) $total = 1;
if ($current > $total) $current = $total;

$page_urls = is_array($a['page_urls']) ? $a['page_urls'] : [];

$first_url = is_string($a['first_url']) ? $a['first_url'] : '';
$prev_url  = is_string($a['prev_url'])  ? $a['prev_url']  : '';
$next_url  = is_string($a['next_url'])  ? $a['next_url']  : '';
$last_url  = is_string($a['last_url'])  ? $a['last_url']  : '';

$aria_label = is_string($a['aria_label']) ? $a['aria_label'] : 'Pagination';
// rel attr for next/prev (set empty string to omit)
$next_rel   = is_string($a['next_rel']) ? $a['next_rel'] : '';
$prev_rel   = is_string($a['prev_rel']) ? $a['prev_rel'] : '';

// If caller did not specify, default differs by context:
// - Home (computed args): First/Prev disabled
// - Pages (caller-provided args): First/Prev enabled
$show_first_prev = array_key_exists('show_first_prev', $a)
  ? (bool) $a['show_first_prev']
  : (!$computed);

$show_next_last  = array_key_exists('show_next_last', $a)
  ? (bool) $a['show_next_last']
  : true;

// If only 1 page, we still render (matches original front-page.php behavior: shows disabled controls).

// Helper: build a fixed set of page numbers that matches the Home mock logic.
// - Always show: 1, 2, 3
// - If total >= 7, show ellipsis
// - Always show: (total-2), (total-1), total
// - For small totals, show all available.
$pages = [];

if ($total <= 4) {
  for ($i = 1; $i <= $total; $i++) $pages[] = $i;
} else {
  $pages = [1, 2, 3];

  if ($total >= 7) {
    $pages[] = '…';
  }

  // Add tail pages without duplicates
  $tail = [];
  $tail[] = $total - 2;
  $tail[] = $total - 1;
  $tail[] = $total;

  foreach ($tail as $p) {
    if ($p >= 1 && $p <= $total && !in_array($p, $pages, true)) {
      $pages[] = $p;
    }
  }
}

?>
      <nav class="pagination" aria-label="<?php echo esc_attr($aria_label); ?>">
        <p class="pager-status"><?php echo esc_html($current); ?><span>/</span><?php echo esc_html($total); ?></p>
        <ul class="pager-prev">
          <li><?php if ($show_first_prev && !empty($first_url) && $current > 1) : ?><a href="<?php echo esc_url($first_url); ?>" class="btn">First</a><?php else : ?><button class="btn" type="button" disabled>First</button><?php endif; ?></li>
          <li><?php if ($show_first_prev && !empty($prev_url) && $current > 1) : ?><a href="<?php echo esc_url($prev_url); ?>" class="btn"<?php echo $prev_rel ? ' rel="' . esc_attr($prev_rel) . '"' : ''; ?>>Prev</a><?php else : ?><button class="btn" type="button" disabled>Prev</button><?php endif; ?></li>
        </ul>
        <ul class="pager-pages">
<?php foreach ($pages as $p) : ?>
<?php
  if ($p === '…') {
    echo "          <li class=\"ellipsis\" aria-hidden=\"true\">…</li>\n";
    continue;
  }
  $p = (int) $p;
  if ($p < 1 || $p > $total) continue;
  $u = isset($page_urls[$p]) ? (string) $page_urls[$p] : '';
  if ($u === '' && $p === 1 && !empty($first_url)) {
    $u = (string) $first_url;
  }
  if ($u === '') continue;
?>
          <li><?php if ($p === $current) : ?><a href="<?php echo esc_url($u); ?>" class="btn" aria-current="page"><?php echo esc_html($p); ?></a><?php else : ?><a href="<?php echo esc_url($u); ?>" class="btn"><?php echo esc_html($p); ?></a><?php endif; ?></li>
<?php endforeach; ?>
        </ul>
        <ul class="pager-next">
          <li><?php if ($show_next_last && !empty($next_url) && $current < $total) : ?><a href="<?php echo esc_url($next_url); ?>" class="btn"<?php echo $next_rel ? ' rel="' . esc_attr($next_rel) . '"' : ''; ?>>Next</a><?php else : ?><button class="btn" type="button" disabled>Next</button><?php endif; ?></li>
          <li><?php if ($show_next_last && !empty($last_url) && $current < $total) : ?><a href="<?php echo esc_url($last_url); ?>" class="btn">Last</a><?php else : ?><button class="btn" type="button" disabled>Last</button><?php endif; ?></li>
        </ul>
      </nav>
