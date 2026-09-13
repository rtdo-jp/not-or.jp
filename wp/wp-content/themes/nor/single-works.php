<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<?php
    $post_id = get_the_ID();

    // Dates
    $published    = get_the_date('Y-m-d');
    $published_dt = get_the_date('c');
    $updated      = get_the_modified_date('Y-m-d');
    $updated_dt   = get_the_modified_date('c');

    $published_ts = (int) get_the_time('U');
    $updated_ts   = (int) get_the_modified_time('U');

    // NEW badge rules
    // - Published: within 30 days (existing behavior)
    // - Updated: within 7 days
    // - If Published and Updated are the same date, show NEW only on Published
    $is_new_published = nor_is_recent_timestamp((int) $published_ts, 30);
    $is_new_updated = nor_is_recent_timestamp((int) $updated_ts, 7);

    $is_same_day = ($published === $updated);
    if ($is_same_day) {
      $is_new_updated = false;
    }

    // Taxonomies
    $cats = get_the_terms($post_id, 'work_category');
    $tags = get_the_terms($post_id, 'work_tag');

    // Clients (NEW): main/end model
    // - Main client: single term id (nor_main_client_id)
    // - End clients: ordered list (nor_end_client_ids)
    $main_client_id = (int) get_post_meta($post_id, 'nor_main_client_id', true);
    $end_raw = get_post_meta($post_id, 'nor_end_client_ids', true);

    $end_client_ids = [];
    if (is_array($end_raw)) {
      $end_client_ids = array_values(array_filter(array_map('intval', $end_raw)));
    } elseif (is_string($end_raw) && trim($end_raw) !== '') {
      $end_client_ids = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $end_raw)))));
    }

    // Resolve terms (keep meta order)
    $main_client = null;
    if ($main_client_id > 0) {
      $t = get_term($main_client_id, 'work_client');
      if ($t && !is_wp_error($t) && $t instanceof WP_Term) {
        $main_client = $t;
      }
    }

    $end_clients = [];
    if (!empty($end_client_ids)) {
      foreach ($end_client_ids as $cid) {
        if ($cid <= 0) continue;
        if ($main_client && (int) $main_client->term_id === (int) $cid) continue;
        $t = get_term((int) $cid, 'work_client');
        if ($t && !is_wp_error($t) && $t instanceof WP_Term) {
          $end_clients[] = $t;
        }
      }
    }

    // Fallback: if Main is not set, use assigned taxonomy terms (WP default order)
    if (!$main_client) {
      $fallback_clients = get_the_terms($post_id, 'work_client');
      if (!empty($fallback_clients) && !is_wp_error($fallback_clients)) {
        $main_client = $fallback_clients[0] ?? null;
        $end_clients = array_slice($fallback_clients, 1, 3);
      }
    }

    // Display list: Main (1) + End (max 3) = max 4
    $clients = [];
    if ($main_client instanceof WP_Term) {
      $clients[] = $main_client;
    }
    if (!empty($end_clients)) {
      $clients = array_merge($clients, array_slice($end_clients, 0, 3));
    }
    $clients = array_slice($clients, 0, 4);

    // Industries: derived from MAIN client only (1 main client -> 1 industry)
    // work_client term meta key: nor_industry (stores work_industry term_id)
    $industries = [];
    if ($main_client instanceof WP_Term) {
      $iid = (int) get_term_meta($main_client->term_id, 'nor_industry', true);
      if ($iid > 0) {
        $it = get_term($iid, 'work_industry');
        if ($it && !is_wp_error($it) && $it instanceof WP_Term) {
          $industries = [ $it ];
        }
      }
    }

    // Legacy fallback: if any old works still have work_industry assigned directly
    if (empty($industries)) {
      $legacy = get_the_terms($post_id, 'work_industry');
      if (!empty($legacy) && !is_wp_error($legacy)) {
        $industries = [ $legacy[0] ];
      }
    }

    // Meta (Work)
    $tagline    = get_post_meta($post_id, 'nor_tagline', true);
    $summary_en = get_post_meta($post_id, 'nor_summary_en', true);
    $mode_raw   = get_post_meta($post_id, 'nor_content_mode', true);

    $tagline    = is_string($tagline) ? trim($tagline) : '';
    $summary_en = is_string($summary_en) ? trim($summary_en) : '';
    $mode_raw   = is_string($mode_raw) ? trim($mode_raw) : '';

    // Content mode
    // UI stores (history): text-only / full-content
    // We also accept: text, textonly, full, fullcontent
    $mode_norm = strtolower(trim((string) $mode_raw));
    $is_full = in_array($mode_norm, ['full-content', 'fullcontent', 'full'], true);
    $is_text = in_array($mode_norm, ['text-only', 'textonly', 'text'], true);

    // Default fallback: Text-only
    if (!$is_full && !$is_text) {
      $is_full = false;
    }

    $content_mode_label = $is_full ? 'Full-content' : 'Text-only';

    // Summary (JA) uses built-in Excerpt
    $summary_ja = get_the_excerpt();
    $summary_ja = is_string($summary_ja) ? trim($summary_ja) : '';

    if (function_exists('nor_get_work_public_text')) {
      // 'mixed' (not the bare default): nor_tagline is a JA/EN-mixed
      // free-text field, so it alone also needs the client's English-name
      // aliases layered on top of the usual Japanese masking. See
      // nor_get_post_work_client_public_name_map()'s docs — this must stay
      // off the plain 'default' path so it can't leak into $summary_ja
      // (Excerpt) or the Work title/breadcrumb/JSON-LD name, which are
      // Japanese-only contexts.
      $tagline = nor_get_work_public_text($post_id, $tagline, 'mixed');
      $summary_ja = nor_get_work_public_text($post_id, $summary_ja);
      $summary_en = nor_get_work_public_text($post_id, $summary_en, 'en');
    }

    // Tag groups (work_tag is hierarchical; group by top-level parent)
    $tag_group_labels = [
      'document-types' => 'Document types',
      'site-types'     => 'Site types',
      'roles'          => 'Roles',
      'tools'          => 'Tools',
    ];

    $tag_groups = [
      'document-types' => [],
      'site-types'     => [],
      'roles'          => [],
      'tools'          => [],
      '_other'         => [],
    ];

    if (!empty($tags) && !is_wp_error($tags)) {
      foreach ($tags as $t) {
        if (!$t instanceof WP_Term) continue;

        // Walk up to the root parent
        $root = $t;
        while ($root instanceof WP_Term && !empty($root->parent)) {
          $p = get_term((int) $root->parent, 'work_tag');
          if (!$p || is_wp_error($p) || !$p instanceof WP_Term) break;
          $root = $p;
        }

        $root_slug = $root instanceof WP_Term ? (string) $root->slug : '';

        // Do not list the root group term itself (e.g. "document-types") as an item.
        if ($t->slug === $root_slug) {
          continue;
        }

        if (isset($tag_groups[$root_slug])) {
          $tag_groups[$root_slug][] = $t;
        } else {
          $tag_groups['_other'][] = $t;
        }
      }

      // Sort within each group by name (stable, simple)
      foreach ($tag_groups as $k => $arr) {
        usort($arr, function ($a, $b) {
          $an = $a instanceof WP_Term ? (string) $a->name : '';
          $bn = $b instanceof WP_Term ? (string) $b->name : '';
          return strcmp($an, $bn);
        });
        $tag_groups[$k] = $arr;
      }
    }

    // Work number (for hero stats)
    // Use the admin-entered unique number stored in post meta `nor_work_no`.
    // Fallback to post ID when missing.
    $work_no_raw = get_post_meta($post_id, 'nor_work_no', true);
    $work_no_int = is_numeric($work_no_raw) ? (int) $work_no_raw : 0;
    if ($work_no_int <= 0) {
      $work_no_int = (int) $post_id;
    }

    // English ordinal suffix (1st, 2nd, 3rd, 4th ... 11th, 12th, 13th ...)
    $ordinal_suffix = function (int $n): string {
      $n = abs((int) $n);
      $mod100 = $n % 100;
      if ($mod100 >= 11 && $mod100 <= 13) return 'th';
      switch ($n % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
      }
    };

    $work_ordinal_unit = $ordinal_suffix($work_no_int) . ' work';

    // Gallery IDs (sub images)
    $gallery_raw = get_post_meta($post_id, 'nor_gallery_ids', true);
    $gallery_ids = [];
    if (is_array($gallery_raw)) {
      $gallery_ids = array_map('intval', $gallery_raw);
    } elseif (is_string($gallery_raw) && trim($gallery_raw) !== '') {
      $gallery_ids = array_map('intval', array_filter(array_map('trim', explode(',', $gallery_raw))));
    }

    // Determine main image:
    // - Prefer Featured image (post thumbnail)
    // - Fallback to the first gallery image (and remove it from gallery list)
    $main_image_id = 0;
    if (has_post_thumbnail($post_id)) {
      $main_image_id = (int) get_post_thumbnail_id($post_id);
    } elseif (!empty($gallery_ids)) {
      $main_image_id = (int) array_shift($gallery_ids);
    }

    // Attachment caption helpers
    $get_caption_pair = function (int $attachment_id): array {
      $cap_ja = wp_get_attachment_caption($attachment_id);
      $cap_ja = is_string($cap_ja) ? trim($cap_ja) : '';

      $cap_en = get_post_meta($attachment_id, 'nor_caption_en', true);
      $cap_en = is_string($cap_en) ? trim($cap_en) : '';

      return [$cap_ja, $cap_en];
    };

    // Home doubles as the Works index (page 1); /works/ itself 301s to Home
    // (see functions.php template_redirect), so link "back to Works" / the
    // breadcrumb root directly at Home instead of bouncing through /works/.
    $works_url = home_url('/');
    $work_public_title = function_exists('nor_get_work_public_title')
      ? nor_get_work_public_title($post_id, (string) get_the_title($post_id))
      : (string) get_the_title($post_id);

    // Hero (pages common)
    // - Title: Work title (HTML allowed for <cite>)
    // - Tagline: nor_tagline (post meta)
    // - Description: Summary JA (Excerpt) + Summary EN (post meta)
    // - Stats: total published Works
    // - Breadcrumbs: Home / Works / {Work}
    $hero_args = [
      // Title
      'title_html' => '<cite>' . esc_html($work_public_title) . '</cite>',
      'title'      => $work_public_title,
      'specific_tag' => 'hgroup',

      // Copy
      'tagline' => $tagline,
      'desc_ja' => $summary_ja,
      'desc_en' => $summary_en,

      // Stats
      // Show this entry as an ordinal number (e.g. 20th work)
      'count' => $work_no_int,
      'unit'  => $work_ordinal_unit,

      // Breadcrumbs
      // Home already doubles as the Works index, so it's the only ancestor
      // level here (no separate "Works" crumb pointing at /works/).
      'breadcrumbs' => nor_build_breadcrumbs([
        [
          'label'      => $work_public_title,
          'current'    => true,
          'label_html' => '<cite>' . esc_html($work_public_title) . '</cite>',
        ],
      ]),

      // Widget type (none)
      'widget' => 'none',
    ];
    echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>

  <section class="section content-works">
    <h2 class="visually-hidden"><span lang="ja">制作記録</span>（<span lang="en">Work details</span>）</h2>
    <div class="inner">

      <article class="card article">
        <h3 class="visually-hidden">Work entry</h3>

