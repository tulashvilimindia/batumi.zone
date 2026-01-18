<?php
/**
 * Template Part: Tag Cloud (Multilingual)
 *
 * Displays a tag cloud of service tags with weighted sizes.
 * Supports multilingual tag names (GE/RU/EN).
 *
 * @package Batumi_Theme
 * @since 0.5.0
 * @updated 0.9.0-alpha - Added multilingual support
 */

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';

// Normalize language code for meta lookup
$lang_code = ($current_lang === 'ge') ? 'ka' : $current_lang;

// Get tag cloud data
$tags = get_terms(array(
    'taxonomy' => 'service_tag',
    'hide_empty' => true,
    'number' => 30,
    'orderby' => 'count',
    'order' => 'DESC',
));

if (empty($tags) || is_wp_error($tags)) {
    return;
}

// Calculate min/max for sizing
$counts = array_map(function($t) { return $t->count; }, $tags);
$min_count = min($counts);
$max_count = max($counts);
$spread = $max_count - $min_count;
if ($spread == 0) $spread = 1;

// Shuffle for visual variety
shuffle($tags);
?>

<div class="tag-cloud-widget">
    <h3 class="widget-title">
        <?php
        if ($current_lang === 'ru') {
            echo 'Популярные теги';
        } elseif ($current_lang === 'en') {
            echo 'Popular Tags';
        } else {
            echo 'პოპულარული თეგები';
        }
        ?>
    </h3>
    <div class="tag-cloud">
        <?php foreach ($tags as $tag) :
            // Calculate weight (1-5 scale)
            $weight = ceil(1 + (4 * ($tag->count - $min_count) / $spread));
            $link = add_query_arg('stag', $tag->slug, home_url('/'));

            // Get translated name (falls back to default name if no translation)
            $translated_name = get_term_meta($tag->term_id, 'name_' . $lang_code, true);
            $display_name = !empty($translated_name) ? $translated_name : $tag->name;

            // Tooltip text
            $tooltip = $tag->count . ' ' . ($current_lang === 'ru' ? 'услуг' : ($current_lang === 'en' ? 'services' : 'სერვისი'));
        ?>
            <a href="<?php echo esc_url($link); ?>"
               class="tag-cloud-item"
               data-weight="<?php echo esc_attr($weight); ?>"
               title="<?php echo esc_attr($tooltip); ?>">
                <?php echo esc_html($display_name); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
