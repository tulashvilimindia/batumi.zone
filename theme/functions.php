<?php
/**
 * Batumi.zone Theme Functions
 *
 * @package Batumi_Theme
 * @version 0.3.0
 * @updated 2026-01-17 - Added bug fixes B1, B2, B3, B5, B10, B11, B15, B16
 */

if (!defined('ABSPATH')) {
    exit;
}
// Load theme translations
require_once get_template_directory() . '/theme-translations.php';

/**
 * Theme setup
 */
function batumi_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'batumi-theme'),
        'footer'  => __('Footer Menu', 'batumi-theme'),
    ));

    // Load text domain
    load_theme_textdomain('batumi-theme', get_template_directory() . '/languages');

    // Add image sizes for service listings
    add_image_size('service-thumbnail', 400, 300, true);
    add_image_size('service-medium', 800, 600, true);
}
add_action('after_setup_theme', 'batumi_theme_setup');

/**
 * Hide WordPress admin bar for non-administrator users
 * Fix: FB-03 - Admin bar should not be visible to regular users
 */
add_action('after_setup_theme', function() {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});

/**
 * Security: Only allow logout action on wp-login.php
 * All other actions (login, register, lostpassword) are blocked
 * Users should use custom frontend pages for those actions
 */
add_action('login_init', function() {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

    // Only allow logout and confirm_admin_email actions
    $allowed_actions = array('logout', 'confirmaction');

    if (!in_array($action, $allowed_actions)) {
        // Redirect to home page for any non-logout action
        wp_redirect(home_url('/'));
        exit;
    }
});

/**
 * Redirect users to home page after logout
 * Fix: FB-02 - Prevents 403 error since wp-login.php is blocked by Nginx
 */
add_filter('logout_redirect', function($redirect_to, $requested_redirect_to, $user) {
    return home_url('/');
}, 10, 3);

/**
 * Custom Favicon - Override WordPress default
 * Uses SVG favicon for modern browsers (supported by Chrome, Firefox, Edge, Safari 15+)
 */
add_action('wp_head', function() {
    $favicon_svg = get_template_directory_uri() . '/favicon.svg';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url($favicon_svg); ?>">
    <link rel="icon" href="<?php echo esc_url($favicon_svg); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url($favicon_svg); ?>">
    <?php
}, 1);

// Remove WordPress default favicon/site icon
add_filter('get_site_icon_url', '__return_false');

/**
 * Enqueue scripts and styles
 */
