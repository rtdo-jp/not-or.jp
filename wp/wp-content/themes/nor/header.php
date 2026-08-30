<?php
  $is_home = is_front_page();
  $is_contact_page = (function_exists('is_page') && is_page('contact'));
  $req_path = isset($GLOBALS['wp']) ? trim((string) $GLOBALS['wp']->request, '/') : '';
  $resolve_works_archive_year = static function (): string {
    $nor_year = trim((string) get_query_var('nor_year'));
    if ($nor_year !== '' && preg_match('/^\d{4}$/', $nor_year)) return $nor_year;

    $year = trim((string) get_query_var('year'));
    if ($year !== '' && preg_match('/^\d{4}$/', $year)) return $year;

    return '';
  };
  $works_archive_year = $resolve_works_archive_year();
  $site_url = trailingslashit(home_url('/'));
  $theme_uri = get_stylesheet_directory_uri();
  $ga_measurement_id = trim((string) get_option('nor_ga_measurement_id', 'G-NSEHNNMJDH'));
  $social_google_url = trim((string) get_option('nor_social_google_url', ''));
  $social_x_url = trim((string) get_option('nor_social_x_url', ''));
  $social_facebook_url = trim((string) get_option('nor_social_facebook_url', ''));
  $social_instagram_url = trim((string) get_option('nor_social_instagram_url', ''));
  $social_linkedin_url = trim((string) get_option('nor_social_linkedin_url', ''));
  $social_github_url = trim((string) get_option('nor_social_github_url', ''));
  $default_meta_desc_ja_override = trim((string) get_option('nor_default_meta_desc_ja_override', ''));
  $default_meta_desc_en_override = trim((string) get_option('nor_default_meta_desc_en_override', ''));
  $default_og_image_override = trim((string) get_option('nor_default_og_image_override', ''));
  $works_archive_meta_desc_ja_override = trim((string) get_option('nor_works_archive_meta_desc_ja_override', ''));
  $works_archive_meta_desc_en_override = trim((string) get_option('nor_works_archive_meta_desc_en_override', ''));
  $works_archive_og_image_override = trim((string) get_option('nor_works_archive_og_image_override', ''));
  $works_archive_og_title_override = trim((string) get_option('nor_works_archive_og_title_override', ''));
  $works_archive_robots_override = trim((string) get_option('nor_works_archive_robots_override', ''));
  $home_meta_title_override = trim((string) get_option('nor_home_meta_title_override', ''));
  $home_meta_desc_ja_override = trim((string) get_option('nor_home_meta_desc_ja_override', ''));
  $home_meta_desc_en_override = trim((string) get_option('nor_home_meta_desc_en_override', ''));
  $home_canonical_override = trim((string) get_option('nor_home_canonical_override', ''));
  $home_og_image_override = trim((string) get_option('nor_home_og_image_override', ''));
  $home_og_title_override = trim((string) get_option('nor_home_og_title_override', ''));
  $title_wp_raw = function_exists('wp_get_document_title') ? wp_get_document_title() : get_bloginfo('name');
  $title_wp = html_entity_decode((string) $title_wp_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  // Normalize common title separators to pipe.
  $title_wp = (string) preg_replace('/\s+[–—-]\s+/u', ' | ', $title_wp);
  // Collapse redundant middle segments, e.g.:
  // "Categories - ... | Categories | Site" -> "Categories - ... | Site"
  $title_segments = preg_split('/\s*\|\s*/u', (string) $title_wp, -1, PREG_SPLIT_NO_EMPTY);
  if (is_array($title_segments) && count($title_segments) > 1) {
    $reduced_segments = [];
    $seen_segments = [];
    foreach ($title_segments as $seg_raw) {
      $seg = trim((string) $seg_raw);
      if ($seg === '') continue;

      $seg_key = function_exists('mb_strtolower') ? mb_strtolower($seg, 'UTF-8') : strtolower($seg);
      if (isset($seen_segments[$seg_key])) continue;

      if (!empty($reduced_segments)) {
        $prev_seg = $reduced_segments[count($reduced_segments) - 1];
        $prefix_pattern = '/^' . preg_quote($seg, '/') . '\s*[-:–—]\s*/iu';
        if (preg_match($prefix_pattern, $prev_seg)) {
          continue;
        }
      }

      $reduced_segments[] = $seg;
      $seen_segments[$seg_key] = true;
    }
    if (!empty($reduced_segments)) {
      $title_wp = implode(' | ', $reduced_segments);
    }
  }

  // Canonical candidate (path-first, then conditional refinements)
  $page_url = $site_url;
  if (!$is_home) {
    if (function_exists('is_singular') && is_singular()) {
      $p = get_permalink();
      if (is_string($p) && $p !== '') $page_url = $p;
    } elseif (function_exists('is_tax') && is_tax()) {
      $term = get_queried_object();
      if ($term instanceof WP_Term) {
        $turl = get_term_link($term);
        if (!is_wp_error($turl) && is_string($turl) && $turl !== '') $page_url = $turl;
      }
    } elseif (function_exists('is_post_type_archive') && is_post_type_archive('works')) {
      if ($works_archive_year !== '') {
        $page_url = home_url('/archives/' . rawurlencode($works_archive_year) . '/');
      } else {
        $paged = max(1, (int) get_query_var('paged'));
        $page_url = ($paged > 1) ? get_pagenum_link($paged) : home_url('/works/');
      }
    } elseif (function_exists('is_404') && is_404()) {
      $page_url = home_url('/404/');
    } elseif (in_array($req_path, ['403', '410', '5xx'], true)) {
      $page_url = home_url('/' . $req_path . '/');
    } elseif ($req_path !== '') {
      $page_url = home_url('/' . $req_path . '/');
    }
  }

  // Per-entry head override (Page / Works).
  $seo_title_override = '';
  $seo_desc_ja_override = '';
  $seo_desc_en_override = '';
  $seo_canonical_override = '';
  $seo_og_image_override = '';
  $seo_og_title_override = '';
  $seo_robots_override = '';
  $singular_id = 0;

  if (function_exists('is_singular') && is_singular()) {
    $singular_id = (int) get_queried_object_id();
    if ($singular_id > 0) {
      $seo_title_override = trim((string) get_post_meta($singular_id, 'nor_meta_title_override', true));
      $seo_desc_ja_override = trim((string) get_post_meta($singular_id, 'nor_meta_desc_ja_override', true));
      $seo_desc_en_override = trim((string) get_post_meta($singular_id, 'nor_meta_desc_en_override', true));
      $seo_canonical_override = trim((string) get_post_meta($singular_id, 'nor_canonical_override', true));
      $seo_og_image_override = trim((string) get_post_meta($singular_id, 'nor_og_image_override', true));
      $seo_og_title_override = trim((string) get_post_meta($singular_id, 'nor_og_title_override', true));
      $seo_robots_override = trim((string) get_post_meta($singular_id, 'nor_robots_override', true));
    }
  }

  // Inherit selected SEO overrides for dynamic listing pages from their fixed-page counterparts.
  // We intentionally keep title/canonical dynamic to preserve page-specific uniqueness.
  $seo_inherit_page_slug = '';
  if (!(function_exists('is_singular') && is_singular())) {
    if (function_exists('is_tax') && is_tax('work_category')) {
      $seo_inherit_page_slug = 'categories';
    } elseif (function_exists('is_tax') && is_tax('work_tag')) {
      $seo_inherit_page_slug = 'tags';
    } elseif (function_exists('is_tax') && is_tax('work_client')) {
      $seo_inherit_page_slug = 'clients';
    } elseif (function_exists('is_post_type_archive') && is_post_type_archive('works')) {
      if ($works_archive_year !== '') $seo_inherit_page_slug = 'archives';
    }
  }

  if ($seo_inherit_page_slug !== '') {
    $seo_inherit_page = get_page_by_path($seo_inherit_page_slug);
    if ($seo_inherit_page instanceof WP_Post) {
      $seo_inherit_id = (int) $seo_inherit_page->ID;
      if ($seo_inherit_id > 0) {
        $inherit_desc_ja = trim((string) get_post_meta($seo_inherit_id, 'nor_meta_desc_ja_override', true));
        $inherit_desc_en = trim((string) get_post_meta($seo_inherit_id, 'nor_meta_desc_en_override', true));
        $inherit_og_image = trim((string) get_post_meta($seo_inherit_id, 'nor_og_image_override', true));
        $inherit_og_title = trim((string) get_post_meta($seo_inherit_id, 'nor_og_title_override', true));
        $inherit_robots = trim((string) get_post_meta($seo_inherit_id, 'nor_robots_override', true));

        if ($seo_desc_ja_override === '' && $inherit_desc_ja !== '') $seo_desc_ja_override = $inherit_desc_ja;
        if ($seo_desc_en_override === '' && $inherit_desc_en !== '') $seo_desc_en_override = $inherit_desc_en;
        if ($seo_og_image_override === '' && $inherit_og_image !== '') $seo_og_image_override = $inherit_og_image;
        if ($seo_og_title_override === '' && $inherit_og_title !== '') $seo_og_title_override = $inherit_og_title;
        if ($seo_robots_override === '' && $inherit_robots !== '') $seo_robots_override = $inherit_robots;
      }
    }
  }

  // Dedicated Works-archive settings apply to /works/page/{n}/ (non-year archives).
  if (function_exists('is_post_type_archive') && is_post_type_archive('works')) {
    if ($works_archive_year === '') {
      if ($seo_desc_ja_override === '' && $works_archive_meta_desc_ja_override !== '') $seo_desc_ja_override = $works_archive_meta_desc_ja_override;
      if ($seo_desc_en_override === '' && $works_archive_meta_desc_en_override !== '') $seo_desc_en_override = $works_archive_meta_desc_en_override;
      if ($seo_og_image_override === '' && $works_archive_og_image_override !== '') $seo_og_image_override = $works_archive_og_image_override;
      if ($seo_og_title_override === '' && $works_archive_og_title_override !== '') $seo_og_title_override = $works_archive_og_title_override;
      if ($seo_robots_override === '' && $works_archive_robots_override !== '') $seo_robots_override = $works_archive_robots_override;
    }
  }

  if ($is_home && $home_canonical_override !== '') {
    $page_url = (string) $home_canonical_override;
  } elseif ($seo_canonical_override !== '') {
    $page_url = (string) $seo_canonical_override;
  }

  // Writings list pagination: the fixed page's nor_canonical_override is a
  // page-1-only value ("/writings/") and must not be reused as-is on later
  // pages — each page self-canonicalizes to its own /writings/page/N/ URL.
  if (function_exists('is_page') && is_page('writings')) {
    $writings_paged = max(1, (int) get_query_var('paged'));
    if ($writings_paged > 1) {
      $page_url = home_url('/writings/page/' . $writings_paged . '/');
    }
  }

  if (function_exists('nor_seo_meta_normalize_url')) {
    $normalized_page_url = (string) nor_seo_meta_normalize_url((string) $page_url);
    if ($normalized_page_url !== '') {
      $page_url = $normalized_page_url;
    }
  }

  // Description (JA/EN): explicit settings only.
  // Fallback order: default settings -> per-entry override -> Home override.
  $desc_ja = '';
  $desc_en = '';
  if ($default_meta_desc_ja_override !== '') $desc_ja = $default_meta_desc_ja_override;
  if ($default_meta_desc_en_override !== '') $desc_en = $default_meta_desc_en_override;
  if ($seo_desc_ja_override !== '') $desc_ja = $seo_desc_ja_override;
  if ($seo_desc_en_override !== '') $desc_en = $seo_desc_en_override;
  if ($is_home && $home_meta_desc_ja_override !== '') $desc_ja = $home_meta_desc_ja_override;
  if ($is_home && $home_meta_desc_en_override !== '') $desc_en = $home_meta_desc_en_override;

  // Meta descriptions should be single-line for stable head output.
  $normalize_meta_line = static function (string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $text = (string) preg_replace('/\s+/u', ' ', $text);
    return trim($text);
  };
  $desc_ja = $normalize_meta_line((string) $desc_ja);
  $desc_en = $normalize_meta_line((string) $desc_en);

  $desc_parts = array_values(array_filter([$desc_ja, $desc_en], static function ($v) {
    return is_string($v) && $v !== '';
  }));
  $desc = !empty($desc_parts) ? implode(' | ', $desc_parts) : '';

  // Title (default formula + optional override)
  $site_name_for_title = 'nør. Ryousuke Tamura Design Office';
  $title = $title_wp;
  $title_auto = '';
  if (function_exists('is_singular') && is_singular() && $singular_id > 0) {
    $current_name = trim((string) wp_strip_all_tags((string) get_the_title($singular_id)));
    if (is_singular('works') && function_exists('nor_get_work_public_title')) {
      $current_name = trim((string) wp_strip_all_tags((string) nor_get_work_public_title($singular_id, $current_name)));
    }
    $page_name = is_singular('works') ? 'Works' : $current_name;

    $head = $current_name;

    $segments = [];
    $seen = [];
    $push_unique = static function (array &$segments, array &$seen, string $value): void {
      $value = trim($value);
      if ($value === '') return;
      $key = strtolower($value);
      if (isset($seen[$key])) return;
      $segments[] = $value;
      $seen[$key] = true;
    };

    $push_unique($segments, $seen, $head);
    $push_unique($segments, $seen, $page_name);
    $push_unique($segments, $seen, $site_name_for_title);
    $title_auto = implode(' | ', $segments);
  }

  if ($title_auto !== '') {
    $title = $title_auto;
  }
  if ($seo_title_override !== '') {
    $title = $seo_title_override;
  }
  // Year archives: keep title and social title consistent with Archives-specific SEO setting.
  // If no explicit title override exists, adopt OG title override (inherited from fixed page "archives").
  if (!$is_home && $works_archive_year !== '' && $seo_title_override === '' && $seo_og_title_override !== '') {
    $title = $seo_og_title_override;
  }
  if ($is_home && $home_meta_title_override !== '') {
    $title = $home_meta_title_override;
  }

  // Final title cleanup (defensive): avoid duplicated middle segments
  // like "... | Categories | Site" when already prefixed in the first segment.
  $title_segments_final = preg_split('/\s*\|\s*/u', (string) $title, -1, PREG_SPLIT_NO_EMPTY);
  if (is_array($title_segments_final) && count($title_segments_final) > 1) {
    $final_reduced = [];
    $final_seen = [];
    foreach ($title_segments_final as $seg_raw) {
      $seg = trim((string) $seg_raw);
      if ($seg === '') continue;

      $seg_key = function_exists('mb_strtolower') ? mb_strtolower($seg, 'UTF-8') : strtolower($seg);
      if (isset($final_seen[$seg_key])) continue;

      if (!empty($final_reduced)) {
        $prev_seg = $final_reduced[count($final_reduced) - 1];
        $prefix_pattern = '/^' . preg_quote($seg, '/') . '\s*[-:–—]\s*/iu';
        if (preg_match($prefix_pattern, $prev_seg)) {
          continue;
        }
      }

      $final_reduced[] = $seg;
      $final_seen[$seg_key] = true;
    }
    if (!empty($final_reduced)) {
      $title = implode(' | ', $final_reduced);
    }
  }

  $social_title = $title;
  // Year archives: force consistency between <title> and og/twitter titles.
  if (!(!$is_home && $works_archive_year !== '')) {
    if ($seo_og_title_override !== '') $social_title = $seo_og_title_override;
    if ($is_home && $home_og_title_override !== '') $social_title = $home_og_title_override;
  }
  $social_title_segments = preg_split('/\s*\|\s*/u', (string) $social_title, -1, PREG_SPLIT_NO_EMPTY);
  if (is_array($social_title_segments) && count($social_title_segments) > 1) {
    $social_reduced = [];
    $social_seen = [];
    foreach ($social_title_segments as $seg_raw) {
      $seg = trim((string) $seg_raw);
      if ($seg === '') continue;

      $seg_key = function_exists('mb_strtolower') ? mb_strtolower($seg, 'UTF-8') : strtolower($seg);
      if (isset($social_seen[$seg_key])) continue;

      if (!empty($social_reduced)) {
        $prev_seg = $social_reduced[count($social_reduced) - 1];
        $prefix_pattern = '/^' . preg_quote($seg, '/') . '\s*[-:–—]\s*/iu';
        if (preg_match($prefix_pattern, $prev_seg)) {
          continue;
        }
      }

      $social_reduced[] = $seg;
      $social_seen[$seg_key] = true;
    }
    if (!empty($social_reduced)) {
      $social_title = implode(' | ', $social_reduced);
    }
  }

  $robots_default = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
  $robots = $robots_default;
  $allowed_robots = [
    $robots_default,
    'index, follow',
    'noindex, follow',
    'noindex, nofollow',
    'index, nofollow',
  ];
  $env_type = function_exists('nor_get_environment_type')
    ? (string) nor_get_environment_type()
    : ((function_exists('wp_get_environment_type') ? (string) wp_get_environment_type() : 'production'));
  $force_noindex_by_env = (strtolower(trim((string) $env_type)) !== 'production');
  $discourage_search = ((int) get_option('blog_public', 1) === 0);
  if ($force_noindex_by_env || $discourage_search) {
    $robots = 'noindex, nofollow';
  } elseif ($seo_robots_override !== '' && in_array($seo_robots_override, $allowed_robots, true)) {
    $robots = $seo_robots_override;
  }

  // OGP image: explicit settings only.
  // Fallback order: default settings -> per-entry override -> Home override.
  $og_image = '';
  if ($default_og_image_override !== '') $og_image = $default_og_image_override;
  if ($seo_og_image_override !== '') $og_image = $seo_og_image_override;
  if ($is_home && $home_og_image_override !== '') $og_image = $home_og_image_override;
  $og_image_secure = '';
  $og_image_type = '';
  $og_image_width = 0;
  $og_image_height = 0;
  $og_image_alt = '';
  if ($og_image !== '') {
    $og_image_secure = set_url_scheme($og_image, 'https');
    $attachment_id = (int) attachment_url_to_postid($og_image);
    if ($attachment_id > 0) {
      $mime = (string) get_post_mime_type($attachment_id);
      if (str_starts_with($mime, 'image/')) {
        $og_image_type = $mime;
      }

      $meta = wp_get_attachment_metadata($attachment_id);
      if (is_array($meta)) {
        if (!empty($meta['width'])) $og_image_width = (int) $meta['width'];
        if (!empty($meta['height'])) $og_image_height = (int) $meta['height'];
      }

      $alt_raw = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
      $og_image_alt = trim(wp_strip_all_tags($alt_raw));
    } else {
      $home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
      $image_host = (string) wp_parse_url($og_image, PHP_URL_HOST);
      $image_path = (string) wp_parse_url($og_image, PHP_URL_PATH);
      if ($home_host !== '' && $image_host !== '' && strcasecmp($home_host, $image_host) === 0 && $image_path !== '') {
        $local_path = untrailingslashit(ABSPATH) . '/' . ltrim($image_path, '/');
        if (is_readable($local_path)) {
          $img_info = @getimagesize($local_path);
          if (is_array($img_info)) {
            if (!empty($img_info[0])) $og_image_width = (int) $img_info[0];
            if (!empty($img_info[1])) $og_image_height = (int) $img_info[1];
            if (!empty($img_info['mime']) && is_string($img_info['mime']) && str_starts_with($img_info['mime'], 'image/')) {
              $og_image_type = $img_info['mime'];
            }
          }
        }
      }
    }

    if ($og_image_alt === '') {
      $fallback_alt = trim((string) $social_title);
      if ($fallback_alt === '') $fallback_alt = trim((string) $title);
      $og_image_alt = $fallback_alt;
    }
  }
  $og_type = (is_singular('works') || is_singular('post')) ? 'article' : 'website';
  $has_social_meta = ($social_title !== '' || $desc !== '' || $og_image !== '');

  $same_as = array_values(array_unique(array_filter([
    $social_google_url,
    $social_x_url,
    $social_facebook_url,
    $social_instagram_url,
    $social_linkedin_url,
    $social_github_url,
  ], static function ($v): bool {
    return is_string($v) && trim($v) !== '';
  })));

  // Structured data
  $schema_ctx = function_exists('nor_get_header_schema_context')
    ? nor_get_header_schema_context((string) $site_url, (string) $page_url, (string) $req_path, (string) $works_archive_year, (bool) $is_home)
    : ['schema_page_type' => 'WebPage', 'breadcrumbs' => [['name' => 'Home', 'url' => $site_url]]];
  $schema_page_type = is_string($schema_ctx['schema_page_type'] ?? null) ? (string) $schema_ctx['schema_page_type'] : 'WebPage';
  $breadcrumbs = is_array($schema_ctx['breadcrumbs'] ?? null) ? $schema_ctx['breadcrumbs'] : [['name' => 'Home', 'url' => $site_url]];

  $page_base = (string) $page_url;
  $qpos = strpos($page_base, '?');
  if ($qpos !== false) $page_base = substr($page_base, 0, $qpos);
  $page_base = trailingslashit(untrailingslashit($page_base));

  $breadcrumb_list = [];
  foreach ($breadcrumbs as $idx => $crumb) {
    $item = [
      '@type'    => 'ListItem',
      'position' => (int) $idx + 1,
      'name'     => (string) ($crumb['name'] ?? ''),
    ];
    $item_url = $crumb['url'] ?? null;
    if (is_string($item_url) && $item_url !== '') $item['item'] = $item_url;
    $breadcrumb_list[] = $item;
  }

  $schema_graph = [];
  $schema_graph[] = [
    '@type' => 'WebSite',
    '@id'   => $site_url . '#website',
    'url'   => $site_url,
    'name'  => 'nør.',
    'alternateName' => ['Noah', 'ノア', 'nor.', 'nor', 'not-or'],
    'inLanguage' => ['ja', 'en'],
    'publisher' => ['@id' => $site_url . '#organization'],
    'potentialAction' => [
      [
        '@type' => 'SearchAction',
        'target' => [
          '@type' => 'EntryPoint',
          'urlTemplate' => home_url('/search/?q={search_term_string}'),
        ],
        'query-input' => 'required name=search_term_string',
      ],
    ],
  ];
  if ($desc_ja !== '' && $desc_en !== '') {
    $schema_graph[0]['description'] = [
      ['@value' => $desc_ja, '@language' => 'ja'],
      ['@value' => $desc_en, '@language' => 'en'],
    ];
  } elseif ($desc !== '') {
    $schema_graph[0]['description'] = $desc;
  }

  $primary_image_id = '';
  if ($og_image !== '') {
    $primary_image_id = $page_base . '#primaryimage';
    $schema_graph[] = [
      '@type' => 'ImageObject',
      '@id'   => $primary_image_id,
      'url'   => $og_image,
    ];
  }

  $page_node = [
    '@type' => $schema_page_type,
    '@id'   => $page_base . '#webpage',
    'url'   => $page_url,
    'name'  => $title,
    'inLanguage' => ['ja', 'en'],
    'isPartOf' => ['@id' => $site_url . '#website'],
    'about' => ['@id' => $site_url . '#organization'],
    'publisher' => ['@id' => $site_url . '#organization'],
    'breadcrumb' => ['@id' => $page_base . '#breadcrumb'],
  ];
  if ($primary_image_id !== '') {
    $page_node['primaryImageOfPage'] = ['@id' => $primary_image_id];
  }
  if ($desc_ja !== '' && $desc_en !== '') {
    $page_node['description'] = [
      ['@value' => $desc_ja, '@language' => 'ja'],
      ['@value' => $desc_en, '@language' => 'en'],
    ];
  } elseif ($desc !== '') {
    $page_node['description'] = $desc;
  }
  if (is_singular('works')) $page_node['mainEntity'] = ['@id' => $page_base . '#work'];
  if (is_singular('post')) $page_node['mainEntity'] = ['@id' => $page_base . '#article'];

  // FAQPage: build mainEntity from stored FAQ markup when available.
  if ($schema_page_type === 'FAQPage' && function_exists('is_page') && is_page()) {
    $faq_main_entities = [];
    $faq_page_id = (int) get_queried_object_id();
    $raw_content = (string) get_post_field('post_content', $faq_page_id);
    if ($raw_content !== '') {
      $faq_html = function_exists('do_blocks') ? (string) do_blocks($raw_content) : $raw_content;
      if ($faq_html !== '' && class_exists('DOMDocument')) {
        $normalize_text = static function ($s): string {
          $s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
          $s = trim((string) preg_replace('/\s+/u', ' ', $s));
          return $s;
        };

        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $faq_html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($loaded) {
          $xp = new DOMXPath($dom);
          $qa_nodes = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' faq-qa ')]");
          if ($qa_nodes !== false) {
            foreach ($qa_nodes as $qa) {
              $q_ja = $normalize_text($xp->evaluate("string(.//*[contains(concat(' ', normalize-space(@class), ' '), ' question ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' ja ')][1])", $qa));
              $a_ja = $normalize_text($xp->evaluate("string(.//*[contains(concat(' ', normalize-space(@class), ' '), ' answer ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' ja ')][1])", $qa));
              $q_en = $normalize_text($xp->evaluate("string(.//*[contains(concat(' ', normalize-space(@class), ' '), ' question ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' en ')][1])", $qa));
              $a_en = $normalize_text($xp->evaluate("string(.//*[contains(concat(' ', normalize-space(@class), ' '), ' answer ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' en ')][1])", $qa));

              if ($q_ja !== '' && $a_ja !== '') {
                $faq_main_entities[] = [
                  '@type' => 'Question',
                  'name' => $q_ja,
                  'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a_ja,
                  ],
                ];
              }
              if ($q_en !== '' && $a_en !== '') {
                $faq_main_entities[] = [
                  '@type' => 'Question',
                  'name' => $q_en,
                  'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a_en,
                  ],
                ];
              }
            }
          }
        }
      }
    }

    $raw_content_empty = (trim((string) $raw_content) === '');
    if (empty($faq_main_entities) && $raw_content_empty && function_exists('nor_faqs_get_sections') && function_exists('nor_faqs_build_main_entities')) {
      $fallback_sections = nor_faqs_get_sections($faq_page_id);
      if (is_array($fallback_sections) && !empty($fallback_sections)) {
        $faq_main_entities = nor_faqs_build_main_entities($fallback_sections);
      }
    }

    if (!empty($faq_main_entities)) {
      $page_node['mainEntity'] = array_values($faq_main_entities);
    }
  }
  $schema_graph[] = $page_node;

  if (is_singular('works')) {
    $work_id = (int) get_queried_object_id();
    $work_name = trim((string) wp_strip_all_tags((string) get_the_title($work_id)));
    if (function_exists('nor_get_work_public_title')) {
      $work_name = trim((string) wp_strip_all_tags((string) nor_get_work_public_title($work_id, $work_name)));
    }
    $summary_ja = trim((string) wp_strip_all_tags((string) get_post_field('post_excerpt', $work_id)));
    $summary_en = trim((string) wp_strip_all_tags((string) get_post_meta($work_id, 'nor_summary_en', true)));
    if (function_exists('nor_get_work_public_text')) {
      $summary_ja = trim((string) wp_strip_all_tags((string) nor_get_work_public_text($work_id, $summary_ja)));
      $summary_en = trim((string) wp_strip_all_tags((string) nor_get_work_public_text($work_id, $summary_en, 'en')));
    }
    $desc_pair = [];
    if ($summary_ja !== '') $desc_pair[] = ['@value' => $summary_ja, '@language' => 'ja'];
    if ($summary_en !== '') $desc_pair[] = ['@value' => $summary_en, '@language' => 'en'];

    $creative = [
      '@type' => 'CreativeWork',
      '@id'   => $page_base . '#work',
      'url'   => $page_url,
      'name'  => $work_name,
      'creator' => ['@id' => $site_url . '#organization'],
      'publisher' => ['@id' => $site_url . '#organization'],
      'inLanguage' => ['ja', 'en'],
      'isPartOf' => ['@id' => $site_url . '#website'],
      'mainEntityOfPage' => ['@id' => $page_base . '#webpage'],
    ];
    if ($og_image !== '') $creative['image'] = $og_image;
    if (!empty($desc_pair)) $creative['description'] = $desc_pair;

    $published_iso = (string) get_the_date('c', $work_id);
    $updated_iso = (string) get_the_modified_date('c', $work_id);
    if ($published_iso !== '') $creative['datePublished'] = $published_iso;
    if ($updated_iso !== '') $creative['dateModified'] = $updated_iso;

    $schema_graph[] = $creative;
  }

  if (is_singular('post')) {
    $writing_id = (int) get_queried_object_id();
    $writing_name = trim((string) wp_strip_all_tags((string) get_the_title($writing_id)));
    $writing_summary_ja = trim((string) wp_strip_all_tags((string) get_post_field('post_excerpt', $writing_id)));
    $writing_summary_en = trim((string) wp_strip_all_tags((string) get_post_meta($writing_id, 'nor_summary_en', true)));

    $writing_desc_pair = [];
    if ($writing_summary_ja !== '') $writing_desc_pair[] = ['@value' => $writing_summary_ja, '@language' => 'ja'];
    if ($writing_summary_en !== '') $writing_desc_pair[] = ['@value' => $writing_summary_en, '@language' => 'en'];

    // Article, not CreativeWork: author is the Person (not the Organization,
    // unlike Works' CreativeWork "creator"), per static's confirmed spec.
    $article = [
      '@type' => 'Article',
      '@id'   => $page_base . '#article',
      'url'   => $page_url,
      'headline' => $writing_name,
      'name'  => $writing_name,
      'author' => ['@id' => $site_url . '#person'],
      'publisher' => ['@id' => $site_url . '#organization'],
      'inLanguage' => ['ja', 'en'],
      'isPartOf' => ['@id' => $site_url . '#website'],
      'mainEntityOfPage' => ['@id' => $page_base . '#webpage'],
    ];
    if ($og_image !== '') $article['image'] = $og_image;
    if (!empty($writing_desc_pair)) $article['description'] = $writing_desc_pair;

    $writing_published_iso = (string) get_the_date('c', $writing_id);
    $writing_updated_iso = (string) get_the_modified_date('c', $writing_id);
    if ($writing_published_iso !== '') $article['datePublished'] = $writing_published_iso;
    if ($writing_updated_iso !== '') $article['dateModified'] = $writing_updated_iso;

    $schema_graph[] = $article;
  }

  $schema_graph[] = [
    '@type' => 'BreadcrumbList',
    '@id'   => $page_base . '#breadcrumb',
    'itemListElement' => $breadcrumb_list,
  ];

  $organization_node = [
    '@type' => 'Organization',
    '@id'   => $site_url . '#organization',
    'name'  => 'nør.',
    'alternateName' => ['Ryousuke Tamura Design Office', '田村綾佑デザイン事務所'],
    'url'   => $site_url,
    'logo'  => [
      '@type' => 'ImageObject',
      '@id'   => $site_url . '#logo',
      'url'   => $theme_uri . '/assets/img/logo.png',
    ],
    'founder' => ['@id' => $site_url . '#person'],
    'location' => [
      '@type' => 'Place',
      'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'Sapporo',
        'addressRegion' => 'Hokkaido',
        'addressCountry' => 'JP',
      ],
    ],
  ];
  if ($schema_page_type === 'ContactPage') {
    $organization_node['contactPoint'] = [
      [
        '@type' => 'ContactPoint',
        'contactType' => 'customer support',
        'url' => home_url('/contact/'),
        'availableLanguage' => ['ja', 'en'],
        'areaServed' => 'JP',
      ],
    ];
  }
  $schema_graph[] = $organization_node;

  $schema_graph[] = [
    '@type' => 'Person',
    '@id'   => $site_url . '#person',
    'name'  => 'Ryousuke Tamura',
    'alternateName' => '田村綾佑',
    'jobTitle' => ['Designer', 'Developer', 'Art Director'],
    'url'   => $site_url,
    'sameAs' => $same_as,
    'knowsAbout' => [
      'Graphic Design',
      'Web Design',
      'UI/UX Design',
      'Front-end Development',
      'Brand Identity',
    ],
    'alumniOf' => [
      '@type' => 'EducationalOrganization',
      'name' => 'Hokkaido College of Art & Design',
      'alternateName' => '北海道芸術デザイン専門学校',
    ],
    'worksFor' => [
      [
        '@type' => 'Organization',
        'name' => 'Bitstar Inc.',
        'alternateName' => 'ビットスター株式会社',
      ],
      ['@id' => $site_url . '#organization'],
    ],
    'affiliation' => [
      [
        '@type' => 'OrganizationRole',
        'roleName' => 'Past affiliation',
        'organization' => [
          '@type' => 'Organization',
          'name' => 'Oz Inc.',
          'alternateName' => '株式会社オズ',
        ],
      ],
      [
        '@type' => 'OrganizationRole',
        'roleName' => 'Past affiliation',
        'organization' => [
          '@type' => 'Organization',
          'name' => 'Ruler Inc.',
          'alternateName' => '株式会社ルーラー',
        ],
      ],
    ],
  ];
  if (empty($same_as)) {
    $person_idx = count($schema_graph) - 1;
    if (isset($schema_graph[$person_idx]['sameAs'])) {
      unset($schema_graph[$person_idx]['sameAs']);
    }
  }

  $schema_json = wp_json_encode(
    ['@context' => 'https://schema.org', '@graph' => $schema_graph],
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
  );
  // JSON_PRETTY_PRINT uses 4-space indents by default. Normalize to 2 spaces for source readability.
  if (is_string($schema_json) && $schema_json !== '') {
    $schema_json = (string) preg_replace_callback('/^( +)/m', static function (array $m): string {
      $len = strlen($m[1]);
      if ($len <= 0) return '';
      return str_repeat('  ', intdiv($len, 4)) . str_repeat(' ', $len % 4);
    }, $schema_json);
  }
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js" data-theme="light"<?php echo $is_home ? ' data-nor-glitch="1"' : ''; ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no">
<meta name="color-scheme" content="light dark">
<meta name="robots" content="<?php echo esc_attr($robots); ?>">
<meta name="generator" content="<?php echo esc_attr('WordPress ' . get_bloginfo('version')); ?>">

