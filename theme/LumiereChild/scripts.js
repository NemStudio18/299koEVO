/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * 
 * Scripts JavaScript pour le thème Lumiere
 */

document.addEventListener("DOMContentLoaded", function () {
    var pathname = window.location.href.split('#')[0];
    document.querySelectorAll('a[href^="#"]').forEach(function (item) {
        var link = item.getAttribute('href');
        item.setAttribute('href', pathname + link);
    });

    document.querySelectorAll('.msg').forEach(function (item, index) {
        var closeButton = item.querySelector('.msg-button-close');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                fadeOut(item);
            });
        }

        setTimeout(function () {
            fadeOut(item);
        }, 5000 + index * 5000);
    });

    // Navigation hide/show on scroll (optimisé pour mobile)
    var headerContent = document.getElementById('header_content');
    var header = document.getElementById('header');
    if (headerContent && header) {
        var lastScrollTop = 0;
        var scrollThreshold = 10; // Minimum scroll distance to trigger hide/show
        var isScrolling = false;
        var scrollTimeout = null;

        // Détecter si on est sur mobile
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        // Sur mobile, utiliser un throttling plus agressif
        var throttleDelay = isMobile ? 100 : 16; // 100ms sur mobile, 16ms (60fps) sur desktop

        window.addEventListener('scroll', function () {
            // Throttling pour éviter trop d'appels sur mobile
            if (scrollTimeout) {
                return;
            }

            scrollTimeout = setTimeout(function () {
                scrollTimeout = null;

                if (isScrolling) return;

                isScrolling = true;
                requestAnimationFrame(function () {
                    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                    // Only trigger if scroll distance is significant
                    if (Math.abs(scrollTop - lastScrollTop) < scrollThreshold) {
                        isScrolling = false;
                        return;
                    }

                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down - hide header
                        header.classList.add('header-hidden');
                    } else {
                        // Scrolling up - show header
                        header.classList.remove('header-hidden');
                    }

                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                    isScrolling = false;
                });
            }, throttleDelay);
        }, { passive: true }); // passive: true pour améliorer les performances sur mobile
    }

    // Gestion du dropdown claim en mode mobile (toggle classique)
    var claimDropdowns = document.querySelectorAll('#navigation .claim-menu-dropdown');
    claimDropdowns.forEach(function (dropdown) {
        var link = dropdown.querySelector('a');
        if (link) {
            link.addEventListener('click', function (e) {
                // En mode mobile uniquement (largeur < 1024px)
                if (window.innerWidth <= 1024) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Fermer les autres dropdowns
                    claimDropdowns.forEach(function (other) {
                        if (other !== dropdown) {
                            other.classList.remove('active');
                        }
                    });

                    // Toggle le dropdown actuel
                    dropdown.classList.toggle('active');
                }
            });
        }
    });

    // Fermer les dropdowns quand on clique ailleurs (mobile)
    // Ne pas interférer avec les clics sur les liens de navigation
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 1024) {
            var target = e.target;

            // Si on clique sur un lien de navigation (<a href="...">), ne pas fermer le dropdown
            // Laisser la navigation se faire naturellement
            var link = target.closest('a');
            if (link && link.href) {
                // Ignorer les liens d'ancre (#) ou javascript:void(0)
                var href = link.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    // C'est un vrai lien de navigation, ne rien faire
                    // Le dropdown se fermera automatiquement avec la navigation
                    return;
                }
            }

            // Pour les autres clics, vérifier si on doit fermer les dropdowns
            var clickedInside = false;
            claimDropdowns.forEach(function (dropdown) {
                if (dropdown.contains(target)) {
                    clickedInside = true;
                }
            });

            if (!clickedInside) {
                claimDropdowns.forEach(function (dropdown) {
                    dropdown.classList.remove('active');
                });
            }
        }
    });

    // Note: Le dropdown poi-edit a été supprimé - maintenant c'est une simple liste ul/li

});

// Protection contre les animations multiples simultanées
var activeFadeAnimations = new WeakSet();

function fadeOut(el) {
    // Éviter les animations multiples sur le même élément
    if (activeFadeAnimations.has(el)) {
        return;
    }

    activeFadeAnimations.add(el);
    el.style.opacity = 1;

    (function fade() {
        var currentOpacity = parseFloat(el.style.opacity) || 1;
        var newOpacity = currentOpacity - 0.03;

        if (newOpacity < 0) {
            el.style.display = "none";
            activeFadeAnimations.delete(el);
        } else {
            el.style.opacity = newOpacity;
            requestAnimationFrame(fade);
        }
    })();
}

function fadeIn(el, display) {
    // Éviter les animations multiples sur le même élément
    if (activeFadeAnimations.has(el)) {
        return;
    }

    activeFadeAnimations.add(el);
    el.style.opacity = 0;
    el.style.display = display || "block";

    (function fade() {
        var val = parseFloat(el.style.opacity) || 0;
        var newVal = val + 0.03;

        if (newVal > 1) {
            el.style.opacity = 1;
            activeFadeAnimations.delete(el);
        } else {
            el.style.opacity = newVal;
            requestAnimationFrame(fade);
        }
    })();
}


