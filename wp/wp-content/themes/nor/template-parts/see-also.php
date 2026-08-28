<?php
/**
 * Template part: See Also (pages)
 *
 * Usage:
 * get_template_part('template-parts/see-also', null, [
 *   'current_slug' => 'about', // optional
 * ]);
 */

if (!defined('ABSPATH')) {
  exit;
}

$args = (isset($args) && is_array($args)) ? $args : [];

$current_slug = isset($args['current_slug']) ? sanitize_title((string) $args['current_slug']) : '';
if ($current_slug === '') {
  $obj = get_queried_object();
  if (is_object($obj) && isset($obj->post_name)) {
    $current_slug = sanitize_title((string) $obj->post_name);
  }
}

$entries = [
  'about' => [
    'path'    => '/about/',
    'label'   => 'About',
    'ja_pre'  => '運営者情報・記録方針・制作姿勢などは「',
    'ja_post' => '」で御確認ください。',
    'en_pre'  => '“',
    'en_post' => '” — profile, record policy, and stance.',
  ],
  'policies' => [
    'path'    => '/policies/',
    'label'   => 'Policies',
    'ja_pre'  => 'ライセンス・クレジット・掲載ポリシーなどは「',
    'ja_post' => '」で御確認ください。',
    'en_pre'  => '“',
    'en_post' => '” — licenses, credits, and publication policy.',
  ],
  'notes' => [
    'path'    => '/notes/',
    'label'   => 'Notes',
    'ja_pre'  => 'サイト仕様・設計体系・稼働状況などは「',
    'ja_post' => '」で御確認ください。',
    'en_pre'  => '“',
    'en_post' => '” — site specs, design system, and diagnostics.',
  ],
  'contact' => [
    'path'    => '/contact/',
    'label'   => 'Contact',
    'ja_pre'  => 'お問い合わせ・削除依頼は「',
    'ja_post' => '」から御連絡ください。',
    'en_pre'  => '“',
    'en_post' => '” — inquiries and takedown requests.',
  ],
];

$targets = [];
$custom_targets = isset($args['targets']) && is_array($args['targets']) ? $args['targets'] : [];
$custom_targets = array_map(static fn($slug): string => sanitize_title((string) $slug), $custom_targets);
$custom_targets = array_values(array_filter($custom_targets, static fn($slug): bool => $slug !== '' && isset($entries[$slug])));

if (!empty($custom_targets)) {
  $targets = $custom_targets;
} else {
  $targets = ['about', 'policies', 'notes'];
  $targets = array_values(array_filter($targets, static fn($slug): bool => $slug !== $current_slug));
  $targets[] = 'contact';
}

$targets = array_values(array_unique($targets));

$render_see_also_item = static function (array $entry, string $url): string {
  $ja_pre  = isset($entry['ja_pre']) ? (string) $entry['ja_pre'] : '';
  $ja_post = isset($entry['ja_post']) ? (string) $entry['ja_post'] : '';
  $en_pre  = isset($entry['en_pre']) ? (string) $entry['en_pre'] : '';
  $en_post = isset($entry['en_post']) ? (string) $entry['en_post'] : '';
  $label   = isset($entry['label']) ? (string) $entry['label'] : '';

  $out  = "    <ul>\n";
  $out .= '      <li class="ja" lang="ja">' . esc_html($ja_pre) . '<a href="' . esc_url($url) . '"><i>' . esc_html($label) . '</i></a>' . esc_html($ja_post) . "</li>\n";
  $out .= '      <li class="en" lang="en">' . esc_html($en_pre) . '<a href="' . esc_url($url) . '"><i>' . esc_html($label) . '</i></a>' . esc_html($en_post) . "</li>\n";
  $out .= "    </ul>\n";

  return $out;
};
?>

<section class="see-also">
  <header class="head">
    <h2 class="en" lang="en">See Also</h2>
    <p class="ja" lang="ja">補足</p>
  </header>
  <div class="body">
<?php
    foreach ($targets as $slug) {
      if (!isset($entries[$slug]) || !is_array($entries[$slug])) continue;
      $entry = $entries[$slug];
      $url = home_url((string) $entry['path']);
      echo $render_see_also_item($entry, $url);
    }
?>
  </div>
</section>
