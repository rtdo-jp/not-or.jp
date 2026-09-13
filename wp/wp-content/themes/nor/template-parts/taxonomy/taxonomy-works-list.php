<?php
/**
 * Template part: Common taxonomy works list.
 *
 * Args:
 * - root_label (string)       Breadcrumb 2nd label (e.g. Categories, Tags, Clients)
 * - root_url (string)         Breadcrumb 2nd URL
 * - back_to_list_url (string) Back-to-list URL
 * - append_query_args (array) Query args appended to pagination URLs (e.g. ['from' => 'index-by-initial'])
 * - card_newline_count (int)  Newline count after each rendered card
 *
 * NOTE: tagline/desc_ja/desc_en are plain text passed to hero-pages.php as-is
 * (that template applies the common abbreviation dictionary uniformly when
 * rendering plain text, so no per-caller abbr wiring is needed here) EXCEPT
 * for work_category/work_tag, where all three are B "Inline rich text"
 * (tagline_html/desc_ja_html/desc_en_html, pre-rendered here via
 * nor_render_inline_rich_text()) — work_client's tagline/desc_ja/desc_en are
 * untouched and still go through hero-pages.php's plain-text path.
 */

$args = isset($args) && is_array($args) ? $args : [];

$root_label = isset($args['root_label']) ? (string) $args['root_label'] : 'Categories';
$root_url = isset($args['root_url']) ? (string) $args['root_url'] : home_url('/categories/');
$back_to_list_url = isset($args['back_to_list_url']) ? (string) $args['back_to_list_url'] : $root_url;

$append_query_args = isset($args['append_query_args']) && is_array($args['append_query_args'])
  ? $args['append_query_args']
  : [];

$card_newline_count = isset($args['card_newline_count']) ? (int) $args['card_newline_count'] : 1;
if ($card_newline_count < 1) $card_newline_count = 1;
$card_line_break = str_repeat("\n", $card_newline_count);
?>
<main id="site-main" tabindex="-1">

