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
            $num = str_pad((string) max(0, $work_no), 3, '0', STR_PAD_LEFT);

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
  echo nor_render_indices([
    'trim' => 'left',
    'indent' => 2,
  ]);
?>

</main>

<?php get_footer(); ?>
