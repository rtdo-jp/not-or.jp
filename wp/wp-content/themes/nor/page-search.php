<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    // ===== Page context =====
    $page_id = get_queried_object_id();
    $landing = nor_get_landing_page_shell_args($page_id, [
      'count' => 0,
      'unit' => nor_format_count_unit(0, 'work', 'works', 'archived'),
      'title_fallback' => 'Search',
      'section_h2_fallback' => '—',
    ]);
    $title = is_string($landing['title'] ?? null) ? (string) $landing['title'] : 'Search';
    $hero_base_args = (isset($landing['hero_args']) && is_array($landing['hero_args'])) ? $landing['hero_args'] : [
      'title' => $title,
      'tagline' => '',
      'desc_ja' => '',
      'desc_en' => '',
      'breadcrumbs' => nor_build_single_breadcrumb($title),
    ];

    // ===== Search context =====
    $q_raw = isset($_GET['q']) ? (string) wp_unslash($_GET['q']) : '';
    $q = trim(wp_strip_all_tags($q_raw));
    $is_search = ($q !== '');

    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    $per_page = 12;

    // ===== Works count =====
    $works_count = nor_get_published_works_count();

    // ===== Query (Search / Default latest) =====
    $base_query_args = [
      'post_type'           => 'works',
      'post_status'         => 'publish',
      'posts_per_page'      => $per_page,
      'paged'               => $paged,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'ignore_sticky_posts' => true,
    ];

    // Hero stats/unit:
    // - default: total published works / "works archived."
    // - searching: matched works / "search results."
    $hero_count = $works_count;
    $hero_unit  = nor_format_count_unit($works_count, 'work', 'works', 'archived');

    $display_q = null;
    $matched_work_ids = [];
    $is_empty_search = false;
    $search_state = 'default'; // default | results | empty

    if (!$is_search) {
      $display_q = new WP_Query($base_query_args);
    } else {
      $hero_unit = 'search results.';

      $matched_work_ids = nor_find_work_ids_by_keyword($q);

      $hero_count = count($matched_work_ids);
      $is_empty_search = ($hero_count === 0);
      $search_state = $is_empty_search ? 'empty' : 'results';

      if ($is_empty_search) {
        // Empty search: show latest works list (mock behavior)
        $display_q = new WP_Query($base_query_args);
      } else {
        $display_q = new WP_Query(array_merge($base_query_args, [
          'post__in' => $matched_work_ids,
        ]));
      }
    }

    // Hero search state copy
    ob_start();
?>
<div class="textpair" data-state="<?php echo esc_attr($search_state); ?>" aria-live="polite">
  <div class="default"<?php echo ($search_state === 'default') ? '' : ' hidden'; ?>>
    <p class="ja" lang="ja">最新の記録を新しい順に表示しています。</p>
    <p class="en" lang="en">Showing the latest records first.</p>
  </div>
  <div class="searching" hidden>
    <p class="ja" lang="ja">検索中です…</p>
    <p class="en" lang="en">Searching…</p>
  </div>
  <div class="results"<?php echo ($search_state === 'results') ? '' : ' hidden'; ?>>
    <p class="ja" lang="ja">「<q class="keyword"><?php echo esc_html($q); ?></q>」を含む記録を新しい順に表示しています。</p>
    <p class="en" lang="en">Showing records containing “<q class="keyword"><?php echo esc_html($q); ?></q>”, newest first.</p>
  </div>
  <div class="empty"<?php echo ($search_state === 'empty') ? '' : ' hidden'; ?>>
    <p class="ja" lang="ja">「<q class="keyword"><?php echo esc_html($q); ?></q>」に一致する記録は見つかりませんでした。最新の記録を新しい順に表示しています。</p>
    <p class="en" lang="en">No records matched “<q class="keyword"><?php echo esc_html($q); ?></q>”. Showing the latest records instead.</p>
  </div>
  <div class="error" hidden>
    <p class="ja" lang="ja">検索中にエラーが発生しました。しばらくしてから再度お試しください。</p>
    <p class="en" lang="en">An error occurred while searching. Please try again later.</p>
  </div>
