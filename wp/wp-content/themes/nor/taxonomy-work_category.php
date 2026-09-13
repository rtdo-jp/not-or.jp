<?php get_header(); ?>

<?php
// Term tagline/desc_ja/desc_en are saved as plain text (raw <abbr> input
// is blocked by the host WAF on the taxonomy editor); hero-pages.php
// abbr-enriches them from the common dictionary at display time.
get_template_part('template-parts/taxonomy/taxonomy-works-list', null, [
  'root_label' => 'Categories',
  'root_url' => home_url('/categories/'),
  'back_to_list_url' => home_url('/categories/'),
  'append_query_args' => [],
  'card_newline_count' => 2,
]);
?>

<?php get_footer(); ?>
