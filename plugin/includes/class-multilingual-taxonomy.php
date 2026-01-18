<?php
/**
 * Multilingual Taxonomy Support
 * Adds multilingual support for taxonomy terms (GE/RU/EN)
 *
 * @package Batumi_Zone_Core
 * @since 0.5.0
 * @updated 0.9.0-alpha - Added service_tag support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Multilingual_Taxonomy {

    private $supported_languages = array('ka', 'ru', 'en');
    private $default_language = 'en';

    /**
     * Taxonomies that support multilingual translations
     */
    private $translatable_taxonomies = array('service_category', 'coverage_area', 'service_tag');

    public function __construct() {
        // Add custom fields to taxonomy edit screens
        add_action('service_category_edit_form_fields', array($this, 'add_translation_fields'), 10, 2);
        add_action('coverage_area_edit_form_fields', array($this, 'add_translation_fields'), 10, 2);
        add_action('service_tag_edit_form_fields', array($this, 'add_translation_fields'), 10, 2);

        // Add fields to "Add New" forms as well
        add_action('service_tag_add_form_fields', array($this, 'add_translation_fields_new'), 10);

        // Save custom fields
        add_action('edited_service_category', array($this, 'save_translation_fields'), 10, 2);
        add_action('edited_coverage_area', array($this, 'save_translation_fields'), 10, 2);
        add_action('edited_service_tag', array($this, 'save_translation_fields'), 10, 2);
        add_action('created_service_tag', array($this, 'save_translation_fields'), 10, 2);

        // Filter term name display on frontend
        add_filter('get_term', array($this, 'translate_term_name'), 10, 2);

        // Add columns to admin list
        add_filter('manage_edit-service_tag_columns', array($this, 'add_translation_columns'));
        add_filter('manage_service_tag_custom_column', array($this, 'render_translation_column'), 10, 3);
    }

    /**
     * Add translation fields to taxonomy edit screens (edit form)
     */
    public function add_translation_fields($term, $taxonomy = null) {
        // Handle both edit form (object) and add form (string taxonomy)
        $term_id = is_object($term) ? $term->term_id : 0;
        ?>
        <tr class="form-field">
            <th scope="row" colspan="2">
                <h2><?php _e('Translations', 'batumizone'); ?></h2>
            </th>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="name_ka"><?php _e('Name (Georgian)', 'batumizone'); ?></label>
            </th>
            <td>
                <input type="text" name="name_ka" id="name_ka" value="<?php echo esc_attr($term_id ? get_term_meta($term_id, 'name_ka', true) : ''); ?>" class="regular-text">
                <p class="description"><?php _e('Georgian translation (ქართული)', 'batumizone'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="name_ru"><?php _e('Name (Russian)', 'batumizone'); ?></label>
            </th>
            <td>
                <input type="text" name="name_ru" id="name_ru" value="<?php echo esc_attr($term_id ? get_term_meta($term_id, 'name_ru', true) : ''); ?>" class="regular-text">
                <p class="description"><?php _e('Russian translation (Русский)', 'batumizone'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="name_en"><?php _e('Name (English)', 'batumizone'); ?></label>
            </th>
            <td>
                <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($term_id ? get_term_meta($term_id, 'name_en', true) : ''); ?>" class="regular-text">
                <p class="description"><?php _e('English translation', 'batumizone'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row" colspan="2">
                <p class="description">
                    <strong><?php _e('Note:', 'batumizone'); ?></strong>
                    <?php _e('If translation is not provided, the default name will be used. The "Name" field above is used as fallback for all languages.', 'batumizone'); ?>
                </p>
            </th>
        </tr>
        <?php
    }

    /**
     * Add translation fields to "Add New Tag" form
     */
    public function add_translation_fields_new($taxonomy) {
        ?>
        <div class="form-field">
            <h3><?php _e('Translations (Optional)', 'batumizone'); ?></h3>
        </div>

        <div class="form-field">
            <label for="name_ka"><?php _e('Name (Georgian)', 'batumizone'); ?></label>
            <input type="text" name="name_ka" id="name_ka" value="">
            <p><?php _e('Georgian translation (ქართული)', 'batumizone'); ?></p>
        </div>

        <div class="form-field">
            <label for="name_ru"><?php _e('Name (Russian)', 'batumizone'); ?></label>
            <input type="text" name="name_ru" id="name_ru" value="">
            <p><?php _e('Russian translation (Русский)', 'batumizone'); ?></p>
        </div>

        <div class="form-field">
            <label for="name_en"><?php _e('Name (English)', 'batumizone'); ?></label>
            <input type="text" name="name_en" id="name_en" value="">
            <p><?php _e('English translation', 'batumizone'); ?></p>
        </div>
        <?php
    }

    /**
     * Save translation fields
     */
    public function save_translation_fields($term_id, $tt_id = null) {
        if (isset($_POST['name_ka'])) {
            update_term_meta($term_id, 'name_ka', sanitize_text_field($_POST['name_ka']));
        }
        if (isset($_POST['name_ru'])) {
            update_term_meta($term_id, 'name_ru', sanitize_text_field($_POST['name_ru']));
        }
        if (isset($_POST['name_en'])) {
            update_term_meta($term_id, 'name_en', sanitize_text_field($_POST['name_en']));
        }
    }

    /**
     * Translate term name based on current language
     */
    public function translate_term_name($term, $taxonomy) {
        if (!in_array($taxonomy, $this->translatable_taxonomies)) {
            return $term;
        }

        $current_lang = $this->get_current_language();
        $translated_name = get_term_meta($term->term_id, 'name_' . $current_lang, true);

        if (!empty($translated_name)) {
            $term->name = $translated_name;
        }

        return $term;
    }

    /**
     * Add translation columns to admin list
     */
    public function add_translation_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['name_ka'] = __('Georgian', 'batumizone');
                $new_columns['name_ru'] = __('Russian', 'batumizone');
                $new_columns['name_en'] = __('English', 'batumizone');
            }
        }
        return $new_columns;
    }

    /**
     * Render translation column content
     */
    public function render_translation_column($content, $column_name, $term_id) {
        if (in_array($column_name, array('name_ka', 'name_ru', 'name_en'))) {
            $translation = get_term_meta($term_id, $column_name, true);
            return $translation ? esc_html($translation) : '—';
        }
        return $content;
    }

    /**
     * Get current language from Polylang or URL
     */
    private function get_current_language() {
        // Try Polylang first
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language();
            // Map Polylang codes to our codes
            if ($lang === 'ge' || $lang === 'ka') return 'ka';
            if ($lang === 'ru') return 'ru';
            if ($lang === 'en') return 'en';
        }

        // Fallback to URL detection
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        if (strpos($request_uri, '/ru/') === 0 || strpos($request_uri, '/ru') === 0) {
            return 'ru';
        } elseif (strpos($request_uri, '/en/') === 0 || strpos($request_uri, '/en') === 0) {
            return 'en';
        }

        // Default to Georgian
        return 'ka';
    }

    /**
     * Get translated term name for specific language
     *
     * @param int $term_id Term ID
     * @param string|null $lang Language code (ka, ru, en) or null for current language
     * @return string Translated term name or original name
     */
    public static function get_term_name($term_id, $lang = null) {
        if ($lang === null) {
            $instance = new self();
            $lang = $instance->get_current_language();
        }

        $translated_name = get_term_meta($term_id, 'name_' . $lang, true);

        if (!empty($translated_name)) {
            return $translated_name;
        }

        // Fallback to default term name
        $term = get_term($term_id);
        return $term ? $term->name : '';
    }

    /**
     * Get all translations for a term
     *
     * @param int $term_id Term ID
     * @return array Associative array of translations
     */
    public static function get_all_translations($term_id) {
        $term = get_term($term_id);
        $default_name = $term ? $term->name : '';

        return array(
            'default' => $default_name,
            'ka' => get_term_meta($term_id, 'name_ka', true) ?: $default_name,
            'ru' => get_term_meta($term_id, 'name_ru', true) ?: $default_name,
            'en' => get_term_meta($term_id, 'name_en', true) ?: $default_name,
        );
    }

    /**
     * Set translations for a term (used by API)
     *
     * @param int $term_id Term ID
     * @param array $translations Associative array of translations
     */
    public static function set_translations($term_id, $translations) {
        if (isset($translations['ka'])) {
            update_term_meta($term_id, 'name_ka', sanitize_text_field($translations['ka']));
        }
        if (isset($translations['ru'])) {
            update_term_meta($term_id, 'name_ru', sanitize_text_field($translations['ru']));
        }
        if (isset($translations['en'])) {
            update_term_meta($term_id, 'name_en', sanitize_text_field($translations['en']));
        }
    }
}
