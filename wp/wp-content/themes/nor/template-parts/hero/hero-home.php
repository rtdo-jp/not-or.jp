<?php
/**
 * Template part: Home hero
 *
 * Notes:
 * - Home hero is unique (identity + statement) and is sourced from the original front-page.php.
 * - This partial intentionally computes the Works count internally (no args required).
 */

$works_count = nor_get_published_works_count();
$works_count_unit = nor_format_count_unit($works_count, 'work', 'works', 'archived');
?>

  <section class="hero">
    <div class="inner">
      <div class="identity">
        <div class="name">
          <p class="ja" lang="ja">田村綾佑デザイン事務所</p>
          <p class="en" lang="en">Ryousuke Tamura <br>Design Office</p>
          <h1>nør.</h1>
        </div>
        <div class="stats">
          <p class="pair">
            <data class="count" value="<?php echo esc_attr($works_count); ?>"><?php echo esc_html($works_count); ?></data>
            <span class="unit"><?php echo esc_html($works_count_unit); ?></span>
          </p>
          <p class="permission">Visuals appear only <br>with client permission. <br>Otherwise, entries remain <br>as <strong>text — never lost</strong>.</p>
        </div>
      </div>

      <div class="statement">
        <div class="textpair">
          <p class="ja" lang="ja">感性と理屈の交わる場所に、<br class="mobile">ちょうど良い、を見つけたい。<br>美しく整え、無から定義し、<br class="mobile">正しく伝え、次へ繋げる。<br>デザインは、なくても良い。<br>けれど、<br class="mobile">あれば明日が少し良くなる。</p>
          <p class="en" lang="en">Where sensibility meets reason, <br>we look for what feels just right. <br>We bring order and beauty, <br>define from nothing, <br>communicate with clarity, <br>and connect to what comes next. <br>Design isn’t always necessary — <br>but when it is, <br class="mobile">tomorrow feels a little better.</p>
        </div>
        <div class="visual">
          <span aria-hidden="true" class="visual-top">LATEST WORKS</span>
          <span aria-hidden="true" class="visual-bottom">LATEST WORKS</span>
        </div>
      </div>
    </div>
  </section>
