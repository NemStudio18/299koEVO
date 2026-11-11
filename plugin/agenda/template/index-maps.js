// Index Maps Initialization for Agenda
document.addEventListener('DOMContentLoaded', function () {
    const mapContainers = document.querySelectorAll('.event-mini-map');

    if (mapContainers.length === 0 || typeof L === 'undefined') {
        return;
    }

    mapContainers.forEach(container => {
        let lat = parseFloat(container.dataset.lat);
        let lon = parseFloat(container.dataset.lon);
        const title = container.dataset.title || '';

        const hasCoords = !isNaN(lat) && !isNaN(lon);

        if (!hasCoords) {
            // Default center: Bourges
            lat = 47.081;
            lon = 2.398;
        }

        try {
            const map = L.map(container, {
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                touchZoom: false
            }).setView([lat, lon], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OSM'
            }).addTo(map);

            if (hasCoords) {
                L.marker([lat, lon]).addTo(map);
            }

            // Re-center on click (to fix potential container size issues)
            setTimeout(() => map.invalidateSize(), 500);
        } catch (e) {
            console.error('[Agenda Maps] Error initializing map:', e);
            container.style.display = 'none';
        }
    });

    console.log('[Agenda Maps] ' + mapContainers.length + ' maps initialized.');
});
