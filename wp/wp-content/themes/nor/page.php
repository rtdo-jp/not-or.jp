<?php get_header(); ?>

<main id="site-main" tabindex="-1">

<?php
  echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, null, [
    'trim' => 'left',
    'indent' => 2,
  ]);
?>

  <section class="section pages">
    <div class="inner">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="content">
          <?php the_content(); ?>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </section>

</main>

<?php get_footer(); ?>