function batumi_theme_scripts() {
    // Main stylesheet
    wp_enqueue_style('batumi-theme-style', get_stylesheet_uri(), array(), '0.3.0');

    // Mobile menu script
    wp_enqueue_script('batumi-theme-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '0.2.0', true);

    // Favorites system
    wp_enqueue_script('batumi-theme-favorites', get_template_directory_uri() . '/js/favorites.js', array('jquery'), '0.3.0', true);

    // Fancy Frontend styles and scripts
    wp_enqueue_style('batumi-fancy-styles', get_template_directory_uri() . '/fancy-frontend-styles.css', array(), '0.5.0');
    wp_enqueue_style('batumi-header-fixes', get_template_directory_uri() . '/HEADER-FIXES.css', array('batumi-fancy-styles'), '1.1.0');
    wp_enqueue_script('batumi-fancy-frontend', get_template_directory_uri() . '/js/fancy-frontend.js', array('jquery'), '0.4.2', true);

    // === BUG FIX CSS FILES (v0.3.0) ===
    // Dark mode complete (V1-V16)
    wp_enqueue_style('batumi-dark-mode', get_template_directory_uri() . '/dark-mode-complete.css', array('batumi-header-fixes'), '1.0.0');
    // Accessibility fixes (V7, V8, V12, V17, V18)
    wp_enqueue_style('batumi-accessibility', get_template_directory_uri() . '/accessibility-fixes.css', array('batumi-dark-mode'), '1.0.0');
    // Service Form Styles (Create/Edit Service pages)
    wp_enqueue_style("batumi-service-form", get_template_directory_uri() . "/service-form-styles.css", array("batumi-accessibility"), "1.0.0");

    // Report Modal (Phase 7)
    wp_enqueue_style('batumi-report-modal', get_template_directory_uri() . '/report-modal.css', array(), '0.3.0');
    wp_enqueue_script('batumi-report-modal', get_template_directory_uri() . '/js/report-modal.js', array(), '0.3.0', true);

    // Poster Promotion CSS (Phase 8.1 - Poster Dashboard)
    wp_enqueue_style('batumi-poster-promotion', get_template_directory_uri() . '/assets/css/poster-promotion.css', array(), '1.0.0');

    // Sponsored Badges CSS (Phase 8.1 - Frontend Labels)
    wp_enqueue_style('batumi-sponsored-badges', get_template_directory_uri() . '/assets/css/sponsored-badges.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'batumi_theme_scripts');

/**
 * Register widget areas
 */
function batumi_theme_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'batumi-theme'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'batumi-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Widgets', 'batumi-theme'),
        'id'            => 'footer-widgets',
        'description'   => __('Add footer widgets here.', 'batumi-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="footer-widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'batumi_theme_widgets_init');

/**
 * Custom excerpt length
 */
function batumi_custom_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'batumi_custom_excerpt_length', 999);

/**
 * Custom excerpt more
 */
function batumi_custom_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'batumi_custom_excerpt_more');

/**
 * Add Polylang language switcher to menu
 */
function batumi_language_switcher() {
    if (function_exists('pll_the_languages')) {
        $languages = pll_the_languages(array(
            'show_flags' => 1,
            'show_names' => 1,
            'hide_if_empty' => 0,
            'echo' => 0,
            'raw' => 1
        ));

        if (!empty($languages)) {
            echo '<div class="language-switcher">';
            echo '<ul>';
            foreach ($languages as $lang) {
                $class = $lang['current_lang'] ? 'current-lang' : '';
                echo '<li class="lang-item ' . esc_attr($class) . '">';
                echo '<a href="' . esc_url($lang['url']) . '" hreflang="' . esc_attr($lang['slug']) . '">';
                if (!empty($lang['flag'])) {
                    echo '<img src="' . esc_url($lang['flag']) . '" alt="' . esc_attr($lang['name']) . '">';
                }
                echo '<span>' . esc_html($lang['name']) . '</span>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}

/**
 * Get current language code
 */
function batumi_get_current_language() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
        // Normalize language codes
        if ($lang === 'ka') return 'ge';
        return $lang;
    }
    return 'ge'; // Default to Georgian
}

/**
 * Format service price
 */
function batumi_format_price($price_model, $price_value, $currency = 'GEL') {
    if (empty($price_model)) {
        return '';
    }

    switch ($price_model) {
        case 'fixed':
            return $price_value . ' ' . $currency;
        case 'hourly':
            return $price_value . ' ' . $currency . '/' . __('hour', 'batumi-theme');
        case 'negotiable':
            return __('Negotiable', 'batumi-theme');
        default:
            return $price_value . ' ' . $currency;
    }
}

/**
 * Body classes for different pages
 */
function batumi_body_classes($classes) {
    // Add language class
    $current_lang = batumi_get_current_language();
    $classes[] = 'lang-' . $current_lang;

    // Add mobile class (can be enhanced with user agent detection)
    if (wp_is_mobile()) {
        $classes[] = 'mobile';
    }

    return $classes;
}
add_filter('body_class', 'batumi_body_classes');

/**
 * Disable WordPress emoji scripts (performance optimization)
 */
function batumi_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'batumi_disable_emojis');

/**
 * Security: Remove WordPress version from head
 */
remove_action('wp_head', 'wp_generator');

/**
 * Security: Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');


/* ============================================
 * BUG FIXES - Added 2026-01-17
 * ============================================ */

/**
 * BUG FIX B1: Include service_listing in WordPress search
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $query->set('post_type', array('post', 'page', 'service_listing'));
    }
}, 10);

/**
 * BUG FIX B1 (Part 2): Extend search to ACF meta fields using posts_where filter
 * This is more reliable than posts_search filter
 */
add_filter('posts_where', function($where, $query) {
    global $wpdb;

    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return $where;
    }

    $search_term = $query->get('s');
    if (empty($search_term)) {
        return $where;
    }

    $like = '%' . $wpdb->esc_like($search_term) . '%';

    // Add OR clause to include services found by ACF meta fields
    $where .= $wpdb->prepare(
        " OR ({$wpdb->posts}.ID IN (
            SELECT DISTINCT post_id FROM {$wpdb->postmeta}
            WHERE meta_key IN ('title_ge', 'title_ru', 'title_en', 'desc_ge', 'desc_ru', 'desc_en')
            AND meta_value LIKE %s
        ) AND {$wpdb->posts}.post_type = 'service_listing' AND {$wpdb->posts}.post_status = 'publish')",
        $like
    );

    return $where;
}, 10, 2);

/**
 * BUG FIX B2: Bulk update existing service slugs (one-time)
 * Run via: /wp-admin/?batumi_fix_slugs=1 (admin only)
 */
