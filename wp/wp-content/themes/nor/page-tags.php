<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php
    // ===== Page meta / hero (Tags landing) =====
    $page_id = get_queried_object_id();

    // ===== Tag group definition (fixed order) =====
    // Tag groups are top-level terms in `work_tag` taxonomy.
    // Children terms live under each group.
    $tag_group_defs = nor_get_work_tag_group_definitions();

    $groups = [];
    $group_idx = 0;
    foreach (is_array($tag_group_defs) ? $tag_group_defs : [] as $def) {
      if (!is_array($def)) continue;

      $slug = isset($def['slug']) && is_string($def['slug']) ? trim((string) $def['slug']) : '';
      $label = isset($def['label']) && is_string($def['label']) ? trim((string) $def['label']) : '';
      if ($slug === '' || $label === '') continue;

      $group_idx++;
      $groups[] = [
        'idx'           => str_pad((string) $group_idx, 2, '0', STR_PAD_LEFT),
        'slug'          => $slug,
        'label'         => $label,
        'card_id'       => (isset($def['card_id']) && is_string($def['card_id']) && trim((string) $def['card_id']) !== '')
          ? trim((string) $def['card_id'])
          : ('tag-group-' . $slug),
        'aria'          => (isset($def['card_aria']) && is_string($def['card_aria']) && trim((string) $def['card_aria']) !== '')
          ? trim((string) $def['card_aria'])
          : $label,
        'split'         => !empty($def['split']),
        'title_link'    => !empty($def['title_link']),
        'more_disabled' => !empty($def['more_disabled']),
      ];
    }

    $tag_group_count = is_array($groups) ? count($groups) : 0;

    $landing = nor_get_landing_page_shell_args((int) $page_id, [
      'count' => (int) $tag_group_count,
      'unit' => nor_format_count_unit((int) $tag_group_count, 'tag group', 'tag groups'),
    ]);
    $hero_args = is_array($landing['hero_args'] ?? null) ? $landing['hero_args'] : [];
    $section_h2_ja = (string) ($landing['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($landing['section_h2_en'] ?? '—');

    // ===== Hero (pages common) =====
    echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'left',
      'indent' => 2,
    ]);

    // term meta key used for English description (added by theme)
    $EN_META_KEY = 'nor_desc_en';

    $get_group_term = function(string $slug) {
      return nor_get_work_tag_group_term($slug);
    };

    $get_en_desc = function($term) use ($EN_META_KEY) {
      if (!$term || !($term instanceof WP_Term)) return '';
      return nor_get_term_meta_text((int) $term->term_id, $EN_META_KEY);
    };

    // Helper: get children terms for a group (in work_tag).
    $get_children = function(int $parent_id) {
      return nor_get_work_tag_group_children_by_parent($parent_id, false);
    };

    // Helper: count published works that have ANY of the given work_tag term IDs.
    $count_posts_for_terms = function(array $term_ids) {
      return nor_count_published_works_for_terms('work_tag', $term_ids, true);
    };

    // Helper: last updated (latest published work) for a set of term IDs.
    $last_updated_for_terms = function(array $term_ids) {
      return nor_get_latest_published_work_for_terms('work_tag', $term_ids, true);
    };

    $render_term_label = function($term, bool $is_tools_group) {
      if (!$term || is_wp_error($term) || !($term instanceof WP_Term)) return '';
      if (!$is_tools_group) return nor_render_label_with_abbr((string) $term->name);
      return nor_render_work_tag_tool_label($term);
    };

    $split_title = function(string $label) {
      // Split into two lines at first space (e.g., "Document types")
      $label = trim($label);
      $parts = preg_split('/\s+/', $label, 2);
      if (is_array($parts) && count($parts) === 2) {
        return '<span class="character-line">' . esc_html($parts[0] . ' ') . '</span><span class="character-line">' . esc_html($parts[1]) . '</span>';
      }
      return '<span class="character-line">' . esc_html($label) . '</span>';
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

    // More link: go to the tag-group term archive in work_tag (/tags/{group-slug}/)
    $more_href_for_group = function($group_term) {
      if ($group_term && !is_wp_error($group_term) && $group_term instanceof WP_Term) {
        $u = get_term_link($group_term);
        if (!is_wp_error($u)) return (string) $u;
      }
      return '';
    };
  ?>

<?php
  $list_body_html = '';
  foreach ($groups as $g) {
    $group_term = $get_group_term($g['slug']);
    // Raw context: get_terms()'s own ->description can come back through
    // display-time filtering (which strips markup like <abbr>), so the B
    // renderer below must start from the actual stored value instead.
    $jp_desc = '';
    if ($group_term instanceof WP_Term) {
      $jp_desc_raw = get_term_field('description', (int) $group_term->term_id, $group_term->taxonomy, 'raw');
      $jp_desc = is_wp_error($jp_desc_raw) ? '' : (string) $jp_desc_raw;
    }
    $en_desc = $get_en_desc($group_term);

    $children = $get_children($group_term ? (int) $group_term->term_id : 0);
    $child_ids = [];
    if (!empty($children)) {
      foreach ($children as $ct) {
        if ($ct instanceof WP_Term) $child_ids[] = (int) $ct->term_id;
      }
    }
    $count_posts = $count_posts_for_terms($child_ids);
    $lu = $last_updated_for_terms($child_ids);
    $more_href = $more_href_for_group($group_term);

    $list_body_html .= nor_render_template_part('template-parts/card/card-taxonomy', null, [
      'num'               => (string) $g['idx'],
      'jp_desc'           => $jp_desc,
      'en_desc'           => $en_desc,
      'projects'          => $count_posts,
      'lu'                => $lu,
      'card_id'           => (string) $g['card_id'],
      'card_label'        => 'Tag group',
      'term_url'          => $more_href,
      'aria_more'         => sprintf('View all works in “%s”', (string) $g['label']),
      'title_html'        => !empty($g['split']) ? $split_title((string) $g['label']) : '<span class="character-line">' . esc_html((string) $g['label']) . '</span>',
      'title_link'        => !empty($g['title_link']),
      'children'          => $children,
      'children_aria'     => (string) $g['aria'],
      'more_disabled'     => !empty($g['more_disabled']),
      'render_term_label' => function($term) use ($render_term_label, $g) {
        $is_tools_group = ((string) $g['slug'] === 'tools');
        return $render_term_label($term, $is_tools_group);
      },
      'render_desc'       => $render_desc,
    ], [
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 4,
      'suffix' => "\n\n",
    ]);
  }
  echo nor_render_list_section_shell([
    'section_class' => 'list-tags',
    'section_h2_ja' => $section_h2_ja,
    'section_h2_en' => $section_h2_en,
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
