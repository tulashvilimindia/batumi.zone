/**
 * Fancy Frontend - Glassmorphism Header with Filters & Infinite Scroll
 * @package Batumi_Theme
 * @since 0.4.3
 * @updated 2026-01-18 - Bug fixes: API endpoint, XSS prevention, error handling
 */

(function($) {
    'use strict';

    // ======================
    // UTILITY FUNCTIONS
    // ======================

    /**
     * Escape HTML to prevent XSS attacks
     * @param {string} str - String to escape
     * @returns {string} - Escaped string
     */
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ======================
    // DARK MODE ONLY (Light theme disabled)
    // ======================
    const ThemeToggle = {
        init() {
            // Always force dark mode
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    };

    // ======================
    // LANGUAGE DROPDOWN
    // ======================
    const LanguageDropdown = {
        init() {
            this.currentFlag = document.querySelector('.lang-current-flag');
            this.dropdown = document.querySelector('.lang-dropdown');

            if (!this.currentFlag || !this.dropdown) return;

            // Toggle dropdown
            this.currentFlag.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });

            // Close on click outside
            document.addEventListener('click', () => {
                this.close();
            });

            // Prevent dropdown close when clicking inside
            this.dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        },

        toggle() {
            this.dropdown.classList.toggle('active');
            this.currentFlag.classList.toggle('active');
        },

        close() {
            this.dropdown.classList.remove('active');
            this.currentFlag.classList.remove('active');
        }
    };

    // ======================
    // FILTERS PANEL
    // ======================
    const FiltersPanel = {
        init() {
            this.panel = document.querySelector('.filters-panel');
            this.toggleBtn = document.querySelector('.filter-toggle');
            this.applyBtn = document.getElementById('apply-filters');
            this.clearBtn = document.getElementById('clear-filters');
            this.isOpen = false;

            if (!this.panel || !this.toggleBtn) return;

            this.bindEvents();
        },

        bindEvents() {
            // Toggle panel
            this.toggleBtn.addEventListener('click', () => this.toggle());

            // Click outside to close
            document.addEventListener('click', (e) => {
                if (this.isOpen &&
                    !this.panel.contains(e.target) &&
                    !this.toggleBtn.contains(e.target)) {
                    this.close();
                }
            });

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });

            // Apply filters
            if (this.applyBtn) {
                this.applyBtn.addEventListener('click', () => this.applyFilters());
            }

            // Clear filters
            if (this.clearBtn) {
                this.clearBtn.addEventListener('click', () => this.clearFilters());
            }

            // Enter key on inputs
            this.panel.querySelectorAll('.filter-input, .filter-select').forEach(input => {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.applyFilters();
                    }
                });
            });
        },

        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        open() {
            this.panel.classList.add('active');
            this.toggleBtn.classList.add('active');
            this.isOpen = true;

            // Animate with slight delay
            requestAnimationFrame(() => {
                this.panel.style.maxHeight = this.panel.scrollHeight + 'px';
            });
        },

        close() {
            this.panel.classList.remove('active');
            this.toggleBtn.classList.remove('active');
            this.isOpen = false;
            this.panel.style.maxHeight = '0';
        },

        applyFilters() {
            const params = new URLSearchParams();

            // Get all filter values
            const category = document.getElementById('filter-category').value;
            const area = document.getElementById('filter-area').value;
            const priceMin = document.getElementById('filter-price-min').value;
            const priceMax = document.getElementById('filter-price-max').value;
            const sort = document.getElementById('filter-sort').value;

            // Build query string
            if (category) params.set('category', category);
            if (area) params.set('area', area);
            if (priceMin) params.set('price_min', priceMin);
            if (priceMax) params.set('price_max', priceMax);
            if (sort && sort !== 'date') params.set('sort', sort);

            // Redirect to services page with filters
            const queryString = params.toString();
            window.location.href = queryString ? `/?${queryString}` : '/';
        },

        clearFilters() {
            document.getElementById('filter-category').value = '';
            document.getElementById('filter-area').value = '';
            document.getElementById('filter-price-min').value = '';
            document.getElementById('filter-price-max').value = '';
            document.getElementById('filter-sort').value = 'date';

            // Reload page without filters
            window.location.href = '/';
        }
    };

    // ======================
    // INFINITE SCROLL
    // ======================
    const InfiniteScroll = {
        init() {
            this.container = document.getElementById('services-grid');
            this.loadingIndicator = document.getElementById('loading-indicator');
            this.page = 1;
            this.loading = false;
            this.hasMore = true;

            if (!this.container) return;

            this.setupObserver();
        },

        setupObserver() {
            // Create sentinel element at the bottom
            this.sentinel = document.createElement('div');
            this.sentinel.className = 'infinite-scroll-sentinel';
            this.container.parentElement.appendChild(this.sentinel);

            // Hide pagination when infinite scroll is active (CSS fallback for :has())
            const pagination = document.querySelector('.pagination-nav');
            if (pagination) {
                pagination.classList.add('hidden-by-scroll');
            }
            // Also add class to parent for CSS :has() fallback
            const servicesPage = document.querySelector('.services-page');
            if (servicesPage) {
                servicesPage.classList.add('infinite-scroll-active');
            }

            // Intersection Observer
            this.observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.loading && this.hasMore) {
                            this.loadMore();
                        }
                    });
                },
                {
                    root: null,
                    rootMargin: '200px', // Load earlier for better UX
                    threshold: 0
                }
            );

            this.observer.observe(this.sentinel);
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return;

            this.loading = true;
            this.showLoading();

            this.page++;

            // Get current filter params
            const urlParams = new URLSearchParams(window.location.search);

            // Convert 's' parameter to 'query' for API (WordPress search param -> API search param)
            if (urlParams.has('s')) {
                urlParams.set('query', urlParams.get('s'));
                urlParams.delete('s');
            }

            urlParams.set('page', this.page);
            urlParams.set('per_page', '20');

            try {
                // Bug #1 Fix: Correct API endpoint (was batumi-api, should be batumizone)
                const response = await fetch(`/wp-json/batumizone/v1/services?${urlParams.toString()}`);

                // Bug #3 Fix: Check response.ok before parsing JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Bug #3 Fix: Validate response is an array
                if (!data || !Array.isArray(data)) {
                    console.warn('Invalid API response format');
                    this.hasMore = false;
                    return;
                }

                if (data.length === 0) {
                    this.hasMore = false;
                    this.hideLoading();
                    return;
                }

                // Render new service cards
                data.forEach(service => {
                    this.container.appendChild(this.createServiceCard(service));
                });

                // If less than 20 returned, no more pages
                if (data.length < 20) {
                    this.hasMore = false;
                }
            } catch (error) {
                // Bug #10 Fix: Properly handle errors and stop infinite scroll
                console.error('Failed to load more services:', error);
                this.hasMore = false;
                // Optionally show user-friendly error message
                if (this.loadingIndicator) {
                    this.loadingIndicator.textContent = 'Unable to load more services';
                    this.loadingIndicator.style.display = 'block';
                    setTimeout(() => this.hideLoading(), 3000);
                }
            } finally {
                this.loading = false;
                this.hideLoading();
            }
        },

        createServiceCard(service) {
            const card = document.createElement('article');
            const isPromoted = service.is_promoted || false;
            const promotionPriority = service.promotion_priority || 0;

            card.className = isPromoted ? 'service-card service-card-promoted' : 'service-card';
            card.style.position = 'relative';

            if (isPromoted && promotionPriority) {
                card.setAttribute('data-priority', promotionPriority);
            }

            // Get current language for title
            const currentLang = document.documentElement.lang.split('-')[0] || 'ge';
            const title = service[`title_${currentLang}`] || service.title_en || service.title_ge || service.title_ru || 'Untitled';

            // Bug #2 Fix: Escape all user-supplied data to prevent XSS
            const safeTitle = escapeHtml(title);
            const safeCategory = escapeHtml(service.service_direction || '');
            const safeArea = escapeHtml(service.coverage_area || '');
            const safePrice = escapeHtml(String(service.price_value || ''));
            const safeCurrency = escapeHtml(service.currency || 'GEL');
            const safePhone = escapeHtml(service.phone || '');
            const safeImageUrl = service.featured_image ? escapeHtml(service.featured_image.thumbnail) : '';
            const serviceId = parseInt(service.id, 10) || 0; // Ensure ID is numeric

            // Build card HTML with escaped values
            card.innerHTML = `
                <button class="favorite-btn" data-service-id="${serviceId}" data-service-title="${safeTitle}" aria-label="Add to favorites" style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10;">
                    <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                ${safeImageUrl ? `
                    <div class="service-card-image-wrapper" style="position: relative;">
                        <a href="/service/${serviceId}/" class="service-card-image-link">
                            <img src="${safeImageUrl}" alt="${safeTitle}" class="service-card-image" loading="lazy">
                        </a>
                        ${isPromoted ? '<span class="sponsored-badge">Sponsored</span>' : ''}
                    </div>
                ` : ''}

                <div class="service-card-content">
                    <h3 class="service-card-title">
                        <a href="/service/${serviceId}/">${safeTitle}</a>
                    </h3>

                    <div class="service-card-meta">
                        ${safeCategory ? `<span class="service-category">${safeCategory}</span>` : ''}
                        ${safeArea ? `<span class="service-area">${safeArea}</span>` : ''}
                    </div>

                    ${safePrice ? `
                        <div class="service-card-price">
                            ${safePrice} ${safeCurrency}
                        </div>
                    ` : ''}

                    <div class="service-card-footer">
                        <a href="/service/${serviceId}/" class="btn btn-secondary btn-sm">View Details</a>
                        ${safePhone ? `<a href="tel:${safePhone}" class="btn btn-primary btn-sm"><svg class="phone-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Call</a>` : ''}
                    </div>
                </div>
            `;

            return card;
        },

        showLoading() {
            if (this.loadingIndicator) {
                this.loadingIndicator.style.display = 'block';
            }
        },

        hideLoading() {
            if (this.loadingIndicator) {
                this.loadingIndicator.style.display = 'none';
            }
        }
    };

    // ======================
    // USER MENU DROPDOWN
    // ======================
    const UserMenu = {
        init() {
            this.toggle = document.querySelector('.user-menu-toggle');
            this.dropdown = document.querySelector('.user-dropdown');

            if (!this.toggle || !this.dropdown) return;

            this.toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                this.dropdown.classList.toggle('active');
            });

            document.addEventListener('click', () => {
                this.dropdown.classList.remove('active');
            });
        }
    };

    // ======================
    // LAZY LOADING IMAGES
    // ======================
    const LazyImages = {
        init() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }
    };

    // ======================
    // MOBILE VIEWPORT FIX
    // ======================
    const MobileViewport = {
        init() {
            // Fix for mobile browsers' viewport height
            const setVH = () => {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            };

            setVH();
            window.addEventListener('resize', setVH);
            window.addEventListener('orientationchange', setVH);
        }
    };

    // ======================
    // INITIALIZATION
    // ======================
    document.addEventListener('DOMContentLoaded', function() {
        ThemeToggle.init();
        LanguageDropdown.init();
        FiltersPanel.init();
        InfiniteScroll.init();
        UserMenu.init();
        LazyImages.init();
        MobileViewport.init();

        // Initialize favorites on dynamically loaded cards
        document.addEventListener('click', function(e) {
            if (e.target.closest('.favorite-btn')) {
                const btn = e.target.closest('.favorite-btn');
                const serviceId = btn.dataset.serviceId;
                const serviceTitle = btn.dataset.serviceTitle;

                // Use BatumiFavorites (handles class toggle internally)
                if (window.BatumiFavorites) {
                    window.BatumiFavorites.toggle(serviceId, serviceTitle);
                }
            }
        });
    });

})(jQuery);
