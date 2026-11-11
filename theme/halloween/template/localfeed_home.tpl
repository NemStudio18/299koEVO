{% set HIDE_PAGE_TITLE = true %}
<div class="localfeed-home-wrapper">
    <!-- Barre d'accès rapide pleine largeur (identique landing) -->
    <div class="localfeed-top-nav-wrap" style="width: 100%; max-width: 1200px; margin: 0 auto 15px; padding: 15px 15px 0;">
        <nav class="landing-quick-nav" aria-label="Accès rapide aux rubriques">
            <a href="/SearchUi" class="quick-nav-item" title="Rechercher par carte">
                <span class="qn-label">Carte &amp; Annuaire</span>
            </a>
            <a href="/agenda" class="quick-nav-item" title="Voir l'agenda des sorties">
                <span class="qn-label">Agenda</span>
            </a>
            <a href="/recherche/commerces" class="quick-nav-item" title="Commerces et artisans">
                <span class="qn-label">Commerces</span>
            </a>
            <a href="/recherche/services" class="quick-nav-item" title="Services publics et démarches">
                <span class="qn-label">Services</span>
            </a>
            <a href="/bons-plans" class="quick-nav-item quick-nav-deals" title="Bons plans déstockage et anti-gaspi">
                <span class="qn-label">Bons Plans</span>
            </a>
            <a href="/offers" class="quick-nav-item quick-nav-offers" title="Offres et réductions en magasin">
                <span class="qn-label">Offres Promo</span>
            </a>
            <a href="/recherche/patrimoine" class="quick-nav-item" title="Patrimoine et lieux incontournables">
                <span class="qn-label">Patrimoine</span>
            </a>
            <a href="/pro" class="quick-nav-item quick-nav-pro" title="Espace professionnel — Phase pilote gratuite" style="margin-left:auto; color:#64748b; font-style:italic; font-size:0.82rem; border-left:1px solid #e2e8f0;">
                <span class="qn-label">Vous êtes un professionnel ?</span>
            </a>
        </nav>
    </div>

    <div class="localfeed-container" style="padding-top: 5px;">
        <!-- Badge En direct de Bourges -->
        <div style="text-align: center; margin-bottom: 12px;">
            <div class="localfeed-title-badge">
                <i class="fa-solid fa-bolt-lightning text-gold"></i> En direct de Bourges
            </div>
        </div>

        <!-- Filtres rapides sur une seule ligne -->
        <div class="localfeed-filters">
            <button class="feed-filter-btn active" data-filter="all" onclick="filterFeed('all', this)" title="Afficher tout le flux">
                <i class="fa-solid fa-layer-group"></i> Tout
            </button>
            <button class="feed-filter-btn" data-filter="posts" onclick="filterFeed('posts', this)" title="Publications des commerces">
                <i class="fa-solid fa-store"></i> Commerces
            </button>
            <button class="feed-filter-btn" data-filter="events" onclick="filterFeed('events', this)" title="Sorties et événements à venir">
                <i class="fa-solid fa-calendar-check"></i> Événements
            </button>
            <button class="feed-filter-btn" data-filter="blog" onclick="filterFeed('blog', this)" title="Articles de blog et actualités">
                <i class="fa-solid fa-newspaper"></i> Blog
            </button>
            <button class="feed-filter-btn" data-filter="offers" onclick="filterFeed('offers', this)" title="Bons plans déstockage et anti-gaspi">
                <i class="fa-solid fa-tag"></i> Bons Plans
            </button>
        </div>

        <!-- Bandeau "nouvelles publications" (apparaît quand du contenu frais arrive) -->
        <button type="button" id="localfeed-new-banner" class="localfeed-new-banner" hidden onclick="LiveFeed.showFresh()">
            <i class="fa-solid fa-arrow-up"></i> <span class="lnb-text">Nouvelles publications</span>
        </button>

        <!-- Conteneur du Flux Infini -->
        <div id="localfeed-stream" class="localfeed-stream">
            <?php 
                $timeline = $feedTimeline ?? ['items' => [], 'has_more' => false, 'page' => 1];
                $filter = 'all';
                include ROOT . 'plugin/LocalFeed/template/feed_items.tpl';
            ?>
        </div>

    </div>
</div>

