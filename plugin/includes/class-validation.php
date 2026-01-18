<?php
/**
 * Validation Engine for Service Listings
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Validation {

    // Batumi geographic bounds
    const BATUMI_LAT_MIN = 41.57;
    const BATUMI_LAT_MAX = 41.70;
    const BATUMI_LNG_MIN = 41.57;
    const BATUMI_LNG_MAX = 41.72;

    /**
     * Initialize validation hooks
     */
    public function __construct() {
        // Hook into post transition for status changes
        add_action('transition_post_status', array($this, 'validate_on_publish'), 10, 3);

        // Add admin notices for validation errors
        add_action('admin_notices', array($this, 'display_validation_errors'));

        // Add validation to REST API
        add_filter('rest_pre_insert_service_listing', array($this, 'validate_rest_request'), 10, 2);
    }

    /**
     * Validate service listing when attempting to publish
     */
    public function validate_on_publish($new_status, $old_status, $post) {
        // Only validate service_listing post type
        if ($post->post_type !== 'service_listing') {
            return;
        }

        // Only validate when transitioning TO publish status
        if ($new_status !== 'publish') {
            return;
        }

        // Skip validation for auto-saves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post->ID)) {
            return;
        }

        // Run validation
        $validation_result = $this->validate_service_listing($post->ID);

        // If validation fails, revert to draft and set error message
        if (!$validation_result['valid']) {
            // Unhook to prevent infinite loop
            remove_action('transition_post_status', array($this, 'validate_on_publish'), 10);

            // Revert to draft status
            wp_update_post(array(
                'ID' => $post->ID,
                'post_status' => 'draft',
            ));

            // Re-hook
            add_action('transition_post_status', array($this, 'validate_on_publish'), 10, 3);

            // Store errors in transient for display
            set_transient('batumizone_validation_errors_' . $post->ID, $validation_result['errors'], 45);
        }
    }

    /**
     * Validate service listing via REST API
     */
    public function validate_rest_request($prepared_post, $request) {
        // Only validate when publishing
        if ($prepared_post->post_status !== 'publish') {
            return $prepared_post;
        }

        // For new posts, validate without ID (use request params)
        // For updates, validate with existing ID
        $post_id = isset($prepared_post->ID) ? $prepared_post->ID : null;

        $validation_result = $this->validate_service_listing($post_id, $request);

        if (!$validation_result['valid']) {
            return new WP_Error(
                'validation_failed',
                'Validation failed. Cannot publish service listing.',
                array(
                    'status' => 400,
                    'errors' => $validation_result['errors'],
                )
            );
        }

        return $prepared_post;
    }

    /**
     * Main validation function
     */
    public function validate_service_listing($post_id, $request = null) {
        $errors = array();

        // Validation 1: One-language rule (at least title+desc in one language)
        if (!$this->validate_one_language($post_id, $request)) {
            $errors[] = array(
                'field' => 'multilingual_content',
                'message' => __('At least one language must have both title and description completed (Georgian, Russian, or English).', 'batumizone'),
            );
        }

        // Validation 2: Phone requirement
        if (!$this->validate_phone($post_id, $request)) {
            $errors[] = array(
                'field' => 'phone',
                'message' => __('Phone number is required for all published listings.', 'batumizone'),
            );
        }

        // Validation 3: Batumi bounds for location
        if (!$this->validate_location_bounds($post_id, $request)) {
            $errors[] = array(
                'field' => 'location',
                'message' => sprintf(
                    __('Location must be within Batumi bounds (Latitude: %s-%s, Longitude: %s-%s).', 'batumizone'),
                    self::BATUMI_LAT_MIN,
                    self::BATUMI_LAT_MAX,
                    self::BATUMI_LNG_MIN,
                    self::BATUMI_LNG_MAX
                ),
            );
        }

        // Validation 4: Required direction/subtype
        if (!$this->validate_required_taxonomy($post_id, 'service_category', $request)) {
            $errors[] = array(
                'field' => 'service_category',
                'message' => __('Service direction is required. Please select at least one category.', 'batumizone'),
            );
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * Validate one-language rule: at least title+desc in one language
     */
    private function validate_one_language($post_id, $request = null) {
        $languages = array('ge', 'ru', 'en');

        foreach ($languages as $lang) {
            $title = $this->get_field_value('title_' . $lang, $post_id, $request);
            $desc = $this->get_field_value('desc_' . $lang, $post_id, $request);

            // If both title and description exist for this language, validation passes
            if (!empty($title) && !empty($desc)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate phone requirement
     */
    private function validate_phone($post_id, $request = null) {
        $phone = $this->get_field_value('phone', $post_id, $request);
        return !empty($phone);
    }

    /**
     * Validate location is within Batumi bounds
     */
    private function validate_location_bounds($post_id, $request = null) {
        $latitude = $this->get_field_value('latitude', $post_id, $request);
        $longitude = $this->get_field_value('longitude', $post_id, $request);

        // If no location provided, it's optional so return true
        if (empty($latitude) || empty($longitude)) {
            return true;
        }

        $lat = floatval($latitude);
        $lng = floatval($longitude);

        // Check if within Batumi bounds
        $lat_valid = ($lat >= self::BATUMI_LAT_MIN && $lat <= self::BATUMI_LAT_MAX);
        $lng_valid = ($lng >= self::BATUMI_LNG_MIN && $lng <= self::BATUMI_LNG_MAX);

        return $lat_valid && $lng_valid;
    }

    /**
     * Validate required taxonomy (service_category)
     */
    private function validate_required_taxonomy($post_id, $taxonomy, $request = null) {
        if ($post_id) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            return !empty($terms) && !is_wp_error($terms);
        }

        // For REST API requests, check if taxonomy is being set
        if ($request && isset($request[$taxonomy])) {
            return !empty($request[$taxonomy]);
        }

        return false;
    }

    /**
     * Get field value from post meta or request
     */
    private function get_field_value($field_name, $post_id, $request = null) {
        // Try to get from request first (for REST API)
        if ($request && isset($request['meta'][$field_name])) {
            return $request['meta'][$field_name];
        }

        // Fall back to post meta
        if ($post_id) {
            return get_post_meta($post_id, $field_name, true);
        }

        return '';
    }

    /**
     * Display validation errors in admin
     */
    public function display_validation_errors() {
        global $post;

        if (!$post || $post->post_type !== 'service_listing') {
            return;
        }

        $errors = get_transient('batumizone_validation_errors_' . $post->ID);

        if (!$errors) {
            return;
        }

        // Delete transient after displaying
        delete_transient('batumizone_validation_errors_' . $post->ID);

        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>' . __('Cannot publish service listing. Please fix the following errors:', 'batumizone') . '</strong></p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error['message']) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Get validation errors for a post (for API responses)
     */
    public function get_validation_errors($post_id) {
        $validation_result = $this->validate_service_listing($post_id);
        return $validation_result['errors'];
    }
}
