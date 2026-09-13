<?php
/**
 * Template part: Indices
 *
 * Note:
 * - This part is intended to be included on all pages except the 5xx error page.
 * - Links/labels are based on the current site IA: /categories/, /tags/, /archives/, /clients/.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>
<section class="indices">
  <div class="inner">
    <header class="head">
      <h2><span>Categories, Tags</span><br><span>&amp; Indexes</span></h2>
    </header>
    <div class="body">
      <div class="textpair">
        <p class="ja" lang="ja">カテゴリー・タグ・年・クライアント別のインデックス。公開の有無にかかわらず、制作記録は<strong>全て保存</strong>。</p>
        <p class="en" lang="en">Indexes by category, tag, year, and client. Every project kept on record — <strong>public or not</strong>.</p>
      </div>

      <div class="indices-nav">
        <nav class="categories" aria-label="Categories navigation">
          <header class="title">
            <h3>Categories</h3>
            <p>Browse works <br>by design discipline.</p>
          </header>
          <div class="detail">
            <ul>
<?php
                $home_categories = get_terms([
                  'taxonomy'   => 'work_category',
                  'hide_empty' => true,
                  'orderby'    => 'name',
                  'order'      => 'ASC',
                ]);

                // Force "Uncategorized" to be listed last.
                if (!is_wp_error($home_categories) && is_array($home_categories) && !empty($home_categories)) {
                  usort($home_categories, function ($a, $b) {
                    if (!$a || !$b || is_wp_error($a) || is_wp_error($b)) return 0;
                    $a_is_uncat = ((string) $a->slug === 'uncategorized');
                    $b_is_uncat = ((string) $b->slug === 'uncategorized');
                    if ($a_is_uncat && !$b_is_uncat) return 1;
                    if (!$a_is_uncat && $b_is_uncat) return -1;
                    return strcmp((string) $a->name, (string) $b->name);
                  });
                }

                $render_category_name = static function ($t): string {
                  if (!$t || is_wp_error($t)) return '';
                  return nor_render_work_category_label($t, 'inline');
                };

                if (!is_wp_error($home_categories) && is_array($home_categories) && !empty($home_categories)) {
                  foreach ($home_categories as $t) {
                    $term_url = get_term_link($t);
                    if (is_wp_error($term_url)) continue;
                    echo '              <li><a href="' . esc_url($term_url) . '">' . $render_category_name($t) . "</a></li>\n";
                  }
                }
              ?>
            </ul>
          </div>
        </nav>

        <div class="tags">
          <header class="title">
            <h3>Tags</h3>
            <p>Find works by type, role, <br>and tool.</p>
          </header>
<?php
            // ===== Home indices: derive tag lists from hierarchical work_tag =====
            // Top-level group terms live in taxonomy `work_tag`:
            // - document-types / site-types / roles / tools
            // Child terms (actual selectable tags) are listed under each group.
            // Strict filtering keeps only terms that have published works.

            $get_tag_group_children = static function (string $group_slug): array {
              return nor_get_work_tag_group_children($group_slug, true);
            };

            $tag_group_defs = nor_get_work_tag_group_definitions();
            $tag_group_map = [];
            foreach (is_array($tag_group_defs) ? $tag_group_defs : [] as $def) {
              if (!is_array($def)) continue;
              $slug = isset($def['slug']) && is_string($def['slug']) ? trim((string) $def['slug']) : '';
              $label = isset($def['label']) && is_string($def['label']) ? trim((string) $def['label']) : '';
              if ($slug === '' || $label === '') continue;
              $tag_group_map[$slug] = [
                'slug' => $slug,
                'label' => $label,
                'nav_class' => (isset($def['nav_class']) && is_string($def['nav_class']) && trim((string) $def['nav_class']) !== '')
                  ? trim((string) $def['nav_class'])
                  : $slug,
                'nav_aria' => (isset($def['nav_aria']) && is_string($def['nav_aria']) && trim((string) $def['nav_aria']) !== '')
                  ? trim((string) $def['nav_aria'])
                  : ($label . ' navigation'),
              ];
            }

            $doc_group = $tag_group_map['document-types'] ?? ['slug' => 'document-types', 'label' => 'Document types', 'nav_class' => 'document-types', 'nav_aria' => 'Document types navigation'];
            $site_group = $tag_group_map['site-types'] ?? ['slug' => 'site-types', 'label' => 'Site types', 'nav_class' => 'site-types', 'nav_aria' => 'Site types navigation'];
            $roles_group = $tag_group_map['roles'] ?? ['slug' => 'roles', 'label' => 'Roles', 'nav_class' => 'roles', 'nav_aria' => 'Roles navigation'];
            $tools_group = $tag_group_map['tools'] ?? ['slug' => 'tools', 'label' => 'Tools', 'nav_class' => 'tools', 'nav_aria' => 'Tools navigation'];

            $idx_doc_types  = $get_tag_group_children((string) $doc_group['slug']);
            $idx_site_types = $get_tag_group_children((string) $site_group['slug']);
            $idx_roles      = $get_tag_group_children((string) $roles_group['slug']);
            $idx_tools      = $get_tag_group_children((string) $tools_group['slug']);

            $render_tool_label = static function (WP_Term $t): string {
              return nor_render_work_tag_tool_label($t);
            };

            $render_term_links = static function (array $terms, callable $label_cb = null): void {
              foreach ($terms as $t) {
                if (!$t || is_wp_error($t) || !$t instanceof WP_Term) continue;
                $u = get_term_link($t);
                if (is_wp_error($u)) continue;
                $label = $label_cb ? (string) $label_cb($t) : nor_render_label_with_abbr((string) $t->name);
                echo '                  <li><a href="' . esc_url($u) . '">' . $label . "</a></li>\n";
              }
            };
          ?>
          <div class="detail">
            <div class="types">
              <nav class="<?php echo esc_attr((string) $doc_group['nav_class']); ?>" aria-label="<?php echo esc_attr((string) $doc_group['nav_aria']); ?>">
                <h4><?php echo esc_html((string) $doc_group['label']); ?></h4>
                <ul>
<?php $render_term_links($idx_doc_types); ?>
                </ul>
              </nav>

              <nav class="<?php echo esc_attr((string) $site_group['nav_class']); ?>" aria-label="<?php echo esc_attr((string) $site_group['nav_aria']); ?>">
                <h4><?php echo esc_html((string) $site_group['label']); ?></h4>
                <ul>
<?php $render_term_links($idx_site_types); ?>
                </ul>
              </nav>
            </div>

            <div class="roles-tools">
              <nav class="<?php echo esc_attr((string) $roles_group['nav_class']); ?>" aria-label="<?php echo esc_attr((string) $roles_group['nav_aria']); ?>">
                <h4><?php echo esc_html((string) $roles_group['label']); ?></h4>
                <ul>
<?php $render_term_links($idx_roles); ?>
                </ul>
              </nav>

              <nav class="<?php echo esc_attr((string) $tools_group['nav_class']); ?>" aria-label="<?php echo esc_attr((string) $tools_group['nav_aria']); ?>">
                <h4><?php echo esc_html((string) $tools_group['label']); ?></h4>
                <ul>
<?php $render_term_links($idx_tools, $render_tool_label); ?>
                </ul>
              </nav>
            </div>
          </div>
        </div>

        <nav class="archives" aria-label="Archives navigation">
          <header class="title">
            <h3>Archives</h3>
            <p>Browse works <br>by publication year.</p>
          </header>
          <div class="detail">
<?php
              global $wpdb;

              $years = $wpdb->get_col("
                SELECT DISTINCT YEAR(post_date) AS y
                FROM {$wpdb->posts}
                WHERE post_type = 'works'
                  AND post_status = 'publish'
                ORDER BY y DESC
              ");

              if (!is_array($years)) $years = [];
            ?>
            <ul>
<?php
                if (!empty($years)) {
                  foreach ($years as $y) {
                    $y = (int) $y;
                    if ($y <= 0) continue;
                    $url = home_url('/archives/' . $y . '/');
                    echo '              <li><a href="' . esc_url($url) . '"><data value="' . esc_attr($y) . '">' . esc_html($y) . "</data></a></li>\n";
                  }
                }
              ?>
            </ul>
          </div>
        </nav>

        <nav class="clients" aria-label="Clients navigation">
          <header class="title">
            <h3>Clients</h3>
            <p>Browse clients <br>by initial and industry.</p>
          </header>
<?php
            // ===== Home: Clients / Industries (exist-only) =====

            // IOT groups (固定：モックのidに合わせる)
            $iot_groups = [
              'aiueo'      => ['label' => 'あいうえお', 'en' => 'a, i, u, e, o'],
              'kakikukeko' => ['label' => 'かきくけこ', 'en' => 'ka, ki, ku, ke, ko'],
              'sashisuseso'=> ['label' => 'さしすせそ', 'en' => 'sa, shi, su, se, so'],
              'tachitsuteto'=>['label' => 'たちつてと', 'en' => 'ta, chi, tsu, te, to'],
              'naninuneno' => ['label' => 'なにぬねの', 'en' => 'na, ni, nu, ne, no'],
              'hahifuheho' => ['label' => 'はひふへほ', 'en' => 'ha, hi, fu, he, ho'],
              'mamimumemo' => ['label' => 'まみむめも', 'en' => 'ma, mi, mu, me, mo'],
              'yayuyo'     => ['label' => 'やゆよ', 'en' => 'ya, yu, yo'],
              'waon'       => ['label' => 'わをん', 'en' => 'wa, o, n'],
              'etc'        => ['label' => 'その他', 'en' => 'etc...'],
            ];

            // 先頭1文字からグループ判定（term meta "nor_yomi" があれば優先）
            $nor_iot_key_for_client_term = function($term) {
              $fallback = 'etc';
              if (!$term || is_wp_error($term)) return $fallback;

              $yomi = '';
              $meta = get_term_meta($term->term_id, 'nor_yomi', true);
              if (is_string($meta) && $meta !== '') $yomi = $meta;

              $src = $yomi !== '' ? $yomi : $term->name;
              $src = trim((string)$src);
              if ($src === '') return $fallback;

              $ch = mb_substr($src, 0, 1, 'UTF-8');

              // カタカナ→ひらがな（軽い正規化）
              $ch = mb_convert_kana($ch, 'c', 'UTF-8'); // カタカナをひらがなへ

              // ひらがな行判定
              $aiueo = ['あ','い','う','え','お'];
              $k     = ['か','き','く','け','こ','が','ぎ','ぐ','げ','ご'];
              $s     = ['さ','し','す','せ','そ','ざ','じ','ず','ぜ','ぞ'];
              $t     = ['た','ち','つ','て','と','だ','ぢ','づ','で','ど'];
              $n     = ['な','に','ぬ','ね','の'];
              $h     = ['は','ひ','ふ','へ','ほ','ば','び','ぶ','べ','ぼ','ぱ','ぴ','ぷ','ぺ','ぽ'];
              $m     = ['ま','み','む','め','も'];
              $y     = ['や','ゆ','よ'];
              $w     = ['わ','を','ん'];

              if (in_array($ch, $aiueo, true)) return 'aiueo';
              if (in_array($ch, $k, true))     return 'kakikukeko';
              if (in_array($ch, $s, true))     return 'sashisuseso';
              if (in_array($ch, $t, true))     return 'tachitsuteto';
              if (in_array($ch, $n, true))     return 'naninuneno';
              if (in_array($ch, $h, true))     return 'hahifuheho';
              if (in_array($ch, $m, true))     return 'mamimumemo';
              if (in_array($ch, $y, true))     return 'yayuyo';
              if (in_array($ch, $w, true))     return 'waon';

              return $fallback;
            };

            // Clients一覧は「登録済みクライアント」を基準にする（works 紐付け有無は問わない）
            $home_clients = get_terms([
              'taxonomy'   => 'work_client',
              'hide_empty' => false,
            ]);

            $iot_counts = array_fill_keys(array_keys($iot_groups), 0);
            $industry_counts_by_id = [];
            if (!is_wp_error($home_clients) && is_array($home_clients)) {
              foreach ($home_clients as $ct) {
                $key = $nor_iot_key_for_client_term($ct);
                if (!isset($iot_counts[$key])) $key = 'etc';
                $iot_counts[$key] += 1;

                // work_client term meta "nor_industry" を業種集計に利用
                $iid = (int) nor_get_term_meta_text((int) $ct->term_id, 'nor_industry');
                if ($iid > 0) {
                  if (!isset($industry_counts_by_id[$iid])) {
                    $industry_counts_by_id[$iid] = 0;
                  }
                  $industry_counts_by_id[$iid] += 1;
                }
              }
            }

            // Industries: モック順を優先しつつ、存在するものだけ出す
            $industries_order = [
              'agriculture-forestry-and-fisheries',
              'mining-and-quarrying-of-stone-and-gravel',
              'construction',
              'manufacturing',
              'electricity-gas-heat-supply-and-water',
              'information-and-communications',
              'transport-and-postal-services',
              'wholesale-and-retail-trade',
              'finance-and-insurance',
              'real-estate-and-goods-rental-and-leasing',
              'scientific-research-professional-and-technical-services',
              'accommodations-eating-and-drinking-services',
              'living-related-and-personal-services-and-amusement-services',
              'education-learning-support',
              'medical-health-care-and-welfare',
              'compound-services',
              'other-unclassified',
            ];

            $home_industries = get_terms([
              'taxonomy'   => 'work_industry',
              'hide_empty' => false,
            ]);

            $home_industries_by_slug = [];
            if (!is_wp_error($home_industries) && is_array($home_industries)) {
              foreach ($home_industries as $it) {
                $home_industries_by_slug[$it->slug] = $it;
              }
            }

            $iot_url = home_url('/clients/index-by-initial/');
            $industries_url = home_url('/clients/index-by-industry/');
          ?>

          <div class="detail">
            <nav class="index-by-initial" aria-label="Index by Initial navigation">
              <h4>Index by Initial</h4>
              <ul>
<?php
                  foreach ($iot_groups as $key => $g) {
                    if ((int)($iot_counts[$key] ?? 0) <= 0) continue;
                    echo '                <li><a href="' . esc_url($iot_url . '#client-index-' . $key) . '">' . esc_html($g['label']) . "</a></li>\n";
                  }
                ?>
              </ul>
            </nav>

            <nav class="index-by-industry" aria-label="Index by Industry navigation">
              <h4>Index by Industry</h4>
              <ul>
<?php
                  foreach ($industries_order as $slug) {
                    if (!isset($home_industries_by_slug[$slug])) continue;
                    $t = $home_industries_by_slug[$slug];
                    $count = (int) ($industry_counts_by_id[(int) $t->term_id] ?? 0);
                    if ($count <= 0) continue;
                    echo '                <li><a href="' . esc_url($industries_url . '#client-industry-' . $t->slug) . '">' . nor_render_label_with_abbr((string) $t->name) . "</a></li>\n";
                  }
                ?>
              </ul>
            </nav>
          </div>
        </nav>
      </div>

      <p class="scroll-top"><a href="#top" class="btn">Scroll to top</a></p>
    </div>
  </div>
</section>
