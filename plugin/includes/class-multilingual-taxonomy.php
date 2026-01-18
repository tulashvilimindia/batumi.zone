<?php
/**
 * Multilingual Taxonomy Support
 * Adds multilingual support for taxonomy terms (GE/RU/EN)
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Multilingual_Taxonomy {

    private $supported_languages = array('ka', 'ru', 'en');
    private $default_language = 'en';

    public function __construct() {
        // Add custom fields to taxonomy edit screens
        add_action('service_category_edit_form_fields', array($this, 'add_translation_fields'), 10, 2);
        add_action('coverage_area_edit_form_fields', array($this, 'add_translation_fields'), 10, 2);

        // Save custom fields
        add_action('edited_service_category', array($this, 'save_translation_fields'), 10, 2);
        add_action('edited_coverage_area', array($this, 'save_translation_fields'), 10, 2);

        // Filter term name display on frontend
        add_filter('get_term', array($this, 'translate_term_name'), 10, 2);
    }

    /**
     * Add translation fields to taxonomy edit screens
     */
    public function add_translation_fields($term, $taxonomy) {
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
                <input type="text" name="name_ka" id="name_ka" value="<?php echo esc_attr(get_term_meta($term->term_id, 'name_ka', true)); ?>" class="regular-text">
                <p class="description"><?php _e('Georgian translation (ქართული)', 'batumizone'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="name_ru"><?php _e('Name (Russian)', 'batumizone'); ?></label>
            </th>
            <td>
                <input type="text" name="name_ru" id="name_ru" value="<?php echo esc_attr(get_term_meta($term->term_id, 'name_ru', true)); ?>" class="regular-text">
                <p class="description"><?php _e('Russian translation (Русский)', 'batumizone'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="name_en"><?php _e('Name (English)', 'batumizone'); ?></label>
            </th>
            <td>
                <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr(get_term_meta($term->term_id, 'name_en', true)); ?>" class="regular-text">
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
     * Save translation fields
     */
    public function save_translation_fields($term_id, $tt_id) {
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
        if (!in_array($taxonomy, array('service_category', 'coverage_area'))) {
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
     * Get current language from URL or default
     */
    private function get_current_language() {
        $request_uri = $_SERVER['REQUEST_URI'];

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
}