<?php if ($is_full && $main_image_id > 0) : ?>
<?php
    [$cap_ja, $cap_en] = $get_caption_pair($main_image_id);
    $main_img = wp_get_attachment_image($main_image_id, 'full', false, ['fetchpriority' => 'high']);
?>
<?php if ($main_img) : ?>
        <header class="head">
          <figure class="skeleton-figure">
            <?php echo $main_img . "\n"; ?>
<?php if ($cap_ja !== '' || $cap_en !== '') : ?>
            <figcaption>
              <p class="ja" lang="ja"><?php echo ($cap_ja !== '') ? nl2br(esc_html($cap_ja), false) : '—'; ?></p>
              <p class="en" lang="en"><?php echo ($cap_en !== '') ? nl2br(esc_html($cap_en), false) : '—'; ?></p>
            </figcaption>
<?php endif; ?>
          </figure>
        </header>
<?php endif; ?>
<?php endif; ?>

        <div class="body">
          <div class="detail<?php echo $is_full ? '' : ' textpair'; ?>">
<?php if ($is_full) : ?>
<?php
              // Gallery images (sub images).
              // Note: If no Featured image is set, the first gallery image is used as the main image.
?>
<?php if (!empty($gallery_ids)) : ?>
<?php foreach ($gallery_ids as $aid) : ?>
<?php
    $aid = (int) $aid;
    if ($aid <= 0) continue;
    [$cap_ja, $cap_en] = $get_caption_pair($aid);
    $img = wp_get_attachment_image($aid, 'full', false, ['loading' => 'lazy', 'decoding' => 'async']);
    if (!$img) continue;
