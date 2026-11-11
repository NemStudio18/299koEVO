(function () {
    function initMapPicker() {
        const mapEl = document.getElementById("event-map-picker");
        if (!mapEl) return;

        const latInput = document.querySelector('input[name="latitude"]');
        const lonInput = document.querySelector('input[name="longitude"]');

        if (!latInput || !lonInput) return;

        // Default to Bourges if no coords
        const defaultLat = parseFloat(latInput.value) || 47.081;
        const defaultLon = parseFloat(lonInput.value) || 2.398;
        const zoom = (latInput.value && lonInput.value) ? 15 : 12;

        if (typeof L === 'undefined') {
            console.error("[Agenda] Leaflet library not found. Map picker cannot be initialized.");
            return;
        }

        const map = L.map(mapEl).setView([defaultLat, defaultLon], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = null;

        if (latInput.value && lonInput.value) {
            marker = L.marker([defaultLat, defaultLon]).addTo(map);
        }

        map.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lon = e.latlng.lng.toFixed(6);

            latInput.value = lat;
            lonInput.value = lon;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        console.log("[Agenda] Map picker initialized");
    }

    if (document.readyState === "complete") {
        initMapPicker();
    } else {
        window.addEventListener("load", initMapPicker);
    }
})();