add_action('admin_init', function() {
    if (!current_user_can('manage_options') || !isset($_GET['batumi_fix_slugs'])) {
        return;
    }

    $services = get_posts([
        'post_type' => 'service_listing',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);

    $updated = 0;
    foreach ($services as $post_id) {
        $current_slug = get_post_field('post_name', $post_id);

        // Skip if already has a good slug (not auto-generated pattern)
        if (!preg_match('/^service-listing-\d+/', $current_slug)) {
            continue;
        }

        // Get best title for slug
        $title = get_field('title_en', $post_id)
              ?: get_field('title_ge', $post_id)
              ?: get_field('title_ru', $post_id);

        if ($title) {
            $new_slug = sanitize_title($title);
            $new_slug = wp_unique_post_slug($new_slug, $post_id, 'publish', 'service_listing', 0);

            wp_update_post([
                'ID' => $post_id,
                'post_name' => $new_slug
            ]);
            $updated++;
        }
    }

    wp_die("Batumi Slug Fix: Updated $updated service slugs. <a href='" . admin_url() . "'>Go to Dashboard</a>");
});

/**
 * BUG FIX B2: Generate human-readable slugs for services
 */
add_filter('wp_insert_post_data', function($data, $postarr) {
    if ($data['post_type'] !== 'service_listing') {
        return $data;
    }

    // Only auto-generate if slug is empty or auto-generated pattern
    if (empty($data['post_name']) || preg_match('/^service-listing-\d+/', $data['post_name'])) {
        $title_for_slug = '';

        if (!empty($postarr['ID'])) {
            $title_en = get_field('title_en', $postarr['ID']);
            $title_ge = get_field('title_ge', $postarr['ID']);
            $title_ru = get_field('title_ru', $postarr['ID']);

            $title_for_slug = $title_en ?: $title_ge ?: $title_ru ?: '';
        }

        if (empty($title_for_slug) && !empty($data['post_title'])) {
            $title_for_slug = $data['post_title'];
        }

        if (!empty($title_for_slug)) {
            $slug = sanitize_title($title_for_slug);
            $slug = wp_unique_post_slug(
                $slug,
                $postarr['ID'] ?? 0,
                $data['post_status'],
                $data['post_type'],
                $data['post_parent'] ?? 0
            );
            $data['post_name'] = $slug;
        }
    }

    return $data;
}, 10, 2);

/**
 * BUG FIX B3: Use translated titles in document <title>
 */
add_filter('document_title_parts', function($title_parts) {
    if (is_singular('service_listing')) {
        $post_id = get_the_ID();
        $lang = batumi_get_current_language();

        $title = '';
        if ($lang === 'ru') {
            $title = get_field('title_ru', $post_id);
        } elseif ($lang === 'en') {
            $title = get_field('title_en', $post_id);
        } else {
            $title = get_field('title_ge', $post_id);
        }

        // Fallback chain
        if (empty($title)) {
            $title = get_field('title_en', $post_id)
                  ?: get_field('title_ge', $post_id)
                  ?: get_field('title_ru', $post_id)
                  ?: get_the_title($post_id);
        }

        if (!empty($title)) {
            $title_parts['title'] = $title;
        }
    }

    return $title_parts;
}, 10);

/**
 * BUG FIX B5: Invalid category returns empty (not all services)
 */
add_filter('rest_service_listing_query', function($args, $request) {
    $category = $request->get_param('category');

    if (!empty($category)) {
        $term = get_term_by('slug', $category, 'service_category');
        if (!$term) {
            $term = get_term_by('slug', $category, 'service_direction');
        }

        if (!$term) {
            // Invalid category - return no results
            $args['post__in'] = array(0);
        }
    }

    return $args;
}, 10, 2);

/**
 * BUG FIX B10: Add service_listing to XML Sitemap with lastmod dates
 */
add_filter('wp_sitemaps_post_types', function($post_types) {
    if (!isset($post_types['service_listing'])) {
        $post_types['service_listing'] = get_post_type_object('service_listing');
    }
    return $post_types;
});

// B10 Part 2: Add lastmod dates to sitemap entries
add_filter('wp_sitemaps_posts_entry', function($entry, $post) {
    if ($post->post_type === 'service_listing') {
        $entry['lastmod'] = get_the_modified_date('c', $post);
    }
    return $entry;
}, 10, 2);

/**
 * BUG FIX B12: Consistent API Cache Headers for all endpoints
 */
add_filter('rest_post_dispatch', function($response, $server, $request) {
    $route = $request->get_route();

    // Skip if already has cache headers or not a GET request
    if ($request->get_method() !== 'GET' || $response->get_headers()['Cache-Control'] ?? false) {
        return $response;
    }

    // Add cache headers based on endpoint type
    if (strpos($route, '/batumizone/v1/') !== false) {
        if (strpos($route, '/my/') !== false || strpos($route, '/favorites') !== false) {
            // Private endpoints - no cache
            $response->header('Cache-Control', 'private, no-cache');
        } elseif (strpos($route, '/taxonomies') !== false) {
            // Taxonomies - cache longer
            $response->header('Cache-Control', 'public, max-age=3600');
        } else {
            // Public endpoints - moderate cache
            $response->header('Cache-Control', 'public, max-age=300');
        }
    }

    return $response;
}, 20, 3);

/**
 * BUG FIX B13: Add CORS headers for API
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $origin = get_http_origin();

        // Allow same-origin and common dev origins
        $allowed_origins = array(
            home_url(),
            'https://dev.batumi.zone',
            'https://batumi.zone',
            'http://localhost:3000',
            'http://localhost:8080',
        );

        if (in_array($origin, $allowed_origins) || empty($origin)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        }

        return $value;
    });
}, 15);

/**
 * BUG FIX B15: Service View Counter
 */
add_action('wp_head', function() {
    if (!is_singular('service_listing') || is_admin()) {
        return;
    }

    $post_id = get_the_ID();

    // Don't count admin views or bots
    if (current_user_can('manage_options')) {
        return;
    }

    // Increment view count
    $views = (int) get_post_meta($post_id, 'batumi_view_count', true);
    update_post_meta($post_id, 'batumi_view_count', $views + 1);
}, 99);

/**
 * BUG FIX B18: Add rel="nofollow noopener" to external links in content
 */
add_filter('the_content', function($content) {
    if (empty($content)) {
        return $content;
    }

    $site_url = home_url();

    // Find all links and add nofollow to external ones
    $content = preg_replace_callback(
        '/<a\s+([^>]*href=["\']([^"\']+)["\'][^>]*)>/i',
        function($matches) use ($site_url) {
            $full_tag = $matches[0];
            $href = $matches[2];

            // Skip internal links, anchors, and already processed
            if (strpos($href, $site_url) === 0 ||
                strpos($href, '/') === 0 ||
                strpos($href, '#') === 0 ||
                strpos($href, 'mailto:') === 0 ||
                strpos($href, 'tel:') === 0 ||
                strpos($full_tag, 'rel=') !== false) {
                return $full_tag;
            }

            // External link - add nofollow noopener and target blank
            $new_tag = str_replace('<a ', '<a rel="nofollow noopener" target="_blank" ', $full_tag);
            return $new_tag;
        },
        $content
    );

    return $content;
});

/**
 * BUG FIX B19: Custom robots.txt
 */
add_filter('robots_txt', function($output, $public) {
    if (!$public) {
        return $output;
    }

    $custom_robots = "User-agent: *\n";
    $custom_robots .= "Allow: /\n";
    $custom_robots .= "Disallow: /wp-admin/\n";
    $custom_robots .= "Disallow: /wp-includes/\n";
    $custom_robots .= "Disallow: /wp-content/plugins/\n";
    $custom_robots .= "Disallow: /wp-content/cache/\n";
    $custom_robots .= "Disallow: /*?s=\n";
    $custom_robots .= "Disallow: /*?p=\n";
    $custom_robots .= "Disallow: /tag/\n";
    $custom_robots .= "\n";
    $custom_robots .= "# Sitemaps\n";
    $custom_robots .= "Sitemap: " . home_url('/wp-sitemap.xml') . "\n";
    $custom_robots .= "\n";
    $custom_robots .= "# Crawl-delay for politeness\n";
    $custom_robots .= "Crawl-delay: 1\n";

    return $custom_robots;
}, 10, 2);

/**
 * BUG FIX B20: Add Breadcrumb Schema (BreadcrumbList)
 */
add_action('wp_head', function() {
    if (is_front_page() || is_admin()) {
        return;
    }

    $breadcrumbs = array();
    $position = 1;

    // Home
    $breadcrumbs[] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => __('Home', 'batumi-theme'),
        'item' => home_url('/'),
    );

    // Service listing page
    if (is_singular('service_listing')) {
        $post_id = get_the_ID();
        $lang = batumi_get_current_language();

        // Services archive
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('Services', 'batumi-theme'),
            'item' => home_url('/services/'),
        );

        // Category if exists
        $categories = get_the_terms($post_id, 'service_category');
        if ($categories && !is_wp_error($categories)) {
            $cat = $categories[0];
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $cat->name,
                'item' => get_term_link($cat),
            );
        }

        // Current service
        $title = get_field("title_{$lang}", $post_id)
              ?: get_field('title_en', $post_id)
              ?: get_the_title($post_id);

        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $title,
            'item' => get_permalink($post_id),
        );
    }
    // Category/taxonomy archive
    elseif (is_tax('service_category') || is_tax('coverage_area')) {
        $term = get_queried_object();

        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('Services', 'batumi-theme'),
            'item' => home_url('/services/'),
        );

        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $term->name,
            'item' => get_term_link($term),
        );
    }
    // Search results
    elseif (is_search()) {
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => sprintf(__('Search: %s', 'batumi-theme'), get_search_query()),
            'item' => get_search_link(),
        );
    }
    // Regular page
    elseif (is_page()) {
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => get_the_title(),
            'item' => get_permalink(),
        );
    }

    if (count($breadcrumbs) > 1) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs,
        );

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}, 6);

