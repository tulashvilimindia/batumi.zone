<?php
/**
 * REST API Endpoints for Batumi.zone Services
 *
 * @package Batumi_Zone_Core
 * @since 0.5.0
 * @updated 0.9.0-alpha - Added multilingual tag support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_REST_API {

    private $namespace = 'batumizone/v1';

    /**
     * Initialize REST API
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register all REST API routes
     */
    public function register_routes() {
        // GET /services - List all services with filters
        register_rest_route($this->namespace, '/services', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_services'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ),
                'direction' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'area' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'stag' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'price_min' => array(
                    'sanitize_callback' => 'floatval',
                ),
                'price_max' => array(
                    'sanitize_callback' => 'floatval',
                ),
                'query' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'sort' => array(
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // GET /services/{id} - Get single service
        register_rest_route($this->namespace, '/services/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_service'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // GET /taxonomies/service_category - Get all service categories
        register_rest_route($this->namespace, '/taxonomies/service_category', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_service_categorys'),
            'permission_callback' => '__return_true',
            'args' => array(
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // GET /taxonomies/coverage_area - Get all coverage areas
        register_rest_route($this->namespace, '/taxonomies/coverage_area', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_coverage_areas'),
            'permission_callback' => '__return_true',
            'args' => array(
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // GET /taxonomies/service_tag - Get all service tags
        register_rest_route($this->namespace, '/taxonomies/service_tag', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_service_tags'),
            'permission_callback' => '__return_true',
            'args' => array(
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // GET /tags/cloud - Get tag cloud data with counts
        register_rest_route($this->namespace, '/tags/cloud', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tag_cloud'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array(
                    'default' => 30,
                    'sanitize_callback' => 'absint',
                ),
                'orderby' => array(
                    'default' => 'count',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'order' => array(
                    'default' => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'lang' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    /**
     * Get current language for API response
     */
    private function get_api_language($request) {
        $lang = $request->get_param('lang');
        if ($lang && in_array($lang, array('ka', 'ge', 'ru', 'en'))) {
            // Normalize 'ge' to 'ka'
            return ($lang === 'ge') ? 'ka' : $lang;
        }

        // Try Polylang
        if (function_exists('pll_current_language')) {
            $pll_lang = pll_current_language();
            if ($pll_lang === 'ge') return 'ka';
            if (in_array($pll_lang, array('ka', 'ru', 'en'))) return $pll_lang;
        }

        // Default to Georgian
        return 'ka';
    }

    /**
     * GET /services - List services with filtering
     */
    public function get_services($request) {
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100); // Max 100 per page
        $direction = $request->get_param('direction');
        $area = $request->get_param('area');
        $tag = $request->get_param('stag'); // Use 'stag' to avoid WP reserved 'tag'
        $price_min = $request->get_param('price_min');
        $price_max = $request->get_param('price_max');
        $query = $request->get_param('query');
        $sort = $request->get_param('sort');
        $lang = $this->get_api_language($request);

        $args = array(
            'post_type' => 'service_listing',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        );

        // Taxonomy filters
        $tax_query = array('relation' => 'AND');

        if (!empty($direction)) {
            $tax_query[] = array(
                'taxonomy' => 'service_category',
                'field' => 'slug',
                'terms' => $direction,
            );
        }

        if (!empty($area)) {
            $tax_query[] = array(
                'taxonomy' => 'coverage_area',
                'field' => 'slug',
                'terms' => $area,
            );
        }

        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'service_tag',
                'field' => 'slug',
                'terms' => $tag,
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        // Search query (across all language fields)
        if (!empty($query)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array('key' => 'title_ge', 'value' => $query, 'compare' => 'LIKE'),
                array('key' => 'title_ru', 'value' => $query, 'compare' => 'LIKE'),
                array('key' => 'title_en', 'value' => $query, 'compare' => 'LIKE'),
                array('key' => 'desc_ge', 'value' => $query, 'compare' => 'LIKE'),
                array('key' => 'desc_ru', 'value' => $query, 'compare' => 'LIKE'),
                array('key' => 'desc_en', 'value' => $query, 'compare' => 'LIKE'),
            );
        }

        // Price filter
        if (!empty($price_min) || !empty($price_max)) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }

            $price_query = array('key' => 'price_value', 'type' => 'NUMERIC');

            if (!empty($price_min) && !empty($price_max)) {
                $price_query['value'] = array($price_min, $price_max);
                $price_query['compare'] = 'BETWEEN';
            } elseif (!empty($price_min)) {
                $price_query['value'] = $price_min;
                $price_query['compare'] = '>=';
            } else {
                $price_query['value'] = $price_max;
                $price_query['compare'] = '<=';
            }

            $args['meta_query'][] = $price_query;
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                $args['meta_key'] = 'price_value';
                break;
            case 'price_desc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                $args['meta_key'] = 'price_value';
                break;
            case 'date':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        $query_result = new WP_Query($args);

        $services = array();
        if ($query_result->have_posts()) {
            while ($query_result->have_posts()) {
                $query_result->the_post();
                $services[] = $this->format_service(get_post(), $lang);
            }
            wp_reset_postdata();
        }

        // Manual sorting: Promoted services first
        usort($services, function($a, $b) {
            $priority_a = isset($a['promotion_priority']) ? (int)$a['promotion_priority'] : 0;
            $priority_b = isset($b['promotion_priority']) ? (int)$b['promotion_priority'] : 0;

            if ($priority_a !== $priority_b) {
                return $priority_b - $priority_a;
            }
            return 0;
        });

        return new WP_REST_Response(array(
            'services' => $services,
            'pagination' => array(
                'total' => $query_result->found_posts,
                'total_pages' => $query_result->max_num_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ),
            'language' => $lang,
        ), 200);
    }

    /**
     * GET /services/{id} - Get single service
     */
    public function get_service($request) {
        $id = $request->get_param('id');
        $lang = $this->get_api_language($request);
        $post = get_post($id);

        if (!$post || $post->post_type !== 'service_listing' || $post->post_status !== 'publish') {
            return new WP_Error('not_found', 'Service not found', array('status' => 404));
        }

        return new WP_REST_Response($this->format_service($post, $lang), 200);
    }

    /**
     * GET /taxonomies/service_category
     */
    public function get_service_categorys($request) {
        $lang = $this->get_api_language($request);

        $terms = get_terms(array(
            'taxonomy' => 'service_category',
            'hide_empty' => false,
        ));

        if (is_wp_error($terms)) {
            return new WP_Error('taxonomy_error', 'Failed to retrieve service categories', array('status' => 500));
        }

        $directions = array();
        foreach ($terms as $term) {
            $translated_name = $this->get_translated_term_name($term->term_id, $lang, $term->name);
            $directions[] = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $translated_name,
                'name_original' => $term->name,
                'translations' => $this->get_term_translations($term->term_id),
                'description' => $term->description,
                'count' => $term->count,
                'parent' => $term->parent,
            );
        }

        return new WP_REST_Response($directions, 200);
    }

    /**
     * GET /taxonomies/coverage_area
     */
    public function get_coverage_areas($request) {
        $lang = $this->get_api_language($request);

        $terms = get_terms(array(
            'taxonomy' => 'coverage_area',
            'hide_empty' => false,
        ));

        if (is_wp_error($terms)) {
            return new WP_Error('taxonomy_error', 'Failed to retrieve coverage areas', array('status' => 500));
        }

        $areas = array();
        foreach ($terms as $term) {
            $translated_name = $this->get_translated_term_name($term->term_id, $lang, $term->name);
            $areas[] = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $translated_name,
                'name_original' => $term->name,
                'translations' => $this->get_term_translations($term->term_id),
                'description' => $term->description,
                'count' => $term->count,
                'parent' => $term->parent,
            );
        }

        return new WP_REST_Response($areas, 200);
    }

    /**
     * GET /taxonomies/service_tag - Get all service tags (multilingual)
     */
    public function get_service_tags($request) {
        $lang = $this->get_api_language($request);

        $terms = get_terms(array(
            'taxonomy' => 'service_tag',
            'hide_empty' => false,
        ));

        if (is_wp_error($terms)) {
            return new WP_Error('taxonomy_error', 'Failed to retrieve service tags', array('status' => 500));
        }

        $tags = array();
        foreach ($terms as $term) {
            $translated_name = $this->get_translated_term_name($term->term_id, $lang, $term->name);
            $tags[] = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $translated_name,
                'name_original' => $term->name,
                'translations' => $this->get_term_translations($term->term_id),
                'description' => $term->description,
                'count' => $term->count,
            );
        }

        return new WP_REST_Response($tags, 200);
    }

    /**
     * GET /tags/cloud - Get tag cloud data with weighted sizes (multilingual)
     */
    public function get_tag_cloud($request) {
        $limit = $request->get_param('limit');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $lang = $this->get_api_language($request);

        $terms = get_terms(array(
            'taxonomy' => 'service_tag',
            'hide_empty' => true,
            'number' => $limit,
            'orderby' => $orderby,
            'order' => $order,
        ));

        if (is_wp_error($terms)) {
            return new WP_Error('taxonomy_error', 'Failed to retrieve tag cloud', array('status' => 500));
        }

        // Calculate min/max for sizing
        $counts = array_map(function($t) { return $t->count; }, $terms);
        $min_count = !empty($counts) ? min($counts) : 0;
        $max_count = !empty($counts) ? max($counts) : 1;
        $spread = $max_count - $min_count;
        if ($spread == 0) $spread = 1;

        $tags = array();
        foreach ($terms as $term) {
            // Calculate size weight (1-5 scale)
            $weight = 1 + (4 * ($term->count - $min_count) / $spread);
            $translated_name = $this->get_translated_term_name($term->term_id, $lang, $term->name);

            $tags[] = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $translated_name,
                'name_original' => $term->name,
                'translations' => $this->get_term_translations($term->term_id),
                'count' => $term->count,
                'weight' => round($weight, 2),
                'link' => home_url('/?stag=' . $term->slug),
            );
        }

        return new WP_REST_Response(array(
            'tags' => $tags,
            'total' => count($tags),
            'min_count' => $min_count,
            'max_count' => $max_count,
            'language' => $lang,
        ), 200);
    }

    /**
     * Get translated term name for a specific language
     */
    private function get_translated_term_name($term_id, $lang, $fallback = '') {
        $translated_name = get_term_meta($term_id, 'name_' . $lang, true);
        return !empty($translated_name) ? $translated_name : $fallback;
    }

    /**
     * Get all translations for a term
     */
    private function get_term_translations($term_id) {
        $term = get_term($term_id);
        $default_name = $term ? $term->name : '';

        return array(
            'ka' => get_term_meta($term_id, 'name_ka', true) ?: $default_name,
            'ru' => get_term_meta($term_id, 'name_ru', true) ?: $default_name,
            'en' => get_term_meta($term_id, 'name_en', true) ?: $default_name,
        );
    }

    /**
     * Format service post for API response (with multilingual tag support)
     */
    private function format_service($post, $lang = 'ka') {
        $post_id = $post->ID;

        // Get taxonomies
        $directions = wp_get_post_terms($post_id, 'service_category', array('fields' => 'names'));
        $areas = wp_get_post_terms($post_id, 'coverage_area', array('fields' => 'names'));
        $tags = wp_get_post_terms($post_id, 'service_tag', array('fields' => 'all'));

        // Format tags with multilingual support
        $formatted_tags = array();
        if (!is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $translated_name = $this->get_translated_term_name($tag->term_id, $lang, $tag->name);
                $formatted_tags[] = array(
                    'id' => $tag->term_id,
                    'slug' => $tag->slug,
                    'name' => $translated_name,
                    'name_original' => $tag->name,
                    'translations' => $this->get_term_translations($tag->term_id),
                );
            }
        }

        // Get featured image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $thumbnail = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : null;

        return array(
            'id' => $post_id,
            'status' => $post->post_status,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author_id' => $post->post_author,

            // Multilingual content
            'title' => array(
                'ge' => get_field('title_ge', $post_id) ?: '',
                'ru' => get_field('title_ru', $post_id) ?: '',
                'en' => get_field('title_en', $post_id) ?: '',
            ),
            'description' => array(
                'ge' => get_field('desc_ge', $post_id) ?: '',
                'ru' => get_field('desc_ru', $post_id) ?: '',
                'en' => get_field('desc_en', $post_id) ?: '',
            ),

            // Taxonomies
            'direction' => $directions,
            'coverage_area' => $areas,
            'tags' => $formatted_tags,

            // Service details
            'price' => array(
                'model' => get_field('price_model', $post_id),
                'value' => get_field('price_value', $post_id),
                'currency' => get_field('currency', $post_id),
            ),

            // Location
            'location' => array(
                'latitude' => get_field('latitude', $post_id),
                'longitude' => get_field('longitude', $post_id),
                'neighborhood' => get_field('neighborhood', $post_id),
            ),

            // Contact
            'contact' => array(
                'phone' => get_field('phone', $post_id),
                'whatsapp' => get_field('whatsapp', $post_id),
                'email' => get_field('email', $post_id),
            ),

            // Media
            'featured_image' => $thumbnail,

            // Sponsored status
            'is_promoted' => (bool) get_post_meta($post_id, '_is_promoted', true),
            'promotion_priority' => (int) get_post_meta($post_id, '_promotion_priority', true),

            // Language info
            'language' => $lang,
        );
    }
}
