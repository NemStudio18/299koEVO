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

<style>
.dc-error-wrap {
    max-width: 640px;
    margin: 36px auto 56px;
    padding: 16px;
    text-align: center;
}
.dc-error-card {
    background: var(--dc-surface, #ffffff);
    color: var(--dc-text, #0f172a);
    border: 1px solid var(--dc-border, #e2e8f0);
    border-radius: 18px;
    padding: 44px 28px;
    box-shadow: var(--dc-shadow-md, 0 10px 30px rgba(15, 23, 42, 0.06));
}
.dc-error-icon {
    width: 84px;
    height: 84px;
    margin: 0 auto 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.1rem;
    background: var(--dc-accent-soft, rgba(29, 78, 216, 0.1));
    color: var(--dc-accent, #1d4ed8);
    animation: dcIconPulse 3.2s ease-in-out infinite;
}
.dc-error-icon.tone-warning {
    background: var(--dc-warning-bg, #fffbeb);
    color: var(--dc-warning, #b45309);
}
.dc-error-icon.tone-danger {
    background: var(--dc-error-bg, #fef2f2);
    color: var(--dc-error, #b91c1c);
}
.dc-error-badge {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 4px 14px;
    border-radius: 999px;
    letter-spacing: 0.05em;
    margin-bottom: 14px;
    background: var(--dc-error-bg, #fee2e2);
    color: var(--dc-error, #b91c1c);
}
.dc-error-badge.tone-warning {
    background: var(--dc-warning-bg, #fef3c7);
    color: var(--dc-warning, #b45309);
}
.dc-error-title {
    margin: 0 0 12px;
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    color: var(--dc-text, #0f172a);
    line-height: 1.25;
}
.dc-error-msg {
    margin: 0 auto 26px;
    max-width: 480px;
    color: var(--dc-text-muted, #64748b);
    font-size: 1rem;
    line-height: 1.6;
}
.dc-error-actions {
    display: flex;
    justify-content: center;
}
.dc-error-btn {
    background: var(--dc-accent, #1d4ed8);
    color: #ffffff !important;
    font-weight: 700;
    padding: 12px 26px;
    border-radius: 10px;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(29, 78, 216, 0.28);
    transition: transform 0.15s ease, background-color 0.15s ease;
}
.dc-error-btn:hover {
    background: var(--dc-accent-hover, #1e40af);
    transform: translateY(-1px);
}
.dc-error-redirect {
    margin: 18px 0 0;
    font-size: 0.85rem;
    color: var(--dc-text-muted, #64748b);
}
@keyframes dcIconPulse {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
@media (prefers-reduced-motion: reduce) {
    .dc-error-icon { animation: none; }
}
</style>

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