/**
 * BUG FIX V1/V2: Dark mode persistence - inline script in head to prevent flash
 */
add_action('wp_head', function() {
    ?>
    <script>
    (function(){
        var t = localStorage.getItem('batumi-theme');
        if (t === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.body && document.body.setAttribute('data-theme', 'dark');
        }
    })();
    </script>
    <?php
}, 1); // Priority 1 to run very early

/**
 * BUG FIX B11: Add Schema.org JSON-LD markup
 */
add_action('wp_head', function() {
    if (!is_singular('service_listing')) {
        return;
    }

    $post_id = get_the_ID();
    $lang = batumi_get_current_language();

    // Get title and description
    $title = get_field("title_{$lang}", $post_id)
          ?: get_field('title_en', $post_id)
          ?: get_the_title($post_id);

    $description = get_field("desc_{$lang}", $post_id)
                ?: get_field('desc_en', $post_id)
                ?: '';

    $price_value = get_field('price_value', $post_id);
    $price_currency = get_field('price_currency', $post_id) ?: 'GEL';
    $phone = get_field('phone', $post_id);
    $image = get_the_post_thumbnail_url($post_id, 'large');

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $title,
        'description' => wp_strip_all_tags($description),
        'url' => get_permalink($post_id),
        'provider' => array(
            '@type' => 'LocalBusiness',
            'name' => $title,
        ),
    );

    if ($image) {
        $schema['image'] = $image;
    }

    if ($price_value && $price_value > 0) {
        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => $price_value,
            'priceCurrency' => $price_currency,
        );
    }

    if ($phone) {
        $schema['provider']['telephone'] = $phone;
    }

    // B11 Enhancement: Add aggregateRating if available
    $rating = get_field('rating', $post_id) ?: get_post_meta($post_id, 'batumi_avg_rating', true);
    $review_count = get_field('review_count', $post_id) ?: get_post_meta($post_id, 'batumi_review_count', true);

    if ($rating && $rating > 0) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => round((float)$rating, 1),
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => max(1, (int)$review_count),
        );
    }

    // Add view count as interactionStatistic
    $views = get_post_meta($post_id, 'batumi_view_count', true);
    if ($views && $views > 0) {
        $schema['interactionStatistic'] = array(
            '@type' => 'InteractionCounter',
            'interactionType' => 'https://schema.org/ViewAction',
            'userInteractionCount' => (int)$views,
        );
    }

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 5);

