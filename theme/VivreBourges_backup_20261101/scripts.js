/**
 * Vivre Bourges - scripts.js
 * Carte interactive Leaflet, interactions de navigation et filtres dynamiques
 */

document.addEventListener('DOMContentLoaded', function () {
    setupLazyBourgesMap();
    initMobileNav();
    initPoiCarousel();
});

/**
 * Lazy loading de la carte interactive Leaflet (chargement déclenché à l'approche de la zone)
 */
function setupLazyBourgesMap() {
    var mapContainer = document.getElementById('bourges-leaflet-map');
    if (!mapContainer) return;

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    initBourgesMap();
                }
            });
        }, { rootMargin: '250px 0px' });
        observer.observe(mapContainer);
    } else {
        initBourgesMap();
    }
}

/**
 * Initialisation de la carte interactive Leaflet
 */
function initBourgesMap() {
    var mapContainer = document.getElementById('bourges-leaflet-map');
    if (!mapContainer || typeof L === 'undefined') return;

    // Évite l erreur Leaflet si le conteneur est déjà initialisé
    if (mapContainer._leaflet_id) {
        return;
    }

    // Coordonnées du centre historique de Bourges (Cathédrale Saint-Étienne)
    var bourgesCoords = [47.0825, 2.3985];
    var map = L.map('bourges-leaflet-map', {
        center: bourgesCoords,
        zoom: 13,
        zoomControl: true,
        scrollWheelZoom: false
    });

    // Tuiles OpenStreetMap stylisées douces
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Données des POIs
    var dataEl = document.getElementById('bourges-map-data');
    var pois = [];
    if (dataEl && dataEl.textContent) {
        try {
            pois = JSON.parse(dataEl.textContent);
        } catch (e) {
            console.warn('Erreur parsing POIs map:', e);
        }
    }

    // Icône personnalisée pour Bourges
    var customIcon = L.divIcon({
        className: 'bourges-map-pin',
        html: '<div class="pin-inner"><i class="fa-solid fa-location-dot"></i></div>',
        iconSize: [30, 36],
        iconAnchor: [15, 34],
        popupAnchor: [0, -32]
    });

    var markersLayer = L.layerGroup().addTo(map);
    var allMarkers = [];

    // Ajouter les marqueurs
    if (Array.isArray(pois) && pois.length > 0) {
        pois.forEach(function (poi) {
            var lat = parseFloat(poi.lat);
            var lon = parseFloat(poi.lon);
            if (isNaN(lat) || isNaN(lon)) return;

            var popupHtml = '<div class="bourges-poi-popup">' +
                '<h4 class="popup-title">' + escapeHtml(poi.name || 'Lieu') + '</h4>' +
                (poi.category ? '<div class="popup-cat">' + escapeHtml(poi.category) + '</div>' : '') +
                (poi.addr_street ? '<div class="popup-addr"><i class="fa-solid fa-map-pin"></i> ' + escapeHtml(poi.addr_street) + '</div>' : '') +
                '<a href="SearchUi?q=' + encodeURIComponent(poi.name || '') + '" class="popup-link">Voir la fiche</a>' +
                '</div>';

            var marker = L.marker([lat, lon], { icon: customIcon }).bindPopup(popupHtml);
            marker.poiData = poi;
            markersLayer.addLayer(marker);
            allMarkers.push(marker);
        });

        // Adapter la vue si des marqueurs existent
        if (allMarkers.length > 1) {
            var group = new L.featureGroup(allMarkers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    } else {
        // Fallback marqueur sur la Cathédrale de Bourges
        L.marker(bourgesCoords, { icon: customIcon })
            .bindPopup('<b>Cathédrale Saint-Étienne de Bourges</b><br>Patrimoine Mondial de l\'UNESCO')
            .addTo(markersLayer);
    }

    // Filtrage rapide sur la carte
    var filterInput = document.getElementById('map-quick-filter');
    if (filterInput) {
        filterInput.addEventListener('input', function (e) {
            var term = e.target.value.toLowerCase().trim();
            markersLayer.clearLayers();
            allMarkers.forEach(function (m) {
                var p = m.poiData;
                if (!term || (p.name && p.name.toLowerCase().indexOf(term) !== -1) ||
                    (p.category && p.category.toLowerCase().indexOf(term) !== -1) ||
                    (p.addr_street && p.addr_street.toLowerCase().indexOf(term) !== -1)) {
                    markersLayer.addLayer(m);
                }
            });
        });
    }

    // Éviter les soucis de dimensionnement Leaflet
    setTimeout(function () {
        map.invalidateSize();
    }, 400);
}

/**
 * Menu mobile burger
 */
function initMobileNav() {
    var toggle = document.getElementById('nav-toggle-check');
    var nav = document.querySelector('.nav-list');
    if (toggle && nav) {
        toggle.addEventListener('change', function () {
            if (this.checked) {
                nav.classList.add('nav-open');
            } else {
                nav.classList.remove('nav-open');
            }
        });
    }
}

/**
 * Carrousel des adresses et commerces
 */
function initPoiCarousel() {
    var track = document.getElementById('poi-carousel-track');
    var prevBtn = document.getElementById('carousel-prev');
    var nextBtn = document.getElementById('carousel-next');

    if (!track) return;

    var cardWidth = 280; // Largeur moyenne d'une carte avec le gap

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            track.scrollBy({ left: -cardWidth * 2, behavior: 'smooth' });
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            track.scrollBy({ left: cardWidth * 2, behavior: 'smooth' });
        });
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Menu Déroulant Avatar Utilisateur (Mobile Touch & Desktop Click/Hover)
 */
function initUserAvatarMenu() {
    var avatarMenu = document.querySelector('.nav-user-avatar-menu');
    if (!avatarMenu) return;

    var avatarLink = avatarMenu.querySelector('.nav-avatar-link');
    if (!avatarLink) return;

    // Éviter les écouteurs dupliqués
    if (avatarLink.dataset.boundClick === 'true') return;
    avatarLink.dataset.boundClick = 'true';

    avatarLink.addEventListener('click', function (e) {
        // Empêche la redirection immédiate vers /profile pour permettre le dépliage du menu
        e.preventDefault();
        e.stopPropagation();

        var isOpen = avatarMenu.classList.contains('menu-open');
        if (isOpen) {
            avatarMenu.classList.remove('menu-open');
            avatarLink.setAttribute('aria-expanded', 'false');
        } else {
            avatarMenu.classList.add('menu-open');
            avatarLink.setAttribute('aria-expanded', 'true');
        }
    });

    // Clic en dehors pour fermer le menu
    document.addEventListener('click', function (e) {
        if (!avatarMenu.contains(e.target)) {
            avatarMenu.classList.remove('menu-open');
            avatarLink.setAttribute('aria-expanded', 'false');
        }
    });

    // Touche Echap pour fermer
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            avatarMenu.classList.remove('menu-open');
            avatarLink.setAttribute('aria-expanded', 'false');
        }
    });
}

// Relance après chargement et après événements HTMX si présents
document.addEventListener('DOMContentLoaded', initUserAvatarMenu);
document.addEventListener('htmx:afterSwap', initUserAvatarMenu);
