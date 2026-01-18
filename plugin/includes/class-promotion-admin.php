<?php
/**
 * Admin Page for Managing Promotion Requests
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Promotion_Admin {

    private $requests_table;
    private $packages_table;
    private $promotions_table;

    /**
     * Initialize admin page
     */
    public function __construct() {
        global $wpdb;
        $this->requests_table = $wpdb->prefix . 'batumizone_promo_requests';
        $this->packages_table = $wpdb->prefix . 'batumizone_promo_packages';
        $this->promotions_table = $wpdb->prefix . 'batumizone_active_promotions';

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Handle AJAX actions
        add_action('wp_ajax_batumizone_activate_promotion', array($this, 'ajax_activate_promotion'));
        add_action('wp_ajax_batumizone_reject_promotion', array($this, 'ajax_reject_promotion'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'Promotion Requests',                    // Page title
            'Promotions',                            // Menu title
            'edit_posts',                            // Capability (Editor+)
            'batumizone-promotions',                 // Menu slug
            array($this, 'render_admin_page'),       // Callback
            'dashicons-megaphone',                   // Icon
            30                                       // Position
        );
    }

    /**
     * Enqueue admin CSS and JavaScript
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_batumizone-promotions') {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'batumizone-promotion-admin',
            plugins_url('assets/css/promotion-admin.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'batumizone-promotion-admin',
            plugins_url('assets/js/promotion-admin.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script('batumizone-promotion-admin', 'batumiPromoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('batumizone_promo_action'),
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get current filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';

        // Get requests data
        $requests = $this->get_promotion_requests($status_filter);

        // Get counts for status tabs
        $counts = $this->get_status_counts();

        ?>
        <div class="wrap batumizone-promo-admin">
            <h1 class="wp-heading-inline">Promotion Requests</h1>
            <hr class="wp-header-end">

            <!-- Status Filter Tabs -->
            <ul class="subsubsub">
                <li>
                    <a href="?page=batumizone-promotions&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        Pending <span class="count">(<?php echo $counts['pending']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?page=batumizone-promotions&status=approved" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        Approved <span class="count">(<?php echo $counts['approved']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?page=batumizone-promotions&status=rejected" class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        Rejected <span class="count">(<?php echo $counts['rejected']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?page=batumizone-promotions&status=all" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">
                        All <span class="count">(<?php echo $counts['all']; ?>)</span>
                    </a>
                </li>
            </ul>

            <!-- Requests Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Service</th>
                        <th>Poster</th>
                        <th>Package</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Priority</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)) : ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 20px;">
                                <em>No promotion requests found.</em>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($requests as $request) : ?>
                            <tr data-request-id="<?php echo esc_attr($request->id); ?>" class="promo-request-row">
                                <td><strong>#<?php echo esc_html($request->id); ?></strong></td>

                                <td>
                                    <a href="<?php echo get_edit_post_link($request->service_id); ?>" target="_blank">
                                        <?php echo esc_html($this->get_service_title($request->service_id)); ?>
                                    </a>
                                    <br>
                                    <small>ID: <?php echo esc_html($request->service_id); ?></small>
                                </td>

                                <td>
                                    <?php
                                    $user = get_userdata($request->user_id);
                                    if ($user) {
                                        echo esc_html($user->display_name);
                                        echo '<br><small>' . esc_html($user->user_email) . '</small>';
                                    } else {
                                        echo 'Unknown';
                                    }
                                    ?>
                                </td>

                                <td><strong><?php echo esc_html($request->package_name); ?></strong></td>
                                <td><?php echo esc_html($request->duration_days); ?> days</td>
                                <td><?php echo esc_html($request->price_display); ?></td>
                                <td>
                                    <span class="priority-badge priority-<?php echo esc_attr($request->priority); ?>">
                                        <?php echo esc_html($request->priority); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($request->requested_at))); ?></td>

                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                                        <?php echo esc_html(ucfirst($request->status)); ?>
                                    </span>
                                    <?php if ($request->reviewed_at) : ?>
                                        <br><small><?php echo esc_html(date('Y-m-d', strtotime($request->reviewed_at))); ?></small>
                                    <?php endif; ?>
                                </td>

                                <td class="actions-column">
                                    <?php if ($request->status === 'pending') : ?>
                                        <button class="button button-primary btn-activate" data-request-id="<?php echo esc_attr($request->id); ?>">
                                            Activate
                                        </button>
                                        <button class="button btn-reject" data-request-id="<?php echo esc_attr($request->id); ?>">
                                            Reject
                                        </button>
                                    <?php else : ?>
                                        <span class="status-text"><?php echo ucfirst($request->status); ?></span>
                                        <?php if ($request->admin_notes) : ?>
                                            <br><small title="<?php echo esc_attr($request->admin_notes); ?>">
                                                📝 Has notes
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Action Modal -->
        <div id="promo-action-modal" class="promo-modal" style="display: none;">
            <div class="promo-modal-content">
                <span class="promo-modal-close">&times;</span>
                <h2 id="modal-title">Confirm Action</h2>
                <p id="modal-message"></p>

                <div class="modal-form">
                    <label for="admin-notes">Admin Notes (optional):</label>
                    <textarea id="admin-notes" rows="4" placeholder="Enter notes about this decision..."></textarea>
                </div>

                <div class="modal-actions">
                    <button id="modal-confirm" class="button button-primary">Confirm</button>
                    <button id="modal-cancel" class="button">Cancel</button>
                </div>

                <div class="modal-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p>Processing...</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get promotion requests based on status filter
     */
    private function get_promotion_requests($status) {
        global $wpdb;

        $where_clause = '';
        if ($status !== 'all') {
            $where_clause = $wpdb->prepare("WHERE r.status = %s", $status);
        }

        $query = "
            SELECT
                r.*,
                p.name as package_name,
                p.duration_days,
                p.price_display,
                p.priority
            FROM {$this->requests_table} r
            LEFT JOIN {$this->packages_table} p ON r.package_id = p.id
            {$where_clause}
            ORDER BY r.requested_at DESC
        ";

        return $wpdb->get_results($query);
    }

    /**
     * Get status counts for tabs
     */
    private function get_status_counts() {
        global $wpdb;

        $counts = array(
            'all' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        );

        $results = $wpdb->get_results("
            SELECT status, COUNT(*) as count
            FROM {$this->requests_table}
            GROUP BY status
        ");

        foreach ($results as $row) {
            $counts[$row->status] = (int) $row->count;
            $counts['all'] += (int) $row->count;
        }

        return $counts;
    }

    /**
     * Get service title (multilingual)
     */
    private function get_service_title($service_id) {
        // Try to get title in any language
        $title_ge = get_field('title_ge', $service_id);
        $title_en = get_field('title_en', $service_id);
        $title_ru = get_field('title_ru', $service_id);

        return $title_ge ?: $title_en ?: $title_ru ?: 'Untitled Service';
    }

    /**
     * AJAX: Activate promotion
     */
    public function ajax_activate_promotion() {
        check_ajax_referer('batumizone_promo_action', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        if (!$request_id) {
            wp_send_json_error(array('message' => 'Invalid request ID'));
        }

        // Make REST API call to activate
        $api_url = rest_url('batumizone/v1/admin/promotion-requests/' . $request_id . '/activate');

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'admin_notes' => $admin_notes,
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200 && isset($body['success']) && $body['success']) {
            wp_send_json_success(array(
                'message' => 'Promotion activated successfully',
                'data' => $body,
            ));
        } else {
            wp_send_json_error(array(
                'message' => isset($body['message']) ? $body['message'] : 'Activation failed',
            ));
        }
    }

    /**
     * AJAX: Reject promotion
     */
    public function ajax_reject_promotion() {
        check_ajax_referer('batumizone_promo_action', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        if (!$request_id) {
            wp_send_json_error(array('message' => 'Invalid request ID'));
        }

        // Make REST API call to reject
        $api_url = rest_url('batumizone/v1/admin/promotion-requests/' . $request_id . '/reject');

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'admin_notes' => $admin_notes,
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200 && isset($body['success']) && $body['success']) {
            wp_send_json_success(array(
                'message' => 'Promotion request rejected',
            ));
        } else {
            wp_send_json_error(array(
                'message' => isset($body['message']) ? $body['message'] : 'Rejection failed',
            ));
        }
    }
}
