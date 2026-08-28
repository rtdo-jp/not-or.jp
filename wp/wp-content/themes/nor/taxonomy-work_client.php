<?php get_header(); ?>

<?php
// Back destination:
// - from=iot         -> /clients/iot/
// - from=industries  -> /clients/industries/
$from = '';
if (isset($_GET['from'])) {
  $from = sanitize_key((string) wp_unslash($_GET['from']));
}
if (!in_array($from, ['iot', 'industries'], true)) {
  $from = 'iot';
}

$back_to_list_url = ($from === 'industries')
  ? home_url('/clients/industries/')
  : home_url('/clients/iot/');

get_template_part('template-parts/taxonomy/taxonomy-works-list', null, [
  'root_label' => 'Clients',
  'root_url' => $back_to_list_url,
  'back_to_list_url' => $back_to_list_url,
  'append_query_args' => ['from' => $from],
  'use_desc_abbr' => false,
  'card_newline_count' => 2,
]);
?>

<?php get_footer(); ?>
