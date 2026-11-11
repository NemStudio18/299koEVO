/**
 * Service Leaflet minimal pour afficher les POIs sur la carte
 */
console.log("SearchUi: Chargement de home-map-minimal.js (Minimalist Fix)...");
window.HomeMapMinimal = (function () {
    'use strict';

    let map = null;
    let userMarker = null;
    let radiusCircle = null;
    let poiMarkers = [];
    let markersClusterGroup = null;
    let isInitialized = false;
    let baseLayers = {};
    let currentLayer = null;
    let pendingPois = null;
    let moveCallbacks = [];

    const FAMILY_COLORS = {
        'shops': '#ff8c42',
        'food': '#e53935',
        'health': '#43a047',
        'services': '#1e88e5',
        'leisure': '#8e24aa',
        'heritage': '#6d4c41',
        'nature': '#2e7d32',
        'emergency': '#d32f2f',
        'education': '#fbc02d',
        'craft': '#8d6e63',
        'offices': '#546e7a',
        'infrastructure': '#b0bec5',
        'other': '#757575'
    };

    /**
     * Initialiser la carte
     */
    function initMap() {
        const mapElement = document.getElementById('home-map');
        const mapContainer = document.getElementById('home-map-container');

        if (!mapElement || !mapContainer) return false;

        if ((map && isInitialized) || mapElement._leaflet_id) {
            if (map) setTimeout(() => map.invalidateSize(), 50);
            return true;
        }

        try {
            if (typeof L === 'undefined') return false;

            map = L.map(mapElement, {
                center: [47.0810, 2.3986],
                zoom: 13,
                zoomControl: true
            });

            // Définition des couches
            baseLayers = {
                'standard': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }),
                'dark': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; CartoDB Dark Matter'
                }),
                'satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EBP, and the GIS User Community'
                })
            };

            currentLayer = baseLayers.standard.addTo(map);

            // Gérer le mouvement de la carte
            map.on('moveend', () => moveCallbacks.forEach(cb => cb()));

            // Gérer le clic sur la carte pour déplacer le marqueur utilisateur
            map.on('click', function (e) {
                const lat = e.latlng.lat;
                const lon = e.latlng.lng;

                updateUserPosition(lat, lon);

                // Mettre à jour les champs cachés
                const latInput = document.getElementById('user-lat');
                const lonInput = document.getElementById('user-lon');
                if (latInput && lonInput) {
                    latInput.value = lat;
                    lonInput.value = lon;
                    window.userLocation = { lat, lon };
                }

                // Si le rayon est actif, le rafraîchir
                const aroundMe = document.getElementById('around-me-checkbox')?.checked;
                const radius = document.getElementById('radius-slider')?.value;
                if (aroundMe && radius) {
                    updateRadiusCircle(lat, lon, parseFloat(radius));

                    // Déclencher une nouvelle recherche
                    const filterForm = document.getElementById('filter-form');
                    if (filterForm && window.htmx) {
                        window.htmx.trigger(filterForm, 'submit');
                    }
                }
            });

            isInitialized = true;

            // === SCROLL LOCK MOBILE ===
            const isMobile = window.innerWidth <= 991 || 'ontouchstart' in window;
            if (isMobile) {
                map.dragging.disable();
                map.scrollWheelZoom.disable();
                if (map.tap) map.tap.disable();

                const mapContainer2 = document.getElementById('home-map-container');
                if (mapContainer2) {
                    const overlay = document.createElement('div');
                    overlay.id = 'map-scroll-lock-overlay';
                    overlay.innerHTML = '<span><i class="fa-solid fa-hand-pointer"></i> Toucher pour interagir avec la carte</span>';
                    mapContainer2.style.position = 'relative';
                    mapContainer2.appendChild(overlay);

                    let lockTimer = null;
                    const lockMap = () => {
                        map.dragging.disable();
                        map.scrollWheelZoom.disable();
                        if (map.tap) map.tap.disable();
                        overlay.classList.remove('map-overlay-hidden');
                    };
                    const resetTimer = () => {
                        clearTimeout(lockTimer);
                        lockTimer = setTimeout(lockMap, 30000);
                    };
                    const unlockMap = () => {
                        overlay.classList.add('map-overlay-hidden');
                        map.dragging.enable();
                        map.scrollWheelZoom.enable();
                        if (map.tap) map.tap.enable();
                        resetTimer();
                    };

                    overlay.addEventListener('click', unlockMap);
                    overlay.addEventListener('touchend', function (e) { e.preventDefault(); unlockMap(); }, { passive: false });
                    map.on('drag', resetTimer);
                    map.on('zoomend', resetTimer);
                }
            }

            const radius = document.getElementById('radius-slider')?.value;
            const aroundMe = document.getElementById('around-me-checkbox')?.checked;
            const lat = document.getElementById('user-lat')?.value;
            const lon = document.getElementById('user-lon')?.value;

            if (lat && lon && parseFloat(lat) !== 0 && parseFloat(lon) !== 0) {
                updateUserPosition(parseFloat(lat), parseFloat(lon));
                if (aroundMe && radius) {
                    updateRadiusCircle(parseFloat(lat), parseFloat(lon), parseFloat(radius));
                }
                
                if (window.currentPois) {
                    updatePoisOnMap(window.currentPois);
                } else if (pendingPois) {
                    updatePoisOnMap(pendingPois);
                }
            }

            document.addEventListener('locationUpdated', () => {
                const newLat = document.getElementById('user-lat')?.value;
                const newLon = document.getElementById('user-lon')?.value;
                if (newLat && newLon && parseFloat(newLat) !== 0) {
                    updateUserPosition(parseFloat(newLat), parseFloat(newLon));
                }
            });
        } catch (error) {
            console.error('Erreur Leaflet:', error);
            return false;
        }
        return true;
    }

    function setLayer(name) {
        if (!map) return;
        const mapEl = document.getElementById('home-map');
        const containerEl = document.getElementById('home-map-container');
        
        if (name === 'dark' || name === 'night') {
            if (mapEl) mapEl.classList.add('map-night-mode');
            if (containerEl) containerEl.classList.add('map-night-mode');
            if (currentLayer !== baseLayers.standard && baseLayers.standard) {
                if (currentLayer) map.removeLayer(currentLayer);
                currentLayer = baseLayers.standard.addTo(map);
            }
        } else {
            if (mapEl) mapEl.classList.remove('map-night-mode');
            if (containerEl) containerEl.classList.remove('map-night-mode');
            if (baseLayers[name]) {
                if (currentLayer) map.removeLayer(currentLayer);
                currentLayer = baseLayers[name].addTo(map);
            }
        }

        document.querySelectorAll('.search-map-layer-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.querySelector(`.search-map-layer-btn[data-layer="${name}"]`) ||
                          document.querySelector(`.search-map-layer-btn.${name}`);
        if (activeBtn) activeBtn.classList.add('active');
    }

    function updateRadiusCircle(lat, lon, radiusKm) {
        if (!map) return;

        updateUserPosition(lat, lon);

        if (radiusCircle) map.removeLayer(radiusCircle);

        radiusCircle = L.circle([lat, lon], {
            radius: radiusKm * 1000,
            color: '#ff8c42',
            fillColor: '#ff8c42',
            fillOpacity: 0.1,
            weight: 2,
            dashArray: '5, 10'
        }).addTo(map);
    }

    function removeRadiusCircle() {
        if (radiusCircle) map.removeLayer(radiusCircle);
        radiusCircle = null;
    }

    function updateUserPosition(lat, lon) {
        if (!map) return;
        if (userMarker) {
            map.removeLayer(userMarker);
            userMarker = null;
        }

        const pulseHtml = '<div class="user-gps-pulse-marker"><div class="user-gps-dot"></div><div class="user-gps-ring"></div></div>';
        const gpsIcon = L.divIcon({
            html: pulseHtml,
            className: 'user-gps-marker-container',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        userMarker = L.marker([lat, lon], {
            icon: gpsIcon,
            zIndexOffset: 1000
        }).addTo(map).bindTooltip("<b>Vous êtes ici</b>", { direction: 'top', offset: [0, -10] });
    }

    function updatePoisOnMap(pois) {
        pendingPois = pois;
        if (!map || !isInitialized) return;

        if (markersClusterGroup) {
            map.removeLayer(markersClusterGroup);
        }

        // Use markerClusterGroup if the library is loaded
        if (typeof L.markerClusterGroup !== 'undefined') {
            markersClusterGroup = L.markerClusterGroup({
                maxClusterRadius: 50,
                showCoverageOnHover: false,
                disableClusteringAtZoom: 18,
                spiderfyOnMaxZoom: true
            });
        } else {
            markersClusterGroup = L.featureGroup();
        }

        poiMarkers.forEach(m => {
            if (!markersClusterGroup) map.removeLayer(m);
        });
        poiMarkers = [];

        if (!pois || pois.length === 0) return;

        const familiesOnMap = new Set();

        pois.forEach(poi => {
            if (poi.lat && poi.lon) {
                const color = FAMILY_COLORS[poi.family] || FAMILY_COLORS.other;
                familiesOnMap.add(poi.family);

                const marker = L.marker([poi.lat, poi.lon], {
                    icon: L.divIcon({
                        className: 'custom-poi-marker',
                        html: `<div class="marker-pin marker-${poi.family || 'other'}">
                                <i class="fa-solid fa-${poi.category_icon || 'location-dot'}"></i>
                               </div>`,
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                        popupAnchor: [0, -35]
                    })
                });

                marker.bindPopup(`<div style="min-width: 150px;">
                    <strong style="color:${color};">${poi.name}</strong><br>
                    <small>${poi.address || ''}</small><br>
                    <a href="${poi.detail_url_final || '#'}" hx-boost="false" style="display:block; margin-top:8px; color:#ff8c42; font-weight:bold;">Voir plus</a>
                </div>`);

                markersClusterGroup.addLayer(marker);
                poiMarkers.push(marker);
            }
        });

        map.addLayer(markersClusterGroup);

        if (poiMarkers.length > 0) {
            const group = new L.featureGroup(poiMarkers);
            // Si on utilise cluster, les bounds du featureGroup sont parfois trompeuses
            // On s'en sert quand même pour recentrer
            map.fitBounds(group.getBounds().pad(0.1), { animate: true });
        }

        updateLegend(familiesOnMap);
    }

    function appendPoisOnMap(newPois) {
        if (!map || !isInitialized || !newPois || newPois.length === 0) return;
        if (!markersClusterGroup) {
            markersClusterGroup = (typeof L.markerClusterGroup !== 'undefined') ? L.markerClusterGroup() : L.featureGroup();
            map.addLayer(markersClusterGroup);
        }

        const familiesOnMap = new Set();
        poiMarkers.forEach(m => {
            // Re-detect existing families to keep legend accurate
            if (m._family) familiesOnMap.add(m._family);
        });

        newPois.forEach(poi => {
            if (poi.lat && poi.lon) {
                const color = FAMILY_COLORS[poi.family] || FAMILY_COLORS.other;
                familiesOnMap.add(poi.family);

                const marker = L.marker([poi.lat, poi.lon], {
                    icon: L.divIcon({
                        className: 'custom-poi-marker',
                        html: `<div class="marker-pin marker-${poi.family || 'other'}">
                                <i class="fa-solid fa-${poi.category_icon || 'location-dot'}"></i>
                               </div>`,
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                        popupAnchor: [0, -35]
                    })
                });
                marker._family = poi.family;

                marker.bindPopup(`<div style="min-width: 150px;">
                    <strong style="color:${color};">${poi.name}</strong><br>
                    <small>${poi.address || ''}</small><br>
                    <a href="${poi.detail_url_final || '#'}" hx-boost="false" style="display:block; margin-top:8px; color:#ff8c42; font-weight:bold;">Voir plus</a>
                </div>`);

                markersClusterGroup.addLayer(marker);
                poiMarkers.push(marker);
            }
        });

        updateLegend(familiesOnMap);
    }

    let legendControl = null;
    function updateLegend(families) {
        if (!map) return;
        if (legendControl) map.removeControl(legendControl);

        if (families.size === 0) return;

        legendControl = L.control({ position: 'bottomleft' });
        legendControl.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');
            div.style.background = 'white';
            div.style.padding = '8px 10px';
            div.style.borderRadius = '8px';
            div.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            div.style.fontSize = '0.75rem';
            div.style.position = 'relative';

            // Bouton fermer
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.title = 'Masquer la l\u00e9gende';
            closeBtn.style.cssText = 'position:absolute;top:4px;right:6px;background:none;border:none;font-size:16px;font-weight:bold;cursor:pointer;color:#888;line-height:1;padding:0;';
            closeBtn.onclick = function (e) { e.stopPropagation(); div.style.display = 'none'; };

            let html = '<strong style="display:block; margin-bottom:5px; margin-right:16px;">L\u00e9gende</strong>';
            families.forEach(f => {
                const color = FAMILY_COLORS[f] || FAMILY_COLORS.other;
                const label = (window.familyTranslations && window.familyTranslations[f]) || f;
                html += `<div style="display:flex; align-items:center; gap:8px; margin-bottom:2px;">
                            <span style="width:12px; height:12px; border-radius:50%; background:${color}; display:inline-block;"></span>
                            <span>${label}</span>
                         </div>`;
            });
            div.innerHTML = html;
            div.appendChild(closeBtn);
            return div;
        };
        legendControl.addTo(map);
    }

    return {
        init: initMap,
        initMap: initMap,
        isInitialized: function () { return isInitialized; },
        updatePois: updatePoisOnMap,
        appendPois: appendPoisOnMap,
        invalidateSize: function () { if (map) map.invalidateSize(); },
        getBounds: function () { return map ? map.getBounds() : null; },
        onMove: function (cb) { moveCallbacks.push(cb); },
        setLayer: setLayer,
        updateRadius: updateRadiusCircle,
        removeRadius: removeRadiusCircle
    };
})();
