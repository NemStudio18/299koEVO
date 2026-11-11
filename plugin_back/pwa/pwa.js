/**
 * Script client PWA pour gérer l'installation et les notifications push
 */
(function () {
    'use strict';

    let publicKey = null;
    let registration = null;

    // Helper pour les cookies
    function setCookie(name, value, hours) {
        let expires = "";
        if (hours) {
            let date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // Incrémenter le compteur de visites
    function incrementVisits() {
        if (sessionStorage.getItem('pwa_visit_session_counted')) {
            return parseInt(localStorage.getItem('pwa_visit_count') || '0');
        }

        let visits = parseInt(localStorage.getItem('pwa_visit_count') || '0');
        visits++;
        localStorage.setItem('pwa_visit_count', visits);
        sessionStorage.setItem('pwa_visit_session_counted', 'true');
        return visits;
    }

    // Initialiser le PWA
    function init() {
        const visits = incrementVisits();
        console.log('PWA: Visite #' + visits);

        // Écouter l'événement beforeinstallprompt globalement
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.deferredPrompt = e;
            console.log('PWA: beforeinstallprompt capturé');
            showInstallPrompt(visits);
        });

        showInstallPrompt(visits);

        if ('serviceWorker' in navigator) {
            loadPublicKey().then(() => {
                registerServiceWorker();
            }).catch(() => {
                registerServiceWorker();
            });
        }
    }

    // Charger la clé publique VAPID
    function loadPublicKey() {
        return fetch('/pwa/public-key')
            .then(response => response.text())
            .then(key => {
                publicKey = key.trim();
                return key;
            })
            .catch(error => {
                console.error('PWA: Erreur lors du chargement de la clé publique', error);
            });
    }

    // Enregistrer le Service Worker
    function registerServiceWorker() {
        navigator.serviceWorker.register('/pwa/sw.js')
            .then(reg => {
                registration = reg;
                console.log('PWA: Service Worker enregistré');
                checkSubscription();
            })
            .catch(error => {
                console.error('PWA: Erreur lors de l\'enregistrement du Service Worker', error);
            });
    }

    // Vérifier l'abonnement actuel
    function checkSubscription() {
        if (!registration) return;
        registration.pushManager.getSubscription()
            .then(subscription => {
                if (subscription) {
                    console.log('PWA: Déjà abonné aux notifications');
                }
            })
            .catch(error => {
                console.error('PWA: Erreur lors de la vérification de l\'abonnement', error);
            });
    }

    // Demander la permission et s'abonner
    function subscribe() {
        if (!registration || !publicKey) {
            return Promise.reject('Service Worker ou clé publique non disponible');
        }

        return registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey)
        })
            .then(subscription => {
                return fetch('/pwa/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint,
                        keys: {
                            p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
                            auth: arrayBufferToBase64(subscription.getKey('auth'))
                        }
                    })
                })
                    .then(response => response.json());
            });
    }

    // Se désabonner
    function unsubscribe() {
        if (!registration) return Promise.reject('Service Worker non disponible');
        return registration.pushManager.getSubscription()
            .then(subscription => {
                if (subscription) {
                    return fetch('/pwa/unsubscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ endpoint: subscription.endpoint })
                    })
                        .then(() => subscription.unsubscribe());
                }
            });
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    function detectPlatform() {
        const ua = navigator.userAgent || navigator.vendor || window.opera;
        const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        const isAndroid = /android/i.test(ua);
        const isChrome = /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor);
        const isFirefox = /Firefox/.test(ua);
        const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
        return {
            isIOS: isIOS,
            isAndroid: isAndroid,
            isChrome: isChrome,
            isFirefox: isFirefox,
            isSafari: isSafari,
            isMobile: isIOS || isAndroid
        };
    }

    function isAppInstalled() {
        if (window.matchMedia('(display-mode: standalone)').matches) return true;
        if (window.navigator.standalone === true) return true;
        return false;
    }

    function showInstallPrompt(visits) {
        const banner = document.getElementById('pwa-install-banner');
        if (!banner) return;
        if (isAppInstalled()) return;
        if (getCookie('pwa_snooze')) return;

        const triggerVisits = [5, 15, 25];
        if (!triggerVisits.includes(visits)) return;

        const platform = detectPlatform();
        const confirmBtn = document.getElementById('pwa-install-confirm');
        const laterBtn = document.getElementById('pwa-install-later');

        if (!banner.dataset.listenerAdded) {
            confirmBtn.addEventListener('click', () => {
                if (window.deferredPrompt) {
                    window.deferredPrompt.prompt();
                    window.deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            banner.style.display = 'none';
                        }
                        window.deferredPrompt = null;
                    });
                } else if (platform.isIOS && platform.isSafari) {
                    alert('Pour installer :\n1. Appuyez sur Partager\n2. "Sur l\'écran d\'accueil"\n3. Ajouter');
                } else {
                    alert('Utilisez le menu de votre navigateur pour installer l\'application.');
                }
            });

            laterBtn.addEventListener('click', () => {
                setCookie('pwa_snooze', 'true', 24);
                banner.style.display = 'none';
            });

            banner.dataset.listenerAdded = 'true';
        }

        if (platform.isIOS && platform.isSafari) {
            banner.style.display = 'flex';
        } else if (platform.isFirefox) {
            banner.style.display = 'flex';
        } else if (window.deferredPrompt) {
            banner.style.display = 'flex';
        }
    }

    window.PWA = {
        init: init,
        subscribe: subscribe,
        unsubscribe: unsubscribe,
        showInstallPrompt: showInstallPrompt
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
