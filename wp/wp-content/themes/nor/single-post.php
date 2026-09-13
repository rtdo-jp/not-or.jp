<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<?php
    $post_id = get_the_ID();

    // Dates (same rules as single-works.php: Published new within 30 days,
    // Updated new within 7 days, but suppress the Updated badge specifically
    // when Published and Updated fall on the same day).
    $published    = get_the_date('Y-m-d');
    $published_dt = get_the_date('c');
    $updated      = get_the_modified_date('Y-m-d');
    $updated_dt   = get_the_modified_date('c');

    $published_ts = (int) get_the_time('U');
    $updated_ts   = (int) get_the_modified_time('U');

    $is_new_published = nor_is_recent_timestamp((int) $published_ts, 30);
    $is_new_updated = nor_is_recent_timestamp((int) $updated_ts, 7);

    $is_same_day = ($published === $updated);
    if ($is_same_day) {
      $is_new_updated = false;
    }

    // Theme: standard category, plain metadata only (no link, no filtering).
    $categories = get_the_category($post_id);
    $theme = (!empty($categories) && $categories[0] instanceof WP_Term) ? $categories[0]->name : '';

    // Meta (Writing)
    $tagline = get_post_meta($post_id, 'nor_tagline', true);
    $tagline = is_string($tagline) ? trim($tagline) : '';

    $summary_en = get_post_meta($post_id, 'nor_summary_en', true);
    $summary_en = is_string($summary_en) ? trim($summary_en) : '';

    $body_summary_en = get_post_meta($post_id, 'nor_writing_body_summary_en', true);
    $body_summary_en = is_string($body_summary_en) ? trim($body_summary_en) : '';

    // Summary (JA) uses built-in Excerpt.
    $summary_ja = get_the_excerpt();
    $summary_ja = is_string($summary_ja) ? trim($summary_ja) : '';

    $writing_title = get_the_title($post_id);
    $writings_url = home_url('/writings/');

    // Hero (pages common). Stats show the sitewide published Writings count
    // (same as the Writings list page), not this entry's own Writing Number.
    $writings_count = nor_get_published_writings_count();
    $hero_args = [
      'title_html' => '<cite>' . esc_html($writing_title) . '</cite>',
      'title'      => $writing_title,
      'specific_tag' => 'hgroup',

      'tagline' => $tagline,
      'desc_ja' => $summary_ja,
      'desc_en' => $summary_en,

      'count' => $writings_count,
      'unit'  => nor_format_count_unit($writings_count, 'Writing', 'Writings', 'published'),

      'breadcrumbs' => nor_build_breadcrumbs([
        ['label' => 'Writings', 'url' => $writings_url],
        [
          'label'      => $writing_title,
          'current'    => true,
          'label_html' => '<cite>' . esc_html($writing_title) . '</cite>',
        ],
      ]),

      'widget' => 'none',
    ];
    echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>

  <section class="section content-writings">
    <h2 class="visually-hidden"><span lang="ja">記事詳細</span>（<span lang="en">Writing details</span>）</h2>
    <div class="inner">

      <article class="card article writing">
        <h3 class="visually-hidden">Writing entry</h3>

        <header class="head">
<?php if (has_post_thumbnail($post_id)) : ?>
<?php
    $thumbnail_id = (int) get_post_thumbnail_id($post_id);
    $thumbnail_alt = trim((string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true));
    if ($thumbnail_alt === '') {
      $thumbnail_alt = $writing_title . ' の画像';
    }
    $main_img = wp_get_attachment_image($thumbnail_id, 'full', false, [
      'fetchpriority' => 'high',
      'alt'           => $thumbnail_alt,
    ]);
?>
<?php if ($main_img) : ?>
          <figure class="skeleton-figure">
            <?php echo $main_img . "\n"; ?>
          </figure>
<?php endif; ?>
<?php endif; ?>
          <dl class="meta">
            <dt>Published</dt>
            <dd><time datetime="<?php echo esc_attr($published_dt); ?>" class="value"><?php echo esc_html($published); ?></time><?php if ($is_new_published) : ?><span class="new"><small>New</small></span><?php endif; ?></dd>
            <dt>Updated</dt>
            <dd><time datetime="<?php echo esc_attr($updated_dt); ?>" class="value"><?php echo esc_html($updated); ?></time><?php if ($is_new_updated) : ?><span class="new"><small>New</small></span><?php endif; ?></dd>
            <dt>Theme</dt>
            <dd><span class="value"><?php echo nor_render_writing_theme_label($theme); ?></span></dd>
          </dl>
        </header>

        <div class="body">
          <div class="detail">

            <div class="writing-content ja" lang="ja">
<?php the_content(); ?>
            </div>

<?php if ($body_summary_en !== '') : ?>
            <div class="writing-summary en" lang="en">
              <h4 class="visually-hidden">English Summary</h4>
<?php echo wp_kses_post(wpautop($body_summary_en)); ?>
            </div>
<?php endif; ?>

          </div>
        </div>
      </article>
<?php
      // Adjacent Writings (post_date order, post_type-scoped by get_adjacent_post()
      // itself, so this never picks up Works).
      $prev_post = get_adjacent_post(false, '', true);
      $next_post = get_adjacent_post(false, '', false);

      $prev_url = ($prev_post instanceof WP_Post) ? get_permalink($prev_post) : '';
      $next_url = ($next_post instanceof WP_Post) ? get_permalink($next_post) : '';

      $pagination_args = [
        'aria_label' => 'Writings navigation',
        'back_url'   => $writings_url,
        'back_label' => 'Back to Writings',
        'prev_url'   => $prev_url,
        'next_url'   => $next_url,
      ];
      $pagination_html = nor_render_template_part('template-parts/pagination/pagination-detail', null, $pagination_args, [
        'trim'                   => 'both',
        'normalize_first_indent' => true,
        'indent'                 => 6,
        'suffix'                 => "\n",
      ]);
      if ($pagination_html !== '') {
        echo "\n" . $pagination_html;
      }
?>

    </div>
  </section>

<?php
    // -------------------------
    // Recent Thoughts (latest Writings, excluding the current entry)
    // -------------------------
    $recent_query = new WP_Query([
      'post_type'           => 'post',
      'post_status'         => 'publish',
      'post__not_in'        => [$post_id],
      'posts_per_page'      => 5,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'ignore_sticky_posts' => true,
      'no_found_rows'       => true,
      'fields'              => 'ids',
    ]);
    $recent_ids = is_array($recent_query->posts) ? $recent_query->posts : [];

    // Helper: format Writing Number (#001; no post-ID/loop-index fallback).
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
      $r_excerpt = is_string($r_excerpt) ? trim((string) preg_replace('/\s+/u', ' ', $r_excerpt)) : '';
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
        echo (string) preg_replace('/^(?=.*\S)/m', '          ', $item_html) . "\n\n";
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

    // -------------------------
    // Indices (global navigation)
    // -------------------------
    echo nor_render_indices([
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 2,
      'suffix' => "\n",
    ], true);
    endwhile;
  endif;
?>

</main>

<?php get_footer(); ?>
