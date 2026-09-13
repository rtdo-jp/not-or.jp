<?php
/**
 * Template part: Card (Archive Year)
 *
 * Renders one <article class="card archive"> item.
 *
 * Args:
 * - year (int)
 * - year_url (string)
 * - projects (int)
 * - lu (array|null): ['dt' => string, 'd' => string, 'is_new' => bool]
 */

$args = isset($args) && is_array($args) ? $args : [];

$year = isset($args['year']) ? (int) $args['year'] : 0;
$year_url = isset($args['year_url']) && is_string($args['year_url']) ? (string) $args['year_url'] : '';
$projects = isset($args['projects']) ? (int) $args['projects'] : 0;
$lu = isset($args['lu']) && is_array($args['lu']) ? $args['lu'] : null;

if ($year <= 0 || $year_url === '') {
  return;
}

$updated_html = '';
if ($lu) {
  $updated_html = 'Last updated: <time datetime="' . esc_attr((string) ($lu['dt'] ?? '')) . '" class="value">' . esc_html((string) ($lu['d'] ?? '')) . '</time>';
  if (!empty($lu['is_new'])) {
    $updated_html .= '<span class="new">New</span>';
  }
}
?>
      <article class="card archive">
        <header class="head">
          <div class="title">
            <h3><a href="<?php echo esc_url($year_url); ?>"><time datetime="<?php echo esc_attr((string) $year); ?>" class="character-line"><?php echo esc_html((string) $year); ?></time></a></h3>
<?php if ($updated_html !== '') : ?>
            <p><?php echo $updated_html; ?></p>
<?php endif; ?>
          </div>
        </header>
        <div class="body">
          <div class="stats">
            <p class="pair">
              <data class="count" value="<?php echo esc_attr((string) $projects); ?>"><?php echo esc_html((string) $projects); ?></data>
              <span class="unit">projects archived.</span>
            </p>
          </div>
        </div>
      </article>
