<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    $page_id = get_queried_object_id();

    $allowed_html = nor_notes_allowed_html();

    $content_ctx = nor_get_content_page_hero_context((int) $page_id, [
      'title_fallback' => 'Notes',
      'section_h2_fallback' => '—',
      'unit' => 'works archived.',
      'widget' => 'none',
    ]);
    $title = (string) ($content_ctx['title'] ?? 'Notes');
    $tagline = (string) ($content_ctx['tagline'] ?? '');
    $desc_ja = (string) ($content_ctx['desc_ja'] ?? '');
    $desc_en = (string) ($content_ctx['desc_en'] ?? '');
    $section_h2_ja = (string) ($content_ctx['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($content_ctx['section_h2_en'] ?? '—');

    $majors = nor_notes_get_majors((int) $page_id, false);
    if (!is_array($majors)) $majors = [];

    $hero_args = is_array($content_ctx['hero_args'] ?? null) ? $content_ctx['hero_args'] : [
      'count' => nor_get_published_works_count(),
      'unit' => 'works archived.',
      'title' => $title,
      'tagline' => $tagline,
      'desc_ja' => $desc_ja,
      'desc_en' => $desc_en,
      'widget' => 'none',
      'breadcrumbs' => nor_build_single_breadcrumb($title),
    ];

    echo nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>

  <section class="section content-pages content-notes">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($section_h2_ja); ?></span>（<span lang="en"><?php echo esc_html($section_h2_en); ?></span>）</h2>
    <div class="inner">

<?php foreach ($majors as $m_idx => $major) : ?>
<?php
      if (!is_array($major)) continue;
      $major_title_en = isset($major['major_title_en']) ? trim((string) $major['major_title_en']) : '';
      if ($major_title_en === '') $major_title_en = 'Section ' . ((int) $m_idx + 1);
      $major_id = isset($major['major_id']) ? sanitize_title((string) $major['major_id']) : '';
      if ($major_id === '') $major_id = 'major-heading-' . sanitize_title($major_title_en);

      $summary_ja = isset($major['summary_ja']) ? (string) $major['summary_ja'] : '';
      $summary_en = isset($major['summary_en']) ? (string) $major['summary_en'] : '';
      $middles = isset($major['middles']) && is_array($major['middles']) ? $major['middles'] : [];
?>
<?php if ((int) $m_idx > 0) echo "\n"; ?>
      <section class="content notes major">
        <header class="head">
          <h3 class="index" id="<?php echo esc_attr($major_id); ?>"><span class="value"><?php echo esc_html($major_title_en); ?></span></h3>
        </header>
        <div class="body">
          <div class="textpair">
            <p class="ja" lang="ja"><?php echo nor_render_rich_inline($summary_ja, $allowed_html); ?></p>
            <p class="en" lang="en"><?php echo nor_render_rich_inline($summary_en, $allowed_html); ?></p>
          </div>

<?php foreach ($middles as $md_idx => $middle) : ?>
<?php
            if (!is_array($middle)) continue;
            $middle_title_en = isset($middle['middle_title_en']) ? trim((string) $middle['middle_title_en']) : '';
            if ($middle_title_en === '') $middle_title_en = 'Group ' . ((int) $md_idx + 1);
            $middle_id = isset($middle['middle_id']) ? sanitize_title((string) $middle['middle_id']) : '';
            $items = isset($middle['items']) && is_array($middle['items']) ? $middle['items'] : [];
?>
<?php if ((int) $md_idx > 0) echo "\n"; ?>
          <section class="middle">
            <header class="title">
              <h4<?php echo $middle_id !== '' ? ' id="' . esc_attr($middle_id) . '"' : ''; ?>><span class="value"><?php echo esc_html($middle_title_en); ?></span></h4>
            </header>
            <div class="minor">
<?php foreach ($items as $item) : ?>
<?php
                  if (!is_array($item)) continue;
                  $label = isset($item['label']) ? (string) $item['label'] : '';
                  $item_desc_ja = isset($item['desc_ja']) ? (string) $item['desc_ja'] : '';
                  $item_desc_en = isset($item['desc_en']) ? (string) $item['desc_en'] : '';
                  if (trim($label) === '' && trim($item_desc_ja) === '' && trim($item_desc_en) === '') continue;
?>
              <dl>
                <dt><span class="value"><?php echo nor_render_rich_label($label, $allowed_html); ?></span></dt>
                <dd class="ja" lang="ja"><?php echo nor_render_rich_inline($item_desc_ja, $allowed_html); ?></dd>
                <dd class="en" lang="en"><?php echo nor_render_rich_inline($item_desc_en, $allowed_html); ?></dd>
              </dl>
<?php endforeach; ?>
            </div>
          </section>
<?php endforeach; ?>
        </div>
      </section>
<?php endforeach; ?>

<?php
      echo nor_render_template_part('template-parts/see-also', null, ['current_slug' => 'notes'], [
        'trim' => 'both',
        'indent' => 6,
        'suffix' => "\n",
      ]);
?>

    </div>
  </section>

<?php
    echo nor_render_indices([
      'trim' => 'both',
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>
</main>

<?php get_footer(); ?>
