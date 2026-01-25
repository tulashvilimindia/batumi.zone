/**
 * Fancy Frontend - Glassmorphism Header with Filters & Infinite Scroll
 * @package Batumi_Theme
 * @since 0.4.2
 */

(function($) {
    'use strict';

    // ======================
    // DARK/LIGHT MODE TOGGLE
    // ======================
    const ThemeToggle = {
        init() {
            this.toggleBtn = document.querySelector('.theme-toggle');
            this.sunIcon = document.querySelector('.sun-icon');
            this.moonIcon = document.querySelector('.moon-icon');

            if (!this.toggleBtn) return;

            // Load saved theme
            this.loadTheme();

            // Bind click event
            this.toggleBtn.addEventListener('click', () => this.toggle());
        },

        loadTheme() {
            const savedTheme = localStorage.getItem('batumi_theme') || 'light';
            this.applyTheme(savedTheme);
        },

        toggle() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            this.applyTheme(newTheme);
            localStorage.setItem('batumi_theme', newTheme);
        },

        applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);

            if (theme === 'dark') {
                this.sunIcon.style.display = 'none';
                this.moonIcon.style.display = 'block';
            } else {
                this.sunIcon.style.display = 'block';
                this.moonIcon.style.display = 'none';
            }
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
                const response = await fetch(`/wp-json/batumi-api/v1/services?${urlParams.toString()}`);
                const data = await response.json();

                if (data && Array.isArray(data)) {
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
                }
            } catch (error) {
                console.error('Failed to load more services:', error);
                this.hasMore = false;
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

            // Build card HTML
            card.innerHTML = `
                <button class="favorite-btn" data-service-id="${service.id}" data-service-title="${title}" aria-label="Add to favorites" style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10;">
                    <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                ${service.featured_image ? `
                    <div class="service-card-image-wrapper" style="position: relative;">
                        <a href="/service/${service.id}/" class="service-card-image-link">
                            <img src="${service.featured_image.thumbnail}" alt="${title}" class="service-card-image" loading="lazy">
                        </a>
                        ${isPromoted ? '<span class="sponsored-badge">Sponsored</span>' : ''}
                    </div>
                ` : ''}

                <div class="service-card-content">
                    <h3 class="service-card-title">
                        <a href="/service/${service.id}/">${title}</a>
                    </h3>

                    <div class="service-card-meta">
                        ${service.service_direction ? `<span class="service-category">${service.service_direction}</span>` : ''}
                        ${service.coverage_area ? `<span class="service-area">${service.coverage_area}</span>` : ''}
                    </div>

                    ${service.price_value ? `
                        <div class="service-card-price">
                            ${service.price_value} ${service.currency || 'GEL'}
                        </div>
                    ` : ''}

                    <div class="service-card-footer">
                        <a href="/service/${service.id}/" class="btn btn-secondary btn-sm">View Details</a>
                        ${service.phone ? `<a href="tel:${service.phone}" class="btn btn-primary btn-sm"><span class="btn-icon">📞</span> Call</a>` : ''}
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

                if (typeof Favorites !== 'undefined') {
                    Favorites.toggle(serviceId, serviceTitle);
                    btn.classList.toggle('is-favorited');
                }
            }
        });
    });

})(jQuery);
