<?php
/**
 * Reports System
 *
 * Handles anonymous reporting, moderation queue, and moderator actions
 *
 * @package Batumi_Zone_Core
 * @version 0.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Reports {

    private $table_name;
    private $version = '1.0';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'batumizone_reports';

        // Hooks
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Initialize
     */
    public function init() {
        $this->maybe_create_table();
    }

    /**
     * Create reports table if not exists
     */
    public function maybe_create_table() {
        global $wpdb;

        $installed_version = get_option('batumizone_reports_db_version');

        if ($installed_version === $this->version) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) unsigned NOT NULL,
            reporter_user_id bigint(20) unsigned NULL,
            reporter_fingerprint varchar(64) NULL,
            reporter_ip varchar(45) NULL,
            reason varchar(50) NOT NULL,
            comment text NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            moderator_id bigint(20) unsigned NULL,
            moderator_notes text NULL,
            moderator_action varchar(50) NULL,
            created_at datetime NOT NULL,
            resolved_at datetime NULL,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('batumizone_reports_db_version', $this->version);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Public: Submit report
        register_rest_route('batumizone/v1', '/reports', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_report'),
            'permission_callback' => '__return_true', // Anonymous allowed
            'args' => array(
                'listing_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'reason' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('scam', 'duplicate', 'wrong_category', 'offensive', 'illegal', 'other')
                ),
                'comment' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));

        // Admin: Get reports queue
        register_rest_route('batumizone/v1', '/admin/reports', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reports'),
            'permission_callback' => array($this, 'check_moderator_permission'),
            'args' => array(
                'status' => array(
                    'type' => 'string',
                    'enum' => array('new', 'in_review', 'resolved', 'rejected')
                ),
                'page' => array(
                    'type' => 'integer',
                    'default' => 1
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 20
                )
            )
        ));

        // Admin: Update report (moderator action)
        register_rest_route('batumizone/v1', '/admin/reports/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'moderate_report'),
            'permission_callback' => array($this, 'check_moderator_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'status' => array(
                    'type' => 'string',
                    'enum' => array('in_review', 'resolved', 'rejected')
                ),
                'action' => array(
                    'type' => 'string',
                    'enum' => array('keep', 'unpublish', 'remove', 'ban_user')
                ),
                'notes' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
    }

    /**
     * Submit report (anonymous or logged in)
     */
    public function submit_report($request) {
        global $wpdb;

        $listing_id = $request->get_param('listing_id');
        $reason = $request->get_param('reason');
        $comment = $request->get_param('comment');

        // Verify listing exists
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'service_listing') {
            return new WP_Error('listing_not_found', 'Listing not found', array('status' => 404));
        }

        // Rate limiting check
        $rate_limit = $this->check_rate_limit();
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        // Get user ID if logged in
        $user_id = is_user_logged_in() ? get_current_user_id() : null;

        // Generate fingerprint from IP + User Agent
        $fingerprint = $this->generate_fingerprint();
        $ip = $this->get_client_ip();

        // Insert report
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'listing_id' => $listing_id,
                'reporter_user_id' => $user_id,
                'reporter_fingerprint' => $fingerprint,
                'reporter_ip' => $ip,
                'reason' => $reason,
                'comment' => $comment,
                'status' => 'new',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('report_failed', 'Failed to submit report', array('status' => 500));
        }

        // Log report submission
        $this->log_action('report_submitted', $wpdb->insert_id, array(
            'listing_id' => $listing_id,
            'reason' => $reason
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Report submitted successfully',
            'report_id' => $wpdb->insert_id
        ), 201);
    }

    /**
     * Get reports (moderation queue)
     */
    public function get_reports($request) {
        global $wpdb;

        $status = $request->get_param('status');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}{$where}");

        // Get reports
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.post_title as listing_title, p.post_status as listing_status
             FROM {$this->table_name} r
             LEFT JOIN {$wpdb->posts} p ON r.listing_id = p.ID
             {$where}
             ORDER BY r.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        // Format reports
        $formatted_reports = array_map(array($this, 'format_report'), $reports);

        return new WP_REST_Response(array(
            'reports' => $formatted_reports,
            'total' => (int) $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ));
    }

    /**
     * Moderate report (take action)
     */
    public function moderate_report($request) {
        global $wpdb;

        $report_id = $request->get_param('id');
        $status = $request->get_param('status');
        $action = $request->get_param('action');
        $notes = $request->get_param('notes');
        $moderator_id = get_current_user_id();

        // Get report
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $report_id
        ));

        if (!$report) {
            return new WP_Error('report_not_found', 'Report not found', array('status' => 404));
        }

        // Execute moderator action
        if ($action) {
            $action_result = $this->execute_moderator_action($report, $action);
            if (is_wp_error($action_result)) {
                return $action_result;
            }
        }

        // Update report
        $update_data = array(
            'moderator_id' => $moderator_id,
            'moderator_notes' => $notes,
            'moderator_action' => $action
        );

        if ($status) {
            $update_data['status'] = $status;

            if ($status === 'resolved') {
                $update_data['resolved_at'] = current_time('mysql');
            }
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $report_id),
            array('%d', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update report', array('status' => 500));
        }

        // Log moderator action
        $this->log_action('report_moderated', $report_id, array(
            'action' => $action,
            'status' => $status,
            'listing_id' => $report->listing_id
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Report updated successfully',
            'action_taken' => $action
        ));
    }

    /**
     * Execute moderator action on listing/user
     */
    private function execute_moderator_action($report, $action) {
        switch ($action) {
            case 'keep':
                // No action needed, just resolve report
                break;

            case 'unpublish':
                // Change listing status to draft
                wp_update_post(array(
                    'ID' => $report->listing_id,
                    'post_status' => 'draft'
                ));
                break;

            case 'remove':
                // Soft delete (trash)
                wp_trash_post($report->listing_id);
                break;

            case 'ban_user':
                // Get listing owner
                $post = get_post($report->listing_id);
                if ($post) {
                    // Update user meta to mark as banned
                    update_user_meta($post->post_author, 'account_status', 'banned');
                    update_user_meta($post->post_author, 'banned_at', current_time('mysql'));
                    update_user_meta($post->post_author, 'banned_reason', 'Moderation action from report #' . $report->id);

                    // Unpublish all user's listings
                    $user_posts = get_posts(array(
                        'post_type' => 'service_listing',
                        'author' => $post->post_author,
                        'post_status' => 'publish',
                        'numberposts' => -1
                    ));

                    foreach ($user_posts as $user_post) {
                        wp_update_post(array(
                            'ID' => $user_post->ID,
                            'post_status' => 'draft'
                        ));
                    }
                }
                break;

            default:
                return new WP_Error('invalid_action', 'Invalid moderator action');
        }

        return true;
    }

    /**
     * Format report for API response
     */
    private function format_report($report) {
        return array(
            'id' => (int) $report->id,
            'listing_id' => (int) $report->listing_id,
            'listing_title' => $report->listing_title,
            'listing_status' => $report->listing_status,
            'reporter_user_id' => $report->reporter_user_id ? (int) $report->reporter_user_id : null,
            'reason' => $report->reason,
            'comment' => $report->comment,
            'status' => $report->status,
            'moderator_id' => $report->moderator_id ? (int) $report->moderator_id : null,
            'moderator_notes' => $report->moderator_notes,
            'moderator_action' => $report->moderator_action,
            'created_at' => $report->created_at,
            'resolved_at' => $report->resolved_at
        );
    }

    /**
     * Check rate limit for reporting
     */
    private function check_rate_limit() {
        $fingerprint = $this->generate_fingerprint();
        $limit = 5; // Max 5 reports per hour
        $period = 3600; // 1 hour

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE reporter_fingerprint = %s
             AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            $fingerprint,
            $period
        ));

        if ($count >= $limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                'Too many reports. Please try again later.',
                array('status' => 429)
            );
        }

        return true;
    }

    /**
     * Generate fingerprint from IP + User Agent
     */
    private function generate_fingerprint() {
        $ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return md5($ip . $user_agent);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field(trim($ip));
    }

    /**
     * Check if user has moderator permission
     */
    public function check_moderator_permission() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array('administrator', $user->roles) || in_array('editor', $user->roles);
    }

    /**
     * Log action for audit trail
     */
    private function log_action($action, $report_id, $data = array()) {
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;

        error_log(sprintf(
            '[BATUMI REPORTS] %s | Report ID: %d | User ID: %d | Data: %s',
            $action,
            $report_id,
            $user_id,
            json_encode($data)
        ));

        // TODO: Store in dedicated audit log table in future
    }
}

// Initialize
new Batumi_Reports();
