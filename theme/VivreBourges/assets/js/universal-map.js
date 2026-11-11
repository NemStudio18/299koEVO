/**
 * UniversalMap - Standardized Map Manager for 299Ko
 * Wraps Leaflet to provide a consistent API for all plugins.
 * v1.1.0 - Added multi-marker support and clearMarkers()
 */
window.UniversalMap = class UniversalMap {
    constructor(elementId, options = {}) {
        this.elementId = elementId;
        this.options = Object.assign({
            lat: 47.0810,
            lon: 2.3986,
            zoom: 13,
            editable: false,
            markerFamily: 'other',   // Default family class for marker-pin
            markerIcon: 'location-dot', // Default font-awesome icon
            onChange: null // Callback(lat, lon)
        }, options);

        this.map = null;
        this.marker = null; // Single marker (legacy/edit mode)
        this.markers = [];  // Multiple markers
        this.isInitialized = false;

        this.init();
    }

    init() {
        const element = document.getElementById(this.elementId);
        if (!element) {
            console.warn(`UniversalMap: Element #${this.elementId} not found.`);
            return;
        }

        // Initialize Leaflet
        this.map = L.map(this.elementId).setView([this.options.lat, this.options.lon], this.options.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Add initial marker if lat/lon provided and not 0
        if (this.options.lat !== 0 && this.options.lon !== 0) {
            this.setMarker(this.options.lat, this.options.lon);
        }

        // Handle clicks for editing
        if (this.options.editable) {
            this.map.on('click', (e) => {
                this.setMarker(e.latlng.lat, e.latlng.lng);
                if (typeof this.options.onChange === 'function') {
                    this.options.onChange(e.latlng.lat, e.latlng.lng);
                }
            });
        }

        this.isInitialized = true;

        // Fix display issues when container is resized or shown
        setTimeout(() => {
            if (this.map) this.map.invalidateSize();
        }, 200);
    }

    setMarker(lat, lon) {
        if (!this.map) return;

        const customIcon = L.divIcon({
            className: 'custom-poi-marker',
            html: `<div class="marker-pin marker-${this.options.markerFamily}">
                    <i class="fa-solid fa-${this.options.markerIcon}"></i>
                   </div>`,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
            popupAnchor: [0, -35]
        });

        if (this.marker) {
            this.marker.setLatLng([lat, lon]);
            this.marker.setIcon(customIcon);
        } else {
            this.marker = L.marker([lat, lon], {
                icon: customIcon,
                draggable: this.options.editable
            }).addTo(this.map);

            if (this.options.editable) {
                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    if (typeof this.options.onChange === 'function') {
                        this.options.onChange(pos.lat, pos.lng);
                    }
                });
            }
        }
        this.map.panTo([lat, lon]);
    }

    addMarker(lat, lon, popupHtml = '', options = {}) {
        if (!this.map) return null;

        const family = options.markerFamily || this.options.markerFamily;
        const icon = options.markerIcon || this.options.markerIcon;

        const customIcon = L.divIcon({
            className: 'custom-poi-marker',
            html: `<div class="marker-pin marker-${family}">
                    <i class="fa-solid fa-${icon}"></i>
                   </div>`,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
            popupAnchor: [0, -35]
        });

        const marker = L.marker([lat, lon], { icon: customIcon }).addTo(this.map);
        if (popupHtml) {
            marker.bindPopup(popupHtml);
        }
        this.markers.push(marker);
        return marker;
    }

    clearMarkers() {
        if (!this.map) return;
        this.markers.forEach(m => this.map.removeLayer(m));
        this.markers = [];
        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }
    }

    fitMarkers() {
        if (!this.map || this.markers.length === 0) return;
        const group = new L.featureGroup(this.markers);
        this.map.fitBounds(group.getBounds().pad(0.1));
    }

    // Force refresh (useful for tabs/modals)
    refresh() {
        if (this.map) {
            this.map.invalidateSize();
        }
    }

    /**
     * Toggle map interaction locking
     * @param {boolean} locked 
     */
    toggleLock(locked) {
        if (!this.map) return;
        if (locked) {
            this.map.dragging.disable();
            this.map.touchZoom.disable();
            this.map.doubleClickZoom.disable();
            this.map.scrollWheelZoom.disable();
            this.map.boxZoom.disable();
            this.map.keyboard.disable();
            if (this.map.tap) this.map.tap.disable();
            this.map.getContainer().style.cursor = 'default';
        } else {
            this.map.dragging.enable();
            this.map.touchZoom.enable();
            this.map.doubleClickZoom.enable();
            this.map.scrollWheelZoom.enable();
            this.map.boxZoom.enable();
            this.map.keyboard.enable();
            if (this.map.tap) this.map.tap.enable();
            this.map.getContainer().style.cursor = 'grab';
        }
    }

    /**
     * Set the tile layer (standard, satellite, etc.)
     * @param {string} type 'standard' | 'satellite'
     */
    setLayer(type) {
        if (!this.map) return;

        // Gérer la classe CSS du mode nuit sur le conteneur Leaflet
        const container = this.map.getContainer();
        if (type === 'night') {
            container.classList.add('map-night-mode');
        } else {
            container.classList.remove('map-night-mode');
        }

        // Remove existing tile layers
        this.map.eachLayer((layer) => {
            if (layer instanceof L.TileLayer) {
                this.map.removeLayer(layer);
            }
        });

        let url = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'; // Default Standard
        let options = {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        };

        if (type === 'satellite') {
            // Esri World Imagery (Free, good quality)
            url = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
            options.attribution = 'Tiles © Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community';
        } else if (type === 'night') {
            // Mode Nuit simulé via filtre CSS sur tuiles OSM (aucun token ni clé API requis, aucun filigrane)
            url = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            options.attribution = '© OpenStreetMap contributors (Mode Nuit)';
        }

        L.tileLayer(url, options).addTo(this.map);
    }

    /**
     * Enable/Disable positioning mode (click to set marker)
     * @param {boolean} enabled 
     * @param {function} callback (lat, lon)
     */
    setPositioningMode(enabled, callback) {
        if (!this.map) return;

        // Remove previous click handler if any
        this.map.off('click');

        if (enabled && typeof callback === 'function') {
            this.map.getContainer().style.cursor = 'crosshair';
            this.map.on('click', (e) => {
                this.setMarker(e.latlng.lat, e.latlng.lng);
                callback(e.latlng.lat, e.latlng.lng);
            });
        } else {
            this.map.getContainer().style.cursor = '';
        }
    }
}
console.log('UniversalMap v1.1.0 loaded');
