<?php get_header(); ?>

<main id="site-main" tabindex="-1">
<?php
    // ===== Page context =====
    $page_id = get_queried_object_id();
    $page_title = '';
    $section_h2_ja = '—';
    $section_h2_en = '—';
    $hero_args = [];

    $landing = nor_get_landing_page_shell_args($page_id, [
      'count' => 0,
      'unit' => 'years archived.',
      'title_fallback' => 'Archives',
      'section_h2_fallback' => '—',
    ]);
    $page_title = is_string($landing['title'] ?? null) ? (string) $landing['title'] : '';
    $section_h2_ja = is_string($landing['section_h2_ja'] ?? null) ? (string) $landing['section_h2_ja'] : '—';
    $section_h2_en = is_string($landing['section_h2_en'] ?? null) ? (string) $landing['section_h2_en'] : '—';
    $hero_args = (isset($landing['hero_args']) && is_array($landing['hero_args'])) ? $landing['hero_args'] : [];

    // ===== Archives years =====
    // NOTE: Only list years that actually have at least 1 published work.
    $start = 2013;
    $end   = (int) date('Y');

    // Helpers
    $get_year_projects_count = function (int $year): int {
      return nor_count_published_works_by_year((int) $year);
    };

    $get_year_last_updated = function (int $year) {
      return nor_get_latest_published_work_by_year((int) $year);
    };

    // Build year list (DESC), excluding empty years.
    $years = [];
    for ($y = $end; $y >= $start; $y--) {
      $projects = $get_year_projects_count((int) $y);
      if ($projects <= 0) {
        continue;
      }
      $years[] = [
        'year'     => (int) $y,
        'url'      => home_url("/archives/{$y}/"),
        'projects' => (int) $projects,
        'lu'       => $get_year_last_updated((int) $y),
      ];
    }

    $years_count = is_array($years) ? count($years) : 0;

    // ===== Hero (pages common) =====
    // NOTE: title/tagline/desc are expected to be provided via Page fields/meta.
    $hero_args['count'] = $years_count;
    $hero_args['unit'] = 'years archived.';
    $hero_args['widget'] = 'none';
    $hero_args['breadcrumbs'] = nor_build_single_breadcrumb($page_title);

    echo "\n" . nor_render_template_part('template-parts/hero/hero-pages', null, $hero_args, [
      'trim' => 'left',
      'indent' => 2,
    ]);
?>

  <section class="section list-archives">
    <h2 class="visually-hidden"><span lang="ja"><?php echo esc_html($section_h2_ja); ?></span>（<span lang="en"><?php echo esc_html($section_h2_en); ?></span>）</h2>
    <div class="inner">
<?php
  if (!empty($years)) {
    echo "\n";
    foreach ($years as $row) {
      $y        = (int) ($row['year'] ?? 0);
      $year_url = is_string($row['url'] ?? null) ? (string) $row['url'] : '';
      $projects = (int) ($row['projects'] ?? 0);
      $lu       = (is_array($row['lu'] ?? null)) ? $row['lu'] : null;

      get_template_part('template-parts/card/card-archive', null, [
        'year'     => $y,
        'year_url' => $year_url,
        'projects' => $projects,
        'lu'       => $lu,
      ]);
      echo "\n";
    }
  } else {
    echo "      <div class=\"content empty\" role=\"status\">\n";
    echo "        <div class=\"textpair\">\n";
    echo "          <p class=\"ja\" lang=\"ja\">年別アーカイブはまだありません。<br class=\"desktop tablet\">まずは制作記録を追加してください。</p>\n";
    echo "          <p class=\"en\" lang=\"en\">No archives yet. <br class=\"desktop tablet\">Add works to build year-based archives.</p>\n";
    echo "        </div>\n";
    echo "        <ul class=\"actions\">\n";
    echo '          <li><a href="' . esc_url(home_url('/works/')) . "\" class=\"btn\">Back to Works</a></li>\n";
    echo '          <li><a href="' . esc_url(home_url('/')) . "\" class=\"btn\">Back to Home</a></li>\n";
    echo "        </ul>\n";
    echo "      </div>\n";
  }
?>
    </div>
  </section>
<?php
  echo nor_render_indices([
    'trim' => 'left',
    'indent' => 2,
  ], true);
?>

</main>

<?php get_footer(); ?>
