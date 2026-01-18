/**
 * Ads Frontend - Display and Tracking
 * Phase 8.2 - Public Ad Display
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        loadAds();
    });

    function loadAds() {
        const adContainers = document.querySelectorAll('.ad-container');

        adContainers.forEach(container => {
            const apiUrl = container.dataset.apiUrl;

            fetch(apiUrl)
                .then(response => response.json())
                .then(ads => {
                    if (ads && ads.length > 0) {
                        displayAd(container, ads[0]);
                    } else {
                        container.remove(); // No ad available, remove container
                    }
                })
                .catch(error => {
                    console.error('Failed to load ad:', error);
                    container.remove();
                });
        });
    }

    function displayAd(container, ad) {
        const html = `
            <div class="ad-wrapper">
                <div class="ad-label">Advertisement</div>
                <a href="${escapeHtml(ad.link_url)}"
                   class="ad-link"
                   data-ad-id="${ad.id}"
                   target="_blank"
                   rel="noopener nofollow">
                    <img src="${escapeHtml(ad.image_url)}"
                         alt="${escapeHtml(ad.title)}"
                         class="ad-image"
                         loading="lazy">
                </a>
            </div>
        `;

        container.innerHTML = html;

        // Track impression
        trackImpression(ad.id);

        // Track click
        container.querySelector('.ad-link').addEventListener('click', function() {
            trackClick(ad.id);
        });
    }

    function trackImpression(adId) {
        fetch(`/wp-json/batumizone/v1/ads/${adId}/impression`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).catch(error => console.error('Failed to track impression:', error));
    }

    function trackClick(adId) {
        fetch(`/wp-json/batumizone/v1/ads/${adId}/click`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).catch(error => console.error('Failed to track click:', error));
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
