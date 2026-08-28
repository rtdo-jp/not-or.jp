<?php
get_template_part('template-parts/error/error-page', null, [
  'status_code' => 503,
  'send_nocache' => true,
  'title' => '5xx Server Error',
  'tagline' => 'The site is temporarily unavailable due to a server-side issue.',
  'desc_ja' => "サーバー側の問題により、\nページを表示できない場合のエラーページ。",
  'desc_en' => "Error page shown when a server-side problem\nprevents the page from loading as usual.",
  'widget' => 'none',
  'breadcrumb_label' => '5xx Server Error',
  'h2_ja' => 'サーバーエラーが発生しました',
  'h2_en' => 'A server error occurred',
  'body_ja_html' => 'サーバーエラー。<br class="desktop tablet">一時的な障害又はメンテナンス。<br class="desktop tablet">再読み込み、又は「<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>」へ。解消しない場合は「<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>」。',
  'body_en_html' => 'Server error.<br class="desktop tablet">Temporary issue or maintenance.<br class="desktop tablet">Reload, or go to “<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>”. If it persists, contact us via “<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>”.',
  'actions' => [
    ['url' => home_url('/'), 'label' => 'Back to Home'],
    ['url' => home_url('/contact/'), 'label' => 'Go to Contact'],
  ],
  'include_indices' => false,
]);
