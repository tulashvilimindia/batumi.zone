<?php
/**
 * Plugin Name: Batumi.zone Core
 * Plugin URI: https://batumi.zone
 * Description: Core functionality for Batumi.zone Services MVP
 * Version: 0.5.0
 */

if (!defined('ABSPATH')) { exit; }

define('BATUMIZONE_VERSION', '0.5.0');
define('BATUMIZONE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BATUMIZONE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BATUMIZONE_PLUGIN_FILE', __FILE__);

require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-service-cpt.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-multilingual-taxonomy.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-acf-fields.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-validation.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-media-handler.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-auth-api.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-poster-api.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-reports.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-moderation-admin.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-sponsored-listings.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-promotion-admin.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-ad-system.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-ad-admin.php';
require_once BATUMIZONE_PLUGIN_DIR . 'includes/class-webp-converter.php';

class Batumi_Zone_Core {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        register_activation_hook(__FILE__, array($this, 'on_activation'));
        $this->load_components();
    }

    private function load_components() {
        new Batumi_Service_CPT();
        new Batumi_Multilingual_Taxonomy();
        new Batumi_ACF_Fields();
        new Batumi_REST_API();
        new Batumi_Validation();
        new Batumi_Media_Handler();
        new Batumi_Auth_API();
        new Batumi_Poster_API();
        new Batumi_Reports();
        new Batumi_Moderation_Admin();
        new Batumi_Sponsored_Listings();
        new Batumi_Promotion_Admin();
        new Batumi_Ad_System();
        // Enqueue ads JavaScript on frontend
        add_action("wp_enqueue_scripts", function() {
            wp_enqueue_script("batumizone-ads", BATUMIZONE_PLUGIN_URL . "assets/js/ads.js", array(), "1.0.0", true);
        });
        new Batumi_Ad_Admin();
    }

    public function enqueue_frontend() {
        wp_enqueue_script('batumizone-ads-frontend', BATUMIZONE_PLUGIN_URL . 'assets/js/ads-frontend.js', array(), '1.0.0', true);
        wp_enqueue_style('batumizone-ads-frontend', BATUMIZONE_PLUGIN_URL . 'assets/css/ads-frontend.css', array(), '1.2.0');
    }

    public function init() {
        load_plugin_textdomain('batumizone-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function on_activation() {
        $ad_system = new Batumi_Ad_System();
        $ad_system->create_tables();
    }

    public function admin_notice() {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Batumi.zone Core v0.5.0:</strong> Phase 8.2 Ads System Active!</p></div>';
    }
}

function batumizone_core() {
    return Batumi_Zone_Core::get_instance();
}

batumizone_core();
