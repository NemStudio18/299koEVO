/**
 * Service Leaflet minimal pour afficher les POIs sur la carte
 */
(function () {
    'use strict';

    let map = null;
    let userMarker = null;
    let poiMarkers = []; // Keep references for feature bounds
    let markersClusterGroup = null; // NEW: cluster container
    let userPosition = null;
    let clickToPositionEnabled = false;
    let isInitialized = false;
    let radiusCircle = null;
    let currentRadius = 5.0; // Rayon en km (par défaut)
    let useGPS = true;
    let isLocked = false;
    let baseLayer = null;
    let satelliteLayer = null;
    let nightLayer = null;
    let currentLayerType = 'standard'; // 'standard', 'satellite', 'night'

    /**
     * Initialiser la carte (uniquement quand le conteneur est visible)
     */
    function initMap() {
        const mapElement = document.getElementById('home-map');
        const mapContainer = document.getElementById('home-map-container');

        // Vérifier que l'élément existe et que le conteneur est visible
        if (!mapElement || !mapContainer) {
            return false;
        }

        // Si le conteneur est caché, ne pas initialiser (Leaflet ne peut pas initialiser dans un élément caché)
        if (mapContainer.style.display === 'none' && window.getComputedStyle(mapContainer).display === 'none') {
            return false;
        }

        // Si déjà initialisé, juste invalider la taille
        if (map && isInitialized) {
            setTimeout(function () {
                map.invalidateSize();
            }, 100);
            return true;
        }

        if (map) {
            return true;
        }

        // Position par défaut (Bourges)
        let centerLat = 47.0810;
        let centerLon = 2.3986;
        let zoom = 13;

        try {
            // Créer la carte
            map = L.map(mapElement, {
                center: [centerLat, centerLon],
                zoom: zoom,
                zoomControl: true
            });

            // Initialiser les couches
            baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                subdomains: ['a', 'b', 'c']
            });
            baseLayer.addTo(map);

            // Utiliser la géolocalisation depuis public.js (déjà demandée automatiquement)
            // ou depuis les champs cachés user-lat/user-lon
            const latInputMap = document.getElementById('user-lat');
            const lonInputMap = document.getElementById('user-lon');
            if (latInputMap && lonInputMap && latInputMap.value && lonInputMap.value) {
                // Position déjà disponible depuis public.js
                setUserPosition(parseFloat(latInputMap.value), parseFloat(lonInputMap.value));
            } else if (window.userLocation && window.userLocation.lat && window.userLocation.lon) {
                // Position depuis window.userLocation (public.js)
                setUserPosition(window.userLocation.lat, window.userLocation.lon);
            }

            // Gérer le clic sur la carte pour positionner (toujours actif si activé)
            map.on('click', function (e) {
                if (clickToPositionEnabled) {
                    setUserPosition(e.latlng.lat, e.latlng.lng);
                }
            });

            isInitialized = true;

            // Écouter les mises à jour globales
            window.addEventListener('user-location-updated', function (evt) {
                if (evt.detail && evt.detail.lat) {
                    setUserPosition(evt.detail.lat, evt.detail.lon);
                }
            });

            // Traiter les POIs en attente si disponibles
            if (pendingPois) {
                updatePoisOnMap(pendingPois);
            }

            return true;
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la carte:', error);
            return false;
        }
    }

    /**
     * Obtenir la géolocalisation de l'utilisateur
     */
    function getGeolocation() {
        if (!useGPS) {
            return;
        }

        if (!navigator.geolocation) {
            console.log('Géolocalisation non supportée');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                setUserPosition(lat, lon);
            },
            function (error) {
                console.log('Erreur géolocalisation:', error.message);
            }
        );
    }

    /**
     * Définir la position de l'utilisateur
     */
    function setUserPosition(lat, lon) {
        userPosition = { lat: lat, lon: lon };

        // Mettre à jour les champs cachés pour HTMX
        const userLatInput = document.getElementById('user-lat');
        const userLonInput = document.getElementById('user-lon');
        if (userLatInput) {
            userLatInput.value = lat;
        }
        if (userLonInput) {
            userLonInput.value = lon;
        }

        // Supprimer l'ancien marqueur si existe
        if (userMarker) {
            map.removeLayer(userMarker);
        }

        // Créer le marqueur utilisateur avec l'icône du thème Lumière
        // Utiliser le chemin depuis la racine du site
        const basePath = window.location.pathname.split('/').filter(p => p && p !== 'search' && p !== 'plugin').slice(0, -1).join('/') || '';
        const markerIconUrl = (basePath ? basePath + '/' : '/') + 'theme/Lumiere/leaflet-marker.png';

        userMarker = L.marker([lat, lon], {
            icon: L.icon({
                iconUrl: markerIconUrl,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            })
        }).addTo(map);

        // Mettre à jour le cercle de rayon
        updateRadiusCircle();

        // Centrer la carte sur la position utilisateur si pas lock
        if (!isLocked) {
            map.setView([lat, lon], 13);
        }

        // Mettre à jour les POIs si déjà chargés
        if (window.currentPois && window.currentPois.length > 0) {
            updatePoisOnMap(window.currentPois);
        }
    }

    /**
     * Mettre à jour le cercle de rayon autour de la position utilisateur
     */
    function updateRadiusCircle() {
        if (!map || !userPosition) {
            return;
        }

        // Supprimer l'ancien cercle
        if (radiusCircle) {
            map.removeLayer(radiusCircle);
        }

        // Créer un nouveau cercle avec le rayon actuel
        radiusCircle = L.circle([userPosition.lat, userPosition.lon], {
            radius: currentRadius * 1000, // Convertir km en mètres
            color: '#ff8c42',
            fillColor: '#ff8c42',
            fillOpacity: 0.2,
            weight: 2
        }).addTo(map);
    }

    /**
     * Filtrer les POIs dans le rayon
     */
    function filterPoisInRadius(pois) {
        if (!userPosition) {
            return pois;
        }

        return pois.filter(function (poi) {
            if (!poi.lat || !poi.lon) {
                return false;
            }
            const distance = calculateDistance(
                userPosition.lat,
                userPosition.lon,
                poi.lat,
                poi.lon
            );
            return distance <= currentRadius;
        });
    }

    /**
     * Calculer la distance entre deux points (en km)
     */
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Rayon de la Terre en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    let pendingPois = null;
    let skipRadiusFilter = true; // Par défaut, afficher tous les POIs chargés sans filtrer par rayon client

    /**
     * Mettre à jour les POIs sur la carte
     */
    function updatePoisOnMap(pois) {
        // Stocker pour une utilisation ultérieure si la carte n'est pas encore prête
        pendingPois = pois;

        // Vérifier que la carte est initialisée
        if (!map || !isInitialized) {
            // Pas de log d'erreur ici car c'est un état attendu au chargement initial
            return;
        }

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

        // Supprimer les anciens marqueurs
        poiMarkers.forEach(function (marker) {
            if (!markersClusterGroup) map.removeLayer(marker);
        });
        poiMarkers = [];

        // Filtrer les POIs dans le rayon si on a une position utilisateur et que le filtre est actif
        let poisToDisplay = pois;
        if (userPosition && !skipRadiusFilter) {
            poisToDisplay = filterPoisInRadius(pois);
        }

        // Ajouter les nouveaux marqueurs
        console.log('searchEVO: Mise à jour de ' + poisToDisplay.length + ' POIs sur la carte (SkipRadius: ' + skipRadiusFilter + ')');

        poisToDisplay.forEach(function (poi) {
            if (poi.lat && poi.lon) {
                // Add fallback mapping from color to family class if needed, or use family directly
                const familyClass = poi.family ? `marker-${poi.family}` : 'marker-other';
                const iconClass = poi.category_icon || 'location-dot';

                const marker = L.marker([poi.lat, poi.lon], {
                    icon: L.divIcon({
                        className: 'custom-poi-marker',
                        html: `<div class="marker-pin ${familyClass}">
                                <i class="fa-solid fa-${iconClass}"></i>
                               </div>`,
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                        popupAnchor: [0, -35]
                    })
                });

                // Popup enrichi avec plus d'informations
                let popupContent = '<div class="poi-popup">';

                // Nom du POI
                popupContent += '<div class="poi-popup-title">' +
                    (poi.name && poi.name !== 'POI sans nom' ? poi.name : (poi.id ? 'Point d\'intérêt #' + poi.id : 'Lieu sans nom')) + '</div>';

                // Adresse - toujours essayer d'afficher
                if (poi.address && poi.address.trim()) {
                    popupContent += '<div class="poi-popup-info">';
                    popupContent += '<i class="fa-solid fa-location-dot poi-popup-icon"></i>';
                    popupContent += '<span>' + poi.address + '</span>';
                    popupContent += '</div>';
                }

                // Téléphone
                if (poi.phone && poi.phone.trim()) {
                    popupContent += '<div class="poi-popup-info">';
                    popupContent += '<i class="fa-solid fa-phone poi-popup-icon"></i>';
                    popupContent += '<a href="tel:' + poi.phone + '" class="poi-popup-link">' + poi.phone + '</a>';
                    popupContent += '</div>';
                }

                // Distance - calculer si on a la position utilisateur
                let distanceText = poi.distance_formatted || null;
                if (!distanceText && userPosition && poi.lat && poi.lon) {
                    const distance = calculateDistance(userPosition.lat, userPosition.lon, poi.lat, poi.lon);
                    if (distance < 1) {
                        distanceText = Math.round(distance * 1000) + ' m';
                    } else {
                        distanceText = distance.toFixed(1) + ' km';
                    }
                }
                if (distanceText) {
                    popupContent += '<div class="poi-popup-info">';
                    popupContent += '<i class="fa-solid fa-route poi-popup-icon"></i>';
                    popupContent += '<span>' + distanceText + '</span>';
                    popupContent += '</div>';
                }

                // Lien vers la fiche détail - TOUJOURS afficher si on a un ID
                const detailUrl = poi.detail_url_final || poi.detail_url;
                let finalUrl = detailUrl;
                if (!finalUrl && poi.id) {
                    finalUrl = '/searchEVO/poi/' + poi.id;
                }

                if (finalUrl) {
                    popupContent += '<div class="poi-popup-footer">';
                    popupContent += '<a href="' + finalUrl + '" class="poi-popup-button">';
                    popupContent += '<i class="fa-solid fa-arrow-right"></i>Voir la fiche complète';
                    popupContent += '</a>';
                    popupContent += '</div>';
                } else {
                    console.warn('Popup POI - Pas d\'URL de détail pour:', poi.name || poi.id);
                }

                popupContent += '</div>';

                // Log pour déboguer
                console.log('Popup POI créé:', {
                    id: poi.id,
                    name: poi.name,
                    hasAddress: !!poi.address,
                    address: poi.address,
                    hasDetailUrl: !!finalUrl,
                    detailUrl: finalUrl
                });

                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    minWidth: 200,
                    className: 'poi-popup-custom'
                });

                markersClusterGroup.addLayer(marker);
                poiMarkers.push(marker);
            }
        });

        map.addLayer(markersClusterGroup);

        // Ajuster la vue pour afficher tous les POIs si on a des marqueurs
        if (poiMarkers.length > 0 && map && !isLocked) {
            try {
                const group = new L.featureGroup(poiMarkers);
                const bounds = group.getBounds();
                if (bounds.isValid()) {
                    // Si on a une position utilisateur, l'inclure dans les bounds
                    if (userPosition) {
                        bounds.extend([userPosition.lat, userPosition.lon]);
                    }
                    map.fitBounds(bounds.pad(0.1));
                }
            } catch (error) {
                console.error('Erreur lors de l\'ajustement de la vue:', error);
            }
        } else if (userPosition && map && !isLocked) {
            // Si pas de POIs mais une position utilisateur, centrer sur l'utilisateur
            map.setView([userPosition.lat, userPosition.lon], 13);
        }
    }

    /**
     * Activer/désactiver le mode "cliquer pour positionner"
     */
    function toggleClickToPosition(enabled) {
        clickToPositionEnabled = enabled;
        if (map) {
            if (enabled) {
                map.getContainer().style.cursor = 'crosshair';
            } else {
                map.getContainer().style.cursor = '';
            }
        }
    }

    /**
     * Activer/désactiver l'utilisation du GPS
     */
    function toggleGPS(enabled) {
        useGPS = enabled;
        if (!enabled) {
            // Si on désactive le GPS, activer automatiquement le mode "cliquer pour positionner"
            clickToPositionEnabled = true;
            const checkbox = document.getElementById('click-to-position');
            if (checkbox) {
                checkbox.checked = true;
            }
            if (map) {
                map.getContainer().style.cursor = 'crosshair';
            }
        } else {
            // Réactiver la géolocalisation
            getGeolocation();
        }
    }

    /**
     * Changer le rayon de recherche
     */
    function setRadius(radiusKm) {
        currentRadius = radiusKm;
        updateRadiusCircle();

        // Re-filtrer et mettre à jour les POIs
        if (window.currentPois && window.currentPois.length > 0) {
            updatePoisOnMap(window.currentPois);
        }
    }

    /**
     * Changer le type de couche (standard, satellite, nuit)
     */
    function setLayerType(type) {
        if (!map) return;

        // Supprimer les anciennes couches
        if (baseLayer && map.hasLayer(baseLayer)) {
            map.removeLayer(baseLayer);
        }
        if (satelliteLayer && map.hasLayer(satelliteLayer)) {
            map.removeLayer(satelliteLayer);
        }
        if (nightLayer && map.hasLayer(nightLayer)) {
            map.removeLayer(nightLayer);
        }

        currentLayerType = type;

        switch (type) {
            case 'satellite':
                if (!satelliteLayer) {
                    satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '© Esri',
                        maxZoom: 19
                    });
                }
                satelliteLayer.addTo(map);
                break;
            case 'night':
                if (!nightLayer) {
                    nightLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        attribution: '© OpenStreetMap contributors © CARTO',
                        subdomains: 'abcd',
                        maxZoom: 19
                    });
                }
                nightLayer.addTo(map);
                break;
            default: // 'standard'
                if (!baseLayer) {
                    baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19,
                        subdomains: ['a', 'b', 'c']
                    });
                }
                baseLayer.addTo(map);
                break;
        }
    }

    /**
     * Recentrer la carte sur le marker utilisateur
     * Demande la géolocalisation si pas encore obtenue (interaction utilisateur)
     */
    function recenterOnUser() {
        if (map && userPosition) {
            map.setView([userPosition.lat, userPosition.lon], 13);
        } else if (map && useGPS) {
            // Demander la géolocalisation lors de l'interaction utilisateur
            getGeolocation();
        }
    }

    /**
     * Lock/délock la carte
     */
    function toggleLock(locked) {
        isLocked = locked;
        if (map) {
            if (locked) {
                map.dragging.disable();
                map.touchZoom.disable();
                map.doubleClickZoom.disable();
                map.scrollWheelZoom.disable();
                map.boxZoom.disable();
                map.keyboard.disable();
                if (map.tap) map.tap.disable();
            } else {
                map.dragging.enable();
                map.touchZoom.enable();
                map.doubleClickZoom.enable();
                map.scrollWheelZoom.enable();
                map.boxZoom.enable();
                map.keyboard.enable();
                if (map.tap) map.tap.enable();
            }
        }
    }

    // Exposer les fonctions publiques (uniquement si non déjà défini par SearchUi)
    if (!window.HomeMapMinimal || typeof window.HomeMapMinimal.removeRadius !== 'function') {
        window.HomeMapMinimal = {
        init: function () {
            // Si déjà initialisé, retourner true
            if (map && isInitialized) {
                return true;
            }

            // Forcer l'initialisation même si le conteneur est caché
            const mapElement = document.getElementById('home-map');
            if (!mapElement) {
                return false;
            }

            // Position par défaut (Bourges)
            let centerLat = 47.0810;
            let centerLon = 2.3986;
            let zoom = 13;

            try {
                map = L.map(mapElement, {
                    center: [centerLat, centerLon],
                    zoom: zoom,
                    zoomControl: true
                });

                // Initialiser les couches
                baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a', 'b', 'c']
                });
                baseLayer.addTo(map);

                // Gérer le clic sur la carte pour positionner (toujours actif si activé)
                map.on('click', function (e) {
                    if (clickToPositionEnabled) {
                        setUserPosition(e.latlng.lat, e.latlng.lng);
                    }
                });

                // Utiliser la géolocalisation depuis public.js (déjà demandée automatiquement)
                // ou depuis les champs cachés user-lat/user-lon
                const latInputInit = document.getElementById('user-lat');
                const lonInputInit = document.getElementById('user-lon');
                if (latInputInit && lonInputInit && latInputInit.value && lonInputInit.value) {
                    // Position déjà disponible depuis public.js
                    setUserPosition(parseFloat(latInputInit.value), parseFloat(lonInputInit.value));
                } else if (window.userLocation && window.userLocation.lat && window.userLocation.lon) {
                    // Position depuis window.userLocation (public.js)
                    setUserPosition(window.userLocation.lat, window.userLocation.lon);
                }

                // Gérer les contrôles (les checkboxes ont été remplacés par des boutons)

                const radiusSlider = document.getElementById('radius-slider');
                if (radiusSlider) {
                    radiusSlider.addEventListener('input', function () {
                        const radius = parseFloat(this.value);
                        const label = document.getElementById('radius-label');
                        if (label) {
                            label.textContent = radius.toFixed(1) + ' km';
                        }
                        setRadius(radius);
                    });
                }

                const btnStandard = document.getElementById('btn-standard');
                if (btnStandard) {
                    btnStandard.addEventListener('click', function () {
                        document.getElementById('btn-satellite').classList.remove('active');
                        document.getElementById('btn-night').classList.remove('active');
                        this.classList.add('active');
                        setLayerType('standard');
                    });
                }

                const btnSatellite = document.getElementById('btn-satellite');
                if (btnSatellite) {
                    btnSatellite.addEventListener('click', function () {
                        const isActive = this.classList.contains('active');
                        document.getElementById('btn-standard').classList.remove('active');
                        document.getElementById('btn-night').classList.remove('active');
                        if (isActive) {
                            this.classList.remove('active');
                            setLayerType('standard');
                        } else {
                            this.classList.add('active');
                            setLayerType('satellite');
                        }
                    });
                }

                const btnNight = document.getElementById('btn-night');
                if (btnNight) {
                    btnNight.addEventListener('click', function () {
                        const isActive = this.classList.contains('active');
                        document.getElementById('btn-standard').classList.remove('active');
                        document.getElementById('btn-satellite').classList.remove('active');
                        if (isActive) {
                            this.classList.remove('active');
                            setLayerType('standard');
                        } else {
                            this.classList.add('active');
                            setLayerType('night');
                        }
                    });
                }

                const btnClickPosition = document.getElementById('btn-click-position');
                if (btnClickPosition) {
                    btnClickPosition.addEventListener('click', function () {
                        const isActive = this.classList.contains('active');
                        if (isActive) {
                            this.classList.remove('active');
                            toggleClickToPosition(false);
                        } else {
                            this.classList.add('active');
                            toggleClickToPosition(true);
                        }
                    });
                }

                const btnCloseMap = document.getElementById('btn-close-map');
                if (btnCloseMap) {
                    btnCloseMap.addEventListener('click', function () {
                        const mapContainer = document.getElementById('home-map-container');
                        if (mapContainer) {
                            mapContainer.style.display = 'none';
                        }
                    });
                }

                const btnRecenter = document.getElementById('btn-recenter');
                if (btnRecenter) {
                    btnRecenter.addEventListener('click', function () {
                        recenterOnUser();
                    });
                }

                const btnLock = document.getElementById('btn-lock');
                if (btnLock) {
                    btnLock.addEventListener('click', function () {
                        const isLocked = this.classList.contains('active');
                        if (isLocked) {
                            this.classList.remove('active');
                            this.innerHTML = '<i class="fas fa-unlock"></i>';
                            toggleLock(false);
                        } else {
                            this.classList.add('active');
                            this.innerHTML = '<i class="fas fa-lock"></i>';
                            toggleLock(true);
                        }
                    });
                }

                isInitialized = true;
                // Utiliser la géolocalisation depuis public.js (déjà demandée automatiquement)
                // ou depuis les champs cachés user-lat/user-lon
                const latInput = document.getElementById('user-lat');
                const lonInput = document.getElementById('user-lon');
                if (latInput && lonInput && latInput.value && lonInput.value) {
                    // Position déjà disponible depuis public.js
                    setUserPosition(parseFloat(latInput.value), parseFloat(lonInput.value));
                } else if (window.userLocation && window.userLocation.lat && window.userLocation.lon) {
                    // Position depuis window.userLocation (public.js)
                    setUserPosition(window.userLocation.lat, window.userLocation.lon);
                }
                return true;
            } catch (error) {
                console.error('Erreur lors de l\'initialisation de la carte:', error);
                map = null;
                isInitialized = false;
                return false;
            }
        },
        isInitialized: function () {
            return map !== null && isInitialized;
        },
        updatePois: updatePoisOnMap,
        toggleClickToPosition: toggleClickToPosition,
        toggleGPS: toggleGPS,
        setRadius: setRadius,
        setLayerType: setLayerType,
        recenterOnUser: recenterOnUser,
        toggleLock: toggleLock,
        getUserPosition: function () { return userPosition; },
        invalidateSize: function () {
            if (map) {
                setTimeout(function () {
                    map.invalidateSize();
                }, 100);
            }
        }
    };
    }
})();

