<?php
/**
 * Template Name: Favorites
 * Description: Display user's saved favorite listings
 *
 * @package Batumi_Theme
 * @since 0.3.0
 */

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';

// Multilingual labels
$labels = array(
    'ge' => array(
        'title' => 'შენახული სერვისები',
        'subtitle' => 'თქვენი ფავორიტი სერვისები ინახება ბრაუზერში',
        'loading' => 'იტვირთება...',
        'empty_title' => 'ფავორიტები არ არის',
        'empty_text' => 'თქვენ ჯერ არ შეგინახავთ არცერთი სერვისი. დაათვალიერეთ სერვისები და დააჭირეთ ❤️ შესანახად.',
        'browse_btn' => 'სერვისების დათვალიერება',
        'clear_all' => 'ყველას წაშლა'
    ),
    'ru' => array(
        'title' => 'Избранные услуги',
        'subtitle' => 'Ваши избранные услуги хранятся в браузере',
        'loading' => 'Загрузка...',
        'empty_title' => 'Нет избранных',
        'empty_text' => 'Вы еще не сохранили ни одной услуги. Просматривайте услуги и нажмите ❤️ чтобы сохранить.',
        'browse_btn' => 'Просмотреть услуги',
        'clear_all' => 'Очистить все'
    ),
    'en' => array(
        'title' => 'Favorite Services',
        'subtitle' => 'Your favorite services are saved in your browser',
        'loading' => 'Loading...',
        'empty_title' => 'No favorites yet',
        'empty_text' => 'You haven\'t saved any services yet. Browse services and tap ❤️ to save.',
        'browse_btn' => 'Browse Services',
        'clear_all' => 'Clear all'
    )
);

$l = $labels[$current_lang] ?? $labels['ge'];
?>

<main id="primary" class="site-main favorites-page">
    <div class="container">

        <!-- Page Header -->
        <header class="page-header">
            <h1 class="page-title"><?php echo esc_html($l['title']); ?></h1>
            <p class="page-subtitle"><?php echo esc_html($l['subtitle']); ?></p>

            <div class="favorites-actions">
                <button id="clear-favorites-btn" class="btn btn-danger btn-sm" style="display: none;">
                    <?php echo esc_html($l['clear_all']); ?>
                </button>
            </div>
        </header>

        <!-- Loading State -->
        <div id="favorites-loading" class="loading-state" style="display: none;">
            <div class="spinner">⏳</div>
            <p><?php echo esc_html($l['loading']); ?></p>
        </div>

        <!-- Empty State -->
        <div id="favorites-empty" class="empty-state" style="display: none;">
            <div class="empty-icon">❤️</div>
            <h2><?php echo esc_html($l['empty_title']); ?></h2>
            <p><?php echo esc_html($l['empty_text']); ?></p>
            <a href="<?php echo home_url('/services/'); ?>" class="btn btn-primary">
                <?php echo esc_html($l['browse_btn']); ?>
            </a>
        </div>

        <!-- Services Grid -->
        <div id="favorites-grid" class="services-grid" style="display: none;">
            <!-- Service cards will be inserted here by JavaScript -->
        </div>

    </div>
</main>

<script>
/**
 * Favorites Page JavaScript
 * Loads favorited services from localStorage and displays them
 */
