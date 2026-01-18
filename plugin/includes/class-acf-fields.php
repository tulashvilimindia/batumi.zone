<?php
/**
 * ACF Field Groups for Service Listings
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_ACF_Fields {

    /**
     * Initialize ACF fields
     */
    public function __construct() {
        add_action('acf/init', array($this, 'register_field_groups'));
    }

    /**
     * Register all field groups for service listings
     */
    public function register_field_groups() {
        if (function_exists('acf_add_local_field_group')) {
            $this->register_multilingual_fields();
            $this->register_service_details();
            $this->register_location_fields();
            $this->register_contact_fields();
        }
    }

    /**
     * Multilingual content fields (GE/RU/EN)
     */
    private function register_multilingual_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_service_multilingual',
            'title' => 'Multilingual Content',
            'fields' => array(
                // Georgian Tab
                array(
                    'key' => 'field_tab_georgian',
                    'label' => 'ქართული (Georgian)',
                    'name' => '',
                    'type' => 'tab',
                    'placement' => 'top',
                ),
                array(
                    'key' => 'field_title_ge',
                    'label' => 'Title (Georgian)',
                    'name' => 'title_ge',
                    'type' => 'text',
                    'instructions' => 'Service title in Georgian',
                    'maxlength' => 200,
                ),
                array(
                    'key' => 'field_desc_ge',
                    'label' => 'Description (Georgian)',
                    'name' => 'desc_ge',
                    'type' => 'textarea',
                    'instructions' => 'Detailed description in Georgian',
                    'rows' => 8,
                    'maxlength' => 2000,
                ),

                // Russian Tab
                array(
                    'key' => 'field_tab_russian',
                    'label' => 'Русский (Russian)',
                    'name' => '',
                    'type' => 'tab',
                    'placement' => 'top',
                ),
                array(
                    'key' => 'field_title_ru',
                    'label' => 'Title (Russian)',
                    'name' => 'title_ru',
                    'type' => 'text',
                    'instructions' => 'Service title in Russian',
                    'maxlength' => 200,
                ),
                array(
                    'key' => 'field_desc_ru',
                    'label' => 'Description (Russian)',
                    'name' => 'desc_ru',
                    'type' => 'textarea',
                    'instructions' => 'Detailed description in Russian',
                    'rows' => 8,
                    'maxlength' => 2000,
                ),

                // English Tab
                array(
                    'key' => 'field_tab_english',
                    'label' => 'English',
                    'name' => '',
                    'type' => 'tab',
                    'placement' => 'top',
                ),
                array(
                    'key' => 'field_title_en',
                    'label' => 'Title (English)',
                    'name' => 'title_en',
                    'type' => 'text',
                    'instructions' => 'Service title in English',
                    'maxlength' => 200,
                ),
                array(
                    'key' => 'field_desc_en',
                    'label' => 'Description (English)',
                    'name' => 'desc_en',
                    'type' => 'textarea',
                    'instructions' => 'Detailed description in English',
                    'rows' => 8,
                    'maxlength' => 2000,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'service_listing',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ));
    }

    /**
     * Service details (pricing)
     */
    private function register_service_details() {
        acf_add_local_field_group(array(
            'key' => 'group_service_details',
            'title' => 'Service Details',
            'fields' => array(
                array(
                    'key' => 'field_price_model',
                    'label' => 'Price Model',
                    'name' => 'price_model',
                    'type' => 'select',
                    'instructions' => 'How is the service priced?',
                    'choices' => array(
                        'from' => 'From (starting price)',
                        'hourly' => 'Hourly rate',
                        'per_m2' => 'Per m²',
                        'fixed' => 'Fixed price',
                        'negotiable' => 'Negotiable',
                        'free' => 'Free',
                    ),
                    'default_value' => 'fixed',
                    'allow_null' => 0,
                ),
                array(
                    'key' => 'field_price_value',
                    'label' => 'Price Value',
                    'name' => 'price_value',
                    'type' => 'number',
                    'instructions' => 'Numeric price (leave empty if free or negotiable)',
                    'min' => 0,
                    'step' => 0.01,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_price_model',
                                'operator' => '!=',
                                'value' => 'free',
                            ),
                            array(
                                'field' => 'field_price_model',
                                'operator' => '!=',
                                'value' => 'negotiable',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_currency',
                    'label' => 'Currency',
                    'name' => 'currency',
                    'type' => 'select',
                    'instructions' => 'Price currency',
                    'choices' => array(
                        'GEL' => 'GEL (₾)',
                        'USD' => 'USD ($)',
                        'EUR' => 'EUR (€)',
                    ),
                    'default_value' => 'GEL',
                    'allow_null' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_price_model',
                                'operator' => '!=',
                                'value' => 'free',
                            ),
                            array(
                                'field' => 'field_price_model',
                                'operator' => '!=',
                                'value' => 'negotiable',
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'service_listing',
                    ),
                ),
            ),
            'menu_order' => 1,
            'position' => 'normal',
            'style' => 'default',
        ));
    }

    /**
     * Location fields (Batumi bounds)
     */
    private function register_location_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_service_location',
            'title' => 'Location (Batumi)',
            'fields' => array(
                array(
                    'key' => 'field_latitude',
                    'label' => 'Latitude',
                    'name' => 'latitude',
                    'type' => 'number',
                    'instructions' => 'Latitude (Batumi: 41.57 - 41.70)',
                    'required' => 1,
                    'min' => 41.57,
                    'max' => 41.70,
                    'step' => 0.000001,
                ),
                array(
                    'key' => 'field_longitude',
                    'label' => 'Longitude',
                    'name' => 'longitude',
                    'type' => 'number',
                    'instructions' => 'Longitude (Batumi: 41.57 - 41.72)',
                    'required' => 1,
                    'min' => 41.57,
                    'max' => 41.72,
                    'step' => 0.000001,
                ),
                array(
                    'key' => 'field_neighborhood',
                    'label' => 'Neighborhood',
                    'name' => 'neighborhood',
                    'type' => 'text',
                    'instructions' => 'Optional neighborhood or district name',
                    'maxlength' => 100,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'service_listing',
                    ),
                ),
            ),
            'menu_order' => 2,
            'position' => 'side',
            'style' => 'default',
        ));
    }

    /**
     * Contact information (phone mandatory)
     */
    private function register_contact_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_service_contact',
            'title' => 'Contact Information',
            'fields' => array(
                array(
                    'key' => 'field_phone',
                    'label' => 'Phone',
                    'name' => 'phone',
                    'type' => 'text',
                    'instructions' => 'Phone number (required for publishing)',
                    'required' => 1,
                    'maxlength' => 20,
                    'placeholder' => '+995 XXX XXX XXX',
                ),
                array(
                    'key' => 'field_whatsapp',
                    'label' => 'WhatsApp',
                    'name' => 'whatsapp',
                    'type' => 'text',
                    'instructions' => 'WhatsApp number (optional, can be same as phone)',
                    'maxlength' => 20,
                    'placeholder' => '+995 XXX XXX XXX',
                ),
                array(
                    'key' => 'field_email',
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'instructions' => 'Contact email (optional)',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'service_listing',
                    ),
                ),
            ),
            'menu_order' => 3,
            'position' => 'side',
            'style' => 'default',
        ));
    }
}
