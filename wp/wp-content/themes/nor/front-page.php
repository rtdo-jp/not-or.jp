<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php get_template_part('template-parts/hero/hero-home'); ?>

  <section class="section list-works">
    <h2 class="visually-hidden">Latest works</h2>
    <div class="inner">

<?php
        $home_per_page = nor_get_works_home_count();

        $latest = new WP_Query([
          'post_type'      => 'works',
          'posts_per_page' => $home_per_page,
          'post_status'    => 'publish',
          'orderby'        => 'date',
          'order'          => 'DESC',
          'no_found_rows'  => true,
        ]);

        if ($latest->have_posts()) :
          while ($latest->have_posts()) :
            $latest->the_post();

            $post_id = (int) get_the_ID();
            $work_no = (int) get_post_meta($post_id, 'nor_work_no', true);
            $num = nor_format_seq_no(max(0, $work_no));

            $card_data = nor_get_work_card_data($post_id, [
              'client_mode'              => 'meta',
              'clients_limit'            => 4,
              'legacy_industry_fallback' => true,
              'include_tag_other'        => true,
            ]);

            get_template_part('template-parts/card/card-works', null, [
              'post_id'      => $post_id,
              'num'          => $num,
              'permalink'    => (string) ($card_data['permalink'] ?? get_permalink($post_id)),
              'title'        => (string) ($card_data['title'] ?? get_the_title($post_id)),
              'excerpt'      => (string) ($card_data['excerpt'] ?? ''),
              'published'    => (string) ($card_data['published'] ?? ''),
              'published_dt' => (string) ($card_data['published_dt'] ?? ''),
              'is_new'       => !empty($card_data['is_new']),
              'category'     => ($card_data['category'] ?? null),
              'content_mode' => (string) ($card_data['content_mode'] ?? 'Text-only'),
              'doc_types'    => is_array($card_data['doc_types'] ?? null) ? $card_data['doc_types'] : [],
              'site_types'   => is_array($card_data['site_types'] ?? null) ? $card_data['site_types'] : [],
              'roles'        => is_array($card_data['roles'] ?? null) ? $card_data['roles'] : [],
              'tools'        => is_array($card_data['tools'] ?? null) ? $card_data['tools'] : [],
              'clients'      => is_array($card_data['clients'] ?? null) ? $card_data['clients'] : [],
              'industries'   => is_array($card_data['industries'] ?? null) ? $card_data['industries'] : [],
            ]);
            if (($latest->current_post + 1) < $latest->post_count) {
              echo "\n";
            }
          endwhile;
          wp_reset_postdata();
        else :
      ?>
      <p>No works yet.</p>
      <?php
        endif;

        // Works count (used for Home pager)
        $works_count = nor_get_published_works_count();

        // Pagination policy:
        // - Page 1 is Home (this page): latest N works (fixed setting)
        // - Page 2+ are /works/page/{n}/ : configurable count from Works settings
        $home_url  = home_url('/');
        $works_url = get_post_type_archive_link('works');

        $home_per_page    = nor_get_works_home_count();
        $archive_per_page = nor_get_works_archive_per_page();

        $total = max(0, (int) $works_count);
        $remaining = max(0, $total - $home_per_page);
        $archive_pages = ($archive_per_page > 0) ? (int) ceil($remaining / $archive_per_page) : 0;
        $total_pages = 1 + max(0, $archive_pages);
        if ($total_pages < 1) $total_pages = 1;

        $page_url = static function (int $n) use ($home_url, $works_url): string {
          if ($n <= 1) return (string) $home_url;
          return trailingslashit($works_url) . 'page/' . $n . '/';
        };

        $pagination_args = nor_build_list_pagination_args(
          1,
          (int) $total_pages,
          $page_url,
          [
            'aria_label'      => 'Works pagination',
            'next_rel'        => 'next',
            'prev_rel'        => 'prev',
            // Home pager: First/Prev are always disabled in the mock/implementation.
            'show_first_prev' => false,
            // Home pager: Next/Last should appear (disabled if single page).
            'show_next_last'  => true,
          ]
        );
      ?>

<?php get_template_part('template-parts/pagination/pagination-list', null, $pagination_args); ?>
    </div>
  </section>

