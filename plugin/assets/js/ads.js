/**
 * Ad Display and Tracking System
 * Loads ads from API and tracks impressions/clicks
 */

document.addEventListener("DOMContentLoaded", function() {
    const adContainers = document.querySelectorAll(".ad-container");
    
    adContainers.forEach(function(container) {
        const apiUrl = container.dataset.apiUrl;
        
        if (!apiUrl) {
            console.warn("Ad container missing API URL", container);
            return;
        }
        
        // Fetch ad from API
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const ad = data[0];
                    displayAd(container, ad);
                    trackImpression(ad.id);
                } else {
                    container.style.display = "none";
                }
            })
            .catch(error => {
                console.error("Failed to load ad:", error);
                container.style.display = "none";
            });
    });
});

function displayAd(container, ad) {
    const adHtml = `
        <div class="ad-content">
            <div class="ad-label">Advertisement</div>
            <a href="${escapeHtml(ad.link_url)}" 
               target="_blank" 
               rel="noopener sponsored"
               onclick="trackClick(${ad.id}); return true;">
                <img src="${escapeHtml(ad.image_url)}" 
                     alt="${escapeHtml(ad.title)}"
                     style="max-width: 100%; height: auto; display: block;">
            </a>
        </div>
    `;
    
    container.innerHTML = adHtml;
}

function trackImpression(adId) {
    fetch("/wp-json/batumizone/v1/ads/" + adId + "/impression", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        }
    }).catch(error => console.error("Failed to track impression:", error));
}

function trackClick(adId) {
    fetch("/wp-json/batumizone/v1/ads/" + adId + "/click", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        }
    }).catch(error => console.error("Failed to track click:", error));
}

function escapeHtml(text) {
    const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "\: 
