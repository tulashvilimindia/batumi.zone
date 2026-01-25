/**
 * Favorites System - localStorage-based favorites management
 * Handles: adding/removing favorites, counter updates, toast notifications
 */

(function() {
    'use strict';

    // Toast notification system
    const Toast = {
        container: null,

        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                this.container.setAttribute('aria-live', 'polite');
                this.container.setAttribute('aria-atomic', 'true');
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'success') {
            this.init();

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;

            this.container.appendChild(toast);

            // Trigger animation
            setTimeout(() => toast.classList.add('toast-show'), 10);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('toast-show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };

    // Favorites manager
    const Favorites = {
        storageKey: 'batumi_favorites',
        metaKey: 'batumi_favorites_meta',

        // Get all favorite IDs
        getAll() {
            try {
                const data = localStorage.getItem(this.storageKey);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                console.error('Error reading favorites from localStorage:', e);
                return [];
            }
        },

        // Get metadata for favorites
        getMeta() {
            try {
                const data = localStorage.getItem(this.metaKey);
                return data ? JSON.parse(data) : {};
            } catch (e) {
                console.error('Error reading favorites meta from localStorage:', e);
                return {};
            }
        },

        // Save favorites to localStorage
        save(favorites) {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(favorites));
                return true;
            } catch (e) {
                console.error('Error saving favorites to localStorage:', e);
                Toast.show('Failed to save favorite', 'error');
                return false;
            }
        },

        // Save metadata
        saveMeta(meta) {
            try {
                localStorage.setItem(this.metaKey, JSON.stringify(meta));
                return true;
            } catch (e) {
                console.error('Error saving favorites meta to localStorage:', e);
                return false;
            }
        },

        // Check if service is favorited
        has(serviceId) {
            const favorites = this.getAll();
            return favorites.includes(parseInt(serviceId));
        },

        // Add service to favorites
        add(serviceId, serviceTitle = '', serviceData = null) {
            const favorites = this.getAll();
            const id = parseInt(serviceId);

            if (!favorites.includes(id)) {
                favorites.push(id);

                // Save metadata with more complete service data
                const meta = this.getMeta();
                const btn = document.querySelector(`[data-service-id="${serviceId}"].favorite-btn`);
                const card = btn ? btn.closest('.service-card') : null;

                meta[id] = {
                    added: new Date().toISOString(),
                    title: serviceTitle,
                    // Try to extract more data from the card if available
                    link: card ? (card.querySelector('.service-card-title a')?.href || '') : '',
                    image: card ? (card.querySelector('.service-card-image')?.src || '') : '',
                    price: card ? (card.querySelector('.service-card-price')?.textContent?.trim() || '') : '',
                    category: card ? (card.querySelector('.service-category')?.textContent?.trim() || '') : ''
                };

                if (this.save(favorites) && this.saveMeta(meta)) {
                    this.updateCounter();
                    this.updateButton(serviceId, true);
                    Toast.show('Added to favorites ❤️', 'success');
                    return true;
                }
            }
            return false;
        },

        // Remove service from favorites
        remove(serviceId) {
            let favorites = this.getAll();
            const id = parseInt(serviceId);
            const index = favorites.indexOf(id);

            if (index > -1) {
                favorites.splice(index, 1);

                // Remove from metadata
                const meta = this.getMeta();
                delete meta[id];

                if (this.save(favorites) && this.saveMeta(meta)) {
                    this.updateCounter();
                    this.updateButton(serviceId, false);
                    Toast.show('Removed from favorites', 'info');
                    return true;
                }
            }
            return false;
        },

        // Toggle favorite
        toggle(serviceId, serviceTitle = '') {
            if (this.has(serviceId)) {
                this.remove(serviceId);
            } else {
                this.add(serviceId, serviceTitle);
            }
        },

        // Update counter in header
        updateCounter() {
            const favorites = this.getAll();
            const count = favorites.length;
            const counter = document.querySelector('.favorites-counter');

            if (counter) {
                counter.textContent = count;
                counter.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            // Update link badge
            const badge = document.querySelector('.favorites-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }
        },

        // Update button state
        updateButton(serviceId, isFavorited) {
            const buttons = document.querySelectorAll(`[data-service-id="${serviceId}"].favorite-btn`);

            buttons.forEach(btn => {
                if (isFavorited) {
                    btn.classList.add('is-favorited');
                    btn.setAttribute('aria-label', 'Remove from favorites');
                    btn.title = 'Remove from favorites';
                } else {
                    btn.classList.remove('is-favorited');
                    btn.setAttribute('aria-label', 'Add to favorites');
                    btn.title = 'Add to favorites';
                }
            });
        },

        // Initialize all buttons on page
        initButtons() {
            const buttons = document.querySelectorAll('.favorite-btn');

            buttons.forEach(btn => {
                const serviceId = btn.getAttribute('data-service-id');
                const serviceTitle = btn.getAttribute('data-service-title') || '';

                // Set initial state
                if (this.has(serviceId)) {
                    btn.classList.add('is-favorited');
                    btn.setAttribute('aria-label', 'Remove from favorites');
                    btn.title = 'Remove from favorites';
                } else {
                    btn.classList.remove('is-favorited');
                    btn.setAttribute('aria-label', 'Add to favorites');
                    btn.title = 'Add to favorites';
                }

                // Add click handler
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggle(serviceId, serviceTitle);
                });
            });

            // Update counter
            this.updateCounter();
        },

        // Initialize favorites system
        init() {
            // Initialize buttons
            this.initButtons();

            // Update counter
            this.updateCounter();

            // Log count for debugging
            const count = this.getAll().length;
            console.log(`Favorites initialized: ${count} items`);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Favorites.init());
    } else {
        Favorites.init();
    }

    // Expose Favorites globally for use in other scripts or console
    window.BatumiFavorites = Favorites;
    window.Favorites = Favorites; // Alias for backwards compatibility

})();
