<?php
/**
 * Ad Admin UI - WordPress Admin Page for Ad Campaign Management
 * Phase 8.2 - Ads Placement System
 *
 * @package BatumiZone_Core
 * @since 0.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Ad_Admin {

    /**
     * Initialize Admin UI
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'Ad Campaigns',
            'Ad Campaigns',
            'manage_options',
            'batumizone-ads',
            array($this, 'render_admin_page'),
            'dashicons-megaphone',
            58
        );
    }

    /**
     * Enqueue admin CSS and JS
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_batumizone-ads') {
            return;
        }

        wp_enqueue_style(
            'batumizone-ad-admin',
            BATUMIZONE_PLUGIN_URL . 'assets/css/ad-admin.css',
            array(),
            '1.0.1'
        );

        wp_enqueue_script(
            'batumizone-ad-admin',
            BATUMIZONE_PLUGIN_URL . 'assets/js/ad-admin.js',
            array('jquery'),
            '1.0.1',
            true
        );

        wp_localize_script('batumizone-ad-admin', 'batumiAdAdmin', array(
            'apiUrl' => '/wp-json/batumizone/v1',
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap batumizone-ad-admin">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-megaphone"></span>
                Ad Campaign Management
            </h1>
            <button id="add-campaign-btn" class="page-title-action">Add New Campaign</button>
            <hr class="wp-header-end">

            <!-- Filter Tabs -->
            <div class="ad-filter-tabs">
                <button class="filter-tab active" data-status="all">All (<span id="count-all">0</span>)</button>
                <button class="filter-tab" data-status="active">Active (<span id="count-active">0</span>)</button>
                <button class="filter-tab" data-status="paused">Paused (<span id="count-paused">0</span>)</button>
                <button class="filter-tab" data-status="expired">Expired (<span id="count-expired">0</span>)</button>
            </div>

            <!-- Campaigns Table -->
            <div class="ad-campaigns-container">
                <table class="wp-list-table widefat fixed striped" id="campaigns-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Placement</th>
                            <th>Schedule</th>
                            <th>Impressions</th>
                            <th>Clicks</th>
                            <th>CTR</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="campaigns-tbody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <div class="loading-spinner"></div>
                                <p>Loading campaigns...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add/Edit Campaign Modal -->
            <div id="campaign-modal" class="ad-modal" style="display: none;">
                <div class="ad-modal-content">
                    <span class="ad-modal-close">&times;</span>
                    <h2 id="modal-title">Add New Campaign</h2>

                    <form id="campaign-form">
                        <input type="hidden" id="campaign-id" value="">

                        <div class="form-row">
                            <label for="campaign-title">Campaign Title *</label>
                            <input type="text" id="campaign-title" required>
                        </div>

                        <div class="form-row">
                            <label for="campaign-image-url">Image URL *</label>
                            <input type="url" id="campaign-image-url" required placeholder="https://example.com/ad-image.jpg">
                            <p class="description">Recommended size: 728x90 for home_top, 300x250 for others</p>
                        </div>

                        <div class="form-row">
                            <label for="campaign-link-url">Link URL *</label>
                            <input type="url" id="campaign-link-url" required placeholder="https://example.com/landing-page">
                        </div>

                        <div class="form-row">
                            <label for="campaign-placement">Placement Type *</label>
                            <select id="campaign-placement" required>
                                <option value="">Select placement...</option>
                                <option value="home_top">Home Page - Top Banner</option>
                                <option value="results_after_n">Search Results - After N Items</option>
                                <option value="detail_below_contact">Service Detail - Below Contact</option>
                                <option value="footer_desktop">Desktop - Footer Banner</option>
                                <option value="sidebar_left">Desktop - Left Sidebar</option>
                                <option value="sidebar_right">Desktop - Right Sidebar</option>
                                <option value="footer_mobile">Mobile - Footer Banner</option>
                                <option value="inline_mobile_1">Mobile - Inline Ad #1 (After 2nd Item)</option>
                                <option value="inline_mobile_2">Mobile - Inline Ad #2 (After 5th Item)</option>
                            </select>
                        </div>

                        <div class="form-row" id="position-row" style="display: none;">
                            <label for="campaign-position">Position Index</label>
                            <input type="number" id="campaign-position" min="1" max="20" value="3">
                            <p class="description">For "After N Items" placement: show after this many services</p>
                        </div>

                        <div class="form-row-group">
                            <div class="form-row">
                                <label for="campaign-start-date">Start Date *</label>
                                <input type="datetime-local" id="campaign-start-date" required>
                            </div>

                            <div class="form-row">
                                <label for="campaign-end-date">End Date *</label>
                                <input type="datetime-local" id="campaign-end-date" required>
                            </div>
                        </div>

                        <div class="form-row" id="status-row" style="display: none;">
                            <label for="campaign-status">Status</label>
                            <select id="campaign-status">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </select>
                        </div>

                        <div class="ad-modal-actions">
                            <button type="button" class="button" id="cancel-campaign-btn">Cancel</button>
                            <button type="submit" class="button button-primary">Save Campaign</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Modal -->
            <div id="stats-modal" class="ad-modal" style="display: none;">
                <div class="ad-modal-content ad-modal-large">
                    <span class="ad-modal-close">&times;</span>
                    <h2 id="stats-modal-title">Campaign Statistics</h2>

                    <div class="stats-summary">
                        <div class="stat-card">
                            <div class="stat-value" id="total-impressions">-</div>
                            <div class="stat-label">Total Impressions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="total-clicks">-</div>
                            <div class="stat-label">Total Clicks</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="total-ctr">-</div>
                            <div class="stat-label">CTR (%)</div>
                        </div>
                    </div>

                    <h3>Daily Performance (Last 30 Days)</h3>
                    <div class="stats-table-container">
                        <table class="wp-list-table widefat" id="stats-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>CTR</th>
                                </tr>
                            </thead>
                            <tbody id="stats-tbody">
                                <tr>
                                    <td colspan="4" style="text-align: center;">Loading stats...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}
