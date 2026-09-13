<?php get_header(); ?>

<?php
// Back destination:
// - from=index-by-initial   -> /clients/index-by-initial/
// - from=index-by-industry  -> /clients/index-by-industry/
$from = '';
if (isset($_GET['from'])) {
  $from = sanitize_key((string) wp_unslash($_GET['from']));
}
if (!in_array($from, ['index-by-initial', 'index-by-industry'], true)) {
  $from = 'index-by-initial';
}

$back_to_list_url = ($from === 'index-by-industry')
  ? home_url('/clients/index-by-industry/')
  : home_url('/clients/index-by-initial/');

get_template_part('template-parts/taxonomy/taxonomy-works-list', null, [
  'root_label' => 'Clients',
  'root_url' => $back_to_list_url,
  'back_to_list_url' => $back_to_list_url,
  'append_query_args' => ['from' => $from],
  'card_newline_count' => 2,
]);
?>

<?php get_footer(); ?>
