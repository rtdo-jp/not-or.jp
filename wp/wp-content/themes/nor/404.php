<?php
get_template_part('template-parts/error/error-page', null, [
  'send_nocache' => false,
  'title' => '404 Not Found',
  'tagline' => 'Page not found; it may have moved or the URL is incorrect.',
  'desc_ja' => "ページ未検出。\nURLの誤り、又は移動・削除の可能性。",
  'desc_en' => "Page not found.\nThe URL may be incorrect, moved, or removed.",
  'widget' => 'search',
  'search_default_ja' => 'ページが見つかりません。URLの誤り、又は移動・削除の可能性があります。検索からお探しください。',
  'search_default_en' => "Page not found. Use search to find what you're looking for.",
  'breadcrumb_label' => '404 Not Found',
  'h2_ja' => 'ページが見つかりません',
  'h2_en' => 'Page not found',
  'body_ja_html' => '検索を御利用いただくか、下部の「<i>Categories, Tags &amp; Disciplines</i>」から辿ることができます。<br>必要に応じて「<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>」にお戻りください。',
  'body_en_html' => 'You can search, or browse via “<i>Categories, Tags &amp; Disciplines</i>” below. <br>You can also return to “<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>” if needed.',
  'actions' => [
    ['url' => home_url('/'), 'label' => 'Back to Home'],
  ],
  'include_indices' => true,
]);