</div>
<?php
    $state_html = (string) ob_get_clean();
    $state_html = ltrim($state_html, "\r\n");
    $state_html = rtrim($state_html, "\r\n");

    $hero_args = $hero_base_args;
    $hero_args['count'] = $hero_count;
    $hero_args['unit'] = $hero_unit;
    $hero_args['title'] = $title;
    $hero_args['widget'] = 'search';
    $hero_args['search_form'] = [
      'action'      => home_url('/search/'),
      'query_param' => 'q',
      'input_id'    => 'keyword',
      'placeholder' => 'キーワード、社名、タグ… / keyword, client, tag…',
      'state_html'  => $state_html,
      'state_indent' => 8,
    ];
    // Breadcrumb: while searching, fold the (already-sanitized) search
    // keyword into the single current crumb ("Search - {$q}") rather than
    // adding it as its own hierarchy level; pagination doesn't change $q,
    // so page 2+ naturally keeps the same crumb without a page-number one.
    $hero_args['breadcrumbs'] = $is_search
      ? nor_build_single_breadcrumb($title . ' - ' . $q)
      : nor_build_single_breadcrumb($title);

    $hero_html = nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'left',
      'indent' => 2,
      'suffix' => "\n",
    ]);
    echo $hero_html;

    ob_start();
      if ($display_q->have_posts()) {
        while ($display_q->have_posts()) {
          $display_q->the_post();

          $post_id = get_the_ID();
          $work_no = (int) get_post_meta($post_id, 'nor_work_no', true);
          $num = nor_format_seq_no(max(0, $work_no));

          $card_data = nor_get_work_card_data((int) $post_id, [
            'client_mode'              => 'meta',
            'clients_limit'            => 4,
            'legacy_industry_fallback' => true,
            'include_tag_other'        => true,
          ]);

          $card_html = nor_render_template_part('template-parts/card/card-works', null, [
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
          ], [
            'trim' => 'both',
            // card-works partial includes a base 6-space indent; normalize before nesting.
            'strip_leading_spaces' => 6,
            'indent' => 6,
            'suffix' => "\n\n",
          ]);
          echo $card_html;
        }

        // ===== Pagination =====
        // Only shown while searching ($q set): the unsearched default view
        // is a single implicit "latest works" landing, not a browsable
        // archive, so page 2+ has no entry point and isn't linked here.
        if ($is_search) {
          $total_pages = (int) ($display_q->max_num_pages ?? 1);
          if ($total_pages < 1) $total_pages = 1;

          $page_url = function (int $n) use ($q): string {
            $u = get_pagenum_link($n);
            if ($q !== '') {
              $u = add_query_arg('q', $q, $u);
            }
            return (string) $u;
          };

          $pagination_args = nor_build_list_pagination_args(
            (int) $paged,
            (int) $total_pages,
            $page_url,
            [
              'aria_label'      => 'Works pagination',
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
            'suffix' => "\n\n",
          ]);
          if ($pagination_html !== '') {
            echo $pagination_html;
          }
        }

        wp_reset_postdata();
      } else {
        // Defensive fallback: in case both search and latest have no published works.
        $empty_title = ($q !== '') ? $q : $title;
        echo nor_render_empty_works_list([
          'title' => $empty_title,
          'primary_url' => home_url('/search/'),
          'primary_label' => 'Back to Search',
        ], [
          'trim' => 'right',
        ]);
      }
    $list_body_html = (string) ob_get_clean();
    echo nor_render_list_section_shell([
      'section_class' => 'list-works',
      'section_h2_ja' => '制作記録一覧',
      'section_h2_en' => 'Works',
      'body_html' => $list_body_html,
    ], [
      'trim' => 'left',
      'indent' => 2,
    ], true);
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
