<?php
/**
 * Archive Template for Service Listings
 *
 * @package Batumi_Theme
 * @since 0.2.0
 */

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';

// Get current filters from URL
$current_direction = isset($_GET['direction']) ? sanitize_text_field($_GET['direction']) : '';
$current_area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
$current_sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date';
$search_query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : '';

// Build WP_Query arguments
$args = array(
    'post_type'      => 'service_listing',
    'post_status'    => 'publish',
    'posts_per_page' => 20,
    'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
);

// Add search query
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

// Add taxonomy filters
$tax_query = array('relation' => 'AND');

if (!empty($current_direction)) {
    $tax_query[] = array(
        'taxonomy' => 'service_category',
        'field'    => 'slug',
        'terms'    => $current_direction,
    );
}

if (!empty($current_area)) {
    $tax_query[] = array(
        'taxonomy' => 'coverage_area',
        'field'    => 'slug',
        'terms'    => $current_area,
    );
}

if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
}

// Add price filtering
if (!empty($price_min) || !empty($price_max)) {
    $meta_query = array('relation' => 'AND');

    if (!empty($price_min)) {
        $meta_query[] = array(
            'key'     => 'price_value',
            'value'   => $price_min,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if (!empty($price_max)) {
        $meta_query[] = array(
            'key'     => 'price_value',
            'value'   => $price_max,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    $args['meta_query'] = $meta_query;
}

// Add sorting
switch ($current_sort) {
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
    default: // 'date'
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
}

$services_query = new WP_Query($args);
?>

<main id="primary" class="site-main services-archive">
    <div class="container">
        <div class="services-page">

            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <button class="filters-toggle" aria-controls="filters-panel" aria-expanded="false">
                    <span class="filters-icon">⚙️</span>
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Фильтры';
                    } elseif ($current_lang === 'en') {
                        echo 'Filters';
                    } else {
                        echo 'ფილტრები';
                    }
                    ?>
                </button>

                <form id="filters-panel" class="filters-form" method="get">
                    <?php if (!empty($search_query)) : ?>
                        <input type="hidden" name="query" value="<?php echo esc_attr($search_query); ?>">
                    <?php endif; ?>

                    <!-- Service Direction Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Категория';
                            } elseif ($current_lang === 'en') {
                                echo 'Category';
                            } else {
                                echo 'კატეგორია';
                            }
                            ?>
                        </label>
                        <select name="direction" class="filter-select">
                            <option value=""><?php
                            if ($current_lang === 'ru') {
                                echo 'Все категории';
                            } elseif ($current_lang === 'en') {
                                echo 'All Categories';
                            } else {
                                echo 'ყველა კატეგორია';
                            }
                            ?></option>
                            <?php
                            $directions = get_terms(array('taxonomy' => 'service_category', 'hide_empty' => true));
                            if (!is_wp_error($directions)) {
                                foreach ($directions as $term) {
                                    $selected = ($current_direction === $term->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($term->slug),
                                        $selected,
                                        esc_html($term->name),
                                        $term->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Coverage Area Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Район';
                            } elseif ($current_lang === 'en') {
                                echo 'Area';
                            } else {
                                echo 'უბანი';
                            }
                            ?>
                        </label>
                        <select name="area" class="filter-select">
                            <option value=""><?php
                            if ($current_lang === 'ru') {
                                echo 'Все районы';
                            } elseif ($current_lang === 'en') {
                                echo 'All Areas';
                            } else {
                                echo 'ყველა უბანი';
                            }
                            ?></option>
                            <?php
                            $areas = get_terms(array('taxonomy' => 'coverage_area', 'hide_empty' => true));
                            if (!is_wp_error($areas)) {
                                foreach ($areas as $term) {
                                    $selected = ($current_area === $term->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($term->slug),
                                        $selected,
                                        esc_html($term->name),
                                        $term->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Цена';
                            } elseif ($current_lang === 'en') {
                                echo 'Price';
                            } else {
                                echo 'ფასი';
                            }
                            ?>
                        </label>
                        <div class="price-range">
                            <input
                                type="number"
                                name="price_min"
                                class="price-input"
                                placeholder="<?php echo $current_lang === 'ru' ? 'Мин' : ($current_lang === 'en' ? 'Min' : 'მინ'); ?>"
                                value="<?php echo esc_attr($price_min); ?>"
                            >
                            <span class="price-separator">—</span>
                            <input
                                type="number"
                                name="price_max"
                                class="price-input"
                                placeholder="<?php echo $current_lang === 'ru' ? 'Макс' : ($current_lang === 'en' ? 'Max' : 'მაქს'); ?>"
                                value="<?php echo esc_attr($price_max); ?>"
                            >
                        </div>
                    </div>

                    <!-- Sort Options -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Сортировка';
                            } elseif ($current_lang === 'en') {
                                echo 'Sort By';
                            } else {
                                echo 'დალაგება';
                            }
                            ?>
                        </label>
                        <select name="sort" class="filter-select">
                            <option value="date" <?php selected($current_sort, 'date'); ?>>
                                <?php echo $current_lang === 'ru' ? 'Сначала новые' : ($current_lang === 'en' ? 'Newest First' : 'ახალი პირველი'); ?>
                            </option>
                            <option value="price_low" <?php selected($current_sort, 'price_low'); ?>>
                                <?php echo $current_lang === 'ru' ? 'Цена: по возрастанию' : ($current_lang === 'en' ? 'Price: Low to High' : 'ფასი: დაბალი → მაღალი'); ?>
                            </option>
                            <option value="price_high" <?php selected($current_sort, 'price_high'); ?>>
                                <?php echo $current_lang === 'ru' ? 'Цена: по убыванию' : ($current_lang === 'en' ? 'Price: High to Low' : 'ფასი: მაღალი → დაბალი'); ?>
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <?php echo $current_lang === 'ru' ? 'Применить фильтры' : ($current_lang === 'en' ? 'Apply Filters' : 'ფილტრების გამოყენება'); ?>
                    </button>

                    <?php if (!empty($current_direction) || !empty($current_area) || !empty($price_min) || !empty($price_max) || $current_sort !== 'date') : ?>
                        <a href="<?php echo esc_url(get_post_type_archive_link('service_listing')); ?>" class="btn btn-secondary btn-block btn-reset">
                            <?php echo $current_lang === 'ru' ? 'Сбросить фильтры' : ($current_lang === 'en' ? 'Reset Filters' : 'ფილტრების გასუფთავება'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Services Main Content -->
            <div class="services-main">

                <!-- Services Header -->
                <header class="services-header">
                    <h1 class="page-title">
                        <?php
                        if (!empty($search_query)) {
                            $search_text = $current_lang === 'ru' ? 'Результаты поиска: ' : ($current_lang === 'en' ? 'Search Results: ' : 'ძებნის შედეგები: ');
                            echo esc_html($search_text) . '<span class="search-term">' . esc_html($search_query) . '</span>';
                        } else {
                            echo $current_lang === 'ru' ? 'Услуги в Батуми' : ($current_lang === 'en' ? 'Services in Batumi' : 'სერვისები ბათუმში');
                        }
                        ?>
                    </h1>

                    <p class="results-count">
                        <?php
                        $count_text = $current_lang === 'ru' ? 'услуг найдено' : ($current_lang === 'en' ? 'services found' : 'სერვისი ნაპოვნია');
                        echo '<strong>' . number_format_i18n($services_query->found_posts) . '</strong> ' . esc_html($count_text);
                        ?>
                    </p>
                </header>

                <!-- Services Grid -->
                <div class="services-grid">
                    <?php
                    if ($services_query->have_posts()) {
                        $result_count = 0;
                        while ($services_query->have_posts()) {
                            $services_query->the_post();
                            $result_count++;
                            get_template_part('template-parts/content', 'service-card');

                            // Insert ad after 5th result
                            if ($result_count === 5) {
                                ?>
                                <div class="ad-container" data-placement="results_after_n" data-position="5" data-api-url="/wp-json/batumizone/v1/ads/placement/results_after_n?position=5">
                                    <div class="ad-label">Advertisement</div>
                                    <div class="ad-loading">Loading...</div>
                                </div>
                                <?php
                            }
                        }
                        wp_reset_postdata();
                    } else {
                        $no_results = $current_lang === 'ru' ? 'Услуги не найдены. Попробуйте изменить параметры фильтра.' :
                                      ($current_lang === 'en' ? 'No services found. Try adjusting your filter settings.' :
                                       'სერვისები ვერ მოიძებნა. სცადეთ ფილტრების შეცვლა.');
                        echo '<div class="no-results"><p class="no-results-text">' . esc_html($no_results) . '</p></div>';
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <?php if ($services_query->max_num_pages > 1) : ?>
                    <nav class="pagination-nav">
                        <?php
                        echo paginate_links(array(
                            'total'     => $services_query->max_num_pages,
                            'current'   => max(1, get_query_var('paged')),
                            'prev_text' => '←',
                            'next_text' => '→',
                            'type'      => 'list',
                            'add_args'  => array_filter(array(
                                'direction' => $current_direction,
                                'area'      => $current_area,
                                'sort'      => $current_sort !== 'date' ? $current_sort : false,
                                'query'     => $search_query,
                                'price_min' => $price_min,
                                'price_max' => $price_max,
                            )),
                        ));
                        ?>
                    </nav>
                <?php endif; ?>

            </div>

        </div>
    </div>
</main>

<?php
get_footer();
