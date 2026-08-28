<?php get_header(); ?>

<?php
get_template_part('template-parts/taxonomy/taxonomy-works-list', null, [
  'root_label' => 'Tags',
  'root_url' => home_url('/tags/'),
  'back_to_list_url' => home_url('/tags/'),
  'append_query_args' => [],
  'use_desc_abbr' => false,
  'card_newline_count' => 2,
]);
?>

<?php get_footer(); ?>