?>
            <figure class="skeleton-figure">
              <?php echo $img . "\n"; ?>
<?php if ($cap_ja !== '' || $cap_en !== '') : ?>
              <figcaption>
                <p class="ja" lang="ja"><?php echo ($cap_ja !== '') ? nl2br(esc_html($cap_ja), false) : '—'; ?></p>
                <p class="en" lang="en"><?php echo ($cap_en !== '') ? nl2br(esc_html($cap_en), false) : '—'; ?></p>
              </figcaption>
<?php endif; ?>
            </figure>
<?php endforeach; ?>
<?php endif; ?>
<?php else : ?>
            <p class="ja" lang="ja">画像は許諾がある場合にのみ表示します。現在はテキストのみで記録しています。</p>
            <p class="en" lang="en">Visuals are shown only with permission. This entry is currently Text-only.</p>
<?php endif; ?>
          </div>

          <div class="aside">
            <dl class="meta">
              <dt>Published</dt>
              <dd><time datetime="<?php echo esc_attr($published_dt); ?>" class="value"><?php echo esc_html($published); ?></time><?php if ($is_new_published) : ?><span class="new"><small>New</small></span><?php endif; ?></dd>
              <dt>Updated</dt>
              <dd><time datetime="<?php echo esc_attr($updated_dt); ?>" class="value"><?php echo esc_html($updated); ?></time><?php if ($is_new_updated) : ?><span class="new"><small>New</small></span><?php endif; ?></dd>
              <dt>Categories</dt>
              <dd><?php if (!empty($cats) && !is_wp_error($cats)) : ?><span class="value"><a href="<?php echo esc_url(get_term_link($cats[0])); ?>"><?php echo nor_render_work_category_label($cats[0], 'inline'); ?></a></span><?php else : ?><span class="value">—</span><?php endif; ?></dd>
              <dt>Content mode</dt>
              <dd><?php echo esc_html($content_mode_label); ?></dd>
            </dl>
