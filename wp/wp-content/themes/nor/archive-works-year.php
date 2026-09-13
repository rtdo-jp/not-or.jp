<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    global $wp_query;
    $year = (int) get_query_var('nor_year');
    if ($year < 1900) $year = (int) get_query_var('year');

    $paged = max(1, (int) get_query_var('paged'));
    $max   = (int) $wp_query->max_num_pages;

    $per_page = nor_get_works_archive_per_page();
    if ($per_page < 1) $per_page = 12;

    // Hero (pages-common)
    $works_count = (int) ($wp_query->found_posts ?? 0);

    // Inherit copy from /archives/ page meta.
    $archives_meta = nor_get_page_meta_bundle_by_path('archives', 'Archives', '—');
    $tagline = (string) ($archives_meta['tagline'] ?? '');
    $desc_ja = (string) ($archives_meta['desc_ja'] ?? '');
    $desc_en = (string) ($archives_meta['desc_en'] ?? '');

    $hero_html = nor_render_template_part('template-parts/hero/hero-pages', null, [
      // Use a plain h1: "Archives 2026" (no HTML)
      'title'       => 'Archives ' . $year,
      'tagline'     => $tagline,
      'desc_ja'     => $desc_ja,
      'desc_en'     => $desc_en,
      'count' => $works_count,
      'unit'  => nor_format_count_unit($works_count, 'work', 'works', 'archived'),
      'breadcrumbs' => nor_build_breadcrumbs([
        ['label' => 'Archives', 'url' => home_url('/archives/')],
        ['label' => (string) $year, 'current' => true],
      ]),
      'widget' => 'none',
    ], [
      'trim' => 'left',
      'indent' => 2,
      'suffix' => "\n",
    ]);
    echo $hero_html;

    // Section heading (visually-hidden): year archive specific label
    if ($year > 0) {
      $section_h2_ja = 'Archives ' . $year . ' 一覧';
      $section_h2_en = 'List of archives ' . $year;
    } else {
      $section_h2_ja = '— 一覧';
      $section_h2_en = 'List of —';
    }
?>
<?php
      ob_start();
      if (have_posts()) : while (have_posts()) : the_post();
            // Works number is each Work's own saved nor_work_no (admin-set,
            // e.g. from its URL slug's 4-digit segment), not a list-position
            // sequence -- same source/format as Category/Tag/Client
            // (template-parts/taxonomy/taxonomy-works-list.php) and the main
            // Works archive (archive-works.php), so it stays stable across
            // pagination instead of resetting per page.
            $post_id = (int) get_the_ID();
            $work_no = (int) get_post_meta($post_id, 'nor_work_no', true);
            $num = nor_format_seq_no(max(0, $work_no));

            $card_data = nor_get_work_card_data($post_id, [
              'client_mode'              => 'taxonomy',
              'clients_limit'            => 4,
              'legacy_industry_fallback' => false,
              'include_tag_other'        => false,
            ]);

            // Types: show ONLY one group (Document types preferred, else Site types)
            $types_mode = (!empty($card_data['doc_types'])) ? 'document' : ((!empty($card_data['site_types'])) ? 'site' : 'none');
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
              'types_colon'  => true,
            ], [
              'trim' => 'both',
              'normalize_first_indent' => true,
              'indent' => 6,
              'suffix' => "\n\n",
            ]);
            echo $card_html;
          endwhile;

          // ===== Pagination (year archive) =====
          // Use WP-native paging, rendered by the shared pagination UI.
          $total_pages = (int) $max;
          if ($total_pages < 1) $total_pages = 1;

          $page_url = static function (int $n): string {
            $u = get_pagenum_link($n);
            return is_string($u) ? $u : '';
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
            'trim' => 'right',
          ]);
          if ($pagination_html !== '') {
            echo "\n" . $pagination_html . "\n\n";
          }
          echo nor_render_back_list_button((string) home_url('/archives/'), 'Back to List', [
            'indent' => 6,
            'suffix' => "\n",
          ]);
      else :
        echo nor_render_empty_works_list([
          'title' => (string) $year,
          'primary_url' => home_url('/archives/'),
          'primary_label' => 'Back to List',
        ], [
          'trim' => 'right',
        ]);
      endif;
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
    $indices_html = nor_render_indices([
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 2,
      'suffix' => "\n",
    ]);
    if ($indices_html !== '') {
      echo $indices_html;
    }
?>

</main>

<?php get_footer(); ?>
