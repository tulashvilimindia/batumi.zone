<?php
/**
 * Single Service Listing Template
 *
 * @package Batumi_Theme
 * @since 0.2.0
 */

get_header();

while (have_posts()) : the_post();
    $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
    $lang_code = ($current_lang === 'ge') ? 'ka' : $current_lang;
    $post_id = get_the_ID();

    // Get multilingual fields with fallback
    $title = get_field("title_{$current_lang}", $post_id);
    if (empty($title)) {
        $title = get_field('title_en', $post_id) ?: get_field('title_ge', $post_id) ?: get_field('title_ru', $post_id);
    }

    $desc = get_field("desc_{$current_lang}", $post_id);
    if (empty($desc)) {
        $desc = get_field('desc_en', $post_id) ?: get_field('desc_ge', $post_id) ?: get_field('desc_ru', $post_id);
    }

    // Get other fields
    $price_model = get_field('price_model', $post_id);
    $price_value = get_field('price_value', $post_id);
    $currency = get_field('currency', $post_id) ?: 'GEL';
    $latitude = get_field('location_lat', $post_id);
    $longitude = get_field('location_lng', $post_id);
    $neighborhood = get_field('neighborhood', $post_id);
    $phone = get_field('phone', $post_id);
    $whatsapp = get_field('whatsapp', $post_id);
    $email = get_field('email', $post_id);

    // Get gallery
    $gallery_ids = get_post_meta($post_id, '_gallery_image_ids', true);
    $gallery = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();

    // Get taxonomies
    $directions = get_the_terms($post_id, 'service_category');
    $areas = get_the_terms($post_id, 'coverage_area');
    ?>

    <main id="primary" class="site-main service-detail-page">
        <div class="container">
            <div class="service-detail-wrapper">

                <!-- Main Content -->
                <article id="post-<?php the_ID(); ?>" <?php post_class('service-detail'); ?>>

                    <!-- Breadcrumbs -->
                    <nav class="breadcrumbs">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo $current_lang === 'ru' ? 'Главная' : ($current_lang === 'en' ? 'Home' : 'მთავარი'); ?></a>
                        <span class="sep"> / </span>
                        <a href="<?php echo esc_url(get_post_type_archive_link('service_listing')); ?>"><?php echo $current_lang === 'ru' ? 'Услуги' : ($current_lang === 'en' ? 'Services' : 'სერვისები'); ?></a>
                        <?php if ($directions && !is_wp_error($directions)) : ?>
                            <span class="sep"> / </span>
                            <a href="<?php echo esc_url(get_term_link($directions[0])); ?>"><?php echo esc_html($directions[0]->name); ?></a>
                        <?php endif; ?>
                        <span class="sep"> / </span>
                        <span class="current"><?php echo esc_html($title); ?></span>
                    </nav>

                    <!-- Service Header -->
                    <header class="service-header">
                        <h1 class="service-title"><?php echo esc_html($title); ?></h1>

                        <div class="service-meta">
                            <?php if ($directions && !is_wp_error($directions)) : ?>
                                <?php foreach ($directions as $term) : ?>
                                    <span class="badge badge-category"><?php echo esc_html($term->name); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($areas && !is_wp_error($areas)) : ?>
                                <?php foreach ($areas as $term) : ?>
                                    <span class="badge badge-area">📍 <?php echo esc_html($term->name); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php
                            // Display service tags (multilingual)
                            $tags = get_the_terms($post_id, 'service_tag');
                            if ($tags && !is_wp_error($tags)) :
                            ?>
                                <div class="service-tags" style="margin-top: 0.5rem;">
                                    <?php foreach ($tags as $tag) :
                                        $translated_name = get_term_meta($tag->term_id, 'name_' . $lang_code, true);
                                        $display_name = !empty($translated_name) ? $translated_name : $tag->name;
                                    ?>
                                        <a href="<?php echo esc_url(add_query_arg('stag', $tag->slug, home_url('/'))); ?>" class="service-tag">
                                            <?php echo esc_html($display_name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <span class="service-date">
                                <?php echo get_the_date(); ?>
                            </span>
                        </div>
                    </header>

                    <!-- Gallery -->
                    <?php if (!empty($gallery) || has_post_thumbnail()) : ?>
                        <div class="service-gallery">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="gallery-main">
                                    <?php the_post_thumbnail('large', array('class' => 'gallery-main-image')); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($gallery) && count($gallery) > 0) : ?>
                                <div class="gallery-thumbs">
                                    <?php
                                    // Include featured image in thumbs
                                    if (has_post_thumbnail()) {
                                        echo '<div class="gallery-thumb">';
                                        the_post_thumbnail('thumbnail');
                                        echo '</div>';
                                    }

                                    // Add gallery images
                                    foreach ($gallery as $image_id) {
                                        if (!empty($image_id) && is_numeric($image_id)) {
                                            echo '<div class="gallery-thumb">';
                                            echo wp_get_attachment_image($image_id, 'thumbnail');
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="service-description">
                        <h2><?php echo $current_lang === 'ru' ? 'Описание' : ($current_lang === 'en' ? 'Description' : 'აღწერა'); ?></h2>
                        <div class="description-content">
                            <?php echo nl2br(esc_html($desc)); ?>
                        </div>

                        <!-- Available Languages -->
                        <?php
                        $available_langs = array();
                        if (!empty(get_field('title_ge', $post_id))) $available_langs[] = 'GE';
                        if (!empty(get_field('title_ru', $post_id))) $available_langs[] = 'RU';
                        if (!empty(get_field('title_en', $post_id))) $available_langs[] = 'EN';

                        if (count($available_langs) > 1) :
                        ?>
                            <p class="available-languages">
                                <small>
                                    <?php
                                    $also_text = $current_lang === 'ru' ? 'Также доступно на:' : ($current_lang === 'en' ? 'Also available in:' : 'ასევე ხელმისაწვდომია:');
                                    echo esc_html($also_text) . ' ' . implode(', ', $available_langs);
                                    ?>
                                </small>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Price -->
                    <?php if ($price_model && $price_value) : ?>
                        <div class="service-pricing">
                            <h2><?php echo $current_lang === 'ru' ? 'Цена' : ($current_lang === 'en' ? 'Pricing' : 'ფასი'); ?></h2>
                            <div class="price-display">
                                <?php
                                if (function_exists('batumi_format_price')) {
                                    echo batumi_format_price($price_model, $price_value, $currency);
                                } else {
                                    echo esc_html($price_value . ' ' . $currency);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Location -->
                    <?php if ($latitude && $longitude) : ?>
                        <div class="service-location">
                            <h2><?php echo $current_lang === 'ru' ? 'Расположение' : ($current_lang === 'en' ? 'Location' : 'მდებარეობა'); ?></h2>

                            <?php if ($neighborhood) : ?>
                                <p class="location-neighborhood">
                                    <strong><?php echo $current_lang === 'ru' ? 'Район:' : ($current_lang === 'en' ? 'Neighborhood:' : 'უბანი:'); ?></strong>
                                    <?php echo esc_html($neighborhood); ?>
                                </p>
                            <?php endif; ?>

                            <p class="location-coordinates">
                                <strong><?php echo $current_lang === 'ru' ? 'Координаты:' : ($current_lang === 'en' ? 'Coordinates:' : 'კოორდინატები:'); ?></strong>
                                <?php echo esc_html($latitude); ?>, <?php echo esc_html($longitude); ?>
                            </p>

                            <!-- Map Placeholder (will add Leaflet.js in future) -->
                            <div class="map-placeholder">
                                <p><?php echo $current_lang === 'ru' ? 'Карта будет отображаться здесь' : ($current_lang === 'en' ? 'Map will be displayed here' : 'რუკა აქ გამოჩნდება'); ?></p>
                                <p><small><?php echo $current_lang === 'ru' ? 'Широта/Долгота' : ($current_lang === 'en' ? 'Lat/Lng' : 'Lat/Lng'); ?>: <?php echo esc_html($latitude . ', ' . $longitude); ?></small></p>
                            </div>
                        </div>
                    <?php endif; ?>

                </article>

                <!-- Contact Block (Sticky Sidebar) -->
                <aside class="service-contact-block">
                    <h2><?php echo $current_lang === 'ru' ? 'Контакты' : ($current_lang === 'en' ? 'Contact' : 'კონტაქტი'); ?></h2>

                    <?php if ($phone) : ?>
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="contact-btn contact-phone">
                            <span class="btn-icon">📞</span>
                            <span class="btn-content">
                                <span class="btn-label"><?php echo $current_lang === 'ru' ? 'Позвонить' : ($current_lang === 'en' ? 'Call' : 'დარეკვა'); ?></span>
                                <span class="btn-value"><?php echo esc_html($phone); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($whatsapp) : ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>"
                           class="contact-btn contact-whatsapp"
                           target="_blank"
                           rel="noopener">
                            <span class="btn-icon">💬</span>
                            <span class="btn-content">
                                <span class="btn-label"><?php echo $current_lang === 'ru' ? 'WhatsApp' : ($current_lang === 'en' ? 'WhatsApp' : 'WhatsApp'); ?></span>
                                <span class="btn-value"><?php echo esc_html($whatsapp); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($email) : ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-btn contact-email">
                            <span class="btn-icon">✉️</span>
                            <span class="btn-content">
                                <span class="btn-label"><?php echo $current_lang === 'ru' ? 'Email' : ($current_lang === 'en' ? 'Email' : 'Email'); ?></span>
                                <span class="btn-value"><?php echo esc_html($email); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <!-- Report Button (Phase 7 functionality) -->
                    <button class="report-btn" data-service-id="<?php echo esc_attr($post_id); ?>" >
                        <span class="btn-icon">⚠️</span>
                        <?php echo $current_lang === 'ru' ? 'Пожаловаться' : ($current_lang === 'en' ? 'Report' : 'ჩივილი'); ?>
                    </button>

                    <!-- Back to Results -->
                    <a href="<?php echo esc_url(get_post_type_archive_link('service_listing')); ?>" class="btn btn-secondary btn-block">
                        ← <?php echo $current_lang === 'ru' ? 'Все услуги' : ($current_lang === 'en' ? 'All Services' : 'ყველა სერვისი'); ?>
                    </a>
                </aside>

                <!-- Ad Below Contact -->
                <div class="ad-container" data-placement="detail_below_contact" data-api-url="/wp-json/batumizone/v1/ads/placement/detail_below_contact" style="margin-top: 30px;">
                    <div class="ad-loading">Loading...</div>
                </div>

            </div>
        </div>
    </main>

    <?php
endwhile;

get_footer();
