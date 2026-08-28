<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    $page_id = get_queried_object_id();

    $allowed_html = nor_faqs_allowed_html();

    $content_ctx = nor_get_content_page_hero_context((int) $page_id, [
      'title_fallback' => 'FAQs',
      'section_h2_fallback' => '—',
      'unit' => 'works archived.',
      'widget' => 'none',
    ]);
    $title = (string) ($content_ctx['title'] ?? 'FAQs');
    $tagline = (string) ($content_ctx['tagline'] ?? '');
    $desc_ja = (string) ($content_ctx['desc_ja'] ?? '');
    $desc_en = (string) ($content_ctx['desc_en'] ?? '');
    $section_h2_ja = (string) ($content_ctx['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($content_ctx['section_h2_en'] ?? '—');

    $sections = nor_faqs_get_sections((int) $page_id);
    if (!is_array($sections)) $sections = [];

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

  <section class="section content-pages content-faqs">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($section_h2_ja); ?></span>（<span lang="en"><?php echo esc_html($section_h2_en); ?></span>）</h2>
    <div class="inner">

<?php foreach ($sections as $index => $section) : ?>
<?php
      if (!is_array($section)) continue;
      $title_en = isset($section['title_en']) ? trim((string) $section['title_en']) : '';
      $title_ja = isset($section['title_ja']) ? trim((string) $section['title_ja']) : '';
      if ($title_en === '' && $title_ja === '') continue;
      if ($title_en === '') $title_en = $title_ja;
      if ($title_ja === '') $title_ja = $title_en;

      $qas = isset($section['qas']) && is_array($section['qas']) ? $section['qas'] : [];
      $display_index = str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT);
?>
<?php if ((int) $index > 0) echo "\n"; ?>
      <section class="content faqs">
        <header class="head">
          <p class="index"><span class="value"><?php echo esc_html($display_index); ?></span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2><span class="character-line"><?php echo esc_html($title_en); ?></span></h2>
            <p class="ja" lang="ja"><?php echo esc_html($title_ja); ?></p>
          </header>
          <div class="detail">
<?php foreach ($qas as $qa_idx => $qa) : ?>
<?php
              if (!is_array($qa)) continue;
              $q_ja = isset($qa['q_ja']) ? trim((string) $qa['q_ja']) : '';
              $q_en = isset($qa['q_en']) ? trim((string) $qa['q_en']) : '';
              $a_ja = isset($qa['a_ja']) ? trim((string) $qa['a_ja']) : '';
              $a_en = isset($qa['a_en']) ? trim((string) $qa['a_en']) : '';
              if (($q_ja === '' && $q_en === '') || ($a_ja === '' && $a_en === '')) continue;
?>
<?php if ((int) $qa_idx > 0) echo "\n"; ?>
            <div class="faq-qa">
              <div class="textpair question">
                <p class="ja" lang="ja"><span class="visually-hidden">Question: </span><?php echo nor_render_rich_inline($q_ja, $allowed_html); ?></p>
                <p class="en" lang="en"><span class="visually-hidden">Question: </span><?php echo nor_render_rich_inline($q_en, $allowed_html); ?></p>
              </div>
              <div class="textpair answer">
                <p class="ja" lang="ja"><span class="visually-hidden">Answer: </span><?php echo nor_render_rich_inline($a_ja, $allowed_html); ?></p>
                <p class="en" lang="en"><span class="visually-hidden">Answer: </span><?php echo nor_render_rich_inline($a_en, $allowed_html); ?></p>
              </div>
            </div>
<?php endforeach; ?>
          </div>
        </div>
      </section>
<?php endforeach; ?>

<?php
      echo nor_render_template_part('template-parts/see-also', null, [
        'current_slug' => 'faqs',
        'targets' => ['policies', 'notes', 'contact'],
      ], [
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