<?php
            // Types are exclusive: Document types OR Site types (prefer Document types)
            $primary_type_label = '';
            $primary_type_terms = [];

            if (!empty($tag_groups['document-types'])) {
              $primary_type_label = 'Document types';
              $primary_type_terms = $tag_groups['document-types'];
            } elseif (!empty($tag_groups['site-types'])) {
              $primary_type_label = 'Site types';
              $primary_type_terms = $tag_groups['site-types'];
            } else {
              $primary_type_label = 'Document types';
              $primary_type_terms = [];
            }
?>
            <div class="types-roles-tools">
              <dl class="types">
                <dt><?php echo esc_html($primary_type_label); ?></dt>
                <dd>
<?php if (!empty($primary_type_terms)) : ?>
                  <ul>
<?php foreach ($primary_type_terms as $t) : ?>
                    <li><span class="value"><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo nor_render_label_with_abbr((string) $t->name); ?></a></span></li>
<?php endforeach; ?>
                  </ul>
<?php else : ?>
                  <ul><li><span class="value">—</span></li></ul>
<?php endif; ?>
                </dd>
              </dl>
              <div class="roles-tools">
                <dl class="roles">
                  <dt>Roles</dt>
                  <dd>
<?php if (!empty($tag_groups['roles'])) : ?>
                    <ul>
