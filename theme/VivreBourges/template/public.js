/**
 * JavaScript pour SearchUi (Refonte User-Centric)
 * Gère l'état local des filtres et les interactions riches.
 */
(function () {
    'use strict';

    // État global
    window.searchState = {
        isDirty: false,
        mapFiltering: false
    };

    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Ré-initialiser après chaque swap HTMX
    document.addEventListener('htmx:afterSwap', function (evt) {
        if (evt.detail.target.id === 'SearchUi-home-wrapper') {
            init(true);
        }
    });

    function init(isSwap = false) {
        console.log('SearchUi: Initialisation du JS (isSwap: ' + isSwap + ')...');
        initFilterForm();
        initGeoloc();
        syncCitySelects();
    }

    /**
     * Gestion du Formulaire de Filtres (Sidebar)
     */
    function initFilterForm() {
        const filterForm = document.getElementById('filter-form');
        if (!filterForm) return;

        const dirtyMessage = document.getElementById('dirty-message');
        const applyBtn = document.getElementById('apply-filters-btn');
        const filterInputs = filterForm.querySelectorAll('.filter-input-dirty');
        const mapContainer = document.getElementById('home-map-container');

        // Détection des changements (Dirty State)
        filterInputs.forEach(input => {
            const eventType = (input.type === 'range') ? 'input' : 'change';
            input.addEventListener(eventType, () => {
                setDirty(true, input);
            });
        });

        function setDirty(isDirty, inputChanged) {
            window.searchState.isDirty = isDirty;

            if (isDirty) {
                if (inputChanged) {
                    const group = inputChanged.closest('.filter-group') || inputChanged.closest('label');
                    if (group) {
                        const badge = group.querySelector('.dirty-indicator');
                        if (badge) badge.style.display = 'inline-block';
                    }
                }
                if (dirtyMessage) dirtyMessage.style.display = 'block';
                if (applyBtn) {
                    applyBtn.style.transform = 'scale(1.05)';
                    applyBtn.style.boxShadow = '0 8px 20px rgba(255,140,66,0.4)';
                    applyBtn.style.animation = 'pulse 2s infinite';
                }
            } else {
                document.querySelectorAll('.dirty-indicator').forEach(b => b.style.display = 'none');
                if (dirtyMessage) dirtyMessage.style.display = 'none';
                if (applyBtn) {
                    applyBtn.style.transform = '';
                    applyBtn.style.boxShadow = '';
                    applyBtn.style.animation = '';
                }
            }
        }

        // Mode Carte Passif (Géré par home.tpl)
        /* 
        Logique de déplacement désactivée car la carte est désormais un composant UI passif
        qui ne doit pas déclencher de nouvelle recherche au survol ou au mouvement.
        */

        // Intercepter la requête HTMX
        filterForm.addEventListener('htmx:configRequest', (evt) => {
            /* 
            Logique d'injection de bounds désactivée : la carte est passive.
            Si on souhaitait filtrer par ce qui est visible à l'écran, 
            on ré-activerait cette partie en récupérant l'état local du bouton Carte.
            */
            setDirty(false);
        });

        // Intercepter le submit pour HTMX (fallback)
        filterForm.addEventListener('submit', (e) => {
            setDirty(false);
        });
    }

    /**
     * Géolocalisation (écoute l'événement global de GlobalGeolocation)
     */
    function initGeoloc() {
        // Initial load from localStorage if available
        const cachedLocation = localStorage.getItem('userLocation');
        if (cachedLocation) {
            try {
                const location = JSON.parse(cachedLocation);
                window.userLocation = location;
                updateLocationInputs(location);
            } catch (e) { }
        }

        // Écouter les mises à jour globales
        window.addEventListener('user-location-updated', function (evt) {
            if (evt.detail) {
                updateLocationInputs(evt.detail);
            }
        });

        function updateLocationInputs(location) {
            const latInp = document.getElementById('user-lat');
            const lonInp = document.getElementById('user-lon');
            if (latInp && lonInp) {
                latInp.value = location.lat;
                lonInp.value = location.lon;
            }
        }
    }

    /**
     * Synchronisation des sélecteurs de ville
     */
    function syncCitySelects() {
        const mainCity = document.getElementById('main-city-select');
        const sidebarCity = document.getElementById('city-select');

        if (mainCity && sidebarCity) {
            mainCity.addEventListener('change', () => sidebarCity.value = mainCity.value);
            sidebarCity.addEventListener('change', () => mainCity.value = sidebarCity.value);
        }
    }

    /**
     * Utilitaires globaux
     */
    window.escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    window.formatNumber = (num) => {
        if (!num) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    };

})();
