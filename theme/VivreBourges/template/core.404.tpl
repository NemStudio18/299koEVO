<div class="vb-error-container" style="max-width: 680px; margin: 40px auto 60px; padding: 20px; text-align: center;">
    <div class="vb-error-card" style="background: #ffffff; border-radius: 20px; padding: 45px 30px; box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
        <div class="vb-error-mascot" style="margin-bottom: 25px;">
            <picture>
                <source srcset="/images/mascot/what_404_2.webp" type="image/webp">
                <img src="/images/mascot/what_404_2.png" alt="What cherche son chemin dans Bourges" width="180" height="170" style="max-width: 180px; height: auto; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.12)); animation: vbFloat 4s ease-in-out infinite;">
            </picture>
        </div>

        <div style="display: inline-block; background: #fee2e2; color: #b91c1c; font-size: 0.85rem; font-weight: 800; padding: 4px 14px; border-radius: 9999px; letter-spacing: 0.05em; margin-bottom: 15px;">
            ERREUR 404 • PAGE INTROUVABLE
        </div>

        <h1 style="margin: 0 0 12px; font-size: 2rem; font-weight: 900; color: #0f172a; line-height: 1.2;">
            Oups ! Cette rue n'existe pas dans Bourges...
        </h1>

        <p style="margin: 0 auto 28px; max-width: 500px; color: #64748b; font-size: 1.05rem; line-height: 1.55;">
            What a retourné le plan de la ville dans tous les sens, mais impossible de mettre la main sur cette page. Elle a peut-être déménagé ou l'adresse a changé.
        </p>

        <div id="vb-auto-redirect" style="margin-bottom: 24px; font-size: 0.92rem; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 9999px; padding: 6px 16px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-clock-rotate-left" style="color: #e67e22;"></i>
            <span>Redirection automatique dans <strong id="vb-countdown" style="color: #0f172a;">8</strong>s...</span>
            <button type="button" id="vb-cancel-redirect" style="background: none; border: none; color: #94a3b8; cursor: pointer; text-decoration: underline; font-size: 0.85rem; padding: 0 4px;" onclick="cancelRedirect()">Annuler</button>
        </div>

        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="/" class="btn" style="background: linear-gradient(135deg, #e67e22, #d35400); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(230,126,34,0.35);">
                <i class="fa-solid fa-house"></i> <span>Retour à l'accueil</span>
            </a>
            <a href="/SearchUi" class="btn" style="background: #f1f5f9; color: #1e293b; font-weight: 700; padding: 12px 24px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1;">
                <i class="fa-solid fa-map-location-dot" style="color: #0284c7;"></i> <span>Carte &amp; Annuaire</span>
            </a>
            <a href="/mur" class="btn" style="background: #f8fafc; color: #475569; font-weight: 600; padding: 12px 20px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-comments"></i> <span>Le Mur de Bourges</span>
            </a>
        </div>
    </div>
</div>

<style>
@keyframes vbFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
</style>

<script>
(function() {
    var timeLeft = 8;
    var countdownEl = document.getElementById('vb-countdown');
    var bannerEl = document.getElementById('vb-auto-redirect');
    var targetUrl = (document.referrer && document.referrer.indexOf(window.location.host) !== -1 && document.referrer !== window.location.href)
        ? document.referrer 
        : '/';
    
    var timer = setInterval(function() {
        timeLeft--;
        if (countdownEl) countdownEl.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(timer);
            window.location.href = targetUrl;
        }
    }, 1000);

    window.cancelRedirect = function() {
        clearInterval(timer);
        if (bannerEl) bannerEl.style.display = 'none';
    };
})();
</script>
