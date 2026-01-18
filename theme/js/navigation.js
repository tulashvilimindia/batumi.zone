/**
 * Navigation JS
 * Handles mobile menu toggle
 *
 * @package Batumi_Theme
 */

(function() {
    'use strict';

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        var menuToggle = document.querySelector('.menu-toggle');
        var navMenu = document.querySelector('#primary-menu');

        if (!menuToggle) {
            return;
        }

        menuToggle.addEventListener('click', function() {
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';

            menuToggle.setAttribute('aria-expanded', !expanded);

            if (navMenu) {
                navMenu.classList.toggle('toggled');

                // For accessibility
                if (navMenu.classList.contains('toggled')) {
                    navMenu.style.display = 'flex';
                } else {
                    navMenu.style.display = '';
                }
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            var isClickInside = document.querySelector('.main-navigation').contains(event.target);

            if (!isClickInside && navMenu && navMenu.classList.contains('toggled')) {
                navMenu.classList.remove('toggled');
                navMenu.style.display = '';
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close mobile menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && navMenu && navMenu.classList.contains('toggled')) {
                navMenu.classList.remove('toggled');
                navMenu.style.display = '';
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

})();

/**
 * Filters Toggle for Mobile (Archive Page)
 */
document.addEventListener('DOMContentLoaded', function() {
    var filtersToggle = document.querySelector('.filters-toggle');
    var filtersPanel = document.getElementById('filters-panel');

    if (filtersToggle && filtersPanel) {
        filtersToggle.addEventListener('click', function() {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            filtersPanel.classList.toggle('active');
        });

        // Close filters when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 768) {
                var isClickInside = document.querySelector('.filters-sidebar').contains(event.target);
                if (!isClickInside && filtersPanel.classList.contains('active')) {
                    filtersPanel.classList.remove('active');
                    filtersToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }
});
