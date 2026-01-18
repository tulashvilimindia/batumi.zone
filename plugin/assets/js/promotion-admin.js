/**
 * Promotion Admin Page JavaScript
 *
 * @package Batumi_Zone_Core
 */

(function($) {
    'use strict';

    // Global variables
    let currentRequestId = null;
    let currentAction = null;

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initModal();
        initActionButtons();
    });

    /**
     * Initialize modal functionality
     */
    function initModal() {
        const modal = $('#promo-action-modal');
        const closeBtn = $('.promo-modal-close');
        const cancelBtn = $('#modal-cancel');

        // Close modal on X button
        closeBtn.on('click', function() {
            closeModal();
        });

        // Close modal on Cancel button
        cancelBtn.on('click', function() {
            closeModal();
        });

        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                closeModal();
            }
        });

        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && modal.is(':visible')) {
                closeModal();
            }
        });

        // Handle confirm button
        $('#modal-confirm').on('click', function() {
            if (currentAction === 'activate') {
                activatePromotion(currentRequestId);
            } else if (currentAction === 'reject') {
                rejectPromotion(currentRequestId);
            }
        });
    }

    /**
     * Initialize action buttons
     */
    function initActionButtons() {
        // Activate button
        $(document).on('click', '.btn-activate', function(e) {
            e.preventDefault();
            const requestId = $(this).data('request-id');
            showModal('activate', requestId);
        });

        // Reject button
        $(document).on('click', '.btn-reject', function(e) {
            e.preventDefault();
            const requestId = $(this).data('request-id');
            showModal('reject', requestId);
        });
    }

    /**
     * Show modal with action context
     */
    function showModal(action, requestId) {
        currentRequestId = requestId;
        currentAction = action;

        const modal = $('#promo-action-modal');
        const title = $('#modal-title');
        const message = $('#modal-message');
        const confirmBtn = $('#modal-confirm');
        const notesField = $('#admin-notes');

        // Clear previous notes
        notesField.val('');

        // Set modal content based on action
        if (action === 'activate') {
            title.text('Activate Promotion Request');
            message.text('Are you sure you want to activate this promotion request? The service will be promoted immediately.');
            confirmBtn.removeClass('button-secondary').addClass('button-primary');
            confirmBtn.text('Activate Promotion');
        } else if (action === 'reject') {
            title.text('Reject Promotion Request');
            message.text('Are you sure you want to reject this promotion request? This action will notify the poster.');
            confirmBtn.removeClass('button-primary').addClass('button-secondary');
            confirmBtn.text('Reject Request');
        }

        // Show modal
        modal.fadeIn(200);
    }

    /**
     * Close modal
     */
    function closeModal() {
        const modal = $('#promo-action-modal');
        modal.fadeOut(200);
        currentRequestId = null;
        currentAction = null;

        // Hide loading state
        $('.modal-loading').hide();
        $('.modal-form, .modal-actions').show();
    }

    /**
     * Activate promotion via AJAX
     */
    function activatePromotion(requestId) {
        const adminNotes = $('#admin-notes').val();
        const row = $('tr[data-request-id="' + requestId + '"]');

        // Show loading
        showLoading();

        $.ajax({
            url: batumiPromoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'batumizone_activate_promotion',
                nonce: batumiPromoAdmin.nonce,
                request_id: requestId,
                admin_notes: adminNotes
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message || 'Promotion activated successfully!');
                    updateRowStatus(row, 'approved');
                    closeModal();
                } else {
                    showNotice('error', response.data.message || 'Failed to activate promotion.');
                    hideLoading();
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'AJAX error: ' + error);
                hideLoading();
            }
        });
    }

    /**
     * Reject promotion via AJAX
     */
    function rejectPromotion(requestId) {
        const adminNotes = $('#admin-notes').val();
        const row = $('tr[data-request-id="' + requestId + '"]');

        // Show loading
        showLoading();

        $.ajax({
            url: batumiPromoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'batumizone_reject_promotion',
                nonce: batumiPromoAdmin.nonce,
                request_id: requestId,
                admin_notes: adminNotes
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message || 'Promotion request rejected.');
                    updateRowStatus(row, 'rejected');
                    closeModal();
                } else {
                    showNotice('error', response.data.message || 'Failed to reject promotion.');
                    hideLoading();
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'AJAX error: ' + error);
                hideLoading();
            }
        });
    }

    /**
     * Show loading state in modal
     */
    function showLoading() {
        $('.modal-form, .modal-actions').hide();
        $('.modal-loading').show();
    }

    /**
     * Hide loading state in modal
     */
    function hideLoading() {
        $('.modal-loading').hide();
        $('.modal-form, .modal-actions').show();
    }

    /**
     * Update row status after action
     */
    function updateRowStatus(row, newStatus) {
        // Update status badge
        const statusBadge = row.find('.status-badge');
        statusBadge.removeClass('status-pending status-approved status-rejected');
        statusBadge.addClass('status-' + newStatus);
        statusBadge.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

        // Update actions column
        const actionsCell = row.find('.actions-column');
        const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        actionsCell.html('<span class="status-text">' + statusText + '</span>');

        // Add subtle highlight
        row.addClass('loading');
        setTimeout(function() {
            row.removeClass('loading');
        }, 500);
    }

    /**
     * Show notice message
     */
    function showNotice(type, message) {
        const noticeHtml = '<div class="promo-notice ' + type + '">' + message + '</div>';
        const container = $('.batumizone-promo-admin');

        // Remove existing notices
        $('.promo-notice').remove();

        // Add new notice
        container.prepend(noticeHtml);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            $('.promo-notice').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Scroll to top to show notice
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

})(jQuery);