<?php
    // ===== Term context =====
    $term = get_queried_object();
    $title = ($term instanceof WP_Term)
      ? nor_get_term_public_name($term, (string) $term->name)
      : (($term && isset($term->name)) ? (string) $term->name : '');
    $title = is_string($title) ? trim($title) : '';
    if ($title === '') $title = '—';

    // Section heading (visually hidden): selected list name (term) + bilingual label
    $section_h2_ja = $title . ' 一覧';
    $section_h2_en = 'List of ' . $title;

    // Term meta / identity
    $term_id = ($term && $term instanceof WP_Term) ? (int) $term->term_id : 0;
    $term_taxonomy = ($term instanceof WP_Term) ? (string) $term->taxonomy : '';

    // JP description uses built-in term description. Fetched via
    // get_term_field(..., 'raw') rather than $term->description directly —
    // the latter can come back through display-time filtering (which
    // strips markup like <abbr>), same issue already fixed on the
    // Categories/Tags list-card pages.
    $desc_ja = '';
    if ($term_id > 0 && $term_taxonomy !== '') {
      $desc_ja_raw = get_term_field('description', $term_id, $term_taxonomy, 'raw');
      $desc_ja = is_wp_error($desc_ja_raw) ? '' : (string) $desc_ja_raw;
    }
    $desc_ja = is_string($desc_ja) ? trim($desc_ja) : '';

    $tagline = ($term_id > 0) ? nor_get_term_meta_text($term_id, 'nor_tagline') : '';
    $desc_en = ($term_id > 0) ? nor_get_term_desc_en($term_id) : '';
    $tagline = is_string($tagline) ? trim($tagline) : '';
    $desc_en = is_string($desc_en) ? trim($desc_en) : '';

    // work_client page:
    // - If masking is enabled, force hero tagline to masked EN label (slug-based when available).
    // - Also keep description text masked.
    if ($term instanceof WP_Term && (string) $term->taxonomy === 'work_client' && function_exists('nor_mask_work_client_term_text')) {
      $mask_enabled = ($term_id > 0) ? (nor_get_term_meta_text($term_id, 'nor_mask_enabled') === '1') : false;
      if ($mask_enabled && function_exists('nor_get_term_public_name_en')) {
        $forced_tagline = nor_get_term_public_name_en($term, '');
        if ($forced_tagline !== '') {
          $tagline = $forced_tagline;
        }
      } else {
        $tagline = nor_mask_work_client_term_text($term, $tagline, 'en');
      }

      $desc_ja = nor_mask_work_client_term_text($term, $desc_ja);
      $desc_en = nor_mask_work_client_term_text($term, $desc_en, 'en');
    }

    // work_category / work_tag only: tagline/desc_ja/desc_en are B "Inline
    // rich text" (same allowlist as nor_desc_en, same renderer as the
    // Categories/Tags list cards). work_client's tagline/desc_ja/desc_en are
    // untouched here — they keep the plain-text hero renderer below
    // (hero-pages.php falls back to it whenever the *_html arg is empty).
    // $br_html is left at its default ('<br class="desktop tablet"/>') to
    // match this Hero's existing line-break layout — unlike the list cards,
    // which pass a plain '<br/>'.
    $tagline_html = '';
    $desc_ja_html = '';
    $desc_en_html = '';
    if (
      $term instanceof WP_Term
      && in_array((string) $term->taxonomy, ['work_category', 'work_tag'], true)
      && function_exists('nor_render_inline_rich_text')
    ) {
      $b_abbr_map = function_exists('nor_get_abbreviation_map') ? nor_get_abbreviation_map() : [];
      $tagline_html = nor_render_inline_rich_text($tagline, $b_abbr_map, '', false);
      $desc_ja_html = nor_render_inline_rich_text($desc_ja, $b_abbr_map, '', false);
      $desc_en_html = nor_render_inline_rich_text($desc_en, $b_abbr_map, '', false);
    }

    // Ancestor chain (work_tag only): hierarchical work_tag terms (e.g.
    // Tags > Document types > Brochure) need each ancestor as its own
    // breadcrumb level between the root ("Tags") and the current term --
    // previously only Root + Current were ever emitted here, so a child
    // term's parent(s) silently disappeared. Root-level tags (no parent)
    // are unaffected -- get_ancestors() returns empty for them. Category/
    // Client are untouched: Category's own hierarchy (if any) is a
    // separate, unscoped issue, and Client is non-hierarchical.
    $ancestor_breadcrumb_items = [];
    if ($term instanceof WP_Term && $term_taxonomy === 'work_tag' && (int) $term->parent > 0) {
      $tag_ancestor_ids = array_reverse(get_ancestors($term_id, 'work_tag', 'taxonomy'));
      foreach ($tag_ancestor_ids as $tag_ancestor_id) {
        $tag_ancestor_term = get_term((int) $tag_ancestor_id, 'work_tag');
        if (!($tag_ancestor_term instanceof WP_Term)) continue;
        $tag_ancestor_label = trim((string) nor_get_term_public_name($tag_ancestor_term, (string) $tag_ancestor_term->name));
        if ($tag_ancestor_label === '') continue;
        $tag_ancestor_url = get_term_link($tag_ancestor_term, 'work_tag');
        $ancestor_breadcrumb_items[] = [
          'label' => $tag_ancestor_label,
          'label_html' => nor_render_label_with_abbr($tag_ancestor_label),
          'url' => (!is_wp_error($tag_ancestor_url) && is_string($tag_ancestor_url)) ? $tag_ancestor_url : '',
        ];
      }
    }

    // Pagination context
    global $wp_query;
    $paged = max(1, (int) get_query_var('paged'));
    $max   = (int) ($wp_query->max_num_pages ?? 1);
    if ($max < 1) $max = 1;

    $per_page = nor_get_works_archive_per_page();
    if ($per_page < 1) $per_page = 12;
    $i = ($paged - 1) * $per_page;

    // ===== Hero (pages common) =====
    $works_count = (int) ($wp_query->found_posts ?? 0);
    $hero_args = [
      // Title
      'title'      => $title,
      'title_html' => nor_render_label_with_abbr($title),

      // Copy
      'tagline' => $tagline,
      'tagline_html' => $tagline_html,
      'desc_ja' => $desc_ja,
      'desc_en' => $desc_en,
      'desc_ja_html' => $desc_ja_html,
      'desc_en_html' => $desc_en_html,

      // Stats
      'count' => $works_count,
      'unit'  => nor_format_count_unit($works_count, 'work', 'works', 'archived'),

      // Widget
      'widget' => 'none',

      // Breadcrumbs
      'breadcrumbs' => nor_build_breadcrumbs(array_merge(
        [['label' => $root_label, 'url' => $root_url]],
        $ancestor_breadcrumb_items,
        [['label' => $title, 'label_html' => nor_render_label_with_abbr($title), 'current' => true]]
      )),
    ];

    echo nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'normalize_first_indent' => true,
      'indent' => 2,
      'suffix' => "\n",
    ]);
?>

<?php
      ob_start();
      if (have_posts()) : while (have_posts()) : the_post();
?>
<?php
          $post_id = get_the_ID();
          $work_no = (int) get_post_meta($post_id, 'nor_work_no', true);
          $num = nor_format_seq_no(max(0, $work_no));

          $card_data = nor_get_work_card_data((int) $post_id, [
            'client_mode'              => 'meta',
            'clients_limit'            => 4,
            'legacy_industry_fallback' => true,
            'include_tag_other'        => true,
          ]);

          $card_args = [
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
          ];

          $card_html = nor_render_template_part('template-parts/card/card-works', null, $card_args, [
            'trim' => 'both',
            'normalize_first_indent' => true,
            'indent' => 4,
            'suffix' => $card_line_break,
          ]);
          echo $card_html;
        endwhile;

        // ===== Pagination (taxonomy archive) =====
        // Use WP-native paging, but render with the shared pagination UI.
        $page_url = static function (int $n) use ($append_query_args): string {
          $u = get_pagenum_link($n);
          if (!is_string($u)) return '';
          if (!empty($append_query_args)) {
            $u = add_query_arg($append_query_args, $u);
          }
          return (string) $u;
        };

        $pagination_args = nor_build_list_pagination_args(
          (int) $paged,
          (int) $max,
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
        echo nor_render_back_list_button((string) $back_to_list_url, 'Back to List', [
          'indent' => 6,
          'suffix' => "\n",
        ]);
      else :
        echo nor_render_empty_works_list([
          'title' => $title,
          'primary_url' => $back_to_list_url,
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
