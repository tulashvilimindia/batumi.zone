<?php
/**
 * Moderation Admin Page
 *
 * WordPress admin interface for viewing and processing reports
 *
 * @package Batumi_Zone_Core
 * @version 0.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Moderation_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'Reports Queue',
            'Reports',
            'edit_posts', // Editors and above can moderate
            'batumi-reports',
            array($this, 'render_page'),
            'dashicons-warning',
            25
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_batumi-reports') {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'batumi-moderation-admin',
            BATUMIZONE_PLUGIN_URL . 'includes/admin/moderation-admin.css',
            array(),
            '0.3.0'
        );

        // Admin JS
        wp_enqueue_script(
            'batumi-moderation-admin',
            BATUMIZONE_PLUGIN_URL . 'includes/admin/moderation-admin.js',
            array('jquery'),
            '0.3.0',
            true
        );

        // Pass data to JavaScript
        wp_localize_script('batumi-moderation-admin', 'batumiModeration', array(
            'apiUrl' => rest_url('batumizone/v1/admin/reports'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }

    /**
     * Render admin page
     */
    public function render_page() {
        ?>
        <div class="wrap batumi-moderation-page">
            <h1 class="wp-heading-inline">
                <?php echo esc_html__('Reports Queue', 'batumizone-core'); ?>
                <span id="reports-count" class="count-badge"></span>
            </h1>

            <hr class="wp-header-end">

            <!-- Status Filter Tabs -->
            <div class="status-tabs">
                <button class="status-tab active" data-status="">
                    All <span class="count" id="count-all">0</span>
                </button>
                <button class="status-tab" data-status="new">
                    New <span class="count" id="count-new">0</span>
                </button>
                <button class="status-tab" data-status="in_review">
                    In Review <span class="count" id="count-in_review">0</span>
                </button>
                <button class="status-tab" data-status="resolved">
                    Resolved <span class="count" id="count-resolved">0</span>
                </button>
                <button class="status-tab" data-status="rejected">
                    Rejected <span class="count" id="count-rejected">0</span>
                </button>
            </div>

            <!-- Reports Table -->
            <div class="reports-container">
                <div id="reports-loading" class="loading">
                    <span class="spinner is-active"></span>
                    Loading reports...
                </div>

                <table id="reports-table" class="wp-list-table widefat fixed striped" style="display:none;">
                    <thead>
                        <tr>
                            <th scope="col" class="column-id">ID</th>
                            <th scope="col" class="column-listing">Listing</th>
                            <th scope="col" class="column-reason">Reason</th>
                            <th scope="col" class="column-comment">Comment</th>
                            <th scope="col" class="column-status">Status</th>
                            <th scope="col" class="column-date">Reported</th>
                            <th scope="col" class="column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody">
                        <!-- Reports will be loaded here -->
                    </tbody>
                </table>

                <div id="reports-empty" class="empty-state" style="display:none;">
                    <p><?php echo esc_html__('No reports found.', 'batumizone-core'); ?></p>
                </div>

                <div id="reports-pagination" class="tablenav bottom" style="display:none;">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="reports-total-text"></span>
                        <span class="pagination-links">
                            <button class="button pagination-btn" id="first-page" disabled>&laquo;</button>
                            <button class="button pagination-btn" id="prev-page" disabled>&lsaquo;</button>
                            <span class="paging-input">
                                <input type="number" id="current-page-input" value="1" min="1" class="current-page" size="4">
                                of <span class="total-pages" id="total-pages-text">1</span>
                            </span>
                            <button class="button pagination-btn" id="next-page">&rsaquo;</button>
                            <button class="button pagination-btn" id="last-page">&raquo;</button>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Moderation Modal -->
        <div id="moderation-modal" class="moderation-modal" style="display:none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Moderate Report #<span id="modal-report-id"></span></h2>
                    <button class="modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="report-details">
                        <div class="detail-row">
                            <strong>Listing:</strong>
                            <span id="modal-listing-title"></span>
                            <a id="modal-listing-link" href="#" target="_blank" class="button button-small">View</a>
                        </div>
                        <div class="detail-row">
                            <strong>Reason:</strong>
                            <span id="modal-reason"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Comment:</strong>
                            <div id="modal-comment"></div>
                        </div>
                        <div class="detail-row">
                            <strong>Reported:</strong>
                            <span id="modal-created-at"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Current Status:</strong>
                            <span id="modal-current-status"></span>
                        </div>
                    </div>

                    <form id="moderation-form">
                        <input type="hidden" id="modal-report-id-input" name="report_id">

                        <div class="form-group">
                            <label for="modal-action">Action *</label>
                            <select id="modal-action" name="action" required>
                                <option value="">-- Choose Action --</option>
                                <option value="keep">Keep Published (No Action)</option>
                                <option value="unpublish">Unpublish Listing</option>
                                <option value="remove">Remove Listing (Trash)</option>
                                <option value="ban_user">Ban User & Unpublish All</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal-status">Report Status *</label>
                            <select id="modal-status" name="status" required>
                                <option value="">-- Choose Status --</option>
                                <option value="in_review">Mark as In Review</option>
                                <option value="resolved">Mark as Resolved</option>
                                <option value="rejected">Mark as Rejected</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal-notes">Moderator Notes</label>
                            <textarea id="modal-notes" name="notes" rows="4" placeholder="Add notes about this decision..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="button modal-cancel">Cancel</button>
                            <button type="submit" class="button button-primary">Submit Decision</button>
                        </div>

                        <div id="modal-message" class="modal-message" style="display:none;"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize
new Batumi_Moderation_Admin();