/**
 * BUG FIX B15: Add cache headers to API responses
 */
add_filter('rest_post_dispatch', function($response, $server, $request) {
    $route = $request->get_route();

    if ($request->get_method() === 'GET') {
        if (strpos($route, '/batumizone/v1/services') !== false && strpos($route, '/my/') === false) {
            $response->header('Cache-Control', 'public, max-age=300');
        } elseif (strpos($route, '/batumizone/v1/taxonomies') !== false) {
            $response->header('Cache-Control', 'public, max-age=3600');
        } elseif (strpos($route, '/batumizone/v1/tags') !== false) {
            $response->header('Cache-Control', 'public, max-age=1800');
        }
    }

    return $response;
}, 10, 3);

/**
 * BUG FIX B16: Consistent translation fallback helper
 */
function batumi_get_translated_field($field_base, $post_id, $preferred_lang = null) {
    if ($preferred_lang === null) {
        $preferred_lang = batumi_get_current_language();
    }

    $lang_priority = array($preferred_lang);
    foreach (array('ge', 'en', 'ru') as $lang) {
        if (!in_array($lang, $lang_priority)) {
            $lang_priority[] = $lang;
        }
    }

    foreach ($lang_priority as $lang) {
        $field_name = $field_base . '_' . $lang;
        $value = get_field($field_name, $post_id);
        if (!empty($value)) {
            return $value;
        }
    }

    return '';
}

/**
 * BUG FIX B7: Add pagination headers to search results
 */
add_action('wp', function() {
    if (is_search() && !is_admin()) {
        global $wp_query;

        $total = $wp_query->found_posts;
        $per_page = $wp_query->query_vars['posts_per_page'];
        $total_pages = $wp_query->max_num_pages;

        // Add headers (will be sent with the response)
        add_action('send_headers', function() use ($total, $total_pages) {
            if (!headers_sent()) {
                header('X-WP-Total: ' . $total);
                header('X-WP-TotalPages: ' . $total_pages);
            }
        });
    }
});

/**
 * BUG FIX B6: Basic rate limiting for search (transient-based)
 * Limits: 30 searches per minute per IP
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip_hash = md5($ip);
        $rate_key = 'search_rate_' . $ip_hash;

        $count = get_transient($rate_key);
        if ($count === false) {
            set_transient($rate_key, 1, 60); // 1 minute window
        } elseif ($count >= 30) {
            // Rate limited - return empty results
            $query->set('post__in', array(0));

            // Log rate limit hit
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Batumi Search Rate Limit: IP $ip exceeded 30 requests/minute");
            }
        } else {
            set_transient($rate_key, $count + 1, 60);
        }
    }
}, 5);

/**
 * BUG FIX B9: Error logging for failed searches
 */
add_action('wp', function() {
    if (is_search() && !is_admin()) {
        global $wp_query;

        $search_term = get_search_query();
        $results_count = $wp_query->found_posts;

        // Log searches with 0 results for analysis
        if ($results_count === 0 && !empty($search_term)) {
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'search_term' => $search_term,
                'ip_hash' => md5($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            );

            // Store in option (last 100 failed searches)
            $failed_searches = get_option('batumi_failed_searches', array());
            array_unshift($failed_searches, $log_entry);
            $failed_searches = array_slice($failed_searches, 0, 100);
            update_option('batumi_failed_searches', $failed_searches, false);

            // Also log to error log if debug enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Batumi Search: No results for '$search_term'");
            }
        }
    }
});

/**
 * BUG FIX DM-01: Add flat title properties to services API response
 * Fixes "Untitled" issue on favorites page where JS expects service.title_ge
 * but API returns service.title.ge
 */
