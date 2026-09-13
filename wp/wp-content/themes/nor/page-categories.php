<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php
    // ===== Page meta / hero (Categories landing) =====
    $page_id = get_queried_object_id();

    // ===== Categories: ABC order =====
    $terms = get_terms([
      'taxonomy'   => 'work_category',
      'hide_empty' => false,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ]);

    // Force "Uncategorized" to be listed last (while keeping others in ABC order)
    if (is_array($terms) && !empty($terms)) {
      usort($terms, function ($a, $b) {
        if (!$a || !$b || is_wp_error($a) || is_wp_error($b)) return 0;

        $a_is_uncat = ($a->slug === 'uncategorized');
        $b_is_uncat = ($b->slug === 'uncategorized');

        if ($a_is_uncat && !$b_is_uncat) return 1;
        if (!$a_is_uncat && $b_is_uncat) return -1;

        // Keep default ABC behavior
        return strcmp($a->name, $b->name);
      });
    }

    $category_count = is_array($terms) ? count($terms) : 0;

    // Keep admin input plain text; enrich only output semantics.
    // Hero desc_ja/desc_en are abbr-enriched by default from the common
    // dictionary (nor_get_abbreviation_map()) when no *_html override is
    // supplied — no per-page abbr map needed here.
    $landing = nor_get_landing_page_shell_args((int) $page_id, [
      'count' => (int) $category_count,
      'unit' => nor_format_count_unit((int) $category_count, 'category', 'categories', 'listed'),
    ]);
    $hero_args = is_array($landing['hero_args'] ?? null) ? $landing['hero_args'] : [];
    $page_h2_ja = (string) ($landing['section_h2_ja'] ?? '—');
    $page_h2_en = (string) ($landing['section_h2_en'] ?? '—');

    // Category title rendering helper
    // - UI/UX: abbr
    // - Video Production: "Video " / "Production"
    // - Presentations & Documents: "Presentations & " / "Documents"
    $render_category_name = function($term) {
      if (!$term || is_wp_error($term)) return '';

      return nor_render_work_category_label($term, 'card');
    };

    $get_projects_count = function($term_id) {
      return nor_count_published_works_for_terms('work_category', [(int) $term_id], true);
    };

    $get_last_updated = function($term_id) {
      return nor_get_latest_published_work_for_terms('work_category', [(int) $term_id], true);
    };

    // B "Inline rich text": card-taxonomy.php's description paragraphs are
    // not wrapped in an outer <a> (only the title is), so <a> stays a real
    // link here. Plain <br> (not the hero's responsive "desktop tablet"
    // class) to keep the existing line-break layout unchanged.
    $desc_abbr_map = function_exists('nor_get_abbreviation_map') ? nor_get_abbreviation_map() : [];
    $render_desc = function (string $raw) use ($desc_abbr_map): string {
      return function_exists('nor_render_inline_rich_text')
        ? nor_render_inline_rich_text($raw, $desc_abbr_map, '', false, '<br/>')
        : nor_render_desc_with_br($raw);
    };
    // Pages hero (shared): indent output by 2 spaces to match source formatting style.
    echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'left',
      'indent' => 2,
    ]);
  ?>

<?php
  $list_body_html = '';
  $i = 0;
  foreach (is_array($terms) ? $terms : [] as $t) {
    if (!$t || is_wp_error($t)) continue;
    $i++;

    $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    // Raw context: get_terms()'s own ->description can come back through
    // display-time filtering (which strips markup like <abbr>), so the B
    // renderer below must start from the actual stored value instead.
    $jp_desc_raw = get_term_field('description', (int) $t->term_id, $t->taxonomy, 'raw');
    $jp_desc = is_wp_error($jp_desc_raw) ? '' : (string) $jp_desc_raw;
    $en_desc = nor_get_term_desc_en((int) $t->term_id); // custom meta (EN)
    $projects = $get_projects_count($t->term_id);
    $lu = $get_last_updated($t->term_id);
    $term_url = get_term_link($t);
    $term_url = is_wp_error($term_url) ? '' : (string) $term_url;
    $aria_more = sprintf('View all works in “%s”', $t->name);

    $list_body_html .= nor_render_template_part('template-parts/card/card-taxonomy', null, [
      'term'                 => $t,
      'num'                  => $num,
      'jp_desc'              => $jp_desc,
      'en_desc'              => $en_desc,
      'projects'             => $projects,
      'lu'                   => $lu,
      'term_url'             => $term_url,
      'aria_more'            => $aria_more,
      // helpers (match existing behavior)
      'render_category_name' => $render_category_name,
      'render_desc'          => $render_desc,
    ], [
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 4,
      'suffix' => "\n\n",
    ]);
  }
  echo nor_render_list_section_shell([
    'section_class' => 'list-categories',
    'section_h2_ja' => $page_h2_ja,
    'section_h2_en' => $page_h2_en,
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
  ], true);
?>

</main>

<?php get_footer(); ?>
