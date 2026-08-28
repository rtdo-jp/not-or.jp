<?php get_header(); ?>

<main id="site-main" tabindex="-1">
  <section class="section pages">
    <h1 class="visually-hidden"><span lang="ja">コンテンツ一覧</span>（<span lang="en">Content list</span>）</h1>
    <div class="inner">
<?php if (have_posts()) : ?>
      <div class="content">
        <ul class="simple-list">
<?php while (have_posts()) : the_post(); ?>
          <li>
            <article>
              <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
<?php if (get_the_date()) : ?>
              <p><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y-m-d')); ?></time></p>
<?php endif; ?>
            </article>
          </li>
<?php endwhile; ?>
        </ul>
      </div>

<?php
      global $wp_query;
      $current_page = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
      $total_pages  = max(1, (int) ($wp_query->max_num_pages ?? 1));

      if ($total_pages > 1) {
        $page_url = static function (int $n): string {
          $n = max(1, $n);
          $u = get_pagenum_link($n);
          return is_string($u) ? $u : '';
        };

        $pagination_args = nor_build_list_pagination_args(
          (int) $current_page,
          (int) $total_pages,
          $page_url,
          [
            'aria_label'      => 'Pagination',
            'next_rel'        => 'next',
            'prev_rel'        => 'prev',
            'show_first_prev' => true,
            'show_next_last'  => true,
          ]
        );

        get_template_part('template-parts/pagination/pagination-list', null, $pagination_args);
      }
?>

<?php else : ?>
      <div class="content empty" role="status">
        <div class="textpair">
          <p class="ja" lang="ja">表示できるコンテンツがありません。</p>
          <p class="en" lang="en">No content available.</p>
        </div>
        <ul class="actions">
          <li><a href="<?php echo esc_url(home_url('/')); ?>" class="btn">Back to Home</a></li>
        </ul>
      </div>
<?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
