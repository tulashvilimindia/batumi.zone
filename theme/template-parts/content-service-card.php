<?php
/**
 * Template part for displaying service cards
 *
 * @package Batumi_Theme
 * @since 0.5.0
 * @updated 0.9.0-alpha - Added multilingual tag support
 */

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
$post_id = get_the_ID();

// Normalize language code for meta lookup
$lang_code = ($current_lang === 'ge') ? 'ka' : $current_lang;

// Get multilingual title with fallback
$title = get_field("title_{$current_lang}", $post_id);
if (empty($title)) {
    $title = get_field('title_en', $post_id) ?: get_field('title_ge', $post_id) ?: get_field('title_ru', $post_id);
}

// Get price information
$price_model = get_field('price_model', $post_id);
$price_value = get_field('price_value', $post_id);
$currency = get_field('currency', $post_id) ?: 'GEL';

// Get contact info
$phone = get_field('phone', $post_id);
$whatsapp = get_field('whatsapp', $post_id);
// Use phone as fallback for WhatsApp if not set
$whatsapp_number = $whatsapp ? $whatsapp : $phone;

// Check if service is promoted
$is_promoted = get_post_meta($post_id, '_is_promoted', true);
$promotion_priority = get_post_meta($post_id, '_promotion_priority', true);

// Build CSS classes
$card_classes = 'service-card';
if ($is_promoted) {
    $card_classes .= ' service-card-promoted';
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class($card_classes); ?> style="position: relative;" <?php echo $is_promoted && $promotion_priority ? 'data-priority="' . esc_attr($promotion_priority) . '"' : ''; ?>>

    <!-- Favorite Button - Always visible at top-right of card -->
    <button
        class="favorite-btn"
        data-service-id="<?php echo esc_attr($post_id); ?>"
        data-service-title="<?php echo esc_attr($title); ?>"
        aria-label="Add to favorites"
        title="Add to favorites"
        style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10;">
        <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
    </button>

    <?php if (has_post_thumbnail()) : ?>
        <div class="service-card-image-wrapper" style="position: relative;">
            <a href="<?php the_permalink(); ?>" class="service-card-image-link">
                <?php the_post_thumbnail('service-thumbnail', array('class' => 'service-card-image')); ?>
            </a>
            <?php if ($is_promoted) : ?>
                <span class="sponsored-badge">Sponsored</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="service-card-content">

        <h3 class="service-card-title">
            <a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a>
        </h3>

        <div class="service-card-meta">
            <?php
            // Display category
            $directions = get_the_terms($post_id, 'service_category');
            if ($directions && !is_wp_error($directions)) {
                $direction = array_shift($directions);
                echo '<span class="service-category">' . esc_html($direction->name) . '</span>';
            }

            // Display coverage area
            $areas = get_the_terms($post_id, 'coverage_area');
            if ($areas && !is_wp_error($areas)) {
                $area = array_shift($areas);
                echo '<span class="service-area">' . esc_html($area->name) . '</span>';
            }

            // Display service tags (with multilingual support)
            $tags = get_the_terms($post_id, 'service_tag');
            if ($tags && !is_wp_error($tags)) {
                echo '<div class="service-tags">';
                foreach (array_slice($tags, 0, 3) as $tag) {
                    // Get translated tag name
                    $translated_name = get_term_meta($tag->term_id, 'name_' . $lang_code, true);
                    $display_name = !empty($translated_name) ? $translated_name : $tag->name;
                    echo '<a href="' . esc_url(add_query_arg('stag', $tag->slug, home_url('/'))) . '" class="service-tag">' . esc_html($display_name) . '</a>';
                }
                if (count($tags) > 3) {
                    echo '<span class="service-tag">+' . (count($tags) - 3) . '</span>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <?php if ($price_model && $price_value) : ?>
            <div class="service-card-price">
                <?php
                if (function_exists('batumi_format_price')) {
                    echo batumi_format_price($price_model, $price_value, $currency);
                } else {
                    echo esc_html($price_value . ' ' . $currency);
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="service-card-footer">
            <a href="<?php the_permalink(); ?>" class="btn btn-secondary btn-sm">
                <?php
                if ($current_lang === 'ru') {
                    _e('Подробнее', 'batumi-theme');
                } elseif ($current_lang === 'en') {
                    _e('View Details', 'batumi-theme');
                } else {
                    _e('დეტალები', 'batumi-theme');
                }
                ?>
            </a>

            <?php if ($whatsapp_number) :
                $wa_clean = preg_replace('/[^0-9]/', '', $whatsapp_number);
            ?>
                <!-- WhatsApp button (default on mobile) -->
                <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">
                    <svg class="whatsapp-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>

                <!-- Call button (visible on desktop) -->
                <a href="tel:<?php echo esc_attr($phone ?: $whatsapp_number); ?>" class="btn btn-primary btn-sm btn-call-desktop">
                    <span class="btn-icon">📞</span>
                    <?php
                    if ($current_lang === 'ru') {
                        _e('Позвонить', 'batumi-theme');
                    } elseif ($current_lang === 'en') {
                        _e('Call', 'batumi-theme');
                    } else {
                        _e('დარეკვა', 'batumi-theme');
                    }
                    ?>
                </a>
            <?php endif; ?>
        </div>

    </div>

</article>