<?php foreach (array_slice($tag_groups['roles'], 0, 4) as $t) : ?>
                      <li><span class="value"><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo nor_render_label_with_abbr((string) $t->name); ?></a></span></li>
<?php endforeach; ?>
                    </ul>
<?php else : ?>
                    <ul><li><span class="value">—</span></li></ul>
<?php endif; ?>
                  </dd>
                </dl>
                <dl class="tools">
                  <dt>Tools</dt>
                  <dd>
<?php if (!empty($tag_groups['tools'])) : ?>
                    <ul>
<?php foreach (array_slice($tag_groups['tools'], 0, 4) as $t) : ?>
                      <li><span class="value"><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo nor_render_work_tag_tool_label($t); ?></a></span></li>
<?php endforeach; ?>
                    </ul>
<?php else : ?>
                    <ul><li><span class="value">—</span></li></ul>
<?php endif; ?>
                  </dd>
                </dl>
              </div>
            </div>

            <div class="client-industries">
              <dl class="client">
                <dt>Clients</dt>
                <dd>
<?php if (!empty($clients) && !is_wp_error($clients)) : ?>
                  <ul>
<?php foreach (array_slice($clients, 0, 4) as $t) : ?>
<?php $client_name = function_exists('nor_get_term_public_name') ? nor_get_term_public_name($t, (string) $t->name) : (string) $t->name; ?>
                    <li><span class="value"><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo nor_render_label_with_abbr($client_name); ?></a></span></li>
<?php endforeach; ?>
                  </ul>
<?php else : ?>
                  <ul><li><span class="value">—</span></li></ul>
<?php endif; ?>
                </dd>
              </dl>
              <dl class="industries">
                <dt>Industries</dt>
                <dd>
<?php if (!empty($industries) && !is_wp_error($industries)) : ?>
                  <ul>
<?php foreach (array_slice($industries, 0, 1) as $t) : ?>
<?php
                      // work_industry's own taxonomy archive is retired (301s to
                      // /clients/index-by-industry/#client-industry-{slug}); link directly
                      // at the real destination instead of bouncing through it.
                      $industry_url = home_url('/clients/index-by-industry/#client-industry-' . $t->slug);
?>
                    <li><span class="value"><a href="<?php echo esc_url($industry_url); ?>"><?php echo nor_render_label_with_abbr((string) $t->name); ?></a></span></li>
<?php endforeach; ?>
                  </ul>
<?php else : ?>
                  <ul><li><span class="value">—</span></li></ul>