add_filter('rest_post_dispatch', function($result, $server, $request) {
    $route = $request->get_route();

    // Only modify batumizone services API responses
    if (strpos($route, '/batumizone/v1/services') === false) {
        return $result;
    }

    $data = $result->get_data();

    // Function to add flat title properties
    $add_flat_titles = function(&$service) {
        if (isset($service['title']) && is_array($service['title'])) {
            // Add flat properties for JavaScript compatibility
            $service['title_ge'] = $service['title']['ge'] ?? '';
            $service['title_ka'] = $service['title']['ge'] ?? ''; // Alias
            $service['title_ru'] = $service['title']['ru'] ?? '';
            $service['title_en'] = $service['title']['en'] ?? '';
        }
        if (isset($service['description']) && is_array($service['description'])) {
            $service['desc_ge'] = $service['description']['ge'] ?? '';
            $service['desc_ka'] = $service['description']['ge'] ?? '';
            $service['desc_ru'] = $service['description']['ru'] ?? '';
            $service['desc_en'] = $service['description']['en'] ?? '';
        }
    };

    // Handle single service response
    if (isset($data['id']) && isset($data['title'])) {
        $add_flat_titles($data);
        $result->set_data($data);
    }
    // Handle services list response
    elseif (isset($data['services']) && is_array($data['services'])) {
        foreach ($data['services'] as &$service) {
            $add_flat_titles($service);
        }
        $result->set_data($data);
    }

    return $result;
}, 10, 3);

/**
 * BUG FIX B4: Favorites REST API for logged-in users
 * Enables cross-device favorites sync for authenticated users
 */
add_action('rest_api_init', function() {
    // GET /favorites - Get user's favorites
    register_rest_route('batumizone/v1', '/favorites', array(
        'methods' => 'GET',
        'callback' => 'batumi_get_favorites',
        'permission_callback' => 'is_user_logged_in',
    ));

    // POST /favorites - Add a favorite
    register_rest_route('batumizone/v1', '/favorites', array(
        'methods' => 'POST',
        'callback' => 'batumi_add_favorite',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'service_id' => array(
                'required' => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // DELETE /favorites/{id} - Remove a favorite
    register_rest_route('batumizone/v1', '/favorites/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'batumi_remove_favorite',
        'permission_callback' => 'is_user_logged_in',
    ));

    // Profile Management Endpoints (FB-04)
    // PUT /me - Update user profile
    register_rest_route('batumizone/v1', '/me', array(
        'methods' => 'PUT',
        'callback' => 'batumi_update_profile',
        'permission_callback' => 'is_user_logged_in',
    ));

    // DELETE /me - Delete user account
    register_rest_route('batumizone/v1', '/me', array(
        'methods' => 'DELETE',
        'callback' => 'batumi_delete_account',
        'permission_callback' => 'is_user_logged_in',
    ));

    // GET /my/services - Get user's services (for profile stats)
    register_rest_route('batumizone/v1', '/my/services', array(
        'methods' => 'GET',
        'callback' => 'batumi_get_my_services',
        'permission_callback' => 'is_user_logged_in',
    ));
});

function batumi_get_favorites($request) {
    $user_id = get_current_user_id();
    $favorite_ids = get_user_meta($user_id, 'batumi_favorites', true);

    if (!is_array($favorite_ids)) {
        $favorite_ids = array();
    }

    // Return full service data, not just IDs
    $services = array();
    foreach ($favorite_ids as $service_id) {
        $service = get_post($service_id);
        if (!$service || $service->post_type !== 'service_listing' || $service->post_status !== 'publish') {
            continue;
        }

        // Get multilingual titles
        $title_ge = get_field('title_ge', $service_id) ?: '';
        $title_ru = get_field('title_ru', $service_id) ?: '';
        $title_en = get_field('title_en', $service_id) ?: '';

        // Get multilingual descriptions
        $desc_ge = get_field('desc_ge', $service_id) ?: '';
        $desc_ru = get_field('desc_ru', $service_id) ?: '';
        $desc_en = get_field('desc_en', $service_id) ?: '';

        // Get other service data
        $price_value = get_field('price_value', $service_id);
        $price_model = get_field('price_model', $service_id);
        $price_currency = get_field('price_currency', $service_id) ?: 'GEL';
        $phone = get_field('phone', $service_id);
        $thumbnail = get_the_post_thumbnail_url($service_id, 'service-thumbnail');

        // Get category
        $categories = get_the_terms($service_id, 'service_category');
        $category_name = '';
        $category_slug = '';
        if ($categories && !is_wp_error($categories)) {
            $category_name = $categories[0]->name;
            $category_slug = $categories[0]->slug;
        }

        $services[] = array(
            'id' => $service_id,
            'slug' => $service->post_name,
            'link' => get_permalink($service_id),
            'title_ge' => $title_ge,
            'title_ru' => $title_ru,
            'title_en' => $title_en,
            'desc_ge' => wp_trim_words(wp_strip_all_tags($desc_ge), 20, '...'),
            'desc_ru' => wp_trim_words(wp_strip_all_tags($desc_ru), 20, '...'),
            'desc_en' => wp_trim_words(wp_strip_all_tags($desc_en), 20, '...'),
            'price_value' => $price_value,
            'price_model' => $price_model,
            'price_currency' => $price_currency,
            'phone' => $phone,
            'thumbnail' => $thumbnail ?: '',
            'category' => $category_name,
            'category_slug' => $category_slug,
            'date_added' => get_post_meta($service_id, 'batumi_favorited_' . $user_id, true) ?: $service->post_date,
        );
    }

    return rest_ensure_response(array(
        'favorites' => $services,
        'count' => count($services),
    ));
}

function batumi_add_favorite($request) {
    $user_id = get_current_user_id();
    $service_id = $request->get_param('service_id');

    // Verify service exists
    $service = get_post($service_id);
    if (!$service || $service->post_type !== 'service_listing') {
        return new WP_Error('invalid_service', 'Service not found', array('status' => 404));
    }

    $favorites = get_user_meta($user_id, 'batumi_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }

    if (!in_array($service_id, $favorites)) {
        $favorites[] = $service_id;
        update_user_meta($user_id, 'batumi_favorites', $favorites);
    }

    // Return the service data that was added
    $title_ge = get_field('title_ge', $service_id) ?: '';
    $title_ru = get_field('title_ru', $service_id) ?: '';
    $title_en = get_field('title_en', $service_id) ?: '';
    $thumbnail = get_the_post_thumbnail_url($service_id, 'service-thumbnail');

    $categories = get_the_terms($service_id, 'service_category');
    $category_name = '';
    if ($categories && !is_wp_error($categories)) {
        $category_name = $categories[0]->name;
    }

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Service added to favorites',
        'service' => array(
            'id' => $service_id,
            'link' => get_permalink($service_id),
            'title_ge' => $title_ge,
            'title_ru' => $title_ru,
            'title_en' => $title_en,
            'thumbnail' => $thumbnail ?: '',
            'category' => $category_name,
        ),
        'count' => count($favorites),
    ));
}

function batumi_remove_favorite($request) {
    $user_id = get_current_user_id();
    $service_id = (int) $request->get_param('id');

    $favorites = get_user_meta($user_id, 'batumi_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }

    $favorites = array_filter($favorites, function($id) use ($service_id) {
        return $id !== $service_id;
    });
    $favorites = array_values($favorites); // Re-index

    update_user_meta($user_id, 'batumi_favorites', $favorites);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Service removed from favorites',
        'favorites' => $favorites,
    ));
}

