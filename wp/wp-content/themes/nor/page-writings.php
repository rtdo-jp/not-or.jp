<?php
// ===== Writings fixed page (this template's own queried object) =====
// Explicit variable, kept separate from the Writings post Loop below so the
// two are never confused.
$writings_page_id = (int) get_queried_object_id();

// ===== Writings (post) query =====
// page-writings.php is a fixed-page template, so no post Loop is provided
// automatically — build a dedicated WP_Query for post_type=post.
// Current page number: /writings/page/{n}/ is resolved by a dedicated
// top-priority rewrite rule straight to `paged` (see functions.php) rather
// than WordPress's default page `page` query var (the <!--nextpage-->
// content-splitting mechanism), so `paged` alone is authoritative here.
$paged = max(1, (int) get_query_var('paged'));

$posts_per_page = (int) get_option('posts_per_page');
if ($posts_per_page < 1) $posts_per_page = 10;

$writings_query = new WP_Query([
  'post_type'      => 'post',
  'post_status'    => 'publish',
  'posts_per_page' => $posts_per_page,
  'paged'          => $paged,
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

// Out-of-range pagination (e.g. /writings/page/999/) is a 404 — distinct from
// a genuinely empty list (page 1 with zero published Writings), which still
// renders normally below via the empty-writings-list state. Checked, and the
// header not yet sent, before get_header() runs so this can safely render the
// theme's own 404 template instead (same pattern already used for the
// wp-sitemap-users-*.xml 404 handler in template_redirect).
if ($paged > 1 && $paged > (int) $writings_query->max_num_pages) {
  // Switch WordPress's own main-query state to 404 too, not just the HTTP
  // status: without this, the queried object stays the Writings page, so
  // is_page('writings') would still read true when header.php runs (via the
  // 404.php include below), leaking Writings SEO overrides / CollectionPage
  // schema onto what should be a plain 404.
  global $wp_query;
  $wp_query->set_404();

  status_header(404);
  nocache_headers();
  $tpl = locate_template('404.php');
  if ($tpl) {
    include $tpl;
    exit;
  }
  wp_die('404 Not Found', '404 Not Found', ['response' => 404]);
}

get_header();
?>

<main id="site-main" tabindex="-1">

<?php
  $page_meta = nor_get_page_meta_bundle($writings_page_id, 'Writings', '—');
  $writings_count = nor_get_published_writings_count();

  $has_writings = $writings_query->have_posts();

  if ($has_writings) {
    $hero_title = 'Writings Index';
    $hero_tagline = (string) $page_meta['tagline'];
    $hero_specific_tag = 'div';
    $breadcrumb_current = 'Writings Index';
    $section_h2_ja = (string) $page_meta['section_h2_ja'];
    $section_h2_en = (string) $page_meta['section_h2_en'];
  } else {
    $hero_title = 'Writings not found';
    $hero_tagline = 'No records match this view.';
    $hero_specific_tag = 'hgroup';
    $breadcrumb_current = 'Writings not found';
    $section_h2_ja = '記事一覧：該当なし';
    $section_h2_en = 'No writings found';
  }

  $hero_html = nor_render_template_part('template-parts/hero/hero-pages', null, [
    'title'        => $hero_title,
    'tagline'      => $hero_tagline,
    'specific_tag' => $hero_specific_tag,
    'desc_ja'      => (string) $page_meta['desc_ja'],
    'desc_en'      => (string) $page_meta['desc_en'],
    'count'        => $writings_count,
    'unit'         => nor_format_count_unit($writings_count, 'Writing', 'Writings', 'published'),
    'widget'       => 'none',
    'breadcrumbs'  => nor_build_breadcrumbs([
      ['label' => 'Writings', 'url' => home_url('/writings/')],
      ['label' => $breadcrumb_current, 'current' => true],
    ]),
  ], [
    'trim' => 'both',
    'indent' => 2,
    'suffix' => "\n",
  ]);
  echo $hero_html;
?>

<?php
  ob_start();

  if ($has_writings) {
    $current_month_key = '';
    $in_month_card = false;

    while ($writings_query->have_posts()) {
      $writings_query->the_post();
      $post_id = (int) get_the_ID();

      // ===== Month grouping (simple time-based heading, not a taxonomy) =====
      $month_key = get_the_date('Y-m', $post_id);
      if ($month_key !== $current_month_key) {
        if ($in_month_card) {
          echo "        </ul>\n      </div>\n    </section>\n\n";
        }
        $current_month_key = $month_key;
        $month_label = get_the_date('Y.n', $post_id);
        $month_id = 'writings-' . $month_key;

        echo '      <section class="card content writings">' . "\n";
        echo '        <header class="head">' . "\n";
        echo '          <h3 class="index" id="' . esc_attr($month_id) . '"><span class="value">' . esc_html($month_label) . '</span></h3>' . "\n";
        echo '        </header>' . "\n";
        echo '        <div class="body">' . "\n";
        echo '          <ul>' . "\n";
        $in_month_card = true;
      }

      // ===== Writing Number (nor_writing_no; #001 display, DB value untouched) =====
      // Published Writings are already validated to require nor_writing_no, so no
      // post-ID/loop-index fallback is used here — a missing number is shown
      // as an empty/safe placeholder rather than substituted.
      $writing_no_raw = get_post_meta($post_id, 'nor_writing_no', true);
      $writing_no_int = is_numeric($writing_no_raw) ? (int) $writing_no_raw : 0;
      if ($writing_no_int > 0) {
        $num_value = str_pad((string) $writing_no_int, 3, '0', STR_PAD_LEFT);
        $num_display = '#' . $num_value;
      } else {
        $num_value = '';
        $num_display = '—';
      }

      // ===== Theme (standard category, shown as metadata text only — no link) =====
      $categories = get_the_category($post_id);
      $theme = (!empty($categories) && $categories[0] instanceof WP_Term) ? $categories[0]->name : '';

      // ===== Published + New badge (same 30-day window as Works) =====
      $published_display = get_the_date('Y-m-d', $post_id);
      $published_dt = get_the_date('c', $post_id);
      $published_ts = (int) get_post_time('U', false, $post_id);
      $is_new = nor_is_recent_timestamp($published_ts, 30);

      $permalink = get_permalink($post_id);
      $title = get_the_title($post_id);
      $excerpt = get_the_excerpt($post_id);

      echo '            <li>' . "\n";
      echo '              <div class="index-mode-meta">' . "\n";
      echo '                <ul class="index-mode">' . "\n";
      echo '                  <li><data value="' . esc_attr($num_value) . '">' . esc_html($num_display) . '</data></li>' . "\n";
      echo '                  <li>' . esc_html($theme) . '</li>' . "\n";
      echo '                </ul>' . "\n";
      echo '                <ul class="meta">' . "\n";
      echo '                  <li>Published: <time datetime="' . esc_attr($published_dt) . '" class="value">' . esc_html($published_display) . '</time>' . ($is_new ? '<span class="new">New</span>' : '') . '</li>' . "\n";
      echo '                </ul>' . "\n";
      echo '              </div>' . "\n";
      echo '              <div class="title-summary">' . "\n";
      echo '                <h4><cite class="value"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></cite></h4>' . "\n";
      echo '                <p class="ja" lang="ja">' . esc_html($excerpt) . '</p>' . "\n";
      echo '              </div>' . "\n";
      echo '            </li>' . "\n";
    }

    if ($in_month_card) {
      echo "        </ul>\n      </div>\n    </section>\n\n";
    }

    wp_reset_postdata();

    // ===== Pagination (custom WP_Query; /writings/page/2/) =====
    // get_pagenum_link() is built around the main-query archive/home paging
    // model; for a static page driving its own loop the standard approach is
    // to build the URL from this page's own permalink instead.
    $current_page = max(1, $paged);
    $total_pages = max(1, (int) $writings_query->max_num_pages);
    $writings_permalink = trailingslashit(get_permalink($writings_page_id));

    $page_url = static function (int $n) use ($writings_permalink): string {
      if ($n <= 1) return $writings_permalink;
      return user_trailingslashit($writings_permalink . 'page/' . $n);
    };

    $pagination_args = nor_build_list_pagination_args(
      $current_page,
      $total_pages,
      $page_url,
      [
        'aria_label'      => 'Writings pagination',
        'next_rel'        => 'next',
        'prev_rel'        => 'prev',
        'show_first_prev' => true,
        'show_next_last'  => true,
      ]
    );

    $pagination_html = nor_render_template_part('template-parts/pagination/pagination-list', null, $pagination_args, [
      'trim' => 'both',
      'strip_leading_spaces' => 6,
      'indent' => 6,
      'suffix' => "\n",
    ]);
    if ($pagination_html !== '') {
      echo $pagination_html;
    }
  } else {
    wp_reset_postdata();
    echo nor_render_template_part('template-parts/empty/empty-writings-list', null, [
      'title' => (string) $page_meta['title'],
    ], [
      'trim' => 'right',
    ]);
  }

  $list_body_html = (string) ob_get_clean();
  echo nor_render_list_section_shell([
    'section_class' => 'content-pages content-writings',
    'section_h2_ja' => $section_h2_ja,
    'section_h2_en' => $section_h2_en,
    'body_html'      => $list_body_html,
  ], [
    'trim' => 'left',
    'indent' => 2,
  ]);
?>

<?php
  echo nor_render_indices([
    'trim' => 'left',
    'indent' => 2,
    'suffix' => "\n",
  ]);
?>

</main>

<?php get_footer(); ?>
