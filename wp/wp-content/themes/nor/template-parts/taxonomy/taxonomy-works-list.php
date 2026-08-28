<?php
/**
 * Template part: Common taxonomy works list.
 *
 * Args:
 * - root_label (string)       Breadcrumb 2nd label (e.g. Categories, Tags, Clients)
 * - root_url (string)         Breadcrumb 2nd URL
 * - back_to_list_url (string) Back-to-list URL
 * - append_query_args (array) Query args appended to pagination URLs (e.g. ['from' => 'iot'])
 * - use_desc_abbr (bool)      Whether to render desc_ja/desc_en with nor_render_inline_with_abbr
 * - desc_abbr_map (array)     Abbreviation map for nor_render_inline_with_abbr
 * - card_newline_count (int)  Newline count after each rendered card
 */

$args = isset($args) && is_array($args) ? $args : [];

$root_label = isset($args['root_label']) ? (string) $args['root_label'] : 'Categories';
$root_url = isset($args['root_url']) ? (string) $args['root_url'] : home_url('/categories/');
$back_to_list_url = isset($args['back_to_list_url']) ? (string) $args['back_to_list_url'] : $root_url;

$append_query_args = isset($args['append_query_args']) && is_array($args['append_query_args'])
  ? $args['append_query_args']
  : [];

$use_desc_abbr = !empty($args['use_desc_abbr']);
$desc_abbr_map = isset($args['desc_abbr_map']) && is_array($args['desc_abbr_map'])
  ? $args['desc_abbr_map']
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

    // JP description uses built-in term description
    $desc_ja = ($term && !empty($term->description)) ? (string) $term->description : '';
    $desc_ja = is_string($desc_ja) ? trim($desc_ja) : '';

    // Term meta
    $term_id = ($term && $term instanceof WP_Term) ? (int) $term->term_id : 0;
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

    $desc_ja_html = '';
    $desc_en_html = '';
    if ($use_desc_abbr) {
      $desc_ja_html = nor_render_inline_with_abbr($desc_ja, '', $desc_abbr_map);
      $desc_en_html = nor_render_inline_with_abbr($desc_en, '', $desc_abbr_map);
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
      'title_html' => '',

      // Copy
      'tagline' => $tagline,
      'desc_ja' => $desc_ja,
      'desc_en' => $desc_en,

      // Stats
      'count' => $works_count,
      'unit'  => 'works archived.',

      // Widget
      'widget' => 'none',

      // Breadcrumbs
      'breadcrumbs' => nor_build_breadcrumbs([
        ['label' => $root_label, 'url' => $root_url],
        ['label' => $title, 'current' => true],
      ]),
    ];

    if ($desc_ja_html !== '') {
      $hero_args['desc_ja_html'] = $desc_ja_html;
    }
    if ($desc_en_html !== '') {
      $hero_args['desc_en_html'] = $desc_en_html;
    }

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
          $num = str_pad((string) max(0, $work_no), 3, '0', STR_PAD_LEFT);

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
