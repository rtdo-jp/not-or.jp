<?php get_header(); ?>

<?php
get_template_part('template-parts/taxonomy/taxonomy-works-list', null, [
  'root_label' => 'Categories',
  'root_url' => home_url('/categories/'),
  'back_to_list_url' => home_url('/categories/'),
  'append_query_args' => [],
  'use_desc_abbr' => true,
  'desc_abbr_map' => [
    'UI' => 'User Interface',
  ],
  'card_newline_count' => 2,
]);
?>

<?php get_footer(); ?>
