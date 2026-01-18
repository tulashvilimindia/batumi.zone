<?php
/**
 * Template Name: My Listings
 * Description: Dashboard for managing service listings
 *
 * @package Batumi_Theme
 * @since 0.3.0
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
$current_user = wp_get_current_user();
?>

<main id="primary" class="site-main dashboard-page">
    <div class="container">

        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Мои объявления';
                } elseif ($current_lang === 'en') {
                    echo 'My Listings';
                } else {
                    echo 'ჩემი განცხადებები';
                }
                ?>
            </h1>

            <div class="dashboard-actions">
                <a href="<?php echo home_url('/create-service/'); ?>" class="btn btn-primary">
                    + <?php
                    if ($current_lang === 'ru') {
                        echo 'Добавить объявление';
                    } elseif ($current_lang === 'en') {
                        echo 'Add Listing';
                    } else {
                        echo 'განცხადების დამატება';
                    }
                    ?>
                </a>
                <a href="<?php echo home_url('/profile/'); ?>" class="btn btn-secondary">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Профиль';
                    } elseif ($current_lang === 'en') {
                        echo 'Profile';
                    } else {
                        echo 'პროფილი';
                    }
                    ?>
                </a>
            </div>
        </div>

        <div id="dashboard-messages" class="dashboard-messages"></div>

        <!-- Filter Tabs -->
        <div class="dashboard-tabs">
            <button class="tab-button active" data-status="all">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Все';
                } elseif ($current_lang === 'en') {
                    echo 'All';
                } else {
                    echo 'ყველა';
                }
                ?> <span class="tab-count" id="count-all">0</span>
            </button>
            <button class="tab-button" data-status="publish">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Активные';
                } elseif ($current_lang === 'en') {
                    echo 'Published';
                } else {
                    echo 'აქტიური';
                }
                ?> <span class="tab-count" id="count-publish">0</span>
            </button>
            <button class="tab-button" data-status="draft">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Черновики';
                } elseif ($current_lang === 'en') {
                    echo 'Drafts';
                } else {
                    echo 'მონახაზი';
                }
                ?> <span class="tab-count" id="count-draft">0</span>
            </button>
            <button class="tab-button" data-status="inactive">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Приостановленные';
                } elseif ($current_lang === 'en') {
                    echo 'Paused';
                } else {
                    echo 'შეჩერებული';
                }
                ?> <span class="tab-count" id="count-inactive">0</span>
            </button>
        </div>

        <!-- Listings Grid -->
        <div id="listings-container" class="listings-grid">
            <div class="loading-spinner">
                <div class="spinner">⏳</div>
                <p><?php echo $current_lang === 'ru' ? 'Загрузка...' : ($current_lang === 'en' ? 'Loading...' : 'იტვირთება...'); ?></p>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <div class="empty-icon">📋</div>
            <h3 class="empty-title">
                <?php
                if ($current_lang === 'ru') {
                    echo 'У вас еще нет объявлений';
                } elseif ($current_lang === 'en') {
                    echo 'You don\'t have any listings yet';
                } else {
                    echo 'თქვენ ჯერ არ გაქვთ განცხადებები';
                }
                ?>
            </h3>
            <p class="empty-text">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Начните с создания вашего первого объявления';
                } elseif ($current_lang === 'en') {
                    echo 'Start by creating your first listing';
                } else {
                    echo 'დაიწყეთ თქვენი პირველი განცხადების შექმნით';
                }
                ?>
            </p>
            <a href="<?php echo home_url('/create-service/'); ?>" class="btn btn-primary">
                + <?php echo $current_lang === 'ru' ? 'Добавить объявление' : ($current_lang === 'en' ? 'Add Listing' : 'განცხადების დამატება'); ?>
            </a>
        </div>

    </div>
</main>

<!-- Promotion Request Modal -->
<div id="promotion-modal" class="promotion-modal" style="display: none;">
    <div class="promotion-modal-content">
        <span class="promotion-modal-close">&times;</span>
        <h2 class="promotion-modal-title">
            <?php echo $current_lang === 'ru' ? 'Продвижение объявления' : ($current_lang === 'en' ? 'Promote Your Listing' : 'განცხადების პრომოცია'); ?>
        </h2>
        <p class="promotion-modal-description">
            <?php echo $current_lang === 'ru' ? 'Выберите пакет продвижения для вашего объявления:' : ($current_lang === 'en' ? 'Choose a promotion package for your listing:' : 'აირჩიეთ თქვენი განცხადების პრომოციის პაკეტი:'); ?>
        </p>

        <div id="promotion-packages" class="promotion-packages-grid"></div>

        <div class="promotion-modal-actions">
            <button id="promotion-cancel" class="btn btn-secondary">
                <?php echo $current_lang === 'ru' ? 'Отмена' : ($current_lang === 'en' ? 'Cancel' : 'გაუქმება'); ?>
            </button>
        </div>

        <div class="promotion-modal-loading" style="display: none;">
            <div class="spinner">⏳</div>
            <p><?php echo $current_lang === 'ru' ? 'Отправка запроса...' : ($current_lang === 'en' ? 'Submitting request...' : 'მიმდინარეობს მოთხოვნის გაგზავნა...'); ?></p>
        </div>
    </div>
</div>

<script>
var currentLang = '<?php echo $current_lang; ?>';
var currentStatus = 'all';
var allListings = [];
var promotionPackages = [];
var promotionRequests = [];
var selectedService = null;

var translations = {
    edit: '<?php echo $current_lang === 'ru' ? 'Редактировать' : ($current_lang === 'en' ? 'Edit' : 'რედაქტირება'); ?>',
    publish: '<?php echo $current_lang === 'ru' ? 'Опубликовать' : ($current_lang === 'en' ? 'Publish' : 'გამოქვეყნება'); ?>',
    pause: '<?php echo $current_lang === 'ru' ? 'Приостановить' : ($current_lang === 'en' ? 'Pause' : 'შეჩერება'); ?>',
    activate: '<?php echo $current_lang === 'ru' ? 'Активировать' : ($current_lang === 'en' ? 'Activate' : 'აქტივაცია'); ?>',
    delete: '<?php echo $current_lang === 'ru' ? 'Удалить' : ($current_lang === 'en' ? 'Delete' : 'წაშლა'); ?>',
    promote: '<?php echo $current_lang === 'ru' ? 'Продвинуть' : ($current_lang === 'en' ? 'Promote' : 'პრომოცია'); ?>',
    confirmDelete: '<?php echo $current_lang === 'ru' ? 'Вы уверены, что хотите удалить это объявление?' : ($current_lang === 'en' ? 'Are you sure you want to delete this listing?' : 'დარწმუნებული ხართ, რომ გსურთ ამ განცხადების წაშლა?'); ?>',
    published: '<?php echo $current_lang === 'ru' ? 'Опубликовано' : ($current_lang === 'en' ? 'Published' : 'გამოქვეყნებული'); ?>',
    draft: '<?php echo $current_lang === 'ru' ? 'Черновик' : ($current_lang === 'en' ? 'Draft' : 'მონახაზი'); ?>',
    inactive: '<?php echo $current_lang === 'ru' ? 'Приостановлено' : ($current_lang === 'en' ? 'Paused' : 'შეჩერებული'); ?>',
    cannotPublish: '<?php echo $current_lang === 'ru' ? 'Не может быть опубликовано' : ($current_lang === 'en' ? 'Cannot publish' : 'ვერ გამოქვეყნდება'); ?>',
    validationErrors: '<?php echo $current_lang === 'ru' ? 'Исправьте ошибки перед публикацией' : ($current_lang === 'en' ? 'Fix errors before publishing' : 'გამოასწორეთ შეცდომები გამოქვეყნებამდე'); ?>',
    promoted: '<?php echo $current_lang === 'ru' ? 'Продвигается' : ($current_lang === 'en' ? 'Promoted' : 'პრომოცია აქტიურია'); ?>',
    promoPending: '<?php echo $current_lang === 'ru' ? 'Запрос отправлен' : ($current_lang === 'en' ? 'Request Pending' : 'მოთხოვნა გაგზავნილია'); ?>',
    promoRejected: '<?php echo $current_lang === 'ru' ? 'Отклонено' : ($current_lang === 'en' ? 'Rejected' : 'უარყოფილი'); ?>',
    days: '<?php echo $current_lang === 'ru' ? 'дней' : ($current_lang === 'en' ? 'days' : 'დღე'); ?>',
    select: '<?php echo $current_lang === 'ru' ? 'Выбрать' : ($current_lang === 'en' ? 'Select' : 'არჩევა'); ?>'
};

jQuery(document).ready(function($) {
    loadData();

    // Tab switching
    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        filterListings();
    });

    // Modal controls
    $('.promotion-modal-close, #promotion-cancel').on('click', function() {
        closePromotionModal();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#promotion-modal')) {
            closePromotionModal();
        }
    });

    function loadData() {
        // Load listings, packages, and promotion requests in parallel
        Promise.all([
            loadListings(),
            loadPromotionPackages(),
            loadPromotionRequests()
        ]).then(() => {
            updateCounts();
            filterListings();
        });
    }

    function loadListings() {
        return $.ajax({
            url: '<?php echo rest_url('batumizone/v1/my/services'); ?>',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                allListings = response.services || [];
            },
            error: function(xhr) {
                showMessage('error', 'Error loading listings');
            }
        });
    }

    function loadPromotionPackages() {
        return $.ajax({
            url: '<?php echo rest_url('batumizone/v1/promotion/packages'); ?>',
            type: 'GET',
            success: function(response) {
                promotionPackages = response.packages || [];
            },
            error: function(xhr) {
                console.error('Error loading promotion packages');
            }
        });
    }

    function loadPromotionRequests() {
        return $.ajax({
            url: '<?php echo rest_url('batumizone/v1/my/promotion-requests'); ?>',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                promotionRequests = response.requests || [];
            },
            error: function(xhr) {
                console.error('Error loading promotion requests');
            }
        });
    }

    function updateCounts() {
        var counts = {
            all: allListings.length,
            publish: allListings.filter(s => s.status === 'publish').length,
            draft: allListings.filter(s => s.status === 'draft').length,
            inactive: allListings.filter(s => s.status === 'inactive').length
        };

        $('#count-all').text(counts.all);
        $('#count-publish').text(counts.publish);
        $('#count-draft').text(counts.draft);
        $('#count-inactive').text(counts.inactive);
    }

    function filterListings() {
        var filtered = currentStatus === 'all'
            ? allListings
            : allListings.filter(s => s.status === currentStatus);

        if (filtered.length === 0) {
            $('#listings-container').hide();
            $('#empty-state').show();
        } else {
            $('#empty-state').hide();
            $('#listings-container').show().html(renderListings(filtered));
        }
    }

    function getPromotionStatus(serviceId) {
        // Check if service is currently promoted
        var listing = allListings.find(l => l.id === serviceId);
        if (listing && listing.is_promoted) {
            return {
                type: 'active',
                priority: listing.promotion_priority
            };
        }

        // Check if there's a pending/approved/rejected request
        var request = promotionRequests.find(r => r.service_id === serviceId);
        if (request) {
            return {
                type: request.status,
                requestId: request.id
            };
        }

        return null;
    }

    function renderListings(listings) {
        return listings.map(listing => {
            // Handle both nested (API format: title.ge) and flat (title_ge) formats
            var titleObj = listing.title || {};
            var title = titleObj[currentLang] || titleObj.en || titleObj.ge ||
                        listing['title_' + currentLang] || listing.title_en || listing.title_ge || 'Untitled';
            var status = listing.status;
            var statusLabel = translations[status] || status;
            var canPublish = listing.validation && listing.validation.can_publish;

            var promoStatus = getPromotionStatus(listing.id);
            var promoHtml = '';

            if (promoStatus) {
                if (promoStatus.type === 'active') {
                    promoHtml = `<div class="promo-badge promo-active">✨ ${translations.promoted}</div>`;
                } else if (promoStatus.type === 'pending') {
                    promoHtml = `<div class="promo-badge promo-pending">⏳ ${translations.promoPending}</div>`;
                } else if (promoStatus.type === 'rejected') {
                    promoHtml = `<div class="promo-badge promo-rejected">❌ ${translations.promoRejected}</div>`;
                }
            }

            var actions = '';
            if (status === 'draft') {
                if (canPublish) {
                    actions += `<button class="btn btn-sm btn-primary" onclick="publishListing(${listing.id})">${translations.publish}</button>`;
                } else {
                    actions += `<button class="btn btn-sm btn-secondary" disabled title="${translations.validationErrors}">${translations.cannotPublish}</button>`;
                }
            } else if (status === 'publish') {
                actions += `<button class="btn btn-sm btn-warning" onclick="pauseListing(${listing.id})">${translations.pause}</button>`;

                // Add promote button if not already promoted and no pending request
                if (!promoStatus || promoStatus.type === 'rejected') {
                    actions += `<button class="btn btn-sm btn-success" onclick="openPromotionModal(${listing.id})">✨ ${translations.promote}</button>`;
                }
            } else if (status === 'inactive') {
                actions += `<button class="btn btn-sm btn-success" onclick="activateListing(${listing.id})">${translations.activate}</button>`;
            }

            actions += `<a href="<?php echo home_url('/edit-service/'); ?>?id=${listing.id}" class="btn btn-sm btn-secondary">${translations.edit}</a>`;
            actions += `<button class="btn btn-sm btn-danger" onclick="deleteListing(${listing.id})">${translations.delete}</button>`;

            return `
                <div class="listing-card ${promoStatus && promoStatus.type === 'active' ? 'listing-promoted' : ''}" data-id="${listing.id}">
                    <div class="listing-status status-${status}">${statusLabel}</div>
                    ${promoHtml}
                    <h3 class="listing-title">${title}</h3>
                    <div class="listing-meta">
                        <span>ID: ${listing.id}</span>
                        <span>${listing.date ? new Date(listing.date).toLocaleDateString() : ''}</span>
                    </div>
                    <div class="listing-actions">
                        ${actions}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Global functions for action buttons
    window.publishListing = function(id) {
        performAction(id, 'publish', '<?php echo rest_url('batumizone/v1/my/services/'); ?>' + id + '/publish');
    };

    window.pauseListing = function(id) {
        performAction(id, 'pause', '<?php echo rest_url('batumizone/v1/my/services/'); ?>' + id + '/pause');
    };

    window.activateListing = function(id) {
        performAction(id, 'activate', '<?php echo rest_url('batumizone/v1/my/services/'); ?>' + id + '/activate');
    };

    window.deleteListing = function(id) {
        if (!confirm(translations.confirmDelete)) return;
        performAction(id, 'delete', '<?php echo rest_url('batumizone/v1/my/services/'); ?>' + id, 'DELETE');
    };

    window.openPromotionModal = function(serviceId) {
        selectedService = serviceId;
        renderPromotionPackages();
        $('#promotion-modal').fadeIn(200);
    };

    function closePromotionModal() {
        $('#promotion-modal').fadeOut(200);
        selectedService = null;
    }

    function renderPromotionPackages() {
        var packagesHtml = promotionPackages.map(pkg => {
            return `
                <div class="promotion-package">
                    <div class="package-header">
                        <h3 class="package-name">${pkg.name}</h3>
                        <div class="package-price">${pkg.price_display}</div>
                    </div>
                    <div class="package-details">
                        <div class="package-duration">⏰ ${pkg.duration_days} ${translations.days}</div>
                        <div class="package-priority">⭐ Priority: ${pkg.priority}</div>
                    </div>
                    ${pkg.description ? `<p class="package-description">${pkg.description}</p>` : ''}
                    <button class="btn btn-primary btn-block" onclick="selectPromotionPackage(${pkg.id})">
                        ${translations.select}
                    </button>
                </div>
            `;
        }).join('');

        $('#promotion-packages').html(packagesHtml);
    }

    window.selectPromotionPackage = function(packageId) {
        if (!selectedService) return;

        // Show loading
        $('.promotion-packages-grid, .promotion-modal-actions').hide();
        $('.promotion-modal-loading').show();

        $.ajax({
            url: '<?php echo rest_url('batumizone/v1/my/services/'); ?>' + selectedService + '/request-promotion',
            type: 'POST',
            data: JSON.stringify({ package_id: packageId }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                closePromotionModal();
                showMessage('success', response.message || 'Promotion request submitted successfully');
                // Reload data to update promotion status
                loadData();
            },
            error: function(xhr) {
                $('.promotion-packages-grid, .promotion-modal-actions').show();
                $('.promotion-modal-loading').hide();
                var error = xhr.responseJSON && xhr.responseJSON.message || 'Failed to submit promotion request';
                showMessage('error', error);
            }
        });
    };

    function performAction(id, action, url, method = 'POST') {
        $.ajax({
            url: url,
            type: method,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                loadData(); // Reload all data
                showMessage('success', 'Action completed successfully');
            },
            error: function(xhr) {
                var error = xhr.responseJSON && xhr.responseJSON.message || 'Action failed';
                showMessage('error', error);
            }
        });
    }

    function showMessage(type, message) {
        var messageHtml = `<div class="message message-${type}">${message}</div>`;
        $('#dashboard-messages').html(messageHtml);
        setTimeout(() => $('#dashboard-messages').empty(), 5000);
    }
});
</script>

<?php
get_footer();
