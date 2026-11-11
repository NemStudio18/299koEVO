/**
 * Thème Declic — Scripts front-end
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