(function() {
    'use strict';

    const apiBase = '<?php echo esc_url(rest_url('batumizone/v1')); ?>';
    const currentLang = '<?php echo esc_js($current_lang); ?>';

    const FavoritesPage = {
        favorites: [],
        services: [],

        init() {
            this.loadFavorites();
            this.bindEvents();
        },

        bindEvents() {
            const clearBtn = document.getElementById('clear-favorites-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => this.clearAll());
            }
        },

        loadFavorites() {
            try {
                const data = localStorage.getItem('batumi_favorites');
                this.favorites = data ? JSON.parse(data) : [];

                if (this.favorites.length === 0) {
                    this.showEmpty();
                } else {
                    this.showLoading();
                    this.fetchServices();
                }
            } catch (e) {
                console.error('Error loading favorites:', e);
                this.showEmpty();
            }
        },

        async fetchServices() {
            try {
                // Fetch services in batches to avoid URL length limits
                const promises = this.favorites.map(id =>
                    fetch(`${apiBase}/services/${id}`)
                        .then(r => r.ok ? r.json() : null)
                        .catch(() => null)
                );

                this.services = (await Promise.all(promises)).filter(s => s !== null);

                if (this.services.length === 0) {
                    this.showEmpty();
                } else {
                    this.renderServices();
                }
            } catch (e) {
                console.error('Error fetching services:', e);
                this.showEmpty();
            }
        },

        renderServices() {
            const grid = document.getElementById('favorites-grid');
            const clearBtn = document.getElementById('clear-favorites-btn');

            grid.innerHTML = this.services.map(service => this.renderServiceCard(service)).join('');
            grid.style.display = 'grid';
            clearBtn.style.display = 'inline-block';

            this.hideLoading();
            this.hideEmpty();

            // Re-initialize favorite buttons
            if (window.BatumiFavorites) {
                window.BatumiFavorites.initButtons();
            }
        },

        renderServiceCard(service) {
            const title = service[`title_${currentLang}`] || service.title_en || service.title_ge || service.title_ru || 'Untitled';
            const price = this.formatPrice(service.price_model, service.price_value, service.currency);
            const thumbnail = service.featured_image?.thumbnail || '';

            return `
                <article class="service-card">
                    ${thumbnail ? `
                        <div class="service-card-image-wrapper">
                            <a href="${service.link}" class="service-card-image-link">
                                <img src="${thumbnail}" alt="${title}" class="service-card-image">
                            </a>
                            <button
                                class="favorite-btn is-favorited"
                                data-service-id="${service.id}"
                                data-service-title="${title}"
                                aria-label="Remove from favorites"
                                title="Remove from favorites">
                                <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                        </div>
                    ` : ''}
                    <div class="service-card-content">
                        <h3 class="service-card-title">
                            <a href="${service.link}">${title}</a>
                        </h3>
                        <div class="service-card-meta">
                            ${service.direction_name ? `<span class="service-category">${service.direction_name}</span>` : ''}
                        </div>
                        ${price ? `<div class="service-card-price">${price}</div>` : ''}
                        <div class="service-card-footer">
                            <a href="${service.link}" class="btn btn-secondary btn-sm">View Details</a>
                        </div>
                    </div>
                </article>
            `;
        },

        formatPrice(model, value, currency = 'GEL') {
            if (!model || !value) return '';

            const labels = {
                fixed: currentLang === 'ge' ? 'ფიქსირებული' : currentLang === 'ru' ? 'Фикс.' : 'Fixed',
                hourly: currentLang === 'ge' ? '/საათი' : currentLang === 'ru' ? '/час' : '/hr',
                daily: currentLang === 'ge' ? '/დღე' : currentLang === 'ru' ? '/день' : '/day',
                negotiable: currentLang === 'ge' ? 'შეთანხმებით' : currentLang === 'ru' ? 'Договорная' : 'Negotiable'
            };

            if (model === 'negotiable') {
                return labels.negotiable;
            }

            return `${value} ${currency}${labels[model] || ''}`;
        },

        clearAll() {
            const confirmMsg = currentLang === 'ge'
                ? 'დარწმუნებული ხართ რომ გსურთ ყველა ფავორიტის წაშლა?'
                : currentLang === 'ru'
                ? 'Вы уверены, что хотите удалить все избранные?'
                : 'Are you sure you want to clear all favorites?';

            if (!confirm(confirmMsg)) return;

            localStorage.removeItem('batumi_favorites');
            localStorage.removeItem('batumi_favorites_meta');

            if (window.BatumiFavorites) {
                window.BatumiFavorites.updateCounter();
            }

            this.favorites = [];
            this.services = [];
            this.showEmpty();
        },

        showLoading() {
            document.getElementById('favorites-loading').style.display = 'block';
        },

        hideLoading() {
            document.getElementById('favorites-loading').style.display = 'none';
        },

        showEmpty() {
            document.getElementById('favorites-empty').style.display = 'block';
            document.getElementById('favorites-grid').style.display = 'none';
            document.getElementById('clear-favorites-btn').style.display = 'none';
        },

        hideEmpty() {
            document.getElementById('favorites-empty').style.display = 'none';
        }
    };

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FavoritesPage.init());
    } else {
        FavoritesPage.init();
    }

})();
</script>

<?php get_footer(); ?>