<title><?php echo esc_html($title); ?></title>
<?php if ($desc) : ?>
<meta name="description" content="<?php echo esc_attr($desc); ?>">
<?php endif; ?>

<?php if ($has_social_meta) : ?>
<meta property="og:url" content="<?php echo esc_url($page_url); ?>">
<meta property="og:type" content="<?php echo esc_attr($og_type); ?>">
<?php if ($social_title !== '') : ?>
<meta property="og:title" content="<?php echo esc_attr($social_title); ?>">
<?php endif; ?>
<?php if ($desc) : ?>
<meta property="og:description" content="<?php echo esc_attr($desc); ?>">
<?php endif; ?>
<meta property="og:site_name" content="nør.">
<?php if ($og_image !== '') : ?>
<meta property="og:image" content="<?php echo esc_url($og_image); ?>">
<?php if ($og_image_secure !== '') : ?>
<meta property="og:image:secure_url" content="<?php echo esc_url($og_image_secure); ?>">
<?php endif; ?>
<?php if ($og_image_type !== '') : ?>
<meta property="og:image:type" content="<?php echo esc_attr($og_image_type); ?>">
<?php endif; ?>
<?php if ($og_image_width > 0) : ?>
<meta property="og:image:width" content="<?php echo (int) $og_image_width; ?>">
<?php endif; ?>
<?php if ($og_image_height > 0) : ?>
<meta property="og:image:height" content="<?php echo (int) $og_image_height; ?>">
<?php endif; ?>
<?php if ($og_image_alt !== '') : ?>
<meta property="og:image:alt" content="<?php echo esc_attr($og_image_alt); ?>">
<?php endif; ?>
<?php endif; ?>
<meta property="og:locale" content="ja_JP">
<?php endif; ?>
<?php if ($has_social_meta && is_singular('works')) : ?>
<?php $work_id_meta = (int) get_queried_object_id(); ?>
<?php $published_iso_meta = (string) get_the_date('c', $work_id_meta); ?>
<?php $updated_iso_meta = (string) get_the_modified_date('c', $work_id_meta); ?>
<?php if ($published_iso_meta !== '') : ?><meta property="article:published_time" content="<?php echo esc_attr($published_iso_meta); ?>"><?php endif; ?>
<?php if ($updated_iso_meta !== '') : ?><meta property="article:modified_time" content="<?php echo esc_attr($updated_iso_meta); ?>"><?php endif; ?>
<?php endif; ?>
<?php if ($has_social_meta) : ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@not-or">
<?php if ($social_title !== '') : ?>
<meta name="twitter:title" content="<?php echo esc_attr($social_title); ?>">
<?php endif; ?>
<?php if ($desc) : ?>
<meta name="twitter:description" content="<?php echo esc_attr($desc); ?>">
<?php endif; ?>
<?php if ($og_image !== '') : ?>
<meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
<?php if ($og_image_alt !== '') : ?>
<meta name="twitter:image:alt" content="<?php echo esc_attr($og_image_alt); ?>">
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<link rel="canonical" href="<?php echo esc_url($page_url); ?>">
<link rel="https://api.w.org/" href="<?php echo esc_url(rest_url()); ?>">
<link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo esc_url(site_url('/xmlrpc.php?rsd')); ?>">

