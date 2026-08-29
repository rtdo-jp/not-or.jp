<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    $page_id = get_queried_object_id();

    $get_meta = function (string $key) use ($page_id): string {
      return nor_get_post_meta_text((int) $page_id, $key);
    };

    $allowed_html = nor_policies_allowed_html();

    // ===== Hero =====
    $content_ctx = nor_get_content_page_hero_context((int) $page_id, [
      'title_fallback' => 'Policies',
      'section_h2_fallback' => '—',
      'unit' => nor_format_count_unit(nor_get_published_works_count(), 'work', 'works', 'archived'),
      'widget' => 'none',
    ]);
    $title = (string) ($content_ctx['title'] ?? 'Policies');
    $tagline = (string) ($content_ctx['tagline'] ?? '');
    $desc_ja = (string) ($content_ctx['desc_ja'] ?? '');
    $desc_en = (string) ($content_ctx['desc_en'] ?? '');

    $updated_date = $get_meta('nor_policies_last_updated');
    $updated_datetime = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $updated_date) === 1) ? $updated_date : '';

    $section_h2_ja = (string) ($content_ctx['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($content_ctx['section_h2_en'] ?? '—');

    $groups = nor_policies_get_groups((int) $page_id, false);

    if (!is_array($groups) || empty($groups)) {
      $groups = [
        [
          'group_id' => 'policies-publishing-and-rights',
          'group_title_en' => 'Publishing & Rights',
          'group_name_ja' => '公開と権利',
          'items' => [],
        ],
        [
          'group_id' => 'policies-group-operations-and-security',
          'group_title_en' => 'Operations & Security',
          'group_name_ja' => '運用とセキュリティ',
          'items' => [],
        ],
        [
          'group_id' => 'policies-group-legal-framework',
          'group_title_en' => 'Legal Framework',
          'group_name_ja' => '法的枠組み',
          'items' => [],
        ],
      ];
    }

    $hero_args = is_array($content_ctx['hero_args'] ?? null) ? $content_ctx['hero_args'] : [
      'count' => nor_get_published_works_count(),
      'unit' => nor_format_count_unit(nor_get_published_works_count(), 'work', 'works', 'archived'),
      'title' => $title,
      'tagline' => $tagline,
      'desc_ja' => $desc_ja,
      'desc_en' => $desc_en,
      'widget' => 'none',
      'breadcrumbs' => nor_build_single_breadcrumb($title),
    ];
    $hero_args['tagline_fallback'] = '—';
    $hero_args['desc_fallback'] = '—';
    $hero_args['updated_date'] = $updated_date;
    $hero_args['updated_datetime'] = $updated_datetime;
    $hero_args['updated_prefix'] = 'Last updated:';

    echo nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>

  <section class="section content-pages content-policies">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($section_h2_ja); ?></span> / <span lang="en"><?php echo esc_html($section_h2_en); ?></span></h2>
    <div class="inner">

<?php foreach ($groups as $g_idx => $group) : ?>
<?php
      $group_id = isset($group['group_id']) ? sanitize_title((string) $group['group_id']) : '';
      if ($group_id === '') $group_id = 'policies-group-' . ((int) $g_idx + 1);

      $group_title_en = isset($group['group_title_en']) ? trim((string) $group['group_title_en']) : '';
      $group_name_ja  = isset($group['group_name_ja']) ? trim((string) $group['group_name_ja']) : '';

      $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
      $clean_items = [];
      $seq = 0;

      foreach ($items as $item) {
        if (!is_array($item)) continue;

        $seq++;
        $title_en = isset($item['title_en']) ? sanitize_text_field((string) $item['title_en']) : '';

        $anchor_raw = isset($item['anchor']) ? (string) $item['anchor'] : '';
        $anchor = sanitize_title($anchor_raw);
        if ($anchor === '' && $title_en !== '') {
          $anchor = 'policies-heading-' . sanitize_title($title_en);
        }
        if ($anchor === '') {
          $anchor = 'policies-heading-item-' . $seq;
        }

        $column = isset($item['column']) ? (int) $item['column'] : 0;
        if ($column < 1 || $column > 3) $column = 0;

        $position = isset($item['position']) ? (int) $item['position'] : 0;
        if ($position < 0) $position = 0;

        $clean_items[] = [
          'title_en'   => $title_en,
          'anchor'     => $anchor,
          'column'     => $column,
          'position'   => $position,
          '__idx'      => $seq,
          'summary_ja' => isset($item['summary_ja']) ? (string) $item['summary_ja'] : '',
          'summary_en' => isset($item['summary_en']) ? (string) $item['summary_en'] : '',
          'body_ja'    => isset($item['body_ja']) ? (string) $item['body_ja'] : '',
          'body_en'    => isset($item['body_en']) ? (string) $item['body_en'] : '',
        ];
      }

      $index_titles = array_values(array_filter(array_map(
        static fn(array $it): string => trim((string) ($it['title_en'] ?? '')),
        $clean_items
      )));

      $index_aria = 'Policies index';
      if (!empty($index_titles)) {
        $index_aria .= ': ' . implode(', ', $index_titles) . '.';
      }

      $sort_items = static function (array $a, array $b): int {
        $pa = isset($a['position']) ? (int) $a['position'] : 0;
        $pb = isset($b['position']) ? (int) $b['position'] : 0;
        if ($pa !== $pb) return $pa <=> $pb;

        $ia = isset($a['__idx']) ? (int) $a['__idx'] : 0;
        $ib = isset($b['__idx']) ? (int) $b['__idx'] : 0;
        return $ia <=> $ib;
      };

      $columns = [[], [], []];
      $auto_items = [];

      foreach ($clean_items as $item) {
        $col = isset($item['column']) ? (int) $item['column'] : 0;
        if ($col >= 1 && $col <= 3) {
          $columns[$col - 1][] = $item;
        } else {
          $auto_items[] = $item;
        }
      }

      usort($auto_items, $sort_items);

      foreach ($auto_items as $item) {
        $target = 0;
        $min = count($columns[0]);
        for ($i = 1; $i < 3; $i++) {
          $c = count($columns[$i]);
          if ($c < $min) {
            $min = $c;
            $target = $i;
          }
        }
        $columns[$target][] = $item;
      }

      for ($i = 0; $i < 3; $i++) {
        usort($columns[$i], $sort_items);
      }
?>
<?php if ((int) $g_idx > 0) echo "\n"; ?>
      <section class="content policies">
        <header class="head">
          <h3 id="<?php echo esc_attr($group_id); ?>" class="visually-hidden"><?php echo esc_html($group_title_en); ?></h3>
          <nav class="index" aria-label="<?php echo esc_attr($index_aria); ?>">
            <ul>
<?php foreach ($clean_items as $index_item) : ?>
              <li><span class="value"><a href="#<?php echo esc_attr($index_item['anchor']); ?>"><?php echo esc_html($index_item['title_en']); ?></a></span></li>
<?php endforeach; ?>
            </ul>
          </nav>
        </header>
        <div class="body">
<?php for ($col = 0; $col < 3; $col++) : ?>
          <div class="column-<?php echo esc_attr((string) ($col + 1)); ?>">

<?php foreach ($columns[$col] as $item) : ?>
<?php
              $body_ja_html = trim(nor_render_rich_block((string) $item['body_ja'], $allowed_html), "\r\n");
              $body_en_html = trim(nor_render_rich_block((string) $item['body_en'], $allowed_html), "\r\n");
?>
            <article>
              <header class="title">
                <h3 id="<?php echo esc_attr($item['anchor']); ?>" lang="en"><span class="character-line"><?php echo esc_html($item['title_en']); ?></span></h3>
              </header>
              <div class="detail">
                <div class="textpair">
                  <p class="ja" lang="ja"><?php echo nor_render_rich_inline((string) $item['summary_ja'], $allowed_html, true); ?></p>
                  <p class="en" lang="en"><?php echo nor_render_rich_inline((string) $item['summary_en'], $allowed_html, true); ?></p>
                </div>
              </div>
              <div class="main">
                <div class="ja" lang="ja">
<?php if ($body_ja_html !== '') : ?>
<?php echo (string) preg_replace('/^(?=.*\S)/m', '                  ', $body_ja_html) . "\n"; ?>
<?php endif; ?>
                </div>
                <div class="en" lang="en">
<?php if ($body_en_html !== '') : ?>
<?php echo (string) preg_replace('/^(?=.*\S)/m', '                  ', $body_en_html) . "\n"; ?>
<?php endif; ?>
                </div>
              </div>
            </article>

<?php endforeach; ?>
          </div>
<?php endfor; ?>
          <p class="group-name"><?php echo esc_html($group_name_ja); ?></p>
        </div>
      </section>
<?php endforeach; ?>

<?php
      echo nor_render_template_part('template-parts/see-also', null, ['current_slug' => 'policies'], [
        'trim' => 'both',
        'indent' => 6,
        'suffix' => "\n",
      ]);
?>

    </div>
  </section>
</main>

<?php get_footer(); ?>
