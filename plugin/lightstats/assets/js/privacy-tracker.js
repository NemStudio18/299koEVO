/**
 * Vivre-Bourges Analytics — Tracker Léger & Gestionnaire de Consentement (Mode C)
 * 100% Souverain, Respectueux de la vie privée (CNIL/RGPD)
 */
(function() {
    'use strict';

    const CONSENT_KEY = 'vb_consent';
    const VISITOR_COOKIE = 'vb_vid';
    const SESSION_COOKIE = 'vb_sid';
    const VISITOR_DAYS = 7;
    const SESSION_MINUTES = 30;

    // Utilitaires Cookies
    function getCookie(name) {
        const matches = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
        return matches ? decodeURIComponent(matches[1]) : null;
    }

    function setCookie(name, value, minutes) {
        let expires = '';
        if (minutes) {
            const date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function deleteCookie(name) {
        document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
    }

    function generateUuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Contexte serveur injecté dynamiquement dans la réponse HTML (sans cookie vb_req)
    const serverCtx = (typeof window.__VB_CTX__ === 'object' && window.__VB_CTX__ !== null) ? window.__VB_CTX__ : {};
    const requestId = (typeof serverCtx.reqId === 'string' && serverCtx.reqId.length > 0) ? serverCtx.reqId : null;
    let sessionId = (typeof serverCtx.sid === 'string' && serverCtx.sid.length > 0) ? serverCtx.sid : null;

    // Gestion du consentement & visitor_id
    const currentConsent = localStorage.getItem(CONSENT_KEY);
    let visitorId = null;

    if (currentConsent === 'accepted') {
        visitorId = getCookie(VISITOR_COOKIE);
        if (!visitorId) {
            visitorId = generateUuid();
            setCookie(VISITOR_COOKIE, visitorId, VISITOR_DAYS * 24 * 60);
        } else {
            // Prolonger la validité du cookie visiteur
            setCookie(VISITOR_COOKIE, visitorId, VISITOR_DAYS * 24 * 60);
        }
    } else if (currentConsent === 'refused') {
        deleteCookie(VISITOR_COOKIE);
    }

    // API Publique VBAnalytics
    window.VBAnalytics = {
        getVisitorId: () => visitorId,
        getSessionId: () => sessionId,
        getRequestId: () => requestId,
        getConsent: () => localStorage.getItem(CONSENT_KEY),
        trackEvent: function(module, eventType, eventData, existingEventId) {
            // Génération d'un event_id stable réutilisé lors des éventuelles réémissions
            const eventId = existingEventId || ('evt_' + generateUuid().replace(/-/g, ''));
            const payload = {
                event_id: eventId,
                request_id: requestId,
                session_id: sessionId,
                visitor_id: visitorId,
                module: module || 'other',
                event_type: eventType,
                page: window.location.pathname + window.location.search,
                event_data: eventData || {}
            };

            const jsonStr = JSON.stringify(payload);
            let sent = false;
            if (navigator.sendBeacon) {
                try {
                    const blob = new Blob([jsonStr], { type: 'application/json' });
                    sent = navigator.sendBeacon('/lightstats/api/event', blob);
                } catch (e) {
                    sent = false;
                }
            }
            if (!sent) {
                fetch('/lightstats/api/event', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: jsonStr,
                    keepalive: true
                }).catch(() => {});
            }
            return eventId;
        },
        openConsentModal: function() {
            showConsentBanner(true);
        },
        revokeConsent: function() {
            localStorage.setItem(CONSENT_KEY, 'refused');
            deleteCookie(VISITOR_COOKIE);
            visitorId = null;
        }
    };

    // Injection du bandeau discret de consentement
    function showConsentBanner(force = false) {
        if (!force && localStorage.getItem(CONSENT_KEY)) {
            return;
        }

        let existing = document.getElementById('vb-privacy-toast');
        if (existing) {
            existing.style.display = 'flex';
            return;
        }

        const toast = document.createElement('div');
        toast.id = 'vb-privacy-toast';
        toast.innerHTML = `
            <div class="vb-privacy-content">
                <div class="vb-privacy-header">
                    <span class="vb-privacy-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <strong>Respect de votre vie privée</strong>
                </div>
                <p class="vb-privacy-text">
                    Nous mesurons l'usage du site pour améliorer ses fonctionnalités locales. Avec votre accord, un identifiant pseudonyme nous permet d'analyser les parcours récurrents. Aucune donnée n'est revendue.
                </p>
                <div class="vb-privacy-actions">
                    <button id="vb-privacy-refuse" class="vb-btn vb-btn-refuse" type="button">Tout refuser</button>
                    <button id="vb-privacy-accept" class="vb-btn vb-btn-accept" type="button">Tout accepter</button>
                </div>
            </div>
        `;

        // Styles CSS intégrés pour être 100% autonome
        const style = document.createElement('style');
        style.textContent = `
            #vb-privacy-toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                max-width: 440px;
                width: calc(100% - 48px);
                background: var(--section-bg-color, #ffffff);
                color: var(--font-color, #16181d);
                border: 1px solid var(--border-color, #e5e2da);
                border-radius: var(--section-radius, 14px);
                padding: 20px;
                box-shadow: var(--dc-shadow-md, 0 16px 36px rgba(0, 0, 0, 0.16));
                z-index: 999999;
                font-family: inherit;
                animation: vbSlideUp 0.35s ease-out;
            }
            @keyframes vbSlideUp {
                from { transform: translateY(30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .vb-privacy-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
                font-size: 15px;
                font-weight: 700;
                color: var(--primary-color, #1d4ed8);
            }
            .vb-privacy-text {
                font-size: 13px;
                line-height: 1.5;
                color: var(--font-secondary, #5a6070);
                margin: 0 0 16px 0;
            }
            .vb-privacy-actions {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }
            .vb-btn {
                padding: 8px 16px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                border: none;
            }
            .vb-btn-refuse {
                background: var(--section-head-bg-color, #f3f1ec);
                color: var(--font-color, #16181d);
                border: 1px solid var(--border-color, #e5e2da);
            }
            .vb-btn-refuse:hover {
                border-color: var(--primary-color, #1d4ed8);
            }
            .vb-btn-accept {
                background: var(--primary-color, #1d4ed8);
                color: var(--primary-inverse-color, #ffffff);
                box-shadow: 0 4px 12px var(--dc-accent-soft, rgba(29, 78, 216, 0.25));
            }
            .vb-btn-accept:hover {
                background: var(--primary-hover-color, #1e40af);
                transform: translateY(-1px);
            }
            @media (max-width: 600px) {
                #vb-privacy-toast {
                    bottom: 12px;
                    right: 12px;
                    width: calc(100% - 24px);
                    padding: 16px;
                }
                .vb-privacy-actions {
                    flex-direction: column;
                }
                .vb-btn {
                    width: 100%;
                    text-align: center;
                }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(toast);

        // Actions boutons
        document.getElementById('vb-privacy-refuse').addEventListener('click', function() {
            localStorage.setItem(CONSENT_KEY, 'refused');
            deleteCookie(VISITOR_COOKIE);
            visitorId = null;
            toast.style.display = 'none';
        });

        document.getElementById('vb-privacy-accept').addEventListener('click', function() {
            localStorage.setItem(CONSENT_KEY, 'accepted');
            visitorId = getCookie(VISITOR_COOKIE);
            if (!visitorId) {
                visitorId = generateUuid();
                setCookie(VISITOR_COOKIE, visitorId, VISITOR_DAYS * 24 * 60);
            }
            toast.style.display = 'none';
        });
    }

    // Détection des interactions enrichies
    function initInteractionTrackers() {
        document.addEventListener('click', function(e) {
            // 1. Clic sur numéro de téléphone (Appels)
            const telLink = e.target.closest('a[href^="tel:"]');
            if (telLink) {
                window.VBAnalytics.trackEvent('poi', 'call_click', {
                    phone: telLink.getAttribute('href'),
                    title: document.title
                });
                return;
            }

            // 2. Clic sur Itinéraire / GPS
            const navLink = e.target.closest('a[href*="maps.google"], a[href*="openstreetmap"], a.poi-directions-btn, a.directions-btn');
            if (navLink) {
                window.VBAnalytics.trackEvent('poi', 'directions_click', {
                    url: navLink.getAttribute('href'),
                    title: document.title
                });
                return;
            }

            // 3. Clic sur site web officiel du commerce
            const webLink = e.target.closest('a.poi-website-link, a[data-track="website"]');
            if (webLink) {
                window.VBAnalytics.trackEvent('poi', 'website_click', {
                    url: webLink.getAttribute('href'),
                    title: document.title
                });
                return;
            }

            // 4. Clic sur le widget Météo du header
            const weatherWidget = e.target.closest('#weather-header, .weather-header-container, [data-widget="weather"]');
            if (weatherWidget) {
                window.VBAnalytics.trackEvent('meteo', 'meteo_open', {
                    context: 'header_click'
                });
                return;
            }

            // 5. Clic / Sélection ligne Agglobus
            const busAction = e.target.closest('.agglobus-line-btn, .agglobus-stop-item, [data-bus-line]');
            if (busAction) {
                window.VBAnalytics.trackEvent('agglobus', 'route_lookup', {
                    line: busAction.dataset.busLine || busAction.textContent.trim()
                });
                return;
            }
        });

        // 6. Impression QR Code sur fiche POI (si le conteneur QR est présent dans le DOM)
        const qrContainer = document.querySelector('[data-qr-poi-id]');
        if (qrContainer) {
            const poiId = qrContainer.dataset.qrPoiId;
            window.VBAnalytics.trackEvent('qr_poi', 'qr_impression', {
                poi_id: parseInt(poiId, 10),
                poi_name: document.title
            });
        }
    }

    // Certification Active View JS (garantit l'exécution côté client par un vrai navigateur)
    function certifyPageView() {
        if (window.location.pathname.indexOf('/admin') === 0) {
            return;
        }
        // Protection impérative : Une certification n'est émise que si un request_id valide est fourni par le serveur
        if (!requestId) {
            return;
        }
        try {
            window.VBAnalytics.trackEvent('page', 'view_certified', {
                title: document.title,
                screen: (window.screen ? (window.screen.width + 'x' + window.screen.height) : ''),
                referrer: document.referrer || ''
            });
        } catch (e) {}
    }

    // Démarrage au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            showConsentBanner();
            initInteractionTrackers();
            certifyPageView();
        });
    } else {
        showConsentBanner();
        initInteractionTrackers();
        certifyPageView();
    }
})();