<link rel="icon" type="image/svg+xml" href="<?php echo esc_url($theme_uri); ?>/assets/img/favicon.svg">
<link rel="icon" sizes="any" href="<?php echo esc_url($theme_uri); ?>/assets/img/favicon.ico">
<link rel="apple-touch-icon" href="<?php echo esc_url($theme_uri); ?>/assets/img/apple-touch-icon.png">
<link rel="manifest" href="<?php echo esc_url($theme_uri); ?>/manifest.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&family=Noto+Sans+JP:wght@300;400;700&family=Source+Code+Pro:wght@400&display=fallback" rel="stylesheet">

<style>
  html { background-color: #f8fafc; color: #020617; }
  @media (prefers-color-scheme: dark) { html { background-color: #020617; color:#eef2f7; }}
  html[data-theme="light"] { background-color: #f8fafc; color: #020617; }
  html[data-theme="dark"] { background-color: #020617; color: #eef2f7; }
</style>

<script>
  document.documentElement.classList.remove('no-js');
</script>

<script>
  (function () {
    const KEY = "nor.themeMode.v1";
    const saved = (function () {
      try { return localStorage.getItem(KEY); } catch (e) {}
      try { return sessionStorage.getItem(KEY); } catch (e) {}
      return null;
    })();
    const mode = (String(saved || "auto").trim().toLowerCase());
    const mq = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
    const systemTheme = mq && mq.matches ? "dark" : "light";
    const theme = (mode === "light" || mode === "dark") ? mode : systemTheme;
    document.documentElement.setAttribute("data-theme", theme);
    document.documentElement.style.backgroundColor = (theme === "dark") ? "#020617" : "#F8FAFC";
  })();
</script>

<?php if ($schema_json) : ?>
<script type="application/ld+json">
<?php
  $schema_json_pretty = trim((string) $schema_json);
  if ($schema_json_pretty !== '') {
    $schema_json_pretty = (string) preg_replace('/^/m', '  ', $schema_json_pretty);
    echo $schema_json_pretty . "\n";
  }
?>
</script>
<?php endif; ?>

<?php if ($ga_measurement_id !== '') : ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo rawurlencode($ga_measurement_id); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', <?php echo wp_json_encode($ga_measurement_id); ?>);
</script>
<?php endif; ?>
<?php wp_head(); ?>
</head>
<?php
  $body_base_class = $is_home ? 'home' : 'pages';
  $body_extra = [];

  // Page-specific body helper classes (match HTML mocks)
  if ($is_contact_page) {
    $body_extra[] = 'contact';
  }
  // Search page is implemented as a Page at /search/
  if (function_exists('is_page') && is_page('search')) {
    $body_extra[] = 'is-search';
  }
  // Error templates
  $req_path = isset($GLOBALS['wp']) ? trim((string) $GLOBALS['wp']->request, '/') : '';
  if ((function_exists('is_404') && is_404()) || in_array($req_path, ['403', '410', '5xx'], true)) {
    $body_extra[] = 'is-error';
  }

  $body_classes = trim($body_base_class . ' ' . implode(' ', $body_extra));

  $render_nav_menu = static function (array $args, int $indent_spaces = 0): void {
    $defaults = [
      'container'    => false,
      'menu_class'   => 'nav-list',
      'depth'        => 1,
      'fallback_cb'  => false,
      'echo'         => false,
      'item_spacing' => 'discard',
    ];
    $html = wp_nav_menu(array_merge($defaults, $args));
    if (!is_string($html)) return;
    $html = trim($html);
    if ($html === '') {
      $html = '<ul class="nav-list"><li><span aria-hidden="true">—</span><span class="visually-hidden"><span lang="ja">未設定</span> / <span lang="en">Not set</span></span></li></ul>';
    }
    $html = (string) preg_replace('/\r\n?/', "\n", $html);
    // Normalize and pretty-print list output.
    $html = (string) preg_replace('/>\s+</', '><', $html);
    $html = (string) preg_replace('/(<ul\b[^>]*>)/i', "$1\n", $html);
    $html = (string) preg_replace('/<li\b/i', "\n  <li", $html);
    $html = (string) preg_replace('/<\/li>\s*<\/ul>/i', "</li>\n</ul>", $html);
    $html = (string) preg_replace('/\n{2,}/', "\n", $html);
    $html = trim($html);
    if ($indent_spaces > 0) {
      $indent = str_repeat(' ', $indent_spaces);
      $html = (string) preg_replace('/^/m', $indent, $html);
    }
    echo $html . "\n";
  };
  $policies_url = esc_url(home_url('/policies/'));
?>
<body id="top" class="<?php echo esc_attr($body_classes); ?>">
<?php wp_body_open(); ?>
<a href="#site-main" class="skip"><span lang="ja">本文へ移動</span> / <span lang="en">Skip to main content</span></a>
<?php if ($is_contact_page) : ?>

<div class="contact-shell">
  <div class="contact-side">
    <header id="site-head">
      <div class="inner">
        <p class="tagline">Graphic &amp; Web Design. <br>Not OR. Just right.</p>
        <div class="logo"><a href="<?php echo esc_url(home_url('/')); ?>">nør.</a></div>
        <div class="cookie-agree" hidden>
          <p><a href="<?php echo $policies_url; ?>" class="cookie-agree-link"><span class="cookie-agree-icon" aria-hidden="true">🍪</span>Cookies &amp; Privacy</a></p>
        </div>
      </div>
    </header>
<?php else : ?>
<header id="site-head">
  <div class="inner">
    <p class="tagline">Graphic &amp; Web Design. <br>Not OR. Just right.</p>
    <div class="logo"><a href="<?php echo esc_url(home_url('/')); ?>">nør.</a></div>
    <nav class="gnav" aria-label="Global navigation">
<?php
  // Primary (Top row)
  $render_nav_menu([
    'theme_location' => 'global_primary',
  ], 6);

  // Secondary (Bottom row)
  $render_nav_menu([
    'theme_location' => 'global_secondary',
  ], 6);
?>
    </nav>
<?php if ($is_home) : ?>
    <div class="cookie-agree" hidden>
      <div class="textpair" id="cookie-notice-text">
        <p class="ja" lang="ja">このサイトでは、表示と操作に必要な Cookie だけを使用します。詳しくは「<a href="<?php echo $policies_url; ?>"><i>Policies</i></a>」を御覧ください。</p>
        <p class="en" lang="en">This site uses only cookies necessary for basic functionality. See <a href="<?php echo $policies_url; ?>"><i>Policies</i></a> for details.</p>
      </div>
      <button class="btn" type="button" aria-describedby="cookie-notice-text" data-cookie-accept>OK</button>
    </div>
<?php else : ?>
    <div class="cookie-agree" hidden>
      <p><a href="<?php echo $policies_url; ?>" class="cookie-agree-link"><span class="cookie-agree-icon" aria-hidden="true">🍪</span>Cookies &amp; Privacy</a></p>
    </div>
<?php endif; ?>
  </div>
</header>
<?php endif; ?>
