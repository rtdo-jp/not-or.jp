<?php
$is_contact_page = (function_exists('is_page') && is_page('contact'));
$contact_url = esc_url(home_url('/contact/'));
if ($is_contact_page) :
?>
<footer id="site-foot">
  <div class="inner">
    <p class="copyright"><small>© 2013–<?php echo date('Y'); ?> nør. All Rights Reserved.</small></p>
  </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
<?php
return;
endif;
?>
<footer id="site-foot">
  <div class="inner">
    <div class="notice">
      <div class="mode-select">
        <button class="btn" type="button" data-mode-cycle>
          <span class="mode-select-icon" aria-hidden="true">🌗</span> <span class="mode-select-label" aria-live="polite">Mode: System</span>
        </button>
      </div>
      <div class="textpair">
        <div class="ja" lang="ja">
          <p>本サイトは制作実績の記録を目的としたアーカイブです。<strong>営業・宣伝を目的としたサイトではありません。</strong></p>
          <p><small>本サイトに掲載されている商標、ロゴ、サービス名、ブランド名は、各社に帰属します。</small></p>
          <p>掲載内容の削除又は修正を御希望の場合は、お手数ですが「<a href="<?php echo $contact_url; ?>"><i>Contact</i></a>」から御連絡ください。</p>
        </div>
        <div class="en" lang="en">
          <p>This site is an archive of past works and is <strong>not intended for commercial promotion.</strong></p>
          <p><small>All trademarks, logos, service names, and brand names on this site belong to their respective owners.</small></p>
          <p>For removal or correction requests, please contact nør. through “<a href="<?php echo $contact_url; ?>"><i>Contact</i></a>”.</p>
        </div>
      </div>
      <p class="copyright"><small>© 2013–<?php echo date('Y'); ?> nør. All Rights Reserved.</small></p>
    </div>

    <div class="information">
      <nav class="fnav" aria-label="Footer navigation">
<?php
          // Footer navigation: list only links registered in WP menus.
          // No static fallback list is injected by code.
          $has_primary = has_nav_menu('global_primary');
          $has_left    = has_nav_menu('global_secondary_left');
          $has_right   = has_nav_menu('global_secondary_right');

          // Merge menu items into one <ul> and normalize indentation/newlines.
          $render_menu_items = function (string $location): string {
            $html = wp_nav_menu([
              'theme_location' => $location,
              'container'      => false,
              'depth'          => 1,
              'fallback_cb'    => false,
              'items_wrap'     => '%3$s',
              'echo'           => false,
              'item_spacing'   => 'discard',
            ]);
            return is_string($html) ? $html : '';
          };

          $items = '';
          if ($has_primary) {
            $items .= $render_menu_items('global_primary');
          }
          if ($has_left) {
            $items .= $render_menu_items('global_secondary_left');
          }
          if ($has_right) {
            $items .= $render_menu_items('global_secondary_right');
          }

          $items = trim((string) preg_replace('/\r\n?/', "\n", (string) $items));
          $items = (string) preg_replace('/>\s+</', '><', $items);
          $items = (string) preg_replace('/(<li\b)/i', "\n$1", $items);
          $items = ltrim($items, "\n");
          $items = (string) preg_replace('/\n{2,}/', "\n", $items);
          $items = trim($items);

          if ($items !== '') {
            echo "        <ul class=\"nav-list\">\n";
            $items = (string) preg_replace('/^/m', '          ', $items);
            echo $items . "\n";
            echo "        </ul>\n";
          } else {
            echo "        <ul class=\"nav-list\">\n";
            echo "          <li><span aria-hidden=\"true\">—</span><span class=\"visually-hidden\"><span lang=\"ja\">未設定</span> / <span lang=\"en\">Not set</span></span></li>\n";
            echo "        </ul>\n";
          }
        ?>
      </nav>
      <div class="biography">
        <div class="textpair">
          <p class="ja" lang="ja"><time datetime="2004">2004年</time>、北海道芸術デザイン専門学校卒業。同年、札幌のデザイン制作会社「株式会社オズ」に入社し、グラフィックデザイン及びWebデザインに従事。その後「株式会社ルーラー」にてWebデザイン及び<abbr title="User Interface">UI</abbr>デザインを中心にディレクターとして経験を積む。<time datetime="2012">2012年</time>、独立し「田村綾佑デザイン事務所」を設立。<time datetime="2014">2014年</time>より「ビットスター株式会社」にて執行役員を務め、制作部門の運営及び経営に携わる。現在、同社でデザインプロデュース及びディレクションを担当するほか、田村綾佑デザイン事務所「<dfn>nør.</dfn>」ではグラフィックデザイン・Webデザインを中心に制作活動を行う。</p>
          <p class="en" lang="en">Ryousuke Tamura graduated from Hokkaido College of Art &amp; Design in <time datetime="2004">2004</time>. He began his career at Oz Inc. in Sapporo, engaging in graphic and web design, then joined Ruler Inc. as a director focusing on web and <abbr title="User Interface">UI</abbr> design. In <time datetime="2012">2012</time>, he founded Ryousuke Tamura Design Office. Since <time datetime="2014">2014</time>, he has served as an executive officer at Bitstar Inc., overseeing the creative division and business operations. Currently, he leads design production and direction at Bitstar, while working independently under “<dfn>nør.</dfn>”</p>
        </div>

        <div class="signature">
          <p class="tagline">Graphic &amp; Web Design. <br>Not OR. Just right.</p>
          <div class="logo"><a href="<?php echo esc_url(home_url('/')); ?>">nør.</a></div>
        </div>
      </div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
