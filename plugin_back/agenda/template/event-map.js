// Simple Event Map using Leaflet (from search-maps plugin)
document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('event-map-container');

    if (!mapContainer || typeof L === 'undefined') {
        console.log('[Event Map] Container or Leaflet not available');
        return;
    }

    // Get coordinates from data attributes (to be set by controller)
    let lat = parseFloat(mapContainer.dataset.lat);
    let lon = parseFloat(mapContainer.dataset.lon);
    const title = mapContainer.dataset.title || 'Événement';

    const hasCoords = !isNaN(lat) && !isNaN(lon);

    if (!hasCoords) {
        // Default center: Bourges
        lat = 47.081;
        lon = 2.398;
    }

    try {
        // Create map
        const map = L.map(mapContainer).setView([lat, lon], 15);

        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Add marker
        if (hasCoords) {
            L.marker([lat, lon])
                .addTo(map)
                .bindPopup(`<strong>${title}</strong>`)
                .openPopup();
        }

        console.log('[Event Map] Initialized successfully');
    } catch (error) {
        console.error('[Event Map] Initialization error:', error);
        mapContainer.style.display = 'none';
    }
});
