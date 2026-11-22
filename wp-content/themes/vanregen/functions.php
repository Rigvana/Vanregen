<?php
/** Theme setup and frontend asset loading. */

function vanregen_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height' => 512,
        'width' => 512,
        'flex-height' => true,
        'flex-width' => true,
    ));
    register_nav_menus(array('primary' => __('Primary Navigation', 'vanregen')));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
}
add_action('after_setup_theme', 'vanregen_theme_setup');
remove_action('wp_head', 'rel_canonical');

function vanregen_brand_logo() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $custom_logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($custom_logo) {
            return array(
                'url' => $custom_logo[0],
                'width' => (int) $custom_logo[1],
                'height' => (int) $custom_logo[2],
            );
        }
    }

    return array(
        'url' => get_site_icon_url(512),
        'width' => 512,
        'height' => 512,
    );
}

function vanregen_seo_current_post() {
    $post = get_queried_object();
    return $post instanceof WP_Post ? $post : null;
}

function vanregen_seo_page_title($post) {
    $settings = function_exists('vanregen_get_site_seo_settings') ? vanregen_get_site_seo_settings() : array();
    $site_name = trim((string) ($settings['site_name'] ?? get_bloginfo('name')));
    if (!$post) {
        if (is_404()) {
            return __('Page not found', 'vanregen') . ' | ' . $site_name;
        }
        if (is_search()) {
            return sprintf(__('Search results for %s', 'vanregen'), get_search_query()) . ' | ' . $site_name;
        }
        return wp_get_document_title();
    }

    $seo_title = trim((string) get_post_meta($post->ID, 'vanregen_seo_title', true));
    if ($seo_title !== '') {
        return $seo_title;
    }
    $title = get_the_title($post);
    $suffix = trim((string) ($settings['title_suffix'] ?? $site_name));
    if ($suffix === '' || strcasecmp($title, $suffix) === 0 || preg_match('/(?:\||-|\s)' . preg_quote($suffix, '/') . '$/i', $title)) {
        return $title;
    }
    return $title . ' | ' . $suffix;
}

function vanregen_seo_description($post) {
    $settings = function_exists('vanregen_get_site_seo_settings') ? vanregen_get_site_seo_settings() : array();
    if (!$post) {
        $description = $settings['default_description'] ?? '';
    } else {
        $description = trim((string) get_post_meta($post->ID, 'vanregen_seo_description', true));
    }

    if ($post && $description === '') {
        if ($post->post_type === 'services') {
            $description = $post->post_excerpt;
        } else {
            $description = get_post_meta($post->ID, 'vanregen_page_summary', true);
            if (!$description) {
                $description = $post->post_excerpt;
            }
        }
    }

    if ($post && !$description) {
        $description = $post->post_content;
    }
    if (!$description) {
        $description = $settings['default_description'] ?? '';
    }

    $description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(strip_shortcodes($description))));
    if (function_exists('mb_strlen') && mb_strlen($description) > 160) {
        $description = rtrim(wp_html_excerpt($description, 157, ''), " \t\n\r\0\x0B.,;:-") . '...';
    } elseif (strlen($description) > 160) {
        $description = rtrim(wp_html_excerpt($description, 157, ''), " \t\n\r\0\x0B.,;:-") . '...';
    }

    return $description;
}

function vanregen_filter_document_title_parts($parts) {
    $post = vanregen_seo_current_post();
    if (!$post) {
        return $parts;
    }

    $parts['title'] = vanregen_seo_page_title($post);
    $parts['site'] = '';
    return $parts;
}
add_filter('document_title_parts', 'vanregen_filter_document_title_parts');
add_filter('document_title_separator', static function () {
    return '|';
});

function vanregen_exclude_user_sitemap($provider, $name) {
    return $name === 'users' ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'vanregen_exclude_user_sitemap', 10, 2);