<?php
  // -------------------------
  // Recent Thoughts (latest Writings; Home, so there's no "current entry" to exclude)
  // -------------------------
  $recent_query = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 5,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
    'fields'              => 'ids',
  ]);
  $recent_ids = is_array($recent_query->posts) ? $recent_query->posts : [];
  wp_reset_postdata();

  // Helper: format Writing Number (#001; no post-ID/loop-index fallback),
  // matching page-writings.php / single-post.php's own policy.
  $format_writing_no = function (int $pid): array {
    $raw = get_post_meta($pid, 'nor_writing_no', true);
    $n = is_numeric($raw) ? (int) $raw : 0;
    if ($n > 0) {
      $val = nor_format_seq_no($n);
      return [$val, '#' . $val];
    }
    return ['', '—'];
  };

  $render_recent_item = function (int $pid) use ($format_writing_no) {
    $r_title = get_the_title($pid);
    $r_permalink = get_permalink($pid);
    $r_published = get_the_date('Y-m-d', $pid);
    $r_published_dt = get_the_date('c', $pid);
    $r_published_ts = (int) get_post_time('U', false, $pid);
    $r_is_new = nor_is_recent_timestamp((int) $r_published_ts, 30);
    $r_categories = get_the_category($pid);
    $r_theme = (!empty($r_categories) && $r_categories[0] instanceof WP_Term) ? $r_categories[0]->name : '';
    $r_excerpt = get_the_excerpt($pid);
    if (is_string($r_excerpt)) {
      // CJK-aware single-line collapse. Preserve the existing behavior of
      // this path, which did not strip HTML tags, so strip_tags is off.
      // See nor_normalize_single_line_text() in functions.php for the full
      // rationale.
      $r_excerpt = nor_normalize_single_line_text($r_excerpt, false);
    } else {
      $r_excerpt = '';
    }
    [$no_val, $no_label] = $format_writing_no($pid);
    ?>
        <li>
          <div class="index-mode-meta">
            <ul class="index-mode">
              <li><data value="<?php echo esc_attr($no_val); ?>"><?php echo esc_html($no_label); ?></data></li>
              <li><?php echo nor_render_writing_theme_label($r_theme); ?></li>
            </ul>
            <ul class="meta">
              <li>Published: <time datetime="<?php echo esc_attr($r_published_dt); ?>" class="value"><?php echo esc_html($r_published); ?></time><?php if ($r_is_new) : ?><span class="new">New</span><?php endif; ?></li>
            </ul>
          </div>
          <div class="title-summary">
            <h3><cite class="value"><a href="<?php echo esc_url($r_permalink); ?>"><?php echo esc_html($r_title); ?></a></cite></h3>
<?php if ($r_excerpt !== '') : ?>
            <p class="ja" lang="ja"><?php echo esc_html($r_excerpt); ?></p>
<?php else : ?>
            <p class="ja" lang="ja">—</p>
<?php endif; ?>
          </div>
        </li>
    <?php
  };

  $render_recent_items = function (array $ids) use ($render_recent_item) {
    foreach ($ids as $rid) {
      ob_start();
      $render_recent_item((int) $rid);
      $item_html = (string) ob_get_clean();
      $item_html = (string) preg_replace('/\A(?:[ \t]*\R)+/u', '', $item_html);
      $item_html = (string) preg_replace('/(?:\R[ \t]*)+\z/u', '', $item_html);
      $item_html = nor_normalize_first_indent($item_html);
      if ($item_html === '') {
        continue;
      }
      echo (string) preg_replace('/^(?=.*\S)/m', '        ', $item_html) . "\n\n";
    }
  };

  if (!empty($recent_ids)) {
    echo nor_render_template_part('template-parts/card/card-related', null, [
      'title'        => 'Recent Thoughts',
      'description'  => "What's on my mind lately.",
      'ids'          => $recent_ids,
      'render_items' => $render_recent_items,
    ], [
      'trim'                   => 'both',
      'normalize_first_indent' => true,
      'indent'                 => 2,
      'suffix'                 => "\n\n",
    ]);
  }
?>

<?php
  echo nor_render_indices([
    'trim' => 'left',
    'indent' => 2,
  ]);
?>

</main>

<?php get_footer(); ?>
