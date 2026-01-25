<?php
/**
 * Front Page Template - Home / Services Landing
 *
 * @package Batumi_Theme
 * @since 0.2.0
 */

get_header();

// Get current language
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
?>

<main id="primary" class="site-main home-page">

    <!-- Hero Section with Search -->
    <section class="home-hero">
        <div class="container">
            <h1 class="hero-title">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Найдите услуги в Батуми';
                } elseif ($current_lang === 'en') {
                    echo 'Find Services in Batumi';
                } else {
                    echo 'იპოვეთ სერვისები ბათუმში';
                }
                ?>
            </h1>
            <p class="hero-subtitle">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Профессиональные услуги для вашего дома и бизнеса';
                } elseif ($current_lang === 'en') {
                    echo 'Professional services for your home and business';
                } else {
                    echo 'პროფესიონალური სერვისები თქვენი სახლისა და ბიზნესისთვის';
                }
                ?>
            </p>
            
            <form class="service-search" method="get" action="<?php echo esc_url(home_url('/services')); ?>">
                <input 
                    type="search" 
                    name="query" 
                    class="search-input"
                    placeholder="<?php
                    if ($current_lang === 'ru') {
                        echo 'Поиск услуг...';
                    } elseif ($current_lang === 'en') {
                        echo 'Search services...';
                    } else {
                        echo 'სერვისების ძებნა...';
                    }
                    ?>"
                    value="<?php echo esc_attr(get_query_var('query')); ?>"
                >
                <button type="submit" class="search-button">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Поиск';
                    } elseif ($current_lang === 'en') {
                        echo 'Search';
                    } else {
                        echo 'ძებნა';
                    }
                    ?>
                </button>
            </form>
        </div>
    </section>

    <!-- Category Navigation Grid -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Категории услуг';
                } elseif ($current_lang === 'en') {
                    echo 'Service Categories';
                } else {
                    echo 'სერვისების კატეგორიები';
                }
                ?>
            </h2>
            
            <div class="categories-grid">
                <?php
                $categories = get_terms(array(
                    'taxonomy'   => 'service_category',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'number'     => 12,
                ));

                if (!is_wp_error($categories) && !empty($categories)) {
                    foreach ($categories as $category) {
                        $category_link = get_term_link($category);
                        $count = $category->count;
                        ?>
                        <a href="<?php echo esc_url($category_link); ?>" class="category-card">
                            <div class="category-icon">📋</div>
                            <h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
                            <span class="category-count">
                                <?php 
                                printf(
                                    _n('%s service', '%s services', $count, 'batumi-theme'),
                                    number_format_i18n($count)
                                );
                                ?>
                            </span>
                        </a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Latest Services Feed -->
    <section class="latest-services-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Последние услуги';
                    } elseif ($current_lang === 'en') {
                        echo 'Latest Services';
                    } else {
                        echo 'ბოლო სერვისები';
                    }
                    ?>
                </h2>
                <a href="<?php echo esc_url(get_post_type_archive_link('service_listing')); ?>" class="view-all-link">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Посмотреть все →';
                    } elseif ($current_lang === 'en') {
                        echo 'View All →';
                    } else {
                        echo 'ყველას ნახვა →';
                    }
                    ?>
                </a>
            </div>

            <div class="services-grid">
                <?php
                $services_query = new WP_Query(array(
                    'post_type'      => 'service_listing',
                    'post_status'    => 'publish',
                    'posts_per_page' => 12,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ));

                if ($services_query->have_posts()) {
                    while ($services_query->have_posts()) {
                        $services_query->the_post();
                        get_template_part('template-parts/content', 'service-card');
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p class="no-services">';
                    if ($current_lang === 'ru') {
                        echo 'Пока нет доступных услуг.';
                    } elseif ($current_lang === 'en') {
                        echo 'No services available yet.';
                    } else {
                        echo 'ჯერ არ არის ხელმისაწვდომი სერვისები.';
                    }
                    echo '</p>';
                }
                ?>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();
