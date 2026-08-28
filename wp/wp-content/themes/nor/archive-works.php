<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    // ===== Hero (Works archive) =====
    $works_count = nor_get_published_works_count();
    $archive_copy = nor_get_works_archive_copy('Works Index', '—');
    $tagline = (string) ($archive_copy['tagline'] ?? '—');
    $desc_ja = (string) ($archive_copy['desc_ja'] ?? '—');
    $desc_en = (string) ($archive_copy['desc_en'] ?? '—');
    $section_h2_ja = (string) ($archive_copy['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($archive_copy['section_h2_en'] ?? '—');

    $hero_html = nor_render_template_part('template-parts/hero/hero-pages', null, [
      'title'    => 'Works Index',
      'tagline'  => $tagline,
      'desc_ja'  => $desc_ja,
      'desc_en'  => $desc_en,
      'count'    => $works_count,
      'unit'     => 'works archived.',
      'widget'   => 'none',
      'breadcrumbs' => nor_build_breadcrumbs([
        ['label' => 'Works', 'url' => home_url('/works/')],
        ['label' => 'Works Index', 'current' => true],
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
      // ===== Paging policy =====
      // - Home (front-page) shows the latest fixed count and acts as page 1.
      // - Works archive starts at /works/page/2/ and should skip the Home batch.
      // - Page 1 link should go to Home.
      global $wp_query;

      $home_count = nor_get_works_home_count();
      $per_page = nor_get_works_archive_per_page();
      if ($per_page < 1) $per_page = 12;

      $total_published = max(0, (int) $works_count);
      $remaining = max(0, $total_published - $home_count);
      $archive_pages = ($remaining > 0) ? (int) ceil($remaining / $per_page) : 0;
      $total_pages = 1 + $archive_pages;

      $paged = max(1, (int) get_query_var('paged'));
      $current_page = ($paged < 2) ? 1 : $paged;

      $offset = $home_count + max(0, ($current_page - 2)) * $per_page;

      $q = new WP_Query([
        'post_type'      => 'works',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'offset'         => $offset,
      ]);

      if ($q->have_posts()) {
        while ($q->have_posts()) {
          $q->the_post();
          $post_id = (int) get_the_ID();

          $work_no_raw = get_post_meta($post_id, 'nor_work_no', true);
          $work_no_int = is_numeric($work_no_raw) ? (int) $work_no_raw : 0;
          if ($work_no_int < 1) $work_no_int = (int) ($offset + $q->current_post + 1);
          $num = str_pad((string) $work_no_int, 3, '0', STR_PAD_LEFT);

          $card_data = nor_get_work_card_data($post_id, [
            'client_mode'              => 'taxonomy',
            'clients_limit'            => 4,
            'legacy_industry_fallback' => false,
            'include_tag_other'        => false,
          ]);

          $types_mode  = !empty($card_data['doc_types']) ? 'document' : (!empty($card_data['site_types']) ? 'site' : 'none');
          $types_label = ($types_mode === 'document') ? 'Document types' : (($types_mode === 'site') ? 'Site types' : 'Types');
          $types_terms = ($types_mode === 'document') ? $card_data['doc_types'] : (($types_mode === 'site') ? $card_data['site_types'] : []);
          $roles_terms = nor_limit_wp_terms($card_data['roles'] ?? [], 4);
          $tools_terms = nor_limit_wp_terms($card_data['tools'] ?? [], 4);

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
            'types_label'  => $types_label,
            'types_terms'  => $types_terms,
            'roles'        => $roles_terms,
            'tools'        => $tools_terms,
            'clients'      => is_array($card_data['clients'] ?? null) ? $card_data['clients'] : [],
            'industries'   => is_array($card_data['industries'] ?? null) ? $card_data['industries'] : [],
            'types_colon'  => false,
          ], [
            'trim' => 'both',
            'strip_leading_spaces' => 6,
            'indent' => 6,
            'suffix' => "\n\n",
          ]);
          echo $card_html;
        }
        wp_reset_postdata();

        $works_base = trailingslashit(get_post_type_archive_link('works'));
        $page_url = static function (int $n) use ($works_base): string {
          if ($n <= 1) return home_url('/');
          return $works_base . 'page/' . $n . '/';
        };

        $pagination_args = nor_build_list_pagination_args(
          (int) $current_page,
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
          'suffix' => "\n",
        ]);
        if ($pagination_html !== '') {
          echo $pagination_html;
        }
      } else {
        $empty_title = 'Works Index';
        echo nor_render_empty_works_list([
          'title' => $empty_title,
          'primary_url' => get_post_type_archive_link('works'),
          'primary_label' => 'Back to List',
        ], [
          'trim' => 'right',
        ]);
      }
      $list_body_html = (string) ob_get_clean();
      echo nor_render_list_section_shell([
        'section_class' => 'list-works',
        'section_h2_ja' => $section_h2_ja,
        'section_h2_en' => $section_h2_en,
        'body_html' => $list_body_html,
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
