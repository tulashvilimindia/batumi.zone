/**
 * Moderation Admin JavaScript
 * Handles loading and processing reports in WordPress admin
 *
 * @version 1.0.0
 */

(function($) {
    'use strict';

    let currentStatus = '';
    let currentPage = 1;
    let totalPages = 1;
    const perPage = 20;

    // Format reason label
    function formatReason(reason) {
        const reasons = {
            'scam': 'Scam/Fraud',
            'duplicate': 'Duplicate',
            'wrong_category': 'Wrong Category',
            'offensive': 'Offensive',
            'illegal': 'Illegal',
            'other': 'Other'
        };
        return reasons[reason] || reason;
    }

    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    // Load reports
    function loadReports() {
        $('#reports-loading').show();
        $('#reports-table, #reports-empty, #reports-pagination').hide();

        const url = batumiModeration.apiUrl + '?per_page=' + perPage + '&page=' + currentPage +
            (currentStatus ? '&status=' + currentStatus : '');

        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiModeration.nonce);
            },
            success: function(data) {
                renderReports(data.reports);
                updatePagination(data);
                updateCounts();
            },
            error: function(xhr) {
                console.error('Error loading reports:', xhr);
                alert('Error loading reports. Please refresh the page.');
            },
            complete: function() {
                $('#reports-loading').hide();
            }
        });
    }

    // Render reports table
    function renderReports(reports) {
        const tbody = $('#reports-tbody');
        tbody.empty();

        if (reports.length === 0) {
            $('#reports-empty').show();
            return;
        }

        $('#reports-table').show();

        reports.forEach(function(report) {
            const row = `
                <tr data-report-id="${report.id}">
                    <td class="column-id">#${report.id}</td>
                    <td class="column-listing">
                        <strong>${report.listing_title || 'Listing #' + report.listing_id}</strong><br>
                        <span class="listing-status status-${report.listing_status}">${report.listing_status}</span>
                    </td>
                    <td class="column-reason">
                        <span class="reason-badge reason-${report.reason}">${formatReason(report.reason)}</span>
                    </td>
                    <td class="column-comment">
                        ${report.comment ? '<div class="comment-preview">' + report.comment.substring(0, 100) +
                            (report.comment.length > 100 ? '...' : '') + '</div>' : '<em>No comment</em>'}
                    </td>
                    <td class="column-status">
                        <span class="status-badge status-${report.status}">${report.status}</span>
                    </td>
                    <td class="column-date">${formatDate(report.created_at)}</td>
                    <td class="column-actions">
                        <button class="button button-small moderate-btn" data-report-id="${report.id}">
                            Moderate
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Update pagination
    function updatePagination(data) {
        totalPages = data.pages;
        currentPage = data.current_page;

        $('#reports-total-text').text(data.total + ' items');
        $('#total-pages-text').text(totalPages);
        $('#current-page-input').val(currentPage).attr('max', totalPages);

        // Enable/disable buttons
        $('#first-page, #prev-page').prop('disabled', currentPage === 1);
        $('#next-page, #last-page').prop('disabled', currentPage === totalPages);

        if (totalPages > 1) {
            $('#reports-pagination').show();
        }
    }

    // Update counts
    function updateCounts() {
        // TODO: Get actual counts from API
        // For now, just update the display
    }

    // Show moderation modal
    function showModerationModal(reportId) {
        // Get report data from the row
        const row = $('tr[data-report-id="' + reportId + '"]');
        const listingTitle = row.find('.column-listing strong').text();
        const reason = row.find('.reason-badge').text();
        const comment = row.find('.comment-preview').text() || 'No comment';
        const status = row.find('.status-badge').text();
        const created = row.find('.column-date').text();

        // Populate modal
        $('#modal-report-id, #modal-report-id-input').val(reportId);
        $('#modal-listing-title').text(listingTitle);
        $('#modal-reason').text(reason);
        $('#modal-comment').text(comment);
        $('#modal-current-status').text(status);
        $('#modal-created-at').text(created);

        // Reset form
        $('#moderation-form')[0].reset();
        hideModalMessage();

        // Show modal
        $('#moderation-modal').fadeIn(200);
    }

    // Hide moderation modal
    function hideModerationModal() {
        $('#moderation-modal').fadeOut(200);
    }

    // Show modal message
    function showModalMessage(message, type) {
        const messageEl = $('#modal-message');
        messageEl.text(message)
            .removeClass('success error')
            .addClass(type)
            .fadeIn();
    }

    // Hide modal message
    function hideModalMessage() {
        $('#modal-message').fadeOut();
    }

    // Submit moderation decision
    function submitModerationDecision(formData) {
        const reportId = formData.report_id;
        const submitBtn = $('#moderation-form button[type="submit"]');
        const originalText = submitBtn.text();

        submitBtn.prop('disabled', true).text('Processing...');
        hideModalMessage();

        $.ajax({
            url: batumiModeration.apiUrl + '/' + reportId,
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                action: formData.action,
                status: formData.status,
                notes: formData.notes
            }),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', batumiModeration.nonce);
            },
            success: function(data) {
                if (data.success) {
                    showModalMessage('Report updated successfully!', 'success');
                    setTimeout(function() {
                        hideModerationModal();
                        loadReports(); // Reload reports
                    }, 1500);
                } else {
                    showModalMessage(data.message || 'Error updating report', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error updating report:', xhr);
                const error = xhr.responseJSON || {};
                showModalMessage(error.message || 'Error updating report. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    // Initialize
    $(document).ready(function() {
        // Load initial reports
        loadReports();

        // Status tab click
        $('.status-tab').on('click', function() {
            $('.status-tab').removeClass('active');
            $(this).addClass('active');
            currentStatus = $(this).data('status');
            currentPage = 1;
            loadReports();
        });

        // Moderate button click
        $(document).on('click', '.moderate-btn', function() {
            const reportId = $(this).data('report-id');
            showModerationModal(reportId);
        });

        // Modal close
        $('.modal-close, .modal-cancel, .modal-overlay').on('click', function() {
            hideModerationModal();
        });

        // Form submission
        $('#moderation-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                report_id: $('#modal-report-id-input').val(),
                action: $('#modal-action').val(),
                status: $('#modal-status').val(),
                notes: $('#modal-notes').val()
            };
            submitModerationDecision(formData);
        });

        // Pagination buttons
        $('#first-page').on('click', function() {
            currentPage = 1;
            loadReports();
        });

        $('#prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadReports();
            }
        });

        $('#next-page').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadReports();
            }
        });

        $('#last-page').on('click', function() {
            currentPage = totalPages;
            loadReports();
        });

        $('#current-page-input').on('change', function() {
            const page = parseInt($(this).val());
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                loadReports();
            } else {
                $(this).val(currentPage);
            }
        });
    });

})(jQuery);
