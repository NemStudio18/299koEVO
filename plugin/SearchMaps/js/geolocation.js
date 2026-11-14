/**
 * Global Geolocation Service for 299koEVO
 * Features:
 * - High-accuracy GPS positioning (enableHighAccuracy: true)
 * - 10-minute cache TTL on localStorage (prevents stale/wrong location trap)
 * - Seamless sync across SearchUi, Agglobus and Map plugins
 * - High precision Leaflet pulsing dot marker support
 */

if (typeof GlobalGeolocation === 'undefined') {
    window.GlobalGeolocation = class GlobalGeolocation {
        constructor() {
            this.storageKey = 'userLocation';
            this.denialKey = 'geo-denial-timestamp';
            this.cacheTTL = 10 * 60 * 1000; // 10 minutes maximum
            this.defaultLocation = { lat: 47.081012, lon: 2.398782, isDefault: true }; // Bourges
            this.init();
        }

        init() {
            // Check if we have a valid, fresh location in cache
            let hasFreshLocation = false;
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                try {
                    const parsed = JSON.parse(stored);
                    const now = Date.now();
                    if (parsed && parsed.lat && parsed.lon && parsed.timestamp && (now - parsed.timestamp < this.cacheTTL)) {
                        window.userLocation = parsed;
                        this.dispatchLocationUpdate();
                        hasFreshLocation = true;
                    } else {
                        // Expired location, clean it up so GPS recalculates
                        localStorage.removeItem(this.storageKey);
                    }
                } catch (e) {
                    localStorage.removeItem(this.storageKey);
                }
            }

            // Bind locate-me buttons across the UI (Search, Header, etc.)
            document.querySelectorAll('#locate-me-btn, .locate-me-btn, #agglo-locate-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    // Si ce n'est pas déjà géré par un script spécifique
                    if (btn.id !== 'agglo-locate-btn') {
                        e.preventDefault();
                    }
                    this.requestPosition(true);
                });
            });

            // If we don't have fresh location, check if already granted silently
            if (!hasFreshLocation) {
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'geolocation' }).then((perm) => {
                        if (perm.state === 'granted') {
                            this.requestPosition(false);
                        }
                    }).catch(() => {});
                }
            }
        }

        showPermissionModal() {
            // Désactivé : la demande native du navigateur suffit amplement et évite tout effet de flou/overlay
            return;
        }

        closeModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 250);
            }
        }

        requestPosition(isManual = false) {
            if (!navigator.geolocation) {
                if (isManual) alert("La géolocalisation n'est pas supportée par votre navigateur.");
                return;
            }

            // Utiliser watchPosition pour permettre au GPS mobile de chauffer et verrouiller les satellites
            let bestCoords = null;
            let isDone = false;
            let watchId = null;

            const finish = (coords) => {
                if (isDone) return;
                isDone = true;
                if (watchId !== null) {
                    try { navigator.geolocation.clearWatch(watchId); } catch(e) {}
                    watchId = null;
                }
                if (coords) {
                    this.handleSuccess({ coords: coords });
                }
            };

            const timer = setTimeout(() => {
                if (bestCoords) {
                    finish(bestCoords);
                } else {
                    finish(null);
                    if (isManual) {
                        alert("Position GPS introuvable. Vérifiez que la localisation est autorisée sur votre téléphone.");
                    }
                }
            }, 10000);

            try {
                watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        const coords = position.coords;
                        if (!bestCoords || coords.accuracy < bestCoords.accuracy) {
                            bestCoords = coords;
                        }
                        if (coords.accuracy && coords.accuracy <= 65) {
                            clearTimeout(timer);
                            finish(coords);
                        }
                    },
                    (err) => {
                        console.warn("GlobalGeolocation: Erreur GPS:", err.message);
                        if (bestCoords) {
                            clearTimeout(timer);
                            finish(bestCoords);
                        }
                    },
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                );
            } catch(e) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => finish(pos.coords),
                    () => finish(null),
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                );
            }
        }

        handleSuccess(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = position.coords.accuracy || 10;

            const loc = {
                lat: lat,
                lon: lon,
                accuracy: accuracy,
                timestamp: Date.now()
            };

            localStorage.setItem(this.storageKey, JSON.stringify(loc));
            window.userLocation = loc;

            // Cookie serveur pour synchroniser immédiatement PHP
            const cookieVal = parseFloat(lat).toFixed(6) + ',' + parseFloat(lon).toFixed(6);
            document.cookie = 'user_geoloc=' + cookieVal + '; path=/; max-age=86400; SameSite=Lax';

            console.log("299koEVO: Nouvelle position GPS fixée ->", lat, lon, "(précision: " + Math.round(accuracy) + "m)");
            this.dispatchLocationUpdate();
        }

        dispatchLocationUpdate() {
            const event = new CustomEvent('user-location-updated', { detail: window.userLocation });
            window.dispatchEvent(event);

            if (typeof htmx !== 'undefined') {
                document.body.dispatchEvent(new Event('locationUpdated'));
            }
        }
    };
}

// Initialisation globale automatique
if (!window.globalGeolocation) {
    const startGeo = function () {
        if (!window.globalGeolocation) {
            window.globalGeolocation = new window.GlobalGeolocation();
        }
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startGeo);
    } else {
        startGeo();
    }
}