<link rel="stylesheet" href="/plugin/LocalFeed/public/css/localfeed.css?v=<?= @filemtime(ROOT . 'plugin/LocalFeed/public/css/localfeed.css') ?>" />
<script src="/plugin/LocalFeed/public/js/localfeed.js?v=<?= @filemtime(ROOT . 'plugin/LocalFeed/public/js/localfeed.js') ?>"></script>

<script>
const LiveFeed = (() => {
    const stream = document.getElementById('localfeed-stream');
    const banner = document.getElementById('localfeed-new-banner');
    let currentFilter = 'all';
    let loading = false;
    let freshHtml = null;
    let observer = null;

    const knownIds = () => new Set([...stream.querySelectorAll('article.feed-card')].map(a => a.id));

    function animateCards(root) {
        const cards = [...root.querySelectorAll('article.feed-card:not(.lf-shown)')];
        cards.forEach((c, i) => {
            c.style.animationDelay = Math.min(i * 70, 560) + 'ms';
            c.classList.add('lf-enter', 'lf-shown');
        });
    }

    function observeSentinel() {
        if (observer) observer.disconnect();
        const sentinel = stream.querySelector('.feed-load-more[data-next]');
        if (!sentinel) return;
        observer = new IntersectionObserver(entries => {
            if (entries.some(e => e.isIntersecting)) loadMore(sentinel);
        }, { rootMargin: '600px 0px' });
        observer.observe(sentinel);
    }

    async function fetchHtml(url) {
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
    }

    async function loadMore(sentinel) {
        if (loading) return;
        loading = true;
        try {
            const html = await fetchHtml(sentinel.dataset.next);
            const tpl = document.createElement('template');
            tpl.innerHTML = html;
            // Évite les doublons si le classement a légèrement bougé entre deux pages
            const ids = knownIds();
            tpl.content.querySelectorAll('article.feed-card').forEach(a => { if (ids.has(a.id)) a.remove(); });
            sentinel.replaceWith(tpl.content);
            animateCards(stream);
            if (typeof initLocalFeedWeather === 'function') initLocalFeedWeather();
        } catch (e) {
            sentinel.innerHTML = '<button class="feed-action-btn" onclick="LiveFeed.retry(this)">Réessayer le chargement</button>';
        } finally {
            loading = false;
            observeSentinel();
        }
    }

    async function filter(type, btn) {
        document.querySelectorAll('.feed-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = type;
        freshHtml = null;
        banner.hidden = true;
        stream.innerHTML = '<div class="feed-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i> Actualisation du flux...</div>';
        try {
            stream.innerHTML = await fetchHtml('/localfeed/feed?filter=' + encodeURIComponent(type));
            animateCards(stream);
            if (typeof initLocalFeedWeather === 'function') initLocalFeedWeather();
            observeSentinel();
        } catch (e) {
            stream.innerHTML = '<div class="localfeed-empty-state"><p>Erreur lors du chargement du flux.</p></div>';
        }
    }

    // Vérifie toutes les 90 s s'il y a du contenu nouveau en tête de flux
    async function poll() {
        if (document.hidden) return;
        try {
            const html = await fetchHtml('/localfeed/feed?filter=' + encodeURIComponent(currentFilter));
            const tpl = document.createElement('template');
            tpl.innerHTML = html;
            const ids = knownIds();
            const fresh = [...tpl.content.querySelectorAll('article.feed-card')].filter(a => !ids.has(a.id));
            // Ne compte que les nouveautés réellement placées en tête (les 5 premières cartes)
            const top = [...tpl.content.querySelectorAll('article.feed-card')].slice(0, 5);
            const n = fresh.filter(a => top.includes(a)).length;
            if (n > 0) {
                freshHtml = html;
                banner.querySelector('.lnb-text').textContent = n + (n > 1 ? ' nouvelles publications' : ' nouvelle publication');
                banner.hidden = false;
            }
        } catch (e) { /* silencieux */ }
    }

    function showFresh() {
        if (!freshHtml) return;
        stream.innerHTML = freshHtml;
        freshHtml = null;
        banner.hidden = true;
        animateCards(stream);
        if (typeof initLocalFeedWeather === 'function') initLocalFeedWeather();
        observeSentinel();
        stream.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function retry(btn) {
        const s = btn.closest('.feed-load-more');
        s.innerHTML = '<div class="feed-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</div>';
        loadMore(s);
    }

    animateCards(stream);
    observeSentinel();
    setInterval(poll, 90000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    return { filter, showFresh, retry };
})();

function filterFeed(filterType, btn) { LiveFeed.filter(filterType, btn); }
</script>
