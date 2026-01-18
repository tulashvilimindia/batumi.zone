<?php
/**
 * Template Name: Fancy Frontend Homepage
 * The fancy homepage with immediate service cards and infinite scroll
 *
 * @package Batumi_Theme
 * @since 0.5.0
 */

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';

// Get filter parameters
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date';

// Build WP_Query args
$args = array(
    'post_type' => 'service_listing',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'paged' => 1,
);

// Sorting
switch ($sort) {
    case 'price_low':
        $args['meta_key'] = 'price_value';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'ASC';
        break;
    case 'price_high':
        $args['meta_key'] = 'price_value';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'random':
        $args['orderby'] = 'rand';
        break;
    default: // date (newest)
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
}

// Taxonomy filters (Category and Area)
$tax_query = array('relation' => 'AND');

if ($category) {
    $tax_query[] = array(
        'taxonomy' => 'service_category',
        'field' => 'slug',
        'terms' => $category,
    );
}

if ($area) {
    $tax_query[] = array(
        'taxonomy' => 'coverage_area',
        'field' => 'slug',
        'terms' => $area,
    );
}

// Only add tax_query if filters are applied
if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
}

// Search query (across all language fields)
if (!empty($search_query)) {
    $meta_query = array(
        'relation' => 'OR',
        array('key' => 'title_ge', 'value' => $search_query, 'compare' => 'LIKE'),
        array('key' => 'title_ru', 'value' => $search_query, 'compare' => 'LIKE'),
        array('key' => 'title_en', 'value' => $search_query, 'compare' => 'LIKE'),
        array('key' => 'desc_ge', 'value' => $search_query, 'compare' => 'LIKE'),
        array('key' => 'desc_ru', 'value' => $search_query, 'compare' => 'LIKE'),
        array('key' => 'desc_en', 'value' => $search_query, 'compare' => 'LIKE'),
    );

    // Combine with price meta query if exists
    if (isset($args['meta_query'])) {
        $args['meta_query'] = array(
            'relation' => 'AND',
            $meta_query,
            $args['meta_query']
        );
    } else {
        $args['meta_query'] = $meta_query;
    }
}

// Price filter (if no search, or combined with search above)
if (($price_min || $price_max) && empty($search_query)) {
    $meta_query = array('relation' => 'AND');

    if ($price_min) {
        $meta_query[] = array(
            'key' => 'price_value',
            'value' => $price_min,
            'type' => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ($price_max) {
        $meta_query[] = array(
            'key' => 'price_value',
            'value' => $price_max,
            'type' => 'NUMERIC',
            'compare' => '<=',
        );
    }

    $args['meta_query'] = $meta_query;
} elseif (($price_min || $price_max) && !empty($search_query)) {
    // Price filter with search query - need to add to existing meta_query
    $price_conditions = array('relation' => 'AND');

    if ($price_min) {
        $price_conditions[] = array(
            'key' => 'price_value',
            'value' => $price_min,
            'type' => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ($price_max) {
        $price_conditions[] = array(
            'key' => 'price_value',
            'value' => $price_max,
            'type' => 'NUMERIC',
            'compare' => '<=',
        );
    }

    // Wrap existing meta_query with price filter
    $args['meta_query'] = array(
        'relation' => 'AND',
        $args['meta_query'],
        $price_conditions
    );
}

// Query services
$services_query = new WP_Query($args);
?>

<main id="primary" class="site-main">

    <!-- Home Top Banner Ad -->
    <div class="ad-container" data-placement="home_top" data-api-url="/wp-json/batumizone/v1/ads/placement/home_top">
        <div class="ad-loading">Loading...</div>
    </div>

    <!-- Services Grid -->
    <div id="services-grid" class="services-grid">
        <?php
        if ($services_query->have_posts()) :
            while ($services_query->have_posts()) : $services_query->the_post();
                get_template_part('template-parts/content', 'service-card');
            endwhile;
        else :
            ?>
            <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
                <h2><?php
                    if ($current_lang === 'ru') {
                        _e('Услуги не найдены', 'batumi-theme');
                    } elseif ($current_lang === 'en') {
                        _e('No services found', 'batumi-theme');
                    } else {
                        _e('სერვისები ვერ მოიძებნა', 'batumi-theme');
                    }
                ?></h2>
                <p><?php
                    if ($current_lang === 'ru') {
                        _e('Попробуйте изменить фильтры или вернуться ко всем услугам', 'batumi-theme');
                    } elseif ($current_lang === 'en') {
                        _e('Try adjusting your filters or return to all services', 'batumi-theme');
                    } else {
                        _e('სცადეთ ფილტრების შეცვლა ან დაბრუნდით ყველა სერვისზე', 'batumi-theme');
                    }
                ?></p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary" style="display: inline-block; margin-top: 1rem;">
                    <?php
                    if ($current_lang === 'ru') {
                        _e('Показать все услуги', 'batumi-theme');
                    } elseif ($current_lang === 'en') {
                        _e('Show all services', 'batumi-theme');
                    } else {
                        _e('ყველა სერვისის ნახვა', 'batumi-theme');
                    }
                    ?>
                </a>
            </div>
            <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>

    <!-- Loading Indicator for Infinite Scroll -->
    <div id="loading-indicator" style="display: none;">
        <div class="loading-spinner"></div>
        <p style="margin-top: 1rem; color: #666; font-size: 0.9375rem;">
            <?php
            if ($current_lang === 'ru') {
                _e('Загрузка...', 'batumi-theme');
            } elseif ($current_lang === 'en') {
                _e('Loading...', 'batumi-theme');
            } else {
                _e('იტვირთება...', 'batumi-theme');
            }
            ?>
        </p>
    </div>

</main>

<?php
get_footer();
?>
