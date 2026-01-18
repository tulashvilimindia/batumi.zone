<?php
/**
 * Service Listing Custom Post Type
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Service_CPT {

    /**
     * Initialize the CPT
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    /**
     * Register the service_listing custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Service Listings', 'Post type general name', 'batumizone'),
            'singular_name'         => _x('Service Listing', 'Post type singular name', 'batumizone'),
            'menu_name'             => _x('Services', 'Admin Menu text', 'batumizone'),
            'name_admin_bar'        => _x('Service Listing', 'Add New on Toolbar', 'batumizone'),
            'add_new'               => __('Add New', 'batumizone'),
            'add_new_item'          => __('Add New Service Listing', 'batumizone'),
            'new_item'              => __('New Service Listing', 'batumizone'),
            'edit_item'             => __('Edit Service Listing', 'batumizone'),
            'view_item'             => __('View Service Listing', 'batumizone'),
            'all_items'             => __('All Services', 'batumizone'),
            'search_items'          => __('Search Services', 'batumizone'),
            'parent_item_colon'     => __('Parent Services:', 'batumizone'),
            'not_found'             => __('No services found.', 'batumizone'),
            'not_found_in_trash'    => __('No services found in Trash.', 'batumizone'),
            'featured_image'        => _x('Service Image', 'Overrides the "Featured Image" phrase', 'batumizone'),
            'set_featured_image'    => _x('Set service image', 'Overrides the "Set featured image" phrase', 'batumizone'),
            'remove_featured_image' => _x('Remove service image', 'Overrides the "Remove featured image" phrase', 'batumizone'),
            'use_featured_image'    => _x('Use as service image', 'Overrides the "Use as featured image" phrase', 'batumizone'),
            'archives'              => _x('Service archives', 'The post type archive label', 'batumizone'),
            'insert_into_item'      => _x('Insert into service', 'Overrides the "Insert into post" phrase', 'batumizone'),
            'uploaded_to_this_item' => _x('Uploaded to this service', 'Overrides the "Uploaded to this post" phrase', 'batumizone'),
            'filter_items_list'     => _x('Filter services list', 'Screen reader text', 'batumizone'),
            'items_list_navigation' => _x('Services list navigation', 'Screen reader text', 'batumizone'),
            'items_list'            => _x('Services list', 'Screen reader text', 'batumizone'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'services'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-clipboard',
            'show_in_rest'       => true,
            'rest_base'          => 'services',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'supports'           => array('title', 'thumbnail', 'author'),
        );

        register_post_type('service_listing', $args);
    }

    /**
     * Register taxonomies for service listings
     */
    public function register_taxonomies() {
        // Service Category taxonomy (renamed from service_direction)
        $category_labels = array(
            'name'              => _x('Service Categories', 'taxonomy general name', 'batumizone'),
            'singular_name'     => _x('Service Category', 'taxonomy singular name', 'batumizone'),
            'search_items'      => __('Search Categories', 'batumizone'),
            'all_items'         => __('All Categories', 'batumizone'),
            'parent_item'       => __('Parent Category', 'batumizone'),
            'parent_item_colon' => __('Parent Category:', 'batumizone'),
            'edit_item'         => __('Edit Category', 'batumizone'),
            'update_item'       => __('Update Category', 'batumizone'),
            'add_new_item'      => __('Add New Category', 'batumizone'),
            'new_item_name'     => __('New Category Name', 'batumizone'),
            'menu_name'         => __('Categories', 'batumizone'),
        );

        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'category'),
            'show_in_rest'      => true,
            'rest_base'         => 'service_categories',
        );

        register_taxonomy('service_category', array('service_listing'), $category_args);

        // Coverage Area taxonomy
        $area_labels = array(
            'name'              => _x('Coverage Areas', 'taxonomy general name', 'batumizone'),
            'singular_name'     => _x('Coverage Area', 'taxonomy singular name', 'batumizone'),
            'search_items'      => __('Search Areas', 'batumizone'),
            'all_items'         => __('All Areas', 'batumizone'),
            'parent_item'       => __('Parent Area', 'batumizone'),
            'parent_item_colon' => __('Parent Area:', 'batumizone'),
            'edit_item'         => __('Edit Area', 'batumizone'),
            'update_item'       => __('Update Area', 'batumizone'),
            'add_new_item'      => __('Add New Area', 'batumizone'),
            'new_item_name'     => __('New Area Name', 'batumizone'),
            'menu_name'         => __('Coverage Areas', 'batumizone'),
        );

        $area_args = array(
            'hierarchical'      => true,
            'labels'            => $area_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'area'),
            'show_in_rest'      => true,
            'rest_base'         => 'coverage_areas',
        );

        register_taxonomy('coverage_area', array('service_listing'), $area_args);

        // Service Tags taxonomy (non-hierarchical, like WordPress tags)
        $tag_labels = array(
            'name'                       => _x('Service Tags', 'taxonomy general name', 'batumizone'),
            'singular_name'              => _x('Service Tag', 'taxonomy singular name', 'batumizone'),
            'search_items'               => __('Search Tags', 'batumizone'),
            'popular_items'              => __('Popular Tags', 'batumizone'),
            'all_items'                  => __('All Tags', 'batumizone'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Tag', 'batumizone'),
            'update_item'                => __('Update Tag', 'batumizone'),
            'add_new_item'               => __('Add New Tag', 'batumizone'),
            'new_item_name'              => __('New Tag Name', 'batumizone'),
            'separate_items_with_commas' => __('Separate tags with commas', 'batumizone'),
            'add_or_remove_items'        => __('Add or remove tags', 'batumizone'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'batumizone'),
            'not_found'                  => __('No tags found.', 'batumizone'),
            'menu_name'                  => __('Tags', 'batumizone'),
        );

        $tag_args = array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'service-tag'),
            'show_in_rest'          => true,
            'rest_base'             => 'service_tags',
        );

        register_taxonomy('service_tag', array('service_listing'), $tag_args);
    }
}
