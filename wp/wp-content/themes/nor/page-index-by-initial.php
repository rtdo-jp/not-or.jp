<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php
  // ===== Page context =====
  $page_id = get_queried_object_id();

  // ===== Clients count (used for hero + grouping below) =====
  $all_clients = get_terms([
    'taxonomy'   => 'work_client',
    'hide_empty' => false,
  ]);
  $client_count = (!is_wp_error($all_clients) && is_array($all_clients)) ? count($all_clients) : 0;

  // ===== Page meta (Clients: Index by Initial) =====
  $page_title = '';
  $page_tagline = '';
  $page_desc_ja = '';
  $page_desc_en = '';
  $h2_ja = '—';
  $h2_en = '—';

  $landing = nor_get_landing_page_shell_args($page_id, [
    'count'               => (int) $client_count,
    'unit'                => nor_format_count_unit((int) $client_count, 'client', 'clients', 'indexed'),
    'title_fallback'      => 'Clients',
    'section_h2_fallback' => '—',
  ]);
  $page_title = is_string($landing['title'] ?? null) ? (string) $landing['title'] : '';
  $h2_ja = is_string($landing['section_h2_ja'] ?? null) ? (string) $landing['section_h2_ja'] : '—';
  $h2_en = is_string($landing['section_h2_en'] ?? null) ? (string) $landing['section_h2_en'] : '—';

  $landing_hero = (isset($landing['hero_args']) && is_array($landing['hero_args'])) ? $landing['hero_args'] : [];
  $page_tagline = is_string($landing_hero['tagline'] ?? null) ? (string) $landing_hero['tagline'] : '';
  $page_desc_ja = is_string($landing_hero['desc_ja'] ?? null) ? (string) $landing_hero['desc_ja'] : '';
  $page_desc_en = is_string($landing_hero['desc_en'] ?? null) ? (string) $landing_hero['desc_en'] : '';

  // ===== Tabs nav (labels from Clients pages) =====
  $tabs_nav = nor_get_clients_tabs_nav('index-by-initial');

  // ===== Hero args (pages common) =====
  $hero_args = [
    // Copy
    'title'   => $page_title,
    'tagline' => $page_tagline,
    'desc_ja' => $page_desc_ja,
    'desc_en' => $page_desc_en,

    // Stats
    'count' => (int) $client_count,
    'unit'  => nor_format_count_unit((int) $client_count, 'client', 'clients', 'indexed'),

    // Widget: tabs
    'widget'        => 'tabs',
    'nav_list_aria' => is_string($tabs_nav['nav_list_aria'] ?? null) ? (string) $tabs_nav['nav_list_aria'] : 'Client views',
    'nav_list'      => (isset($tabs_nav['nav_list']) && is_array($tabs_nav['nav_list'])) ? $tabs_nav['nav_list'] : [],

    // Breadcrumbs (no /clients/ landing)
    'breadcrumbs' => nor_build_single_breadcrumb('Clients - ' . $page_title),
  ];

  // --- Group definitions (モックの10区分) ---
  $groups = [
    'aiueo'       => ['label_ja' => 'あいうえお', 'label_en' => 'a, i, u, e, o'],
    'kakikukeko'  => ['label_ja' => 'かきくけこ', 'label_en' => 'ka, ki, ku, ke, ko'],
    'sashisuseso' => ['label_ja' => 'さしすせそ', 'label_en' => 'sa, shi, su, se, so'],
    'tachitsuteto' => ['label_ja' => 'たちつてと', 'label_en' => 'ta, chi, tsu, te, to'],
    'naninuneno'  => ['label_ja' => 'なにぬねの', 'label_en' => 'na, ni, nu, ne, no'],
    'hahifuheho'  => ['label_ja' => 'はひふへほ', 'label_en' => 'ha, hi, fu, he, ho'],
    'mamimumemo'  => ['label_ja' => 'まみむめも', 'label_en' => 'ma, mi, mu, me, mo'],
    'yayuyo'      => ['label_ja' => 'やゆよ', 'label_en' => 'ya, yu, yo'],
    'waon'        => ['label_ja' => 'わをん', 'label_en' => 'wa, wo, n'],
    'etc'         => ['label_ja' => 'その他', 'label_en' => 'etc...'],
  ];

  // 先頭文字→グループ判定（よみ優先。未設定時は名前から簡易正規化）
  $pick_group = function (string $source) {
    $s = trim($source);
    if ($s === '') {
      return 'etc';
    }

    // よくある接頭辞・記号を除去（よみ未設定時の保険）
    $s = preg_replace('/^(株式会社|有限会社|合同会社|一般社団法人|一般財団法人|医療法人社団|医療法人|社会福祉法人|学校法人|特定非営利活動法人|NPO法人)\s*/u', '', $s);
    $s = preg_replace('/^[\(（\[【『「\s]+/u', '', $s);

    // 先頭1文字（UTF-8）
    $ch = mb_substr($s, 0, 1, 'UTF-8');

    // カタカナ→ひらがな寄せ / 全角半角ゆれ吸収
    $ch = mb_convert_kana($ch, 'c', 'UTF-8');

    $map = [
      'aiueo'        => ['あ', 'い', 'う', 'え', 'お'],
      'kakikukeko'   => ['か', 'き', 'く', 'け', 'こ', 'が', 'ぎ', 'ぐ', 'げ', 'ご'],
      'sashisuseso'  => ['さ', 'し', 'す', 'せ', 'そ', 'ざ', 'じ', 'ず', 'ぜ', 'ぞ'],
      'tachitsuteto' => ['た', 'ち', 'つ', 'て', 'と', 'だ', 'ぢ', 'づ', 'で', 'ど'],
      'naninuneno'   => ['な', 'に', 'ぬ', 'ね', 'の'],
      'hahifuheho'   => ['は', 'ひ', 'ふ', 'へ', 'ほ', 'ば', 'び', 'ぶ', 'べ', 'ぼ', 'ぱ', 'ぴ', 'ぷ', 'ぺ', 'ぽ'],
      'mamimumemo'   => ['ま', 'み', 'む', 'め', 'も'],
      'yayuyo'       => ['や', 'ゆ', 'よ'],
      'waon'         => ['わ', 'を', 'ん'],
    ];

    foreach ($map as $key => $chars) {
      if (in_array($ch, $chars, true)) {
        return $key;
      }
    }

    return 'etc';
  };

  // 全クライアントtermをグルーピング
  $bucket = [];
  foreach (array_keys($groups) as $k) {
    $bucket[$k] = [];
  }

  if (!is_wp_error($all_clients) && is_array($all_clients)) {
    foreach ($all_clients as $t) {
      $yomi = nor_get_term_meta_text((int) $t->term_id, 'nor_yomi');

      // よみがあれば最優先。なければ名前（接頭辞除去は $pick_group 内で実施）
      $source = ($yomi !== '') ? $yomi : $t->name;

      $g = $pick_group($source);
      $bucket[$g][] = $t;
    }
  }

  // グループ内ソート（よみ優先。未設定は名前）
  foreach ($bucket as $k => $terms) {
    usort($terms, function ($a, $b) {
      $ay = nor_get_term_meta_text((int) $a->term_id, 'nor_yomi');
      $by = nor_get_term_meta_text((int) $b->term_id, 'nor_yomi');

      $ka = ($ay !== '') ? $ay : $a->name;
      $kb = ($by !== '') ? $by : $b->name;

      return strcmp($ka, $kb);
    });
    $bucket[$k] = $terms;
  }

  // グループごとの「Last updated」：そのグループのクライアントtermいずれかに紐づく works の最新日
  $last_updated_for_group = function (array $term_ids) {
    return nor_get_latest_published_work_for_terms('work_client', $term_ids, true);
  };

  // ===== Section heading (visually-hidden) =====
  // Keep the bilingual wrapper format: <span lang="ja">...</span>（<span lang="en">...</span>）
  $clients_cards_html = '';
  foreach ($groups as $key => $meta) {
    $terms = $bucket[$key] ?? [];
    $term_ids = array_map(fn($t) => (int) $t->term_id, $terms);
    $lu = $last_updated_for_group($term_ids);
    $count = count($terms);

    $h3_id = 'client-index-' . $key;
    $aria = 'クライアント索引：' . $meta['label_ja'];

    $links = [];
    if ($count > 0) {
      foreach ($terms as $t) {
        $term_link = get_term_link($t);
        if (is_wp_error($term_link)) {
          continue;
        }
        $links[] = [
          'url'   => add_query_arg('from', 'index-by-initial', $term_link),
          'label' => function_exists('nor_get_work_client_list_label')
            ? nor_get_work_client_list_label($t, (string) $t->name)
            : (function_exists('nor_get_term_public_name') ? nor_get_term_public_name($t, (string) $t->name) : (string) $t->name),
        ];
      }
    }

    $clients_cards_html .= nor_render_template_part('template-parts/card/card-client', null, [
      'title_id'               => $h3_id,
      'title_lines'            => [(string) $meta['label_ja']],
      'lu'                     => $lu,
      'count'                  => $count,
      'notice'                 => (string) $meta['label_en'],
      'notice_lang'            => 'en',
      'aria_label'             => $aria,
      'links'                  => $links,
      'render_body_when_empty' => true,
    ], [
      'trim'                   => 'both',
      'normalize_first_indent' => true,
      'indent'                 => 4,
      'suffix'                 => "\n\n",
    ]);
  }

  $shell_html = nor_render_template_part('template-parts/clients/clients-subpage-shell', null, [
    'hero_args'     => $hero_args,
    'section_h2_ja' => $h2_ja,
    'section_h2_en' => $h2_en,
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
