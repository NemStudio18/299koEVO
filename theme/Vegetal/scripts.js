/**
 * Thème Vegetal — Scripts front-end
 */
document.addEventListener("DOMContentLoaded", function () {
    // Préserver l'URL complète pour les liens d'ancres (#...) avec <base href="...">
    var pathname = window.location.href.split("#")[0];
    document.querySelectorAll('a[href^="#"]').forEach(function (item) {
        var link = item.getAttribute("href");
        if (link && link.length > 1) {
            item.setAttribute("href", pathname + link);
        }
    });

    // Fermeture et disparition progressive des notifications CMS
    document.querySelectorAll(".msg").forEach(function (item, index) {
        var closeBtn = item.querySelector(".msg-button-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", function () {
                fadeOut(item);
            });
        }
        setTimeout(function () {
            fadeOut(item);
        }, 5000 + index * 3000);
    });

    // Fermer le menu mobile avec Échap ou clic extérieur
    var navToggle = document.getElementById("nav_toggle");
    var header = document.getElementById("header");
    if (navToggle && header) {
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && navToggle.checked) {
                navToggle.checked = false;
            }
        });
        document.addEventListener("click", function (e) {
            if (navToggle.checked && !header.contains(e.target)) {
                navToggle.checked = false;
            }
        });
    }

    // Sous-menus mobiles : toggle au clic/touch (uniquement sur mobile, hors menu avatar profil)
    document.querySelectorAll("#navigation > li").forEach(function (li) {
        if (li.classList.contains("nav-user-avatar-menu")) return;
        var sub = li.querySelector("ul");
        if (!sub) return;
        var link = li.querySelector("a");
        if (!link) return;
        link.addEventListener("click", function (e) {
            if (window.innerWidth > 1024) return; // Laisser CSS :hover gérer le desktop
            e.preventDefault();
            var isOpen = li.classList.contains("vg-open");
            // Fermer tous les autres
            document.querySelectorAll("#navigation > li.vg-open").forEach(function (el) {
                el.classList.remove("vg-open");
            });
            if (!isOpen) {
                li.classList.add("vg-open");
            }
        });
    });

    // Compteur dynamique de téléchargements (si présent sur la page)
    var dlBadges = document.querySelectorAll(".js-evo-dl-count");
    if (dlBadges.length > 0) {
        fetch("/download.php?stats=1", { cache: "no-store" })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.downloads !== "undefined") {
                    dlBadges.forEach(function (el) {
                        el.textContent = data.downloads;
                    });
                }
            })
            .catch(function () {});

        document.querySelectorAll('a[href*="download.php"]').forEach(function (btn) {
            btn.addEventListener("click", function () {
                setTimeout(function () {
                    fetch("/download.php?stats=1", { cache: "no-store" })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (data && typeof data.downloads !== "undefined") {
                                dlBadges.forEach(function (el) {
                                    el.textContent = data.downloads;
                                });
                            }
                        })
                        .catch(function () {});
                }, 800);
            });
        });
    }
});

function fadeOut(el) {
    if (!el || el.style.display === "none") return;
    el.style.opacity = 1;
    (function fade() {
        var current = parseFloat(el.style.opacity) - 0.05;
        if (current <= 0) {
            el.style.display = "none";
        } else {
            el.style.opacity = current;
            requestAnimationFrame(fade);
        }
    })();
}