/**
 * FB-04: Update user profile via REST API
 * Allows users to update their profile without accessing wp-admin
 */
function batumi_update_profile($request) {
    $user_id = get_current_user_id();
    $params = $request->get_json_params();

    $updated = array();

    // Update email if provided
    if (!empty($params['email'])) {
        $email = sanitize_email($params['email']);
        if (is_email($email)) {
            // Check if email is already in use by another user
            $existing = email_exists($email);
            if ($existing && $existing !== $user_id) {
                return new WP_Error('email_exists', 'Email is already in use', array('status' => 400));
            }
            wp_update_user(array('ID' => $user_id, 'user_email' => $email));
            $updated[] = 'email';
        }
    }

    // Update phone if provided
    if (isset($params['phone'])) {
        $phone = sanitize_text_field($params['phone']);
        update_user_meta($user_id, 'phone', $phone);
        $updated[] = 'phone';
    }

    // Update WhatsApp if provided
    if (isset($params['whatsapp'])) {
        $whatsapp = sanitize_text_field($params['whatsapp']);
        update_user_meta($user_id, 'whatsapp', $whatsapp);
        $updated[] = 'whatsapp';
    }

    // Update password if provided
    if (!empty($params['password'])) {
        if (strlen($params['password']) < 8) {
            return new WP_Error('password_short', 'Password must be at least 8 characters', array('status' => 400));
        }
        wp_set_password($params['password'], $user_id);
        $updated[] = 'password';
    }

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Profile updated successfully',
        'updated' => $updated,
    ));
}

/**
 * FB-04: Delete user account via REST API
 * Allows users to delete their account from the frontend
 */
function batumi_delete_account($request) {
    $user_id = get_current_user_id();

    // Prevent admin deletion via this endpoint
    if (user_can($user_id, 'manage_options')) {
        return new WP_Error('admin_protected', 'Administrator accounts cannot be deleted this way', array('status' => 403));
    }

    // Delete all user's service listings
    $user_services = get_posts(array(
        'post_type' => 'service_listing',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));

    foreach ($user_services as $service_id) {
        wp_delete_post($service_id, true);
    }

    // Delete the user
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    wp_delete_user($user_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Account deleted successfully',
    ));
}

/**
 * FB-04: Get user's services for profile stats
 */
function batumi_get_my_services($request) {
    $user_id = get_current_user_id();

    $services = get_posts(array(
        'post_type' => 'service_listing',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'pending', 'inactive'),
    ));

    $result = array();
    foreach ($services as $service) {
        $result[] = array(
            'id' => $service->ID,
            'title' => $service->post_title,
            'status' => $service->post_status,
            'date' => $service->post_date,
        );
    }

    return rest_ensure_response($result);
}

/**
 * BUG FIX DM-01 (Part 2): JavaScript fix for favorites page title extraction
 * Patches the FavoritesPage.renderServiceCard to handle nested title format
 */