<?php endif; ?>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </article>
<?php
      // Adjacent works (not limited by category)
      // Note: do NOT pass an empty taxonomy string as the 4th argument; it can cause adjacent lookup to fail.
      $prev_post = get_adjacent_post(false, '', true);
      $next_post = get_adjacent_post(false, '', false);

      $prev_url = ($prev_post instanceof WP_Post) ? get_permalink($prev_post) : '';
      $next_url = ($next_post instanceof WP_Post) ? get_permalink($next_post) : '';

      // Detail pager (Works)
      $pagination_args = [
        'aria_label' => 'Works navigation',
        'back_url'   => $works_url,
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
    // Related sections
    // -------------------------

    // Helper: format work index number (3 digits by default)
    $format_work_no = function (int $pid): array {
      $raw = get_post_meta($pid, 'nor_work_no', true);
      $raw = is_string($raw) ? trim($raw) : '';
      $n = 0;
      if ($raw !== '' && ctype_digit($raw)) {
        $n = (int) $raw;
      } elseif (is_numeric($raw)) {
        $n = (int) $raw;
      } else {
        $n = (int) $pid;
      }
      $val = nor_format_seq_no($n);
      return [$val, '#' . $val];
    };

    // Helper: content mode label for a given post
    $get_content_mode_label = function (int $pid): string {
      $m = get_post_meta($pid, 'nor_content_mode', true);
      $m = is_string($m) ? strtolower(trim($m)) : '';
      $is_full = in_array($m, ['full-content', 'fullcontent', 'full'], true);
      $is_text = in_array($m, ['text-only', 'textonly', 'text'], true);

      if ($is_full) {
        return 'Full-content';
      }
      if ($is_text) {
        return 'Text-only';
      }

      return 'Text-only';
    };

    // Helper: NEW badge (Published within 30 days)
    $is_new_by_published = function (int $pid): bool {
      $ts = (int) get_post_time('U', true, $pid);

      if ($ts <= 0) {
        return false;
      }

      return nor_is_recent_timestamp((int) $ts, 30);
    };

    // Helper: render a related list item
    $render_related_item = function (int $pid) use ($format_work_no, $get_content_mode_label, $is_new_by_published) {
      $title = function_exists('nor_get_work_public_title')
        ? nor_get_work_public_title($pid, (string) get_the_title($pid))
        : (string) get_the_title($pid);
      $permalink = get_permalink($pid);
      $published = get_the_date('Y-m-d', $pid);
      $published_dt = get_the_date('c', $pid);
      $excerpt = get_the_excerpt($pid);
      // Raw line breaks are kept intact here (no \s+ collapse) so that
      // nor_inline_rich_text_apply_abbr_and_breaks() below — the same
      // pipeline used by the Works Index card — can apply its own
      // CJK-punctuation-aware line-join rule instead of this call site
      // flattening every line break to a plain space beforehand.
      $excerpt = is_string($excerpt) ? trim($excerpt) : '';
      if (function_exists('nor_get_work_public_text')) {
        $excerpt = nor_get_work_public_text($pid, $excerpt);
      }

      // Same allowlist-sanitize + dictionary-abbr pipeline as Works Index
      // (template-parts/card/card-works.php), so allowed inline markup
      // (<abbr>/<a>/<strong>/etc.) renders as real elements here too,
      // instead of being escaped to literal text by a plain esc_html().
      if (function_exists('nor_sanitize_inline_rich_text') && function_exists('nor_inline_rich_text_apply_abbr_and_breaks')) {
        $excerpt_safe = nor_sanitize_inline_rich_text($excerpt);
        $excerpt_html = ($excerpt_safe !== '')
          ? nor_inline_rich_text_apply_abbr_and_breaks(
              $excerpt_safe,
              function_exists('nor_get_abbreviation_map') ? nor_get_abbreviation_map() : [],
              false // convert_newlines_to_br = false: join as a single line, not <br>
            )
          : '';
      } else {
        $excerpt_html = esc_html($excerpt);
      }

      [$no_val, $no_label] = $format_work_no($pid);
      $mode_label = $get_content_mode_label($pid);
      $is_new = $is_new_by_published($pid);

      ?>
          <li>
            <div class="index-mode-meta">
              <ul class="index-mode">
                <li><data value="<?php echo esc_attr($no_val); ?>"><?php echo esc_html($no_label); ?></data></li>
                <li><?php echo esc_html($mode_label); ?></li>
              </ul>
              <ul class="meta">
                <li>Published: <time datetime="<?php echo esc_attr($published_dt); ?>" class="value"><?php echo esc_html($published); ?></time><?php if ($is_new) : ?><span class="new">New</span><?php endif; ?></li>
              </ul>
            </div>
            <div class="title-summary">
              <h3><cite class="value"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></cite></h3>
<?php if ($excerpt_html !== '') : ?>
              <p class="ja" lang="ja"><?php echo $excerpt_html; ?></p>
<?php else : ?>
              <p class="ja" lang="ja">—</p>
<?php endif; ?>
            </div>
          </li>
      <?php
    };

    $render_related_items = function (array $ids) use ($render_related_item) {
      foreach ($ids as $rid) {
        ob_start();
        $render_related_item((int) $rid);
        $item_html = (string) ob_get_clean();
        // Remove only blank lines at head/tail while keeping structural indentation.
        $item_html = (string) preg_replace('/\A(?:[ \t]*\R)+/u', '', $item_html);
        $item_html = (string) preg_replace('/(?:\R[ \t]*)+\z/u', '', $item_html);
        $item_html = nor_normalize_first_indent($item_html);
        if ($item_html === '') {
          continue;
        }

        echo (string) preg_replace('/^(?=.*\S)/m', '          ', $item_html) . "\n\n";
      }
    };

    $exclude_ids = [$post_id];

    // SAME CATEGORY (max 5)
    $same_category_ids = [];
    $primary_cat = (!empty($cats) && !is_wp_error($cats) && $cats[0] instanceof WP_Term) ? $cats[0] : null;
    if ($primary_cat instanceof WP_Term) {
      $same_category_ids = nor_get_related_work_ids_by_terms(
        'work_category',
        [(int) $primary_cat->term_id],
        $exclude_ids,
        5,
        true
      );

      if (!empty($same_category_ids)) {
        $exclude_ids = array_values(array_unique(array_merge($exclude_ids, $same_category_ids)));
      }
    }

    // RELATED BY TAGS (max 2)
    $related_tag_ids = [];
    $tag_ids = [];
    if (!empty($tags) && !is_wp_error($tags)) {
      foreach ($tags as $t) {
        if ($t instanceof WP_Term) {
          $tag_ids[] = (int) $t->term_id;
        }
      }
      $tag_ids = array_values(array_unique(array_filter($tag_ids)));
    }

    if (!empty($tag_ids)) {
      $related_tag_ids = nor_get_related_work_ids_by_terms(
        'work_tag',
        $tag_ids,
        $exclude_ids,
        2,
        true
      );

      if (!empty($related_tag_ids)) {
        $exclude_ids = array_values(array_unique(array_merge($exclude_ids, $related_tag_ids)));
      }
    }

    // SAME CLIENT (max 2) - based on MAIN client if available
    $same_client_ids = [];
    $primary_client = ($main_client instanceof WP_Term) ? $main_client : null;
    if ($primary_client instanceof WP_Term) {
      $same_client_ids = nor_get_related_work_ids_by_terms(
        'work_client',
        [(int) $primary_client->term_id],
        $exclude_ids,
        2,
        true
      );
    }
?>
<?php if (!empty($same_category_ids) && $primary_cat instanceof WP_Term) : ?>
<?php
  echo nor_render_template_part('template-parts/card/card-related', null, [
    'title'            => 'Same category',
    'description'      => 'Other works in ' . $primary_cat->name . '.',
    'description_html' => 'Other works in ' . nor_render_label_with_abbr((string) $primary_cat->name) . '.',
    'ids'          => $same_category_ids,
    'render_items' => $render_related_items,
  ], [
    'trim'                   => 'both',
    'normalize_first_indent' => true,
    'indent'                 => 2,
    'suffix'                 => "\n\n",
  ]);
?>
<?php endif; ?>

<?php if (!empty($related_tag_ids)) : ?>
<?php
  echo nor_render_template_part('template-parts/card/card-related', null, [
    'title'        => 'Related by tags',
    'description'  => 'Other works sharing these tags.',
    'ids'          => $related_tag_ids,
    'render_items' => $render_related_items,
  ], [
    'trim'                   => 'both',
    'normalize_first_indent' => true,
    'indent'                 => 2,
    'suffix'                 => "\n\n",
  ]);
?>
<?php endif; ?>

<?php if (!empty($same_client_ids) && $primary_client instanceof WP_Term) : ?>
<?php
  $same_client_label = function_exists('nor_get_term_public_name')
    ? nor_get_term_public_name($primary_client, (string) $primary_client->name)
    : (string) $primary_client->name;
  echo nor_render_template_part('template-parts/card/card-related', null, [
    'title'            => 'Same client',
    'description'      => 'Other works for ' . $same_client_label . '.',
    'description_html' => 'Other works for ' . nor_render_label_with_abbr($same_client_label) . '.',
    'ids'          => $same_client_ids,
    'render_items' => $render_related_items,
  ], [
    'trim'                   => 'both',
    'normalize_first_indent' => true,
    'indent'                 => 2,
    'suffix'                 => "\n\n",
  ]);
?>
<?php endif;
    // -------------------------
    // Indices (global navigation)
    // -------------------------
    // Shared partial (all pages except 5xx-error)
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
