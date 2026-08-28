<?php
/**
 * Shared template for error pages (403 / 404 / 410 / 5xx).
 *
 * Usage:
 * get_template_part('template-parts/error/error-page', null, [
 *   'status_code'   => 404, // optional
 *   'send_nocache'  => true,
 *   'title'         => '404 Not Found',
 *   'tagline'       => 'Page not found.',
 *   'desc_ja'       => "ページ未検出。\nURLの誤り。",
 *   'desc_en'       => "Page not found.\nThe URL may be incorrect.",
 *   'widget'        => 'search', // search|none
 *   'search_default_ja' => 'ページが見つかりません。',
 *   'search_default_en' => 'Page not found.',
 *   'breadcrumb_label'  => '404 Not Found',
 *   'h2_ja'         => 'ページが見つかりません',
 *   'h2_en'         => 'Page not found',
 *   'body_ja_html'  => '...',
 *   'body_en_html'  => '...',
 *   'actions'       => [
 *     ['url' => home_url('/'), 'label' => 'Back to Home'],
 *   ],
 *   'include_indices' => true,
 * ]);
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = isset($args) && is_array($args) ? $args : [];

$status_code = isset($args['status_code']) ? (int) $args['status_code'] : 0;
$send_nocache = array_key_exists('send_nocache', $args) ? (bool) $args['send_nocache'] : true;

if ($status_code > 0) {
  status_header($status_code);
}
if ($send_nocache) {
  nocache_headers();
}

get_header();
?>

<main id="site-main" tabindex="-1">
<?php
  $works_count = nor_get_published_works_count();

$title = isset($args['title']) ? trim((string) $args['title']) : 'Error';
$tagline = isset($args['tagline']) ? trim((string) $args['tagline']) : 'An error occurred.';
$desc_ja = isset($args['desc_ja']) ? (string) $args['desc_ja'] : 'エラーが発生しました。';
$desc_en = isset($args['desc_en']) ? (string) $args['desc_en'] : 'An error occurred.';
$breadcrumb_label = isset($args['breadcrumb_label']) ? trim((string) $args['breadcrumb_label']) : $title;
$widget = isset($args['widget']) ? strtolower(trim((string) $args['widget'])) : 'none';
$include_indices = array_key_exists('include_indices', $args) ? (bool) $args['include_indices'] : true;

$hero_args = [
  'count'    => $works_count,
  'unit'     => 'works archived.',
  'title'    => $title,
  'tagline'  => $tagline,
  'desc_ja'  => $desc_ja,
  'desc_en'  => $desc_en,
  'specific_tag' => 'hgroup',
  'widget'   => ($widget === 'search') ? 'search' : 'none',
  'breadcrumbs' => nor_build_single_breadcrumb($breadcrumb_label),
];

if ($widget === 'search') {
  $search_default_ja = isset($args['search_default_ja']) ? trim((string) $args['search_default_ja']) : '関連記録の検索。';
  $search_default_en = isset($args['search_default_en']) ? trim((string) $args['search_default_en']) : 'You can search for related records.';

  ob_start();
  ?>
    <div class="textpair" data-state="default" aria-live="polite">
      <div class="default">
        <p class="ja" lang="ja"><?php echo esc_html($search_default_ja); ?></p>
        <p class="en" lang="en"><?php echo esc_html($search_default_en); ?></p>
      </div>
      <div class="searching" hidden>
        <p class="ja" lang="ja">検索中です…</p>
        <p class="en" lang="en">Searching…</p>
      </div>
      <div class="results" hidden>
        <p class="ja" lang="ja">「<span class="keyword"></span>」を含む記録を新しい順に表示しています。</p>
        <p class="en" lang="en">Showing records containing “<span class="keyword"></span>”, newest first.</p>
      </div>
      <div class="empty" hidden>
        <p class="ja" lang="ja">「<span class="keyword"></span>」に一致する記録は見つかりませんでした。最新の記録を新しい順に表示しています。</p>
        <p class="en" lang="en">No records matched “<span class="keyword"></span>”. Showing the latest records instead.</p>
      </div>
      <div class="error" hidden>
        <p class="ja" lang="ja">検索中にエラーが発生しました。しばらくしてから再度お試しください。</p>
        <p class="en" lang="en">An error occurred while searching. Please try again later.</p>
      </div>
    </div>
  <?php
  $state_html = trim((string) ob_get_clean(), "\r\n");

  $hero_args['search_form'] = [
    'action'      => home_url('/search/'),
    'query_param' => 'q',
    'input_id'    => 'keyword',
    'placeholder' => 'キーワード、社名、タグ… / keyword, client, tag…',
    'state_html'  => $state_html,
  ];
}

echo "\n";
echo nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
  'trim'                   => 'both',
  'normalize_first_indent' => true,
  'indent'                 => 2,
  'suffix'                 => "\n",
]);

$h2_ja = isset($args['h2_ja']) ? trim((string) $args['h2_ja']) : 'エラー';
$h2_en = isset($args['h2_en']) ? trim((string) $args['h2_en']) : 'Error';
$body_ja_html = isset($args['body_ja_html']) ? trim((string) $args['body_ja_html']) : 'ページの表示中にエラーが発生しました。';
$body_en_html = isset($args['body_en_html']) ? trim((string) $args['body_en_html']) : 'An error occurred while rendering this page.';
$actions = isset($args['actions']) && is_array($args['actions']) ? $args['actions'] : [];
?>

  <section class="section content-error">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($h2_ja); ?></span>（<span lang="en"><?php echo esc_html($h2_en); ?></span>）</h2>
    <div class="inner">

      <div class="content empty">
        <div class="textpair">
          <p class="ja" lang="ja"><?php echo $body_ja_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
          <p class="en" lang="en"><?php echo $body_en_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        </div>
<?php if (!empty($actions)) : ?>
        <ul class="actions">
<?php foreach ($actions as $action) : ?>
<?php
  $url = isset($action['url']) ? trim((string) $action['url']) : '';
  $label = isset($action['label']) ? trim((string) $action['label']) : '';
  if ($url === '' || $label === '') {
    continue;
  }
  $especially_url = isset($action['especially_url']) ? trim((string) $action['especially_url']) : '';
  $especially_label = isset($action['especially_label']) ? trim((string) $action['especially_label']) : '';
?>
          <li><a href="<?php echo esc_url($url); ?>" class="btn"><?php echo esc_html($label); ?></a><?php if ($especially_url !== '' && $especially_label !== '') : ?><span class="especially"><a href="<?php echo esc_url($especially_url); ?>"><?php echo esc_html($especially_label); ?></a></span><?php endif; ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>

    </div>
  </section>

<?php
if ($include_indices) {
  echo nor_render_indices([
    'trim'                   => 'both',
    'normalize_first_indent' => true,
    'indent'                 => 2,
    'suffix'                 => "\n",
  ], true);
}
?>

</main>

<?php get_footer(); ?>
