/**
 * Global Geolocation Service for 299Ko
 * Features:
 * - Checks localStorage for existing location
 * - Requests permission via custom modal if needed
 * - Updates window.userLocation and triggers events
 */


if (typeof GlobalGeolocation === 'undefined') {
    window.GlobalGeolocation = class GlobalGeolocation {
        constructor() {
            this.storageKey = 'userLocation';
            this.denialKey = 'geo-denial-timestamp';
            this.defaultLocation = { lat: 47.081012, lon: 2.398782 }; // Bourges
            this.init();
        }

        init() {
            // Check if we already have a location
            const stored = localStorage.getItem(this.storageKey);

            // Add manual trigger listener if button exists
            const triggerBtn = document.getElementById('locate-me-btn');
            if (triggerBtn) {
                triggerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Clear any blocks to force re-evaluation
                    sessionStorage.removeItem('geo-permission-denied');
                    localStorage.removeItem(this.denialKey);
                    this.requestPosition(); // Direct request or show modal
                });
            }

            if (stored) {
                window.userLocation = JSON.parse(stored);
                this.dispatchLocationUpdate();
            } else {
                // No location, show popup
                this.showPermissionModal();
            }
        }

        showPermissionModal() {
            // Check for 7-day cooldown
            const lastDenial = localStorage.getItem(this.denialKey);
            if (lastDenial) {
                const cooldownPeriod = 7 * 24 * 60 * 60 * 1000; // 7 days
                if (Date.now() - parseInt(lastDenial) < cooldownPeriod) {
                    return;
                }
            }

            // Create Modal HTML
            const modalId = 'geo-permission-modal';
            if (document.getElementById(modalId)) return;

            const modalHtml = `
                <div id="${modalId}" class="geo-modal-overlay">
                    <div class="geo-modal-content">
                        <div class="geo-icon">📍</div>
                        <h3>Géolocalisation</h3>
                        <p>Pour afficher les lieux et événements à proximité de vous, nous avons besoin de votre position.</p>
                        <div class="geo-modal-actions">
                            <button id="geo-allow" class="button button-primary">Autoriser</button>
                            <button id="geo-deny" class="button button-secondary">Refuser</button>
                        </div>
                    </div>
                </div>
                <style>
                    .geo-modal-overlay {
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                        background: rgba(0,0,0,0.6); z-index: 9999;
                        display: flex; align-items: center; justify-content: center;
                        backdrop-filter: blur(4px);
                        animation: fadeIn 0.3s ease;
                    }
                    .geo-modal-content {
                        background: var(--card-bg-color, #ffffff);
                        color: var(--font-color, #333);
                        padding: 32px; 
                        border-radius: 20px;
                        max-width: 90%; width: 400px; 
                        text-align: center; 
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        border: 1px solid var(--border-color, rgba(255,255,255,0.1));
                        transform: translateY(20px);
                        animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                    }
                    .geo-icon {
                        font-size: 48px; margin-bottom: 16px; display: block;
                    }
                    .geo-modal-content h3 {
                        margin: 0 0 12px 0; font-size: 1.5rem; font-weight: 700;
                    }
                    .geo-modal-content p {
                        margin: 0 0 24px 0; font-size: 1rem; opacity: 0.8; line-height: 1.5;
                    }
                    .geo-modal-actions {
                        display: flex; gap: 16px; justify-content: center;
                    }
                    .geo-modal-actions .button {
                        padding: 12px 24px; border: none; border-radius: 12px;
                        font-weight: 600; cursor: pointer; transition: all 0.2s;
                        font-size: 1rem;
                    }
                    .geo-modal-actions .button-primary {
                        background: var(--primary-color, #3498db); color: #fff;
                        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
                    }
                    .geo-modal-actions .button-primary:hover {
                        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4); transform: translateY(-2px);
                    }
                    .geo-modal-actions .button-secondary {
                        background: var(--bg-hover-color, #f0f0f0); color: var(--font-color, #333);
                    }
                    .geo-modal-actions .button-secondary:hover {
                        background: var(--border-color, #e0e0e0);
                    }
                    
                    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                    @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
                </style>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            document.getElementById('geo-allow').addEventListener('click', () => {
                this.requestPosition();
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
                setTimeout(() => el.remove(), 300);
            }
        }

        requestPosition() {
            if (!navigator.geolocation) {
                alert("La géolocalisation n'est pas supportée par votre navigateur.");
                return;
            }

            // Bypass Secure Context for Dev/HTTP environments
            if (!window.isSecureContext && window.location.hostname !== 'localhost') {
                const mockPosition = {
                    coords: {
                        latitude: 47.081012, // Bourges Centre
                        longitude: 2.398782
                    }
                };
                this.handleSuccess(mockPosition);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // Si la position IP est à plus de 100km de Bourges (ex: IP datacenter Paris), on prévient et on privilégie Bourges
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const dLat = Math.abs(lat - 47.081012);
                    const dLon = Math.abs(lon - 2.398782);
                    if (dLat > 1.0 || dLon > 1.0) {
                        console.warn("Position détectée éloignée de Bourges (" + lat + ", " + lon + "), utilisation de Bourges Centre.");
                    }
                    this.handleSuccess(position);
                },
                (error) => {
                    console.warn("Erreur de géolocalisation native (" + error.message + "), fallback sur Bourges Centre.");
                    const fallbackPosition = {
                        coords: {
                            latitude: 47.081012,
                            longitude: 2.398782
                        }
                    };
                    this.handleSuccess(fallbackPosition);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }

        handleSuccess(position) {
            const loc = {
                lat: position.coords.latitude,
                lon: position.coords.longitude,
                timestamp: Date.now()
            };
            localStorage.setItem(this.storageKey, JSON.stringify(loc));
            window.userLocation = loc;
            
            // Écrire aussi un cookie lisible par PHP dès le prochain chargement de page
            // Permet au HomeController de SearchUI d'avoir les coords dès le premier rendu serveur
            const val = parseFloat(loc.lat).toFixed(6) + ',' + parseFloat(loc.lon).toFixed(6);
            document.cookie = 'user_geoloc=' + val + '; path=/; max-age=86400; SameSite=Lax';
            
            this.dispatchLocationUpdate();
        }

        dispatchLocationUpdate() {
            // Trigger custom event for other plugins to listen to
            const event = new CustomEvent('user-location-updated', { detail: window.userLocation });
            window.dispatchEvent(event);

            // HTMX integration (if used)
            if (typeof htmx !== 'undefined') {
                document.body.dispatchEvent(new Event('locationUpdated'));
            }
        }
    }
}

// Initialize on load with singleton guard
if (!window.globalGeolocation) {
    var initGeo = function () {
        if (!window.globalGeolocation) {
            window.globalGeolocation = new GlobalGeolocation();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGeo);
    } else {
        initGeo();
    }
}
