/**
 * Report Modal Functionality
 * Handles anonymous reporting of service listings
 *
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Translations
    const translations = {
        ge: {
            reportTitle: 'ჩივილის მიზეზი',
            reasonLabel: 'აირჩიეთ მიზეზი',
            commentLabel: 'დამატებითი ინფორმაცია (არასავალდებულო)',
            commentPlaceholder: 'მიუთითეთ დეტალები...',
            reasons: {
                scam: 'თაღლითობა/აფერა',
                duplicate: 'დუბლიკატი',
                wrong_category: 'არასწორი კატეგორია',
                offensive: 'შეურაცხმყოფელი',
                illegal: 'უკანონო',
                other: 'სხვა'
            },
            submitBtn: 'გაგზავნა',
            cancelBtn: 'გაუქმება',
            submitting: 'იგზავნება...',
            successMsg: 'ჩივილი წარმატებით გაიგზავნა',
            errorMsg: 'შეცდომა. გთხოვთ სცადოთ მოგვიანებით',
            rateLimitMsg: 'ძალიან ბევრი ჩივილი. გთხოვთ სცადოთ მოგვიანებით'
        },
        ru: {
            reportTitle: 'Причина жалобы',
            reasonLabel: 'Выберите причину',
            commentLabel: 'Дополнительная информация (необязательно)',
            commentPlaceholder: 'Укажите детали...',
            reasons: {
                scam: 'Мошенничество/афера',
                duplicate: 'Дубликат',
                wrong_category: 'Неверная категория',
                offensive: 'Оскорбительное',
                illegal: 'Незаконное',
                other: 'Другое'
            },
            submitBtn: 'Отправить',
            cancelBtn: 'Отмена',
            submitting: 'Отправка...',
            successMsg: 'Жалоба успешно отправлена',
            errorMsg: 'Ошибка. Попробуйте позже',
            rateLimitMsg: 'Слишком много жалоб. Попробуйте позже'
        },
        en: {
            reportTitle: 'Report Reason',
            reasonLabel: 'Choose a reason',
            commentLabel: 'Additional information (optional)',
            commentPlaceholder: 'Provide details...',
            reasons: {
                scam: 'Scam/Fraud',
                duplicate: 'Duplicate',
                wrong_category: 'Wrong Category',
                offensive: 'Offensive',
                illegal: 'Illegal',
                other: 'Other'
            },
            submitBtn: 'Submit',
            cancelBtn: 'Cancel',
            submitting: 'Submitting...',
            successMsg: 'Report submitted successfully',
            errorMsg: 'Error. Please try again later',
            rateLimitMsg: 'Too many reports. Please try again later'
        }
    };

    // Get current language
    const currentLang = document.documentElement.lang || 'ge';
    const t = translations[currentLang] || translations.ge;

    // Create modal HTML
    function createModal() {
        const modalHTML = `
            <div id="report-modal" class="report-modal" style="display:none;">
                <div class="report-modal-overlay"></div>
                <div class="report-modal-content">
                    <div class="report-modal-header">
                        <h3>${t.reportTitle}</h3>
                        <button class="report-modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="report-modal-body">
                        <form id="report-form">
                            <input type="hidden" id="report-listing-id" name="listing_id" value="">

                            <div class="form-group">
                                <label for="report-reason">${t.reasonLabel} <span class="required">*</span></label>
                                <select id="report-reason" name="reason" required>
                                    <option value="">-- ${t.reasonLabel} --</option>
                                    <option value="scam">${t.reasons.scam}</option>
                                    <option value="duplicate">${t.reasons.duplicate}</option>
                                    <option value="wrong_category">${t.reasons.wrong_category}</option>
                                    <option value="offensive">${t.reasons.offensive}</option>
                                    <option value="illegal">${t.reasons.illegal}</option>
                                    <option value="other">${t.reasons.other}</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="report-comment">${t.commentLabel}</label>
                                <textarea
                                    id="report-comment"
                                    name="comment"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="${t.commentPlaceholder}"></textarea>
                                <small class="char-count">0 / 500</small>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary report-cancel">${t.cancelBtn}</button>
                                <button type="submit" class="btn btn-primary report-submit">${t.submitBtn}</button>
                            </div>

                            <div id="report-message" class="report-message" style="display:none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Show modal
    function showModal(serviceId) {
        const modal = document.getElementById('report-modal');
        const listingIdInput = document.getElementById('report-listing-id');
        const form = document.getElementById('report-form');

        // Reset form
        form.reset();
        listingIdInput.value = serviceId;
        hideMessage();

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Hide modal
    function hideModal() {
        const modal = document.getElementById('report-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Show message
    function showMessage(message, type = 'success') {
        const messageEl = document.getElementById('report-message');
        messageEl.textContent = message;
        messageEl.className = 'report-message report-message-' + type;
        messageEl.style.display = 'block';
    }

    // Hide message
    function hideMessage() {
        const messageEl = document.getElementById('report-message');
        messageEl.style.display = 'none';
    }

    // Submit report
    async function submitReport(formData) {
        const submitBtn = document.querySelector('.report-submit');
        const originalText = submitBtn.textContent;

        try {
            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = t.submitting;

            // Bug #11 Fix: Build headers with CSRF nonce for security
            const headers = {
                'Content-Type': 'application/json'
            };

            // Add WordPress REST API nonce if available (prevents CSRF attacks)
            if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
                headers['X-WP-Nonce'] = wpApiSettings.nonce;
            } else if (typeof batumiSettings !== 'undefined' && batumiSettings.nonce) {
                headers['X-WP-Nonce'] = batumiSettings.nonce;
            }

            // Make API request
            const response = await fetch('/wp-json/batumizone/v1/reports', {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin', // Include cookies for authentication
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showMessage(t.successMsg, 'success');
                setTimeout(() => {
                    hideModal();
                }, 2000);
            } else {
                // Check for rate limit
                if (data.code === 'rate_limit_exceeded') {
                    showMessage(t.rateLimitMsg, 'error');
                } else {
                    showMessage(data.message || t.errorMsg, 'error');
                }
            }
        } catch (error) {
            console.error('Report submission error:', error);
            showMessage(t.errorMsg, 'error');
        } finally {
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Create modal
        createModal();

        // Report button click handlers
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('report-btn') || e.target.closest('.report-btn')) {
                e.preventDefault();
                const btn = e.target.classList.contains('report-btn') ? e.target : e.target.closest('.report-btn');
                const serviceId = btn.getAttribute('data-service-id');
                if (serviceId) {
                    showModal(serviceId);
                }
            }
        });

        // Close modal handlers
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('report-modal-close') ||
                e.target.classList.contains('report-modal-overlay') ||
                e.target.classList.contains('report-cancel')) {
                hideModal();
            }
        });

        // Character counter
        const commentField = document.getElementById('report-comment');
        if (commentField) {
            commentField.addEventListener('input', function() {
                const charCount = this.value.length;
                const counter = this.parentElement.querySelector('.char-count');
                if (counter) {
                    counter.textContent = charCount + ' / 500';
                }
            });
        }

        // Form submission
        const form = document.getElementById('report-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = {
                    listing_id: parseInt(document.getElementById('report-listing-id').value),
                    reason: document.getElementById('report-reason').value,
                    comment: document.getElementById('report-comment').value || null
                };

                submitReport(formData);
            });
        }

        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('report-modal');
                if (modal && modal.style.display === 'block') {
                    hideModal();
                }
            }
        });
    });
})();
