<div class="dc-error-wrap">
    <div class="dc-error-card">
        <div class="dc-error-icon tone-{{errTone|default('danger')}}" aria-hidden="true">
            <i class="{{errIcon|default('fa-solid fa-compass')}}"></i>
        </div>

        <div class="dc-error-badge tone-{{errTone|default('danger')}}">
            {{errBadge|default('ERREUR')}}
        </div>

        <h1 class="dc-error-title">{{errTitle|default('Page introuvable')}}</h1>

        <p class="dc-error-msg">{{errMessage|default('La page demandée n\'est pas disponible.')}}</p>

        <div class="dc-error-actions">
            <a href="{{homeUrl|default('/')}}" id="dcErrorHomeLink" class="dc-error-btn">
                <i class="fa-solid fa-house"></i>
                <span>Retour au site</span>
            </a>
        </div>

        <p class="dc-error-redirect" aria-live="polite">
            Redirection automatique vers l'accueil dans <strong id="dcErrorCountdown">8</strong> secondes…
        </p>
    </div>
</div>

<script>
(function() {
    let remaining = 8;
    const el = document.getElementById('dcErrorCountdown');
    const link = document.getElementById('dcErrorHomeLink');
    const targetUrl = link ? link.getAttribute('href') : '/';
    const timer = setInterval(() => {
        remaining -= 1;
        if (el) el.textContent = String(Math.max(0, remaining));
        if (remaining <= 0) {
            clearInterval(timer);
            window.location.href = targetUrl;
        }
    }, 1000);
})();
</script>