add_action('wp_footer', function() {
    if (is_page('favorites') || is_page_template('page-favorites.php')) {
        ?>
        <script>
        (function(){
            // Wait for FavoritesPage to be defined, then patch it
            var checkInterval = setInterval(function() {
                if (typeof FavoritesPage !== 'undefined' && FavoritesPage.renderServiceCard) {
                    clearInterval(checkInterval);

                    // Store original function
                    var originalRender = FavoritesPage.renderServiceCard.bind(FavoritesPage);

                    // Helper to get title from either format
                    function getTitle(service, lang) {
                        // Map ka to ge for Georgian
                        var geLang = (lang === 'ka') ? 'ge' : lang;

                        // Try flat format first
                        var flatKey = 'title_' + geLang;
                        if (service[flatKey]) return service[flatKey];
                        if (service.title_en) return service.title_en;
                        if (service.title_ge) return service.title_ge;
                        if (service.title_ru) return service.title_ru;

                        // Try nested format
                        if (service.title && typeof service.title === 'object') {
                            return service.title[geLang] || service.title.en || service.title.ge || service.title.ru || 'Untitled';
                        }

                        // String title
                        if (typeof service.title === 'string') return service.title;

                        return 'Untitled';
                    }

                    // Patch renderServiceCard
                    FavoritesPage.renderServiceCard = function(service) {
                        var currentLang = document.documentElement.lang?.split('-')[0] || 'ka';
                        var title = getTitle(service, currentLang);
                        var price = this.formatPrice(service.price_model || (service.price && service.price.model), service.price_value || (service.price && service.price.value), service.currency || (service.price && service.price.currency));
                        var thumbnail = service.featured_image?.thumbnail || service.featured_image || '';
                        var link = service.link || service.url || '#';
                        var category = service.direction_name || (service.direction && service.direction[0]) || '';

                        return '<article class="service-card">' +
                            (thumbnail ? '<div class="service-card-image-wrapper">' +
                                '<a href="' + link + '" class="service-card-image-link">' +
                                    '<img src="' + thumbnail + '" alt="' + title + '" class="service-card-image">' +
                                '</a>' +
                                '<button class="favorite-btn is-favorited" data-service-id="' + service.id + '" data-service-title="' + title + '" aria-label="Remove from favorites" title="Remove from favorites">' +
                                    '<svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                        '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>' +
                                    '</svg>' +
                                '</button>' +
                            '</div>' : '') +
                            '<div class="service-card-content">' +
                                '<h3 class="service-card-title"><a href="' + link + '">' + title + '</a></h3>' +
                                '<div class="service-card-meta">' +
                                    (category ? '<span class="service-category">' + category + '</span>' : '') +
                                '</div>' +
                                (price ? '<div class="service-card-price">' + price + '</div>' : '') +
                                '<div class="service-card-footer">' +
                                    '<a href="' + link + '" class="btn btn-secondary btn-sm">View Details</a>' +
                                '</div>' +
                            '</div>' +
                        '</article>';
                    };

                    console.log('FavoritesPage.renderServiceCard patched for nested title support');
                }
            }, 100);

            // Stop checking after 5 seconds
            setTimeout(function() { clearInterval(checkInterval); }, 5000);
        })();
        </script>
        <?php
    }
}, 99); // Priority 99 to run after FavoritesPage is defined

/**
 * BUG FIX V1/V2 (Part 2): Dark mode toggle function with localStorage persistence
 */
add_action('wp_footer', function() {
    ?>
    <script>
    (function(){
        // Ensure body has dark mode attribute if it was set on html
        var currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
        }

        // Global toggle function for dark mode
        window.toggleDarkMode = function() {
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            var newTheme = isDark ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('batumi-theme', newTheme);

            // Update toggle button icon if exists
            var toggleBtn = document.querySelector('.theme-toggle-btn');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-pressed', newTheme === 'dark');
            }
        };

        // Initialize toggle button listener if it exists
        document.addEventListener('DOMContentLoaded', function() {
            var toggleBtn = document.querySelector('.theme-toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', window.toggleDarkMode);
            }

            // V4 FIX: Close mobile menu when clicking menu links
            var mobileMenu = document.querySelector('.mobile-menu, .mobile-nav, #mobile-menu');
            var menuToggle = document.querySelector('.mobile-menu-toggle, .hamburger-btn, #menu-toggle');
            if (mobileMenu) {
                mobileMenu.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        mobileMenu.classList.remove('active', 'open', 'is-active');
                        if (menuToggle) {
                            menuToggle.classList.remove('active', 'open', 'is-active');
                            menuToggle.setAttribute('aria-expanded', 'false');
                        }
                        document.body.classList.remove('mobile-menu-open', 'menu-open');
                    });
                });
            }

            // V11 FIX: Gallery Lightbox
            var galleryImages = document.querySelectorAll('.service-gallery img, .gallery-item img');
            if (galleryImages.length > 0) {
                // Create lightbox container
                var lightbox = document.createElement('div');
                lightbox.className = 'gallery-lightbox';
                lightbox.innerHTML = '<button class="gallery-lightbox-close" aria-label="Close">&times;</button><img src="" alt="">';
                document.body.appendChild(lightbox);

                var lightboxImg = lightbox.querySelector('img');
                var closeBtn = lightbox.querySelector('.gallery-lightbox-close');

                galleryImages.forEach(function(img) {
                    img.addEventListener('click', function() {
                        lightboxImg.src = this.src.replace(/-\d+x\d+\./, '.'); // Get full size
                        lightboxImg.alt = this.alt;
                        lightbox.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    });
                });

                closeBtn.addEventListener('click', function() {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = '';
                });

                lightbox.addEventListener('click', function(e) {
                    if (e.target === lightbox) {
                        lightbox.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                        lightbox.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    })();
    </script>
    <?php
}, 20);
