/**
 * Vivre Bourges - scripts.js
 * Carte interactive Leaflet, interactions de navigation et filtres dynamiques
 */

document.addEventListener('DOMContentLoaded', function () {
    setupLazyBourgesMap();
    initMobileNav();
    initPoiCarousel();
    initCivicSlider();
    initFlashMessages();
    initBlogImageZoom();
});

/**
 * Gestion de la fermeture et disparition progressive des alertes / messages flash
 */
function initFlashMessages() {
    document.querySelectorAll('.msg, .alert').forEach(function (item, index) {
        var closeBtn = item.querySelector('.msg-button-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                item.style.opacity = '0';
                item.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    if (item.parentNode) item.remove();
                }, 300);
            });
        }

        // Auto-dismiss après 8 secondes pour ne pas gêner la navigation
        setTimeout(function () {
            if (item && item.parentNode) {
                item.style.opacity = '0';
                item.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    if (item && item.parentNode) item.remove();
                }, 300);
            }
        }, 8000 + (index * 2000));
    });
}

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
    var autoplayTimer = null;
    var isUserInteracting = false;

    function scrollNext() {
        var maxScroll = track.scrollWidth - track.clientWidth - 15;
        if (track.scrollLeft >= maxScroll) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            track.scrollBy({ left: cardWidth, behavior: 'smooth' });
        }
    }

    function scrollPrev() {
        if (track.scrollLeft <= 15) {
            track.scrollTo({ left: track.scrollWidth, behavior: 'smooth' });
        } else {
            track.scrollBy({ left: -cardWidth, behavior: 'smooth' });
        }
    }

    function startAutoplay() {
        if (autoplayTimer || isUserInteracting) return;
        autoplayTimer = setInterval(scrollNext, 3800);
    }

    function stopAutoplay() {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
    }

    function userAction(callback) {
        stopAutoplay();
        callback();
        // Reprendre après 6 secondes d inactivité
        clearTimeout(track._resumeTimeout);
        track._resumeTimeout = setTimeout(function () {
            if (!isUserInteracting) startAutoplay();
        }, 6000);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            userAction(scrollPrev);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            userAction(scrollNext);
        });
    }

    // Pause au survol souris
    track.addEventListener('mouseenter', function () {
        isUserInteracting = true;
        stopAutoplay();
    });
    track.addEventListener('mouseleave', function () {
        isUserInteracting = false;
        startAutoplay();
    });

    // Pause au touch mobile
    track.addEventListener('touchstart', function () {
        isUserInteracting = true;
        stopAutoplay();
    }, { passive: true });
    track.addEventListener('touchend', function () {
        isUserInteracting = false;
        clearTimeout(track._resumeTimeout);
        track._resumeTimeout = setTimeout(startAutoplay, 4000);
    }, { passive: true });

    // Démarrer l'autoplay
    startAutoplay();
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

/**
 * Défilement horizontal des pilules civiques au clic sur les flèches
 */
function initCivicSlider() {
    var track = document.getElementById('civic-pills-track');
    var prevBtn = document.getElementById('civic-prev');
    var nextBtn = document.getElementById('civic-next');

    if (!track) return;

    var step = 280;

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            track.scrollBy({ left: -step, behavior: 'smooth' });
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            track.scrollBy({ left: step, behavior: 'smooth' });
        });
    }
}

/**
 * Toast universel pour notifications d'actions rapides (ex: copier un lien)
 */
function vbShowToast(message) {
    var toast = document.getElementById('vb-floating-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'vb-floating-toast';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> <span>' + message + '</span>';
    toast.className = 'vb-toast-show';
    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(function () {
        toast.className = '';
    }, 2800);
}

/**
 * Copie un lien dans le presse-papier avec retour visuel immédiat sur le bouton
 */
function vbCopyLink(url, btnElement) {
    if (!url) url = window.location.href;

    var copyPromise;
    if (navigator.clipboard && window.isSecureContext) {
        copyPromise = navigator.clipboard.writeText(url);
    } else {
        copyPromise = new Promise(function (resolve, reject) {
            try {
                var ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                ta.style.top = '-9999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                if (ok) resolve();
                else reject(new Error('execCommand copy failed'));
            } catch (err) {
                reject(err);
            }
        });
    }

    copyPromise.then(function () {
        vbShowToast("Lien copié dans le presse-papier !");

        if (btnElement) {
            var originalHtml = btnElement.innerHTML;
            btnElement.classList.add('copied');
            var span = btnElement.querySelector('span');
            var icon = btnElement.querySelector('i');

            if (span) {
                span.textContent = 'Copié !';
            }
            if (icon) {
                icon.className = 'fa-solid fa-check';
            }

            setTimeout(function () {
                btnElement.classList.remove('copied');
                btnElement.innerHTML = originalHtml;
            }, 2500);
        }
    }).catch(function (err) {
        console.warn('Erreur copie presse-papier:', err);
        window.prompt('Copiez ce lien :', url);
    });
}

/**
 * Initialise le zoom Fancybox sur toutes les images d'illustration des articles de blog
 */
function initBlogImageZoom() {
    var content = document.querySelector('.blog-article-content');
    if (!content) return;

    var imgs = content.querySelectorAll('img:not(.mascot-what-img)');
    imgs.forEach(function (img) {
        var src = img.getAttribute('src');
        if (!src) return;

        var figcaption = img.closest('figure') ? img.closest('figure').querySelector('figcaption') : null;
        var caption = figcaption ? figcaption.innerText.trim() : (img.getAttribute('alt') || '');

        var parentA = img.closest('a');
        if (!parentA) {
            var link = document.createElement('a');
            link.href = src;
            link.className = 'blog-image-lightbox';
            link.setAttribute('data-fancybox', 'blog-gallery');
            if (caption) {
                link.setAttribute('data-caption', caption);
            }
            img.parentNode.insertBefore(link, img);
            link.appendChild(img);
        } else {
            if (!parentA.hasAttribute('data-fancybox')) {
                parentA.classList.add('blog-image-lightbox');
                parentA.setAttribute('data-fancybox', 'blog-gallery');
                if (caption && !parentA.getAttribute('data-caption')) {
                    parentA.setAttribute('data-caption', caption);
                }
            }
        }
    });

    if (window.Fancybox && typeof window.Fancybox.bind === 'function') {
        try {
            Fancybox.bind('[data-fancybox]', {
                Image: {
                    zoom: true
                },
                Toolbar: {
                    display: ['zoom', 'slideshow', 'fullscreen', 'close']
                }
            });
        } catch (e) {
            console.warn('Fancybox bind:', e);
        }
    }
}

// Relance au chargement complet (après chargement de Fancybox deferred)
window.addEventListener('load', function () {
    initBlogImageZoom();
});
