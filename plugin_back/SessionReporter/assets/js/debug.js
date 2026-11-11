/**
 * SessionReporter - Front-end Debug Client
 */
(function () {
    'use strict';

    // 1. Automatic Error Capture
    window.onerror = function (message, source, lineno, colno, error) {
        logEvent(window.SR_LOG_URL, {
            message: message,
            url: source,
            line: lineno,
            column: colno,
            stack: error ? error.stack : ''
        });
        return false; // Let browser process it as well
    };

    // Unhandled Promise Rejections
    window.addEventListener('unhandledrejection', function (event) {
        logEvent(window.SR_LOG_URL, {
            message: 'Unhandled Promise Rejection: ' + event.reason,
            url: window.location.href,
            stack: event.reason ? event.reason.stack : ''
        });
    });

    // 2. Report Submission Logic
    const form = document.getElementById('sr-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const statusBox = document.getElementById('sr-status');
            const submitBtn = form.querySelector('button[type="submit"]');

            statusBox.className = 'alert alert-info';
            statusBox.textContent = 'Envoi en cours...';
            statusBox.classList.remove('d-none');
            submitBtn.disabled = true;

            const formData = new FormData(form);
            const params = new URLSearchParams();
            params.append('report[type]', formData.get('type'));
            params.append('report[comment]', formData.get('comment'));
            params.append('report[steps]', formData.get('steps'));

            fetch(window.SR_REPORT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': window.SR_CSRF_TOKEN
                },
                body: params
            })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        statusBox.className = 'alert alert-success';
                        statusBox.textContent = 'Rapport envoyé avec succès ! Merci de votre aide.';
                        setTimeout(() => {
                            window.closeReporter();
                            form.reset();
                            statusBox.classList.add('d-none');
                            submitBtn.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error(res.message || 'Erreur lors de l\'envoi');
                    }
                })
                .catch(err => {
                    statusBox.className = 'alert alert-danger';
                    statusBox.textContent = 'Erreur : ' + err.message;
                    submitBtn.disabled = false;
                });
        });
    }

    /**
     * Helper to send log events to the server.
     */
    function logEvent(url, data) {
        if (!url) return;

        fetch(url, {
            method: 'POST',
            mode: 'cors',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.SR_CSRF_TOKEN
            },
            body: JSON.stringify(data)
        }).catch(() => {
            // Passive fail to avoid recursive error loops
        });
    }

})();
