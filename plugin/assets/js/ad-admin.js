/**
 * Ad Admin JavaScript
 * Phase 8.2 - Admin UI for Ad Campaign Management
 *
 * @package BatumiZone_Core
 * @since 0.5.0
 */

(function($) {
    'use strict';

    let currentFilter = 'all';
    let campaigns = [];

    $(document).ready(function() {
        loadCampaigns();
        bindEvents();
    });

    function bindEvents() {
        // Filter tabs
        $('.filter-tab').on('click', function() {
            $('.filter-tab').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('status');
            renderCampaigns();
        });

        // Add campaign button
        $('#add-campaign-btn').on('click', function() {
            openCampaignModal();
        });

        // Cancel button
        $('#cancel-campaign-btn').on('click', function() {
            closeCampaignModal();
        });

        // Modal close
        $('.ad-modal-close').on('click', function() {
            $(this).closest('.ad-modal').hide();
        });

        // Click outside modal
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('ad-modal')) {
                $(e.target).hide();
            }
        });

        // Placement type change
        $('#campaign-placement').on('change', function() {
            if ($(this).val() === 'results_after_n') {
                $('#position-row').show();
            } else {
                $('#position-row').hide();
            }
        });

        // Form submit
        $('#campaign-form').on('submit', function(e) {
            e.preventDefault();
            saveCampaign();
        });
    }

    function loadCampaigns() {
        $.ajax({
            url: batumiAdAdmin.apiUrl + '/admin/ads/campaigns',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiAdAdmin.nonce);
            },
            success: function(response) {
                campaigns = response;
                updateCounts();
                renderCampaigns();
            },
            error: function(xhr) {
                showMessage('error', 'Failed to load campaigns');
                console.error(xhr);
            }
        });
    }

    function updateCounts() {
        const counts = {
            all: campaigns.length,
            active: campaigns.filter(c => c.status === 'active').length,
            paused: campaigns.filter(c => c.status === 'paused').length,
            expired: campaigns.filter(c => c.status === 'expired').length
        };

        $('#count-all').text(counts.all);
        $('#count-active').text(counts.active);
        $('#count-paused').text(counts.paused);
        $('#count-expired').text(counts.expired);
    }

    function renderCampaigns() {
        const filtered = currentFilter === 'all'
            ? campaigns
            : campaigns.filter(c => c.status === currentFilter);

        if (filtered.length === 0) {
            $('#campaigns-tbody').html(`
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <p>No campaigns found.</p>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        filtered.forEach(campaign => {
            const ctr = campaign.impressions > 0
                ? ((campaign.clicks / campaign.impressions) * 100).toFixed(2)
                : '0.00';

            const statusClass = campaign.status === 'active' ? 'status-active' :
                                campaign.status === 'paused' ? 'status-paused' : 'status-expired';

            html += `
                <tr data-id="${campaign.id}">
                    <td>
                        <strong>${escapeHtml(campaign.title)}</strong>
                        <div class="row-actions">
                            <span><a href="${escapeHtml(campaign.link_url)}" target="_blank">View Link</a></span>
                        </div>
                    </td>
                    <td>${formatPlacement(campaign.placement_type, campaign.position_index)}</td>
                    <td>
                        <div>${formatDate(campaign.start_date)}</div>
                        <div>to ${formatDate(campaign.end_date)}</div>
                    </td>
                    <td>${campaign.impressions}</td>
                    <td>${campaign.clicks}</td>
                    <td>${ctr}%</td>
                    <td><span class="status-badge ${statusClass}">${campaign.status}</span></td>
                    <td>
                        <button class="button button-small view-stats-btn" data-id="${campaign.id}">Stats</button>
                        <button class="button button-small edit-campaign-btn" data-id="${campaign.id}">Edit</button>
                        <button class="button button-small button-link-delete delete-campaign-btn" data-id="${campaign.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        $('#campaigns-tbody').html(html);

        // Bind action buttons
        $('.edit-campaign-btn').on('click', function() {
            const id = $(this).data('id');
            editCampaign(id);
        });

        $('.delete-campaign-btn').on('click', function() {
            const id = $(this).data('id');
            deleteCampaign(id);
        });

        $('.view-stats-btn').on('click', function() {
            const id = $(this).data('id');
            viewStats(id);
        });
    }

    function openCampaignModal(campaign = null) {
        $('#campaign-form')[0].reset();
        $('#campaign-id').val('');

        if (campaign) {
            $('#modal-title').text('Edit Campaign');
            $('#campaign-id').val(campaign.id);
            $('#campaign-title').val(campaign.title);
            $('#campaign-image-url').val(campaign.image_url);
            $('#campaign-link-url').val(campaign.link_url);
            $('#campaign-placement').val(campaign.placement_type).trigger('change');
            $('#campaign-position').val(campaign.position_index);
            $('#campaign-start-date').val(formatDatetimeLocal(campaign.start_date));
            $('#campaign-end-date').val(formatDatetimeLocal(campaign.end_date));
            $('#campaign-status').val(campaign.status);
            $('#status-row').show();
        } else {
            $('#modal-title').text('Add New Campaign');
            $('#status-row').hide();

            // Set default dates
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const nextWeek = new Date(now);
            nextWeek.setDate(nextWeek.getDate() + 7);

            $('#campaign-start-date').val(formatDatetimeLocal(tomorrow));
            $('#campaign-end-date').val(formatDatetimeLocal(nextWeek));
        }

        $('#campaign-modal').show();
    }

    function closeCampaignModal() {
        $('#campaign-modal').hide();
    }

    function editCampaign(id) {
        const campaign = campaigns.find(c => c.id == id);
        if (campaign) {
            openCampaignModal(campaign);
        }
    }

    function saveCampaign() {
        const campaignId = $('#campaign-id').val();
        const isEdit = campaignId !== '';

        // Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL format (YYYY-MM-DD HH:MM:SS)
        const startDate = $('#campaign-start-date').val().replace('T', ' ') + ':00';
        const endDate = $('#campaign-end-date').val().replace('T', ' ') + ':00';

        const data = {
            title: $('#campaign-title').val(),
            image_url: $('#campaign-image-url').val(),
            link_url: $('#campaign-link-url').val(),
            placement_type: $('#campaign-placement').val(),
            position_index: parseInt($('#campaign-position').val()) || 0,
            start_date: startDate,
            end_date: endDate,
            status: isEdit ? $('#campaign-status').val() : 'active'
        };

        const url = isEdit
            ? batumiAdAdmin.apiUrl + '/admin/ads/campaigns/' + campaignId
            : batumiAdAdmin.apiUrl + '/admin/ads/campaigns';

        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiAdAdmin.nonce);
            },
            success: function(response) {
                showMessage('success', response.message || 'Campaign saved successfully');
                closeCampaignModal();
                loadCampaigns();
            },
            error: function(xhr) {
                const error = xhr.responseJSON && xhr.responseJSON.message || 'Failed to save campaign';
                showMessage('error', error);
            }
        });
    }

    function deleteCampaign(id) {
        if (!confirm('Are you sure you want to delete this campaign? This will also delete all statistics.')) {
            return;
        }

        $.ajax({
            url: batumiAdAdmin.apiUrl + '/admin/ads/campaigns/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiAdAdmin.nonce);
            },
            success: function(response) {
                showMessage('success', 'Campaign deleted successfully');
                loadCampaigns();
            },
            error: function(xhr) {
                showMessage('error', 'Failed to delete campaign');
            }
        });
    }

    function viewStats(id) {
        const campaign = campaigns.find(c => c.id == id);
        if (!campaign) return;

        $('#stats-modal-title').text('Statistics: ' + campaign.title);

        $.ajax({
            url: batumiAdAdmin.apiUrl + '/admin/ads/campaigns/' + id + '/stats',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiAdAdmin.nonce);
            },
            success: function(response) {
                // Update totals
                $('#total-impressions').text(response.totals.impressions || 0);
                $('#total-clicks').text(response.totals.clicks || 0);
                $('#total-ctr').text((response.totals.ctr || 0).toFixed(2) + '%');

                // Render daily stats
                if (response.daily.length === 0) {
                    $('#stats-tbody').html('<tr><td colspan="4" style="text-align: center;">No stats yet</td></tr>');
                } else {
                    let html = '';
                    response.daily.forEach(day => {
                        const ctr = day.impressions > 0
                            ? ((day.clicks / day.impressions) * 100).toFixed(2)
                            : '0.00';
                        html += `
                            <tr>
                                <td>${formatDate(day.date)}</td>
                                <td>${day.impressions}</td>
                                <td>${day.clicks}</td>
                                <td>${ctr}%</td>
                            </tr>
                        `;
                    });
                    $('#stats-tbody').html(html);
                }

                $('#stats-modal').show();
            },
            error: function(xhr) {
                showMessage('error', 'Failed to load stats');
            }
        });
    }

    function formatPlacement(type, position) {
        const types = {
            'home_top': 'Home - Top Banner',
            'results_after_n': `Search Results - After ${position} items`,
            'detail_below_contact': 'Detail - Below Contact'
        };
        return types[type] || type;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatDatetimeLocal(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showMessage(type, message) {
        // WordPress-style admin notice
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);
        $('.wrap').prepend(notice);

        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 3000);
    }

})(jQuery);
