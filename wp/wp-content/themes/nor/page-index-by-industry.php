<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php
  // ===== Page meta (Clients: Index by Industry) =====
  $page_id = get_queried_object_id();
  $page_title = '';
  $page_tagline = '';
  $page_desc_ja = '';
  $page_desc_en = '';
  $section_h2_ja = '—';
  $section_h2_en = '—';

  // ===== Stats (clients indexed) =====
  $all_clients = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
  ]);
  $client_count = (!is_wp_error($all_clients) && is_array($all_clients)) ? count($all_clients) : 0;

  $landing = nor_get_landing_page_shell_args($page_id, [
    'count'               => (int) $client_count,
    'unit'                => nor_format_count_unit((int) $client_count, 'client', 'clients', 'indexed'),
    'title_fallback'      => 'Clients',
    'section_h2_fallback' => '—',
  ]);
  $page_title = is_string($landing['title'] ?? null) ? (string) $landing['title'] : '';
  $section_h2_ja = is_string($landing['section_h2_ja'] ?? null) ? (string) $landing['section_h2_ja'] : '—';
  $section_h2_en = is_string($landing['section_h2_en'] ?? null) ? (string) $landing['section_h2_en'] : '—';

  $landing_hero = (isset($landing['hero_args']) && is_array($landing['hero_args'])) ? $landing['hero_args'] : [];
  $page_tagline = is_string($landing_hero['tagline'] ?? null) ? (string) $landing_hero['tagline'] : '';
  $page_desc_ja = is_string($landing_hero['desc_ja'] ?? null) ? (string) $landing_hero['desc_ja'] : '';
  $page_desc_en = is_string($landing_hero['desc_en'] ?? null) ? (string) $landing_hero['desc_en'] : '';

  // ===== Tabs (use actual Page titles) =====
  $tabs_nav = nor_get_clients_tabs_nav('index-by-industry', [
    'index-by-initial'  => 'Index by Initial',
    'index-by-industry' => 'Index by Industry',
  ]);

  // ===== Hero args (pages common) =====
  $hero_args = [
    'title'   => $page_title,
    'tagline' => $page_tagline,
    'desc_ja' => $page_desc_ja,
    'desc_en' => $page_desc_en,

    'count' => (int) $client_count,
    'unit'  => nor_format_count_unit((int) $client_count, 'client', 'clients', 'indexed'),

    // Tabs widget
    'widget'        => 'tabs',
    'nav_list_aria' => is_string($tabs_nav['nav_list_aria'] ?? null) ? (string) $tabs_nav['nav_list_aria'] : 'Client views',
    'nav_list'      => (isset($tabs_nav['nav_list']) && is_array($tabs_nav['nav_list'])) ? $tabs_nav['nav_list'] : [],

    // Breadcrumbs (no /clients/ page)
    'breadcrumbs' => nor_build_single_breadcrumb('Clients - ' . $page_title),
  ];

  // モック順（JSIC major divisions）を維持したいので “term名順”ではなく “定義順”で出す
  $industries_order = [
    ['slug' => 'agriculture-forestry-and-fisheries', 'en' => 'Agriculture, Forestry and Fisheries', 'ja' => '農業、林業、漁業'],
    ['slug' => 'mining-and-quarrying-of-stone-and-gravel', 'en' => 'Mining and Quarrying of Stone and Gravel', 'ja' => '鉱業、採石業、砂利採取業'],
    ['slug' => 'construction', 'en' => 'Construction', 'ja' => '建設業'],
    ['slug' => 'manufacturing', 'en' => 'Manufacturing', 'ja' => '製造業'],
    ['slug' => 'electricity-gas-heat-supply-and-water', 'en' => 'Electricity, Gas, Heat Supply and Water', 'ja' => '電気・ガス・熱供給・水道業'],
    ['slug' => 'information-and-communications', 'en' => 'Information and Communications', 'ja' => '情報通信業'],
    ['slug' => 'transport-and-postal-services', 'en' => 'Transport and Postal Services', 'ja' => '運輸業、郵便業'],
    ['slug' => 'wholesale-and-retail-trade', 'en' => 'Wholesale and Retail Trade', 'ja' => '卸売業、小売業'],
    ['slug' => 'finance-and-insurance', 'en' => 'Finance and Insurance', 'ja' => '金融業、保険業'],
    ['slug' => 'real-estate-and-goods-rental-and-leasing', 'en' => 'Real Estate and Goods Rental and Leasing', 'ja' => '不動産業、物品賃貸業'],
    ['slug' => 'scientific-research-professional-and-technical-services', 'en' => 'Scientific Research, Professional and Technical Services', 'ja' => '学術研究、専門・技術サービス業'],
    ['slug' => 'accommodations-eating-and-drinking-services', 'en' => 'Accommodations, Eating and Drinking Services', 'ja' => '宿泊業、飲食サービス業'],
    ['slug' => 'living-related-and-personal-services-and-amusement-services', 'en' => 'Living-Related and Personal Services and Amusement Services', 'ja' => '生活関連サービス業、娯楽業'],
    ['slug' => 'education-learning-support', 'en' => 'Education, Learning Support', 'ja' => '教育、学習支援業'],
    ['slug' => 'medical-health-care-and-welfare', 'en' => 'Medical, Health Care and Welfare', 'ja' => '医療、福祉'],
    ['slug' => 'compound-services', 'en' => 'Compound Services', 'ja' => '複合サービス事業'],
    ['slug' => 'other-unclassified', 'en' => 'Other / Unclassified', 'ja' => '他に分類されないもの'],
  ];

  // ===== Clients are assigned to exactly ONE industry via term meta =====
  // work_client term meta key: nor_industry (stores work_industry term_id)

  // Build a bucket: industry_term_id => [WP_Term client, ...]
  $clients_bucket_by_industry = [];
  if (!is_wp_error($all_clients) && is_array($all_clients)) {
    foreach ($all_clients as $c) {
      if (!$c || is_wp_error($c)) {
        continue;
      }
      $iid = (int) nor_get_term_meta_text((int) $c->term_id, 'nor_industry');
      if ($iid <= 0) {
        continue;
      }
      if (!isset($clients_bucket_by_industry[$iid])) {
        $clients_bucket_by_industry[$iid] = [];
      }
      $clients_bucket_by_industry[$iid][] = $c;
    }
  }

  // Sort clients within each industry bucket by name (A-Z)
  foreach ($clients_bucket_by_industry as $iid => $list) {
    usort($list, fn($a, $b) => strcmp((string) $a->name, (string) $b->name));
    $clients_bucket_by_industry[$iid] = $list;
  }

  // Last updated for an industry = latest published work among clients in that industry
  $last_updated_for_clients = function (array $client_term_ids) {
    return nor_get_latest_published_work_for_terms('work_client', $client_term_ids, true);
  };

  $clients_cards_html = '';
  foreach ($industries_order as $row) {
    // term が無い（まだ作っていない）業種でも、モック通り「枠は出す」
    $term = get_term_by('slug', $row['slug'], 'work_industry');
    $industry_id = ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
    $clients = ($industry_id > 0 && isset($clients_bucket_by_industry[$industry_id])) ? $clients_bucket_by_industry[$industry_id] : [];
    $count = count($clients);

    // EN display: the work_industry term name is the source of truth (kept
    // in sync with wp-admin by definition), not the hardcoded 'en' above —
    // that value now only serves as ordering/fallback for a term that
    // doesn't exist yet (see the "term が無い" comment above).
    $en_label = ($term && !is_wp_error($term) && trim((string) $term->name) !== '') ? (string) $term->name : (string) $row['en'];

    $client_ids = ($count > 0) ? array_map(fn($t) => (int) $t->term_id, $clients) : [];
    $lu = ($count > 0) ? $last_updated_for_clients($client_ids) : null;

    $h3_id = 'client-industry-' . $row['slug'];
    $aria = 'Clients - Industries: ' . $en_label;

    // Title line breaks: explicit, hand-verified per-slug line arrays.
    // Regex-derived splitting (by comma count / "and" count) silently
    // produced wrong or truncated breaks once some industry names grew
    // longer (e.g. a single "first and only" split point no longer matched
    // where the name actually needed to wrap), so each of the 17 industries
    // has a fixed set of lines here instead of a rule engine. Falls back to
    // the whole label on one line for any slug not listed (shouldn't happen
    // for the current 17, but keeps this safe if a new industry is added
    // without updating this map).
    $title_lines_by_slug = [
      'agriculture-forestry-and-fisheries'                          => ['Agriculture,', 'Forestry and Fisheries'],
      'mining-and-quarrying-of-stone-and-gravel'                    => ['Mining and Quarrying of', 'Stone and Gravel'],
      'construction'                                                => ['Construction'],
      'manufacturing'                                                => ['Manufacturing'],
      'electricity-gas-heat-supply-and-water'                       => ['Electricity, Gas,', 'Heat Supply and Water'],
      'information-and-communications'                              => ['Information and', 'Communications'],
      'transport-and-postal-services'                               => ['Transport and', 'Postal Services'],
      'wholesale-and-retail-trade'                                  => ['Wholesale and Retail Trade'],
      'finance-and-insurance'                                       => ['Finance and Insurance'],
      'real-estate-and-goods-rental-and-leasing'                    => ['Real Estate and', 'Goods Rental and Leasing'],
      'scientific-research-professional-and-technical-services'     => ['Scientific Research,', 'Professional and', 'Technical Services'],
      'accommodations-eating-and-drinking-services'                 => ['Accommodations, Eating', 'and Drinking Services'],
      'living-related-and-personal-services-and-amusement-services' => ['Living-Related and', 'Personal Services and', 'Amusement Services'],
      'education-learning-support'                                  => ['Education, Learning Support'],
      'medical-health-care-and-welfare'                             => ['Medical,', 'Health Care and Welfare'],
      'compound-services'                                           => ['Compound Services'],
      'other-unclassified'                                          => ['Other / Unclassified'],
    ];
    $title_lines = $title_lines_by_slug[(string) $row['slug']] ?? [$en_label];

    $links = [];
    if ($count > 0) {
      foreach ($clients as $t) {
        $term_link = get_term_link($t);
        if (is_wp_error($term_link)) {
          continue;
        }
        $links[] = [
          'url'   => add_query_arg('from', 'index-by-industry', $term_link),
          'label' => function_exists('nor_get_work_client_list_label')
            ? nor_get_work_client_list_label($t, (string) $t->name)
            : (function_exists('nor_get_term_public_name') ? nor_get_term_public_name($t, (string) $t->name) : (string) $t->name),
        ];
      }
    }

    $clients_cards_html .= nor_render_template_part('template-parts/card/card-client', null, [
      'title_id'               => $h3_id,
      'title_lines'            => $title_lines,
      'lu'                     => $lu,
      'count'                  => $count,
      'notice'                 => (string) $row['ja'],
      'notice_lang'            => 'ja',
      'aria_label'             => $aria,
      'links'                  => $links,
      'render_body_when_empty' => false,
    ], [
      'trim'                   => 'both',
      'normalize_first_indent' => true,
      'indent'                 => 4,
      'suffix'                 => "\n\n",
    ]);
  }

  $shell_html = nor_render_template_part('template-parts/clients/clients-subpage-shell', null, [
    'hero_args'     => $hero_args,
    'section_h2_ja' => $section_h2_ja,
    'section_h2_en' => $section_h2_en,
    'cards_html'    => $clients_cards_html,
  ], [
    'trim'   => 'both',
    'suffix' => "\n",
  ]);

  // Normalize hero opening indentation in final output.
  // Handle line-start/BOM drift robustly.
  $shell_html = (string) preg_replace('/^[\x{FEFF}\h]*<section class="hero">/mu', '<section class="hero">', $shell_html, 1);
  $shell_html = (string) preg_replace('/^[\x{FEFF}\h]*<div class="inner">/mu', '    <div class="inner">', $shell_html, 1);

  echo $shell_html;
?>

</main>

<?php get_footer(); ?>
