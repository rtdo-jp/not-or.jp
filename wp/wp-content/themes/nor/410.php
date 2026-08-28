<?php
get_template_part('template-parts/error/error-page', null, [
  'status_code' => 410,
  'send_nocache' => true,
  'title' => '410 Gone',
  'tagline' => 'This page has been intentionally removed from the archive.',
  'desc_ja' => "削除済みページ。\n意図的なアーカイブ除外。",
  'desc_en' => "Page removed.\nIt may have been previously available.",
  'widget' => 'search',
  'search_default_ja' => '関連記録の検索。',
  'search_default_en' => 'You can search for related records.',
  'breadcrumb_label' => '410 Gone',
  'h2_ja' => 'このページは削除されました',
  'h2_en' => 'This page has been removed',
  'body_ja_html' => '関連記録の検索、又は下部の「<i>Categories, Tags &amp; Disciplines</i>」からの参照。<br class="desktop tablet">削除・非公開理由の確認は「<a href="' . esc_url(home_url('/policies/')) . '"><i>Policies</i></a>」（特に「<a href="' . esc_url(home_url('/policies/#takedown')) . '"><i>Takedown</i></a>」）及び「<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>」。',
  'body_en_html' => 'You may find related content via search or by browsing the indices under “<i>Categories, Tags &amp; Disciplines</i>” below.<br class="desktop tablet">If you need to confirm why this record was removed or made private, please see “<a href="' . esc_url(home_url('/policies/')) . '"><i>Policies</i></a>” (especially “<a href="' . esc_url(home_url('/policies/#takedown')) . '"><i>Takedown</i></a>”) and contact us via “<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>” if necessary.',
  'actions' => [
    ['url' => home_url('/'), 'label' => 'Back to Home'],
    ['url' => home_url('/contact/'), 'label' => 'Go to Contact'],
    ['url' => home_url('/policies/'), 'label' => 'Go to Policies', 'especially_url' => home_url('/policies/#takedown'), 'especially_label' => 'Takedown'],
  ],
  'include_indices' => true,
]);
