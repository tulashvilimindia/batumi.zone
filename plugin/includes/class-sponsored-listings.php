<?php
/**
 * Sponsored Listings Management
 *
 * Handles promotion packages, requests, and active promotions
 *
 * @package Batumi_Zone_Core
 * @since 0.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Sponsored_Listings {

    private $packages_table;
    private $requests_table;
    private $promotions_table;
    private $namespace = 'batumizone/v1';
    private $version = '1.0';

    public function __construct() {
        global $wpdb;
        $this->packages_table = $wpdb->prefix . 'batumizone_promo_packages';
        $this->requests_table = $wpdb->prefix . 'batumizone_promo_requests';
        $this->promotions_table = $wpdb->prefix . 'batumizone_active_promotions';

        add_action('init', array($this, 'maybe_create_tables'));
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('batumizone_check_expired_promotions', array($this, 'check_expired_promotions'));

        // Schedule daily cron to check for expired promotions
        if (!wp_next_scheduled('batumizone_check_expired_promotions')) {
            wp_schedule_event(time(), 'daily', 'batumizone_check_expired_promotions');
        }
    }

    /**
     * Create database tables for sponsored listings
     */
    public function maybe_create_tables() {
        $current_version = get_option('batumizone_sponsored_db_version', '0');

        if (version_compare($current_version, $this->version, '<')) {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            // Promotion Packages table
            $sql_packages = "CREATE TABLE {$this->packages_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                duration_days int(11) NOT NULL,
                price_display varchar(50) NULL,
                priority int(11) NOT NULL DEFAULT 1,
                description text NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY status (status)
            ) $charset_collate;";

            // Promotion Requests table
            $sql_requests = "CREATE TABLE {$this->requests_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                service_id bigint(20) unsigned NOT NULL,
                package_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                requested_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                reviewed_at datetime NULL,
                reviewer_id bigint(20) unsigned NULL,
                admin_notes text NULL,
                PRIMARY KEY (id),
                KEY service_id (service_id),
                KEY user_id (user_id),
                KEY status (status)
            ) $charset_collate;";

            // Active Promotions table
            $sql_promotions = "CREATE TABLE {$this->promotions_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                service_id bigint(20) unsigned NOT NULL,
                package_id bigint(20) unsigned NOT NULL,
                request_id bigint(20) unsigned NULL,
                start_date datetime NOT NULL,
                end_date datetime NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                activated_by bigint(20) unsigned NOT NULL,
                activated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY service_id (service_id),
                KEY status (status),
                KEY end_date (end_date)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_packages);
            dbDelta($sql_requests);
            dbDelta($sql_promotions);

            update_option('batumizone_sponsored_db_version', $this->version);

            // Create default packages
            $this->create_default_packages();
        }
    }

    /**
     * Create default promotion packages
     */
    private function create_default_packages() {
        global $wpdb;

        $packages = array(
            array(
                'name' => 'Basic Promotion',
                'duration_days' => 7,
                'price_display' => '10 GEL',
                'priority' => 1,
                'description' => '7 days featured placement'
            ),
            array(
                'name' => 'Premium Promotion',
                'duration_days' => 30,
                'price_display' => '30 GEL',
                'priority' => 2,
                'description' => '30 days top placement with badge'
            ),
            array(
                'name' => 'VIP Promotion',
                'duration_days' => 90,
                'price_display' => '75 GEL',
                'priority' => 3,
                'description' => '90 days premium positioning'
            )
        );

        foreach ($packages as $package) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->packages_table} WHERE name = %s",
                $package['name']
            ));

            if (!$exists) {
                $wpdb->insert($this->packages_table, $package);
            }
        }
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get available packages (public)
        register_rest_route($this->namespace, '/promotion/packages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_packages'),
            'permission_callback' => '__return_true'
        ));

        // Request promotion (poster)
        register_rest_route($this->namespace, '/my/services/(?P<id>\d+)/request-promotion', array(
            'methods' => 'POST',
            'callback' => array($this, 'request_promotion'),
            'permission_callback' => array($this, 'check_user_logged_in'),
            'args' => array(
                'package_id' => array('required' => true, 'type' => 'integer')
            )
        ));

        // Get my promotion requests (poster)
        register_rest_route($this->namespace, '/my/promotion-requests', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_requests'),
            'permission_callback' => array($this, 'check_user_logged_in')
        ));

        // Admin: Get all requests
        register_rest_route($this->namespace, '/admin/promotion-requests', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_requests'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));

        // Admin: Activate promotion
        register_rest_route($this->namespace, '/admin/promotion-requests/(?P<id>\d+)/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'activate_promotion'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'start_date' => array('required' => false, 'type' => 'string'),
                'admin_notes' => array('required' => false, 'type' => 'string')
            )
        ));

        // Admin: Reject request
        register_rest_route($this->namespace, '/admin/promotion-requests/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_request'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'admin_notes' => array('required' => false, 'type' => 'string')
            )
        ));
    }

    /**
     * Get available promotion packages
     */
    public function get_packages($request) {
        global $wpdb;

        $packages = $wpdb->get_results("
            SELECT id, name, duration_days, price_display, priority, description
            FROM {$this->packages_table}
            WHERE status = 'active'
            ORDER BY priority DESC
        ");

        return rest_ensure_response($packages);
    }

    /**
     * Request promotion for a service
     */
    public function request_promotion($request) {
        global $wpdb;

        $service_id = $request['id'];
        $package_id = $request['package_id'];
        $user_id = get_current_user_id();

        // Verify service ownership
        $post = get_post($service_id);
        if (!$post || $post->post_type !== 'service_listing') {
            return new WP_Error('invalid_service', 'Service not found', array('status' => 404));
        }

        if ($post->post_author != $user_id) {
            return new WP_Error('forbidden', 'You do not own this service', array('status' => 403));
        }

        // Verify service is published
        if ($post->post_status !== 'publish') {
            return new WP_Error('service_not_published', 'Only published services can be promoted', array('status' => 400));
        }

        // Verify package exists
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->packages_table} WHERE id = %d AND status = 'active'",
            $package_id
        ));

        if (!$package) {
            return new WP_Error('invalid_package', 'Package not found', array('status' => 404));
        }

        // Check for existing pending request
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$this->requests_table}
            WHERE service_id = %d AND status = 'pending'
        ", $service_id));

        if ($existing) {
            return new WP_Error('request_exists', 'You already have a pending promotion request for this service', array('status' => 400));
        }

        // Create request
        $result = $wpdb->insert($this->requests_table, array(
            'service_id' => $service_id,
            'package_id' => $package_id,
            'user_id' => $user_id,
            'status' => 'pending'
        ));

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create promotion request', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Promotion request submitted successfully',
            'request_id' => $wpdb->insert_id
        ));
    }

    /**
     * Get current user's promotion requests
     */
    public function get_my_requests($request) {
        global $wpdb;
        $user_id = get_current_user_id();

        $requests = $wpdb->get_results($wpdb->prepare("
            SELECT
                r.*,
                p.name as package_name,
                p.duration_days,
                p.price_display,
                s.post_title as service_title
            FROM {$this->requests_table} r
            LEFT JOIN {$this->packages_table} p ON r.package_id = p.id
            LEFT JOIN {$wpdb->posts} s ON r.service_id = s.ID
            WHERE r.user_id = %d
            ORDER BY r.requested_at DESC
        ", $user_id));

        return rest_ensure_response($requests);
    }

    /**
     * Get all promotion requests (admin)
     */
    public function get_all_requests($request) {
        global $wpdb;

        $status = $request->get_param('status');
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE r.status = %s', $status);
        }

        $requests = $wpdb->get_results("
            SELECT
                r.*,
                p.name as package_name,
                p.duration_days,
                p.price_display,
                s.post_title as service_title,
                u.display_name as user_name,
                u.user_email as user_email
            FROM {$this->requests_table} r
            LEFT JOIN {$this->packages_table} p ON r.package_id = p.id
            LEFT JOIN {$wpdb->posts} s ON r.service_id = s.ID
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            {$where}
            ORDER BY r.requested_at DESC
        ");

        return rest_ensure_response($requests);
    }

    /**
     * Activate a promotion request (admin)
     */
    public function activate_promotion($request) {
        global $wpdb;

        $request_id = $request['id'];
        $start_date = $request->get_param('start_date');
        $admin_notes = $request->get_param('admin_notes');
        $admin_id = get_current_user_id();

        // Get request
        $promo_request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->requests_table} WHERE id = %d",
            $request_id
        ));

        if (!$promo_request) {
            return new WP_Error('not_found', 'Request not found', array('status' => 404));
        }

        if ($promo_request->status !== 'pending') {
            return new WP_Error('invalid_status', 'Request has already been processed', array('status' => 400));
        }

        // Get package details
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->packages_table} WHERE id = %d",
            $promo_request->package_id
        ));

        // Calculate dates
        if (!$start_date) {
            $start_date = current_time('mysql');
        }
        $end_date = date('Y-m-d H:i:s', strtotime($start_date . ' +' . $package->duration_days . ' days'));

        // Create active promotion
        $wpdb->insert($this->promotions_table, array(
            'service_id' => $promo_request->service_id,
            'package_id' => $promo_request->package_id,
            'request_id' => $request_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => 'active',
            'activated_by' => $admin_id
        ));

        // Update request status
        $wpdb->update($this->requests_table, array(
            'status' => 'approved',
            'reviewed_at' => current_time('mysql'),
            'reviewer_id' => $admin_id,
            'admin_notes' => $admin_notes
        ), array('id' => $request_id));

        // Set post meta for sorting
        $this->set_promotion_meta($promo_request->service_id, $package->priority);

        // Log action
        error_log(sprintf(
            '[Batumi Sponsored] Admin %d activated promotion for service %d (request %d)',
            $admin_id, $promo_request->service_id, $request_id
        ));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Promotion activated successfully',
            'promotion_id' => $wpdb->insert_id,
            'end_date' => $end_date
        ));
    }

    /**
     * Reject a promotion request (admin)
     */
    public function reject_request($request) {
        global $wpdb;

        $request_id = $request['id'];
        $admin_notes = $request->get_param('admin_notes');
        $admin_id = get_current_user_id();

        $wpdb->update($this->requests_table, array(
            'status' => 'rejected',
            'reviewed_at' => current_time('mysql'),
            'reviewer_id' => $admin_id,
            'admin_notes' => $admin_notes
        ), array('id' => $request_id));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Request rejected'
        ));
    }

    /**
     * Check for expired promotions (cron job)
     */
    public function check_expired_promotions() {
        global $wpdb;

        // Get expired promotions
        $expired_promotions = $wpdb->get_results("
            SELECT id, service_id FROM {$this->promotions_table}
            WHERE status = 'active'
            AND end_date < NOW()
        ");

        if (!empty($expired_promotions)) {
            foreach ($expired_promotions as $promotion) {
                // Update status in database
                $wpdb->update($this->promotions_table, array(
                    'status' => 'expired'
                ), array('id' => $promotion->id));

                // Remove post meta
                $this->remove_promotion_meta($promotion->service_id);
            }

            error_log(sprintf('[Batumi Sponsored] Expired %d promotions', count($expired_promotions)));
        }
    }

    /**
     * Check if service is currently promoted
     */
    public function is_service_promoted($service_id) {
        global $wpdb;

        $promotion = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->promotions_table}
            WHERE service_id = %d
            AND status = 'active'
            AND start_date <= NOW()
            AND end_date > NOW()
            ORDER BY id DESC
            LIMIT 1
        ", $service_id));

        return $promotion;
    }

    /**
     * Get promotion priority for sorting
     */
    public function get_service_priority($service_id) {
        $promotion = $this->is_service_promoted($service_id);

        if ($promotion) {
            global $wpdb;
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT priority FROM {$this->packages_table} WHERE id = %d",
                $promotion->package_id
            ));
            return $package ? $package->priority : 0;
        }

        return 0;
    }

    /**
     * Permission callbacks
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    public function check_admin_permission() {
        return is_user_logged_in() && current_user_can('edit_posts');
    }

    /**
     * Update post meta for promoted service
     */
    private function set_promotion_meta($service_id, $priority) {
        update_post_meta($service_id, '_is_promoted', 1);
        update_post_meta($service_id, '_promotion_priority', $priority);
        update_post_meta($service_id, '_promotion_updated', current_time('timestamp'));
    }

    /**
     * Remove promotion meta from service
     */
    private function remove_promotion_meta($service_id) {
        delete_post_meta($service_id, '_is_promoted');
        delete_post_meta($service_id, '_promotion_priority');
        update_post_meta($service_id, '_promotion_updated', current_time('timestamp'));
    }
}
