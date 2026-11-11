/**
 * Global Geolocation Service for Vivre-Bourges
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

            // If we don't have fresh location, check browser permission or prompt
            if (!hasFreshLocation) {
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'geolocation' }).then((perm) => {
                        if (perm.state === 'granted') {
                            this.requestPosition(false);
                        } else if (perm.state === 'prompt') {
                            this.showPermissionModal();
                        }
                    }).catch(() => {
                        this.showPermissionModal();
                    });
                } else {
                    this.showPermissionModal();
                }
            }
        }

        showPermissionModal() {
            // Check for 7-day cooldown on user denial
            const lastDenial = localStorage.getItem(this.denialKey);
            if (lastDenial) {
                const cooldownPeriod = 7 * 24 * 60 * 60 * 1000;
                if (Date.now() - parseInt(lastDenial) < cooldownPeriod) {
                    return;
                }
            }

            const modalId = 'geo-permission-modal';
            if (document.getElementById(modalId)) return;

            const modalHtml = `
                <div id="${modalId}" class="geo-modal-overlay">
                    <div class="geo-modal-content">
                        <div class="geo-icon">📍</div>
                        <h3>Activer ma position</h3>
                        <p>Pour afficher les commerces, bus et services au plus près de vous à Bourges, activez la localisation précise.</p>
                        <div class="geo-modal-actions">
                            <button id="geo-allow" class="button button-primary">Autoriser</button>
                            <button id="geo-deny" class="button button-secondary">Plus tard</button>
                        </div>
                    </div>
                </div>
                <style>
                    .geo-modal-overlay {
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                        background: rgba(15, 23, 42, 0.6); z-index: 99999;
                        display: flex; align-items: center; justify-content: center;
                        backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
                        animation: geoFadeIn 0.25s ease;
                    }
                    .geo-modal-content {
                        background: #ffffff; color: #1e293b;
                        padding: 28px 24px; border-radius: 20px;
                        max-width: 90%; width: 380px; text-align: center;
                        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
                        border: 1px solid rgba(226, 232, 240, 0.8);
                        animation: geoSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                    }
                    .geo-icon { font-size: 42px; margin-bottom: 12px; }
                    .geo-modal-content h3 { margin: 0 0 10px 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; }
                    .geo-modal-content p { margin: 0 0 20px 0; font-size: 0.95rem; color: #64748b; line-height: 1.45; }
                    .geo-modal-actions { display: flex; gap: 12px; justify-content: center; }
                    .geo-modal-actions .button { padding: 12px 20px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-size: 0.95rem; flex: 1; }
                    .geo-modal-actions .button-primary { background: #ff8c42; color: #fff; box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3); }
                    .geo-modal-actions .button-primary:hover { background: #e67e3a; transform: translateY(-1px); }
                    .geo-modal-actions .button-secondary { background: #f1f5f9; color: #475569; }
                    .geo-modal-actions .button-secondary:hover { background: #e2e8f0; }
                    @keyframes geoFadeIn { from { opacity: 0; } to { opacity: 1; } }
                    @keyframes geoSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
                </style>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            document.getElementById('geo-allow').addEventListener('click', () => {
                this.requestPosition(true);
                this.closeModal(modalId);
            });

            document.getElementById('geo-deny').addEventListener('click', () => {
                sessionStorage.setItem('geo-permission-denied', 'true');
                localStorage.setItem(this.denialKey, Date.now().toString());
                this.closeModal(modalId);
            });
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

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: isManual ? 0 : 30000 // force fresh fix on manual click
            };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.handleSuccess(position);
                },
                (error) => {
                    console.warn("Échec GPS haute précision:", error.message);
                    // Tentative de fallback basse consommation avant d'abandonner
                    navigator.geolocation.getCurrentPosition(
                        (fallbackPos) => this.handleSuccess(fallbackPos),
                        (finalErr) => {
                            console.warn("Échec définitif géoloc:", finalErr.message);
                            if (isManual) {
                                alert("Impossible de récupérer votre position GPS précise. Vérifiez que la localisation est activée sur votre appareil.");
                            }
                        },
                        { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 }
                    );
                },
                options
            );
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

            console.log("Vivre-Bourges: Nouvelle position GPS fixée ->", lat, lon, "(précision: " + Math.round(accuracy) + "m)");
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
