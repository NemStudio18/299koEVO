/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * @author ShevAbam <me@shevarezo.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */

document.addEventListener("DOMContentLoaded", function () {
    // Menu mobile maintenant géré par CSS (checkbox hack) - plus besoin de JS

    var pathname = window.location.href.split('#')[0];
    document.querySelectorAll('a[href^="#"]').forEach(function (item) {
        var link = item.getAttribute('href');
        item.setAttribute('href', pathname + link);
    });


    document.querySelectorAll('.msg').forEach(function (item, index) {
        item.querySelector('.msg-button-close').addEventListener('click', function () {
            fadeOut(item);
        });

        setTimeout(function () {
            fadeOut(item);
        }, 5000 + index * 5000);

    });

    // Init Rotators
    initRotator('agenda-rotator', 5000);
    initRotator('barometer-rotator', 4000);
    initRotator('blog-rotator', 6000);
    initRotator('forum-rotator', 6500);
    initRotator('poll-rotator', 5500);

});

function initRotator(containerId, intervalTime) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var items = container.querySelectorAll('.rotate-item, .stat-item'); // Support both class names
    var dots = container.querySelectorAll('.dot');

    if (items.length <= 1) return;

    var currentIndex = 0;
    var timer = null;

    function showItem(index) {
        items.forEach(function (item) { item.classList.remove('active'); });
        dots.forEach(function (dot) { dot.classList.remove('active'); });

        items[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
    }

    function startTimer() {
        timer = setInterval(function () {
            currentIndex = (currentIndex + 1) % items.length;
            showItem(currentIndex);
        }, intervalTime);
    }

    startTimer();

    // Add click listeners to dots
    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function (e) {
            e.preventDefault();
            clearInterval(timer); // Reset timer on interaction
            currentIndex = index;
            showItem(currentIndex);
            startTimer();
        });
    });
}


function fadeOut(el) {
    el.style.opacity = 1;
    (function fade() {
        if ((el.style.opacity -= .03) < 0) {
            el.style.display = "none";
        } else {
            requestAnimationFrame(fade);
        }
    })();
}
;
function fadeIn(el, display) {
    el.style.opacity = 0;
    el.style.display = display || "block";
    (function fade() {
        var val = parseFloat(el.style.opacity);
        if (!((val += .03) > 1)) {
            el.style.opacity = val;
            requestAnimationFrame(fade);
        }
    })();
};