function vanregen_noindex_author_archives($robots) {
    if (is_author() || is_search() || is_404() || is_date() || (is_archive() && !is_post_type_archive())) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    $post = vanregen_seo_current_post();
    $directive = $post ? get_post_meta($post->ID, 'vanregen_seo_robots', true) : '';
    if ($directive === '') {
        $settings = function_exists('vanregen_get_site_seo_settings') ? vanregen_get_site_seo_settings() : array();
        $directive = $settings['default_robots'] ?? 'index';
    }
    if (strpos($directive, 'noindex') !== false) {
        $robots['noindex'] = true;
    }
    if (strpos($directive, 'nofollow') !== false) {
        $robots['nofollow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'vanregen_noindex_author_archives');

function vanregen_register_robots_rewrite() {
    add_rewrite_rule('^robots\.txt$', 'index.php?vanregen_robots=1', 'top');

    if (get_option('vanregen_robots_rewrite_version') !== '1') {
        flush_rewrite_rules(false);
        update_option('vanregen_robots_rewrite_version', '1', false);
    }
}
add_action('init', 'vanregen_register_robots_rewrite', 99);

function vanregen_add_robots_query_var($vars) {
    $vars[] = 'vanregen_robots';
    return $vars;
}
add_filter('query_vars', 'vanregen_add_robots_query_var');

function vanregen_render_robots_file() {
    if (!get_query_var('vanregen_robots')) {
        return;
    }

    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    $admin_path = wp_parse_url(admin_url(), PHP_URL_PATH);
    $ajax_path = wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
    $admin_path = rtrim($admin_path ?: '/wp-admin/', '/') . '/';
    echo "User-agent: *\n";
    echo 'Disallow: ' . $admin_path . "\n";
    echo 'Allow: ' . ($ajax_path ?: $admin_path . 'admin-ajax.php') . "\n";
    echo 'Sitemap: ' . esc_url(home_url('/wp-sitemap.xml')) . "\n";
    exit;
}
add_action('template_redirect', 'vanregen_render_robots_file', 0);

function vanregen_seo_canonical_url($post) {
    if ($post) {
        $override = trim((string) get_post_meta($post->ID, 'vanregen_seo_canonical_url', true));
        return $override !== '' ? $override : get_permalink($post);
    }
    if (is_search()) {
        return get_search_link(get_search_query());
    }
    if (is_post_type_archive()) {
        $post_type = get_query_var('post_type');
        return get_post_type_archive_link(is_array($post_type) ? reset($post_type) : $post_type);
    }
    if (is_tax() || is_category() || is_tag() || is_author()) {
        $object = get_queried_object();
        return $object instanceof WP_Term ? get_term_link($object) : get_author_posts_url((int) get_queried_object_id());
    }
    return is_404() ? home_url(add_query_arg(array())) : home_url('/');
}

function vanregen_filter_sitemap_posts($args) {
    $excluded = get_posts(array(
        'post_type' => $args['post_type'],
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            'relation' => 'OR',
            array('key' => 'vanregen_seo_sitemap_exclude', 'value' => '1'),
            array('key' => 'vanregen_seo_robots', 'value' => 'noindex', 'compare' => 'LIKE'),
        ),
    ));
    if ($excluded) {
        $args['post__not_in'] = array_values(array_unique(array_merge($args['post__not_in'] ?? array(), array_map('absint', $excluded))));
    }
    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'vanregen_filter_sitemap_posts');

function vanregen_render_seo_metadata() {
    if (is_admin() || is_feed() || is_robots()) {
        return;
    }

    $post = vanregen_seo_current_post();
    $settings = function_exists('vanregen_get_site_seo_settings') ? vanregen_get_site_seo_settings() : array();
    $title = vanregen_seo_page_title($post);
    $description = vanregen_seo_description($post);
    $url = vanregen_seo_canonical_url($post);
    $social_title = $post ? trim((string) get_post_meta($post->ID, 'vanregen_seo_social_title', true)) : '';
    $social_description = $post ? trim((string) get_post_meta($post->ID, 'vanregen_seo_social_description', true)) : '';
    $social_image = $post ? trim((string) get_post_meta($post->ID, 'vanregen_seo_social_image_url', true)) : '';
    $social_image_alt = $post ? trim((string) get_post_meta($post->ID, 'vanregen_seo_social_image_alt', true)) : '';
    if ($social_title === '') {
        $social_title = $title;
    }
    if ($social_description === '') {
        $social_description = $description;
    }
    if ($social_image === '' && $post && has_post_thumbnail($post->ID)) {
        $social_image = get_the_post_thumbnail_url($post, 'full');
        $social_image_alt = get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true);
    }
    if ($social_image === '') {
        $social_image = trim((string) ($settings['default_social_image_url'] ?? ''));
    }
    if ($social_image === '') {
        $brand_logo = vanregen_brand_logo();
        $social_image = $brand_logo['url'];
    }
    if ($social_image_alt === '') {
        $social_image_alt = trim((string) ($settings['default_social_image_alt'] ?? ''));
    }

    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    $og_type = $post && in_array($post->post_type, array('post', 'projects'), true) ? 'article' : 'website';
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($settings['locale'] ?? get_locale()) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($settings['site_name'] ?? get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($social_title) . '">' . "\n";
    if ($social_description !== '') {
        echo '<meta property="og:description" content="' . esc_attr($social_description) . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    if ($social_image !== '') {
        echo '<meta property="og:image" content="' . esc_url($social_image) . '">' . "\n";
        echo '<meta property="og:image:secure_url" content="' . esc_url($social_image) . '">' . "\n";
        $image_id = attachment_url_to_postid($social_image);
        if ($image_id) {
            $image_meta = wp_get_attachment_metadata($image_id);
            if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
                echo '<meta property="og:image:width" content="' . absint($image_meta['width']) . '">' . "\n";
                echo '<meta property="og:image:height" content="' . absint($image_meta['height']) . '">' . "\n";
                echo '<meta property="og:image:type" content="' . esc_attr(get_post_mime_type($image_id)) . '">' . "\n";
            }
        }
        if ($social_image_alt !== '') {
            echo '<meta property="og:image:alt" content="' . esc_attr($social_image_alt) . '">' . "\n";
        }
    }
    if ($og_type === 'article') {
        echo '<meta property="article:published_time" content="' . esc_attr(get_post_time(DATE_ATOM, true, $post)) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_post_modified_time(DATE_ATOM, true, $post)) . '">' . "\n";
    }
    echo '<meta name="twitter:card" content="' . esc_attr($settings['twitter_card'] ?? 'summary_large_image') . '">' . "\n";
    if (!empty($settings['twitter_site'])) {
        echo '<meta name="twitter:site" content="' . esc_attr($settings['twitter_site']) . '">' . "\n";
    }
    echo '<meta name="twitter:title" content="' . esc_attr($social_title) . '">' . "\n";
    echo '<meta name="twitter:url" content="' . esc_url($url) . '">' . "\n";
    if ($social_description !== '') {
        echo '<meta name="twitter:description" content="' . esc_attr($social_description) . '">' . "\n";
    }
    if ($social_image !== '') {
        echo '<meta name="twitter:image" content="' . esc_url($social_image) . '">' . "\n";
        if ($social_image_alt !== '') {
            echo '<meta name="twitter:image:alt" content="' . esc_attr($social_image_alt) . '">' . "\n";
        }
    }

    $organization_name = trim((string) ($settings['organization_name'] ?? ''));
    $organization_legal_name = trim((string) ($settings['organization_legal_name'] ?? ''));
    $brand_logo = vanregen_brand_logo();
    $organization = array(
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => $organization_name !== '' ? $organization_name : get_bloginfo('name'),
        'url' => home_url('/'),
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => $brand_logo['url'],
            'width' => $brand_logo['width'],
            'height' => $brand_logo['height'],
        ),
    );
    if ($organization_legal_name !== '') {
        $organization['legalName'] = $organization_legal_name;
    }
    foreach (array('email' => 'organization_email', 'telephone' => 'organization_phone') as $schema_key => $setting_key) {
        if (!empty($settings[$setting_key])) {
            $organization[$schema_key] = $settings[$setting_key];
        }
    }
    $address = array('@type' => 'PostalAddress');
    foreach (array(
        'streetAddress' => 'organization_address',
        'addressLocality' => 'organization_locality',
        'addressRegion' => 'organization_region',
        'postalCode' => 'organization_postal_code',
        'addressCountry' => 'organization_country',
    ) as $schema_key => $setting_key) {
        if (!empty($settings[$setting_key])) {
            $address[$schema_key] = $settings[$setting_key];
        }
    }
    if (count($address) > 1) {
        $organization['address'] = $address;
    }
    $social_profiles = preg_split('/\r\n|\r|\n/', (string) ($settings['social_profile_urls'] ?? ''));
    $social_profiles = array_values(array_filter(array_map('esc_url_raw', $social_profiles)));
    if ($social_profiles) {
        $organization['sameAs'] = $social_profiles;
    }
    $graph = array(
        $organization,
        array(
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => $settings['site_name'] ?? get_bloginfo('name'),
            'publisher' => array('@id' => home_url('/#organization')),
            'inLanguage' => str_replace('_', '-', $settings['locale'] ?? get_locale()),
        ),
    );

    if ($post) {
        $webpage_type = is_singular(array('projects', 'post')) ? 'Article' : (is_page('contact-quote') || is_page('contact') ? 'ContactPage' : 'WebPage');
        $webpage = array(
            '@type' => $webpage_type,
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $description,
            'isPartOf' => array('@id' => home_url('/#website')),
            'about' => array('@id' => home_url('/#organization')),
            'inLanguage' => str_replace('_', '-', $settings['locale'] ?? get_locale()),
        );
        if ($webpage_type === 'Article') {
            $webpage['headline'] = get_the_title($post);
            $webpage['datePublished'] = get_post_time(DATE_ATOM, true, $post);
            $webpage['dateModified'] = get_post_modified_time(DATE_ATOM, true, $post);
            $webpage['author'] = array('@type' => 'Organization', '@id' => home_url('/#organization'));
            $webpage['publisher'] = array('@id' => home_url('/#organization'));
        }
        if ($post->post_type === 'projects' && $social_image !== '') {
            $webpage['image'] = $social_image;
        }
        $graph[] = $webpage;

        if ($post->post_type === 'services') {
            $graph[] = array(
                '@type' => 'Service',
                '@id' => $url . '#service',
                'name' => get_the_title($post),
                'description' => $description,
                'url' => $url,
                'provider' => array('@id' => home_url('/#organization')),
            );
        }

        $ancestors = $post->post_type === 'page' ? array_reverse(get_post_ancestors($post)) : array();
        $crumbs = array(array('@type' => 'ListItem', 'position' => 1, 'name' => __('Home', 'vanregen'), 'item' => home_url('/')));
        foreach ($ancestors as $ancestor_id) {
            $crumbs[] = array('@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => get_the_title($ancestor_id), 'item' => get_permalink($ancestor_id));
        }
        $crumbs[] = array('@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => get_the_title($post), 'item' => $url);
        $graph[] = array('@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $crumbs);
    }

    echo '<script type="application/ld+json">' . wp_json_encode(
        array('@context' => 'https://schema.org', '@graph' => $graph),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) . '</script>' . "\n";
}
add_action('wp_head', 'vanregen_render_seo_metadata', 1);

function vanregen_page_url($path) {
    $core_pages = function_exists('vanregen_core_page_ids') ? vanregen_core_page_ids() : array();
    $page = !empty($core_pages[$path]) ? get_post($core_pages[$path]) : get_page_by_path($path, OBJECT, 'page');
    return $page ? get_permalink($page) : home_url('/' . trim($path, '/') . '/');
}

function vanregen_service_url($slug) {
    $service = get_page_by_path($slug, OBJECT, 'services');
    return $service ? get_permalink($service) : vanregen_page_url('services/' . $slug);
}

function vanregen_current_frontend_slug() {
    if (is_front_page()) {
        return 'home';
    }
    $post = get_post(get_queried_object_id());
    if ($post && $post->post_type === 'page' && function_exists('vanregen_page_key')) {
        return vanregen_page_key($post);
    }
    return $post ? $post->post_name : 'home';
}

function vanregen_menu_tree($items, $parent = 0, $page_urls = array()) {
    $tree = array();
    foreach ($items as $item) {
        if ((int) $item->menu_item_parent !== $parent || !empty($item->_invalid)
            || ($item->type === 'post_type' && get_post_status($item->object_id) !== 'publish')) {
            continue;
        }
        $url = sanitize_title($item->title) === 'contact' && !empty($page_urls['contact'])
            ? $page_urls['contact']
            : $item->url;
        $tree[] = array(
            'id' => (int) $item->ID,
            'title' => $item->title,
            'url' => $url,
            'target' => $item->target,
            'children' => vanregen_menu_tree($items, (int) $item->ID, $page_urls),
        );
    }
    return $tree;
}

function vanregen_primary_navigation($page_urls) {
    if (has_nav_menu('primary')) {
        $locations = get_nav_menu_locations();
        $items = wp_get_nav_menu_items($locations['primary']);
        return is_array($items) ? vanregen_menu_tree($items, 0, $page_urls) : array();
    }

    $service_links = array();
    $services = get_posts(array('post_type' => 'services', 'post_status' => 'publish', 'numberposts' => -1));
    foreach ($services as $service) {
        $service_links[] = array(
            'id' => (int) $service->ID,
            'title' => get_the_title($service),
            'url' => get_permalink($service),
            'target' => '',
            'children' => array(),
        );
    }
    $links = array(
        'services' => 'services',
        'about' => 'about-us',
        'projects' => 'projects',
        'sustainability' => 'sustainability',
        'contact' => 'contact',
    );
    $navigation = array();
    $core_pages = function_exists('vanregen_core_page_ids') ? vanregen_core_page_ids() : array();
    foreach ($links as $key => $path) {
        $page = !empty($core_pages[$path]) ? get_post($core_pages[$path]) : get_page_by_path($path, OBJECT, 'page');
        if (!$page || $page->post_status !== 'publish') {
            continue;
        }
        $navigation[] = array(
            'id' => $key,
            'title' => get_the_title($page),
            'url' => $page_urls[$key],
            'target' => '',
            'children' => $key === 'services' ? $service_links : array(),
        );
    }
    return $navigation;
}

function vanregen_enqueue_assets() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    $manifest_path = $theme_dir . '/assets/dist/.vite/manifest.json';

    if (!file_exists($manifest_path)) {
        return;
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);
    if (!is_array($manifest) || empty($manifest['index.html']['file'])) {
        return;
    }

    $entry = $manifest['index.html'];
    foreach (($entry['css'] ?? array()) as $index => $css_file) {
        wp_enqueue_style('vanregen-app-' . $index, $theme_uri . '/assets/dist/' . $css_file, array(), null);
    }

    wp_enqueue_script('vanregen-app', $theme_uri . '/assets/dist/' . $entry['file'], array(), null, true);
    wp_script_add_data('vanregen-app', 'type', 'module');

    $page_paths = array(
        'home' => 'home',
        'about' => 'about-us',
        'services' => 'services',
        'e-waste-recycling' => 'services/e-waste-recycling',
        'data-destruction' => 'services/data-destruction',
        'itad-asset-buyback' => 'services/itad-asset-buyback',
        'epr-documentation' => 'services/epr-documentation',
        'sustainability' => 'sustainability',
        'projects' => 'projects',
        'contact' => 'contact',
        'contact-quote' => 'contact-quote',
    );
    $page_urls = array();
    foreach ($page_paths as $key => $path) {
        if ($key === 'home') {
            $page_urls[$key] = home_url('/');
        } elseif (in_array($key, array('e-waste-recycling', 'data-destruction', 'itad-asset-buyback', 'epr-documentation'), true)) {
            $page_urls[$key] = vanregen_service_url($key);
        } else {
            $page_urls[$key] = vanregen_page_url($path);
        }
    }

    $front_page_id = (int) get_option('page_on_front');
    $site_copy_keys = array(
        'companyName' => 'vanregen_global_company_name',
        'brandName' => 'vanregen_global_brand_name',
        'brandTagline' => 'vanregen_global_brand_tagline',
        'toplineDescriptor' => 'vanregen_global_topline_descriptor',
        'toplineCta' => 'vanregen_global_topline_cta',
        'footerStatement' => 'vanregen_global_footer_statement',
        'footerCta' => 'vanregen_global_footer_cta',
        'footerCredit' => 'vanregen_global_footer_credit',
    );
    $site_copy = array();
    foreach ($site_copy_keys as $config_key => $meta_key) {
        $site_copy[$config_key] = $front_page_id ? (string) get_post_meta($front_page_id, $meta_key, true) : '';
    }

    wp_localize_script('vanregen-app', 'vanRegenConfig', array(
        'restUrl' => esc_url_raw(rest_url()),
        'restNonce' => wp_create_nonce('wp_rest'),
        'pageSlug' => vanregen_current_frontend_slug(),
        'isHome' => is_front_page(),
        'postId' => (int) get_queried_object_id(),
        'postType' => get_post_type(get_queried_object_id()) ?: '',
        'siteUrl' => esc_url_raw(home_url('/')),
        'logoUrl' => esc_url_raw(vanregen_brand_logo()['url']),
        'siteCopy' => $site_copy,
        'pageUrls' => $page_urls,
        'navigation' => vanregen_primary_navigation($page_urls),
    ));
}
add_action('wp_enqueue_scripts', 'vanregen_enqueue_assets');

function vanregen_module_script_tag($tag, $handle, $src) {
    if ($handle !== 'vanregen-app') {
        return $tag;
    }
    return '<script type="module" src="' . esc_url($src) . '" id="vanregen-app-js"></script>';
}
add_filter('script_loader_tag', 'vanregen_module_script_tag', 10, 3);
