<?php
get_template_part('template-parts/error/error-page', null, [
  'status_code' => 403,
  'send_nocache' => true,
  'title' => '403 Forbidden',
  'tagline' => 'Access is restricted or this content is not publicly available.',
  'desc_ja' => "アクセス制限、\n又は非公開コンテンツ。",
  'desc_en' => "This page is shown when access is denied\nor the content is not publicly available.",
  'widget' => 'search',
  'search_default_ja' => 'アクセスが制限されています。検索から目的の記録をお探しください。',
  'search_default_en' => "Access is restricted. Use search to find what you're looking for.",
  'breadcrumb_label' => '403 Forbidden',
  'h2_ja' => 'このページへのアクセスは制限されています',
  'h2_en' => 'Access to this page is restricted',
  'body_ja_html' => 'このページは表示できません。<br>アクセス権限がない、又は公開されていない可能性があります。<br>「<a href="' . esc_url(home_url('/search/')) . '"><i>Search</i></a>」で探すか、「<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>」へ戻ってください。必要に応じて「<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>」から御連絡ください。',
  'body_en_html' => 'This page can&rsquo;t be displayed.<br>Access may be restricted, or the content may not be public.<br>Try “<a href="' . esc_url(home_url('/search/')) . '"><i>Search</i></a>”, go back to “<a href="' . esc_url(home_url('/')) . '"><i>Home</i></a>”, or contact us via “<a href="' . esc_url(home_url('/contact/')) . '"><i>Contact</i></a>” if needed.',
  'actions' => [
    ['url' => home_url('/'), 'label' => 'Back to Home'],
    ['url' => home_url('/contact/'), 'label' => 'Go to Contact'],
    ['url' => home_url('/policies/'), 'label' => 'Go to Policies'],
  ],
  'include_indices' => true,
]);
