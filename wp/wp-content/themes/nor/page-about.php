<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
    $page_id = get_queried_object_id();

    $get_meta = function (string $key) use ($page_id): string {
      return nor_get_post_meta_text((int) $page_id, $key);
    };

    $allowed_html = nor_about_allowed_html();

    // ===== Hero meta =====
    $content_ctx = nor_get_content_page_hero_context((int) $page_id, [
      'title_fallback' => 'About',
      'section_h2_fallback' => '—',
      'unit' => nor_format_count_unit(nor_get_published_works_count(), 'work', 'works', 'archived'),
      'widget' => 'none',
    ]);
    $title = (string) ($content_ctx['title'] ?? 'About');
    $tagline = (string) ($content_ctx['tagline'] ?? '');
    $desc_ja = (string) ($content_ctx['desc_ja'] ?? '');
    $desc_en = (string) ($content_ctx['desc_en'] ?? '');
    $section_h2_ja = (string) ($content_ctx['section_h2_ja'] ?? '—');
    $section_h2_en = (string) ($content_ctx['section_h2_en'] ?? '—');
    $works_count = array_key_exists('works_count', $content_ctx)
      ? (int) $content_ctx['works_count']
      : (int) nor_get_published_works_count();
    if ($works_count < 0) {
      $works_count = 0;
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
    $hero_args['count'] = $works_count;
    $hero_args['unit'] = nor_format_count_unit($works_count, 'work', 'works', 'archived');
    $hero_args['tagline_fallback'] = '—';
    $hero_args['desc_fallback'] = '—';

    echo nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'both',
      'indent' => 2,
      'suffix' => "\n",
    ]);

    // ===== Summary: categories =====
    $core_categories = get_terms([
      'taxonomy'   => 'work_category',
      'hide_empty' => false,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ]);
    if (!is_wp_error($core_categories) && is_array($core_categories) && !empty($core_categories)) {
      usort($core_categories, function ($a, $b) {
        if (!$a || !$b || is_wp_error($a) || is_wp_error($b)) return 0;
        $a_is_uncat = ((string) $a->slug === 'uncategorized');
        $b_is_uncat = ((string) $b->slug === 'uncategorized');
        if ($a_is_uncat && !$b_is_uncat) return 1;
        if (!$a_is_uncat && $b_is_uncat) return -1;
        return strcmp((string) $a->name, (string) $b->name);
      });
    } else {
      $core_categories = [];
    }

    $render_category_name = function (WP_Term $t): string {
      return nor_render_work_category_label($t, 'inline');
    };

    $render_core_category_items = function (array $terms) use ($render_category_name): string {
      if (empty($terms)) {
        return "                    <li><span class=\"value\">—</span></li>\n";
      }
      $out = '';
      foreach ($terms as $t) {
        if (!$t instanceof WP_Term) continue;
        $u = get_term_link($t);
        if (is_wp_error($u)) continue;
        $out .= '                    <li><span class="value"><a href="' . esc_url($u) . '">' . $render_category_name($t) . "</a></span></li>\n";
      }
      if ($out === '') {
        $out = "                    <li><span class=\"value\">—</span></li>\n";
      }
      return $out;
    };

    // ===== Summary: years archived =====
    global $wpdb;
    $years_row = $wpdb->get_row("
      SELECT
        MIN(YEAR(post_date)) AS min_year,
        MAX(YEAR(post_date)) AS max_year
      FROM {$wpdb->posts}
      WHERE post_type = 'works'
        AND post_status = 'publish'
    ");

    $min_year = ($years_row && isset($years_row->min_year)) ? (int) $years_row->min_year : 0;
    $max_year = ($years_row && isset($years_row->max_year)) ? (int) $years_row->max_year : 0;
    $years_archived_html = '<span class="value">—</span>';
    if ($min_year > 0 && $max_year > 0) {
      $years_archived_html =
        '<time class="value" datetime="' . esc_attr((string) $min_year) . '">' . esc_html($min_year) . '</time>' .
        ' to ' .
        '<time class="value" datetime="' . esc_attr((string) $max_year) . '">' . esc_html($max_year) . '</time>';
    }

    // ===== Served sectors: top 10 industries by registered clients =====
    $industry_label_map = [
      'agriculture-forestry-and-fisheries'      => ['ja' => '農業、林業、漁業', 'en' => 'Agriculture, Forestry and Fisheries'],
      'mining-and-quarrying'                    => ['ja' => '鉱業、採石業、砂利採取業', 'en' => 'Mining and Quarrying'],
      'construction'                            => ['ja' => '建設業', 'en' => 'Construction'],
      'manufacturing'                           => ['ja' => '製造業', 'en' => 'Manufacturing'],
      'electricity-gas-heat-supply-and-water'  => ['ja' => '電気・ガス・熱供給・水道業', 'en' => 'Electricity, Gas, Heat Supply and Water'],
      'information-and-communications'          => ['ja' => '情報通信業', 'en' => 'Information and Communications'],
      'transport-and-postal'                    => ['ja' => '運輸業、郵便業', 'en' => 'Transport and Postal'],
      'wholesale-and-retail-trade'              => ['ja' => '卸売業、小売業', 'en' => 'Wholesale and Retail Trade'],
      'finance-and-insurance'                   => ['ja' => '金融業、保険業', 'en' => 'Finance and Insurance'],
      'real-estate-and-rental'                  => ['ja' => '不動産業、物品賃貸業', 'en' => 'Real Estate and Rental'],
      'professional-and-technical-services'     => ['ja' => '学術研究、専門・技術サービス業', 'en' => 'Professional and Technical Services'],
      'accommodation-and-food-services'         => ['ja' => '宿泊業、飲食サービス業', 'en' => 'Accommodation and Food Services'],
      'living-and-amusement-services'           => ['ja' => '生活関連サービス業、娯楽業', 'en' => 'Living and Amusement Services'],
      'education-and-learning-support'          => ['ja' => '教育、学習支援業', 'en' => 'Education and Learning Support'],
      'medical-and-welfare'                     => ['ja' => '医療、福祉', 'en' => 'Medical and Welfare'],
      'compound-services'                       => ['ja' => '複合サービス事業', 'en' => 'Compound Services'],
      'not-elsewhere-classified'                => ['ja' => '他に分類されないもの', 'en' => 'Not Elsewhere Classified'],
    ];

    $all_clients = get_terms([
      'taxonomy'   => 'work_client',
      'hide_empty' => true,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ]);

    $industry_counts = [];
    if (!is_wp_error($all_clients) && is_array($all_clients)) {
      foreach ($all_clients as $client_term) {
        if (!$client_term || is_wp_error($client_term)) continue;
        $iid = (int) nor_get_term_meta_text((int) $client_term->term_id, 'nor_industry');
        if ($iid <= 0) continue;
        if (!isset($industry_counts[$iid])) $industry_counts[$iid] = 0;
        $industry_counts[$iid] += 1;
      }
    }

    $industry_rows = [];
    foreach ($industry_counts as $iid => $cnt) {
      $term = get_term((int) $iid, 'work_industry');
      if (!$term || is_wp_error($term)) continue;

      $slug = (string) $term->slug;
      $map = $industry_label_map[$slug] ?? null;

      $ja_name = is_array($map) ? (string) ($map['ja'] ?? '') : '';
      $en_name = is_array($map) ? (string) ($map['en'] ?? '') : '';

      if ($ja_name === '') $ja_name = (string) $term->name;
      if ($en_name === '') $en_name = (string) $term->name;

      $ja_desc = trim((string) $term->description);
      if ($ja_desc === $ja_name) $ja_desc = '';

      $en_title = nor_get_term_desc_en((int) $term->term_id);
      if ($en_title === '') {
        $en_title = nor_get_term_meta_text((int) $term->term_id, 'nor_tagline');
      }
      if ($en_title === $en_name) $en_title = '';

      $industry_rows[] = [
        'count'    => (int) $cnt,
        'slug'     => $slug,
        'ja_name'  => $ja_name,
        'en_name'  => $en_name,
        'ja_desc'  => $ja_desc,
        'en_title' => $en_title,
      ];
    }

    usort($industry_rows, function (array $a, array $b): int {
      if ((int) $a['count'] !== (int) $b['count']) {
        return ((int) $b['count']) <=> ((int) $a['count']);
      }
      return strcmp((string) $a['en_name'], (string) $b['en_name']);
    });
    $industry_rows = array_slice($industry_rows, 0, 10);

    $render_industry_items = static function (array $rows, string $label_key, string $note_key): string {
      if (empty($rows)) {
        return "                    <li><span class=\"value\">—</span></li>\n";
      }
      $out = '';
      foreach ($rows as $row) {
        $slug = isset($row['slug']) ? (string) $row['slug'] : '';
        $label = isset($row[$label_key]) ? (string) $row[$label_key] : '';
        $note = isset($row[$note_key]) ? (string) $row[$note_key] : '';
        if ($slug === '' || $label === '') continue;
        $out .= "                    <li>\n";
        $out .= '                      <span class="value"><a href="' . esc_url(home_url('/clients/industries/#client-industry-' . $slug)) . '">' . esc_html($label) . "</a></span>\n";
        if ($note !== '') {
          if ($note_key === 'ja_desc') {
            $out .= '                      （<span class="value">' . esc_html($note) . "</span>）\n";
          } else {
            $out .= '                      (<span class="value">' . esc_html($note) . "</span>)\n";
          }
        }
        $out .= "                    </li>\n";
      }
      if ($out === '') {
        $out = "                    <li><span class=\"value\">—</span></li>\n";
      }
      return $out;
    };

    // ===== About section copy (defaults + dedicated fields) =====
    $summary_lead_ja = $get_meta('nor_about_summary_lead_ja');
    $summary_lead_en = $get_meta('nor_about_summary_lead_en');

    $structure_lead_ja = $get_meta('nor_about_structure_lead_ja');
    $structure_lead_en = $get_meta('nor_about_structure_lead_en');

    $sectors_lead_ja = $get_meta('nor_about_sectors_lead_ja');
    $sectors_lead_en = $get_meta('nor_about_sectors_lead_en');
    $sectors_note_ja = $get_meta('nor_about_sectors_note_ja');
    $sectors_note_en = $get_meta('nor_about_sectors_note_en');

    $profile_name_ja = $get_meta('nor_about_profile_name_ja');
    $profile_name_en = $get_meta('nor_about_profile_name_en');
    $profile_body_ja = $get_meta('nor_about_profile_body_ja');
    $profile_body_en = $get_meta('nor_about_profile_body_en');

    $stance_lead_ja = $get_meta('nor_about_stance_lead_ja');
    $stance_lead_en = $get_meta('nor_about_stance_lead_en');
    $stance_body_ja = $get_meta('nor_about_stance_body_ja');
    $stance_body_en = $get_meta('nor_about_stance_body_en');
  ?>

  <section class="section content-pages content-about">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($section_h2_ja); ?></span>（<span lang="en"><?php echo esc_html($section_h2_en); ?></span>）</h2>
    <div class="inner">

      <section class="content about">
        <header class="head">
          <p class="index"><span class="value">01</span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2 lang="en"><span class="character-line">Summary</span></h2>
            <p class="ja" lang="ja">概要</p>
          </header>
          <div class="detail">
            <div class="textpair">
              <p class="ja" lang="ja"><?php echo nor_render_rich_inline($summary_lead_ja, $allowed_html); ?></p>
              <p class="en" lang="en"><?php echo nor_render_rich_inline($summary_lead_en, $allowed_html); ?></p>
            </div>
            <div class="main">
              <dl class="recorded">
                <dt>Works archived:</dt>
                <dd><data class="value" value="<?php echo esc_attr($works_count); ?>"><?php echo esc_html($works_count); ?></data> works.</dd>
              </dl>
              <dl class="fields">
                <dt>Core fields:</dt>
                <dd>
                  <ul>
<?php echo $render_core_category_items($core_categories); ?>
                  </ul>
                </dd>
              </dl>
              <dl class="archived">
                <dt>Years archived:</dt>
                <dd><?php echo $years_archived_html; ?></dd>
              </dl>
              <dl class="languages">
                <dt>Languages:</dt>
                <dd><span class="value">Japanese</span> and <span class="value">English</span></dd>
              </dl>
              <dl class="policy">
                <dt>Publication policy:</dt>
                <dd><span class="value">Visuals appear only when permission is granted.</span></dd>
              </dl>
            </div>
          </div>
        </div>
      </section>

      <section class="content about">
        <header class="head">
          <p class="index"><span class="value">02</span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2 lang="en"><span class="character-line">Structure</span></h2>
            <p class="ja" lang="ja">構造</p>
          </header>
          <div class="detail">
            <div class="textpair">
              <p class="ja" lang="ja"><?php echo nor_render_rich_inline($structure_lead_ja, $allowed_html); ?></p>
              <p class="en" lang="en"><?php echo nor_render_rich_inline($structure_lead_en, $allowed_html); ?></p>
            </div>
            <div class="main">
              <div class="ja" lang="ja">
                <ol>
                  <li><span class="value">制作分野別の分類</span>：<dfn class="value"><a href="<?php echo esc_url(home_url('/categories/')); ?>">Categories</a></dfn></li>
                  <li><span class="value">制作物・役割・使用ツールの詳細分類</span>：<dfn class="value"><a href="<?php echo esc_url(home_url('/tags/')); ?>">Tags</a></dfn></li>
                  <li><span class="value">制作・公開年による時系列整理</span>：<dfn class="value"><a href="<?php echo esc_url(home_url('/archives/')); ?>">Archives</a></dfn></li>
                  <li><span class="value">取引先名と業種の索引</span>：<dfn class="value"><a href="<?php echo esc_url(home_url('/clients/iot/')); ?>">Clients</a></dfn></li>
                  <li><span class="value">全文検索による横断参照</span>：<dfn class="value"><a href="<?php echo esc_url(home_url('/search/')); ?>">Search</a></dfn></li>
                </ol>
              </div>
              <div class="en" lang="en">
                <ol>
                  <li><dfn class="value"><a href="<?php echo esc_url(home_url('/categories/')); ?>">Categories</a></dfn>: <span class="value">by design discipline</span></li>
                  <li><dfn class="value"><a href="<?php echo esc_url(home_url('/tags/')); ?>">Tags</a></dfn>: <span class="value">by medium, roles, and tools</span></li>
                  <li><dfn class="value"><a href="<?php echo esc_url(home_url('/archives/')); ?>">Archives</a></dfn>: <span class="value">by year of creation or release</span></li>
                  <li><dfn class="value"><a href="<?php echo esc_url(home_url('/clients/industries/')); ?>">Clients</a></dfn>: <span class="value">index by name and industry</span></li>
                  <li><dfn class="value"><a href="<?php echo esc_url(home_url('/search/')); ?>">Search</a></dfn>: <span class="value">full-text cross-reference</span></li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content about">
        <header class="head">
          <p class="index"><span class="value">03</span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2 lang="en"><span class="character-line">Served Sectors</span></h2>
            <p class="ja" lang="ja">業種</p>
          </header>
          <div class="detail">
            <div class="textpair">
              <p class="ja" lang="ja"><?php echo nor_render_rich_inline($sectors_lead_ja, $allowed_html); ?></p>
              <p class="en" lang="en"><?php echo nor_render_rich_inline($sectors_lead_en, $allowed_html); ?></p>
            </div>
            <div class="main">
              <div class="ja" lang="ja">
                <ul>
<?php echo $render_industry_items($industry_rows, 'ja_name', 'ja_desc'); ?>
                </ul>
                <p><?php echo nor_render_rich_inline($sectors_note_ja, $allowed_html); ?></p>
              </div>
              <div class="en" lang="en">
                <ul>
<?php echo $render_industry_items($industry_rows, 'en_name', 'en_title'); ?>
                </ul>
                <p><?php echo nor_render_rich_inline($sectors_note_en, $allowed_html); ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content about">
        <header class="head">
          <p class="index"><span class="value">04</span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2 lang="en"><span class="character-line">Short Profile</span></h2>
            <p class="ja" lang="ja">略歴</p>
          </header>
          <div class="detail">
            <div class="textpair">
              <p class="ja" lang="ja"><?php echo nor_render_rich_inline($profile_name_ja, $allowed_html); ?></p>
              <p class="en" lang="en"><?php echo nor_render_rich_inline($profile_name_en, $allowed_html); ?></p>
            </div>
            <div class="main">
              <div class="ja" lang="ja">
                <p><?php echo nor_render_rich_compact($profile_body_ja, $allowed_html, '<br>'); ?></p>
              </div>
              <div class="en" lang="en">
                <p><?php echo nor_render_rich_compact($profile_body_en, $allowed_html, ' <br>'); ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content about">
        <header class="head">
          <p class="index"><span class="value">05</span></p>
        </header>
        <div class="body">
          <header class="title">
            <h2 lang="en"><span class="character-line">Stance</span></h2>
            <p class="ja" lang="ja">心得</p>
          </header>
          <div class="detail">
            <div class="textpair">
              <p class="ja" lang="ja"><?php echo nor_render_rich_inline($stance_lead_ja, $allowed_html); ?></p>
              <p class="en" lang="en"><?php echo nor_render_rich_inline($stance_lead_en, $allowed_html); ?></p>
            </div>
            <div class="main">
              <div class="ja" lang="ja">
                <p><?php echo nor_render_rich_compact($stance_body_ja, $allowed_html, '<br>'); ?></p>
              </div>
              <div class="en" lang="en">
                <p><?php echo nor_render_rich_compact($stance_body_en, $allowed_html, ' <br>'); ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>

<?php
      echo nor_render_template_part('template-parts/see-also', null, ['current_slug' => 'about'], [
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
