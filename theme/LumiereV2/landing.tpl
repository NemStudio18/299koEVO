{# Landing page personnalisée avec structure Hero -> Qualification -> Recherche -> Résultats #}

<!-- {# =========================
   SECTION HERO
   ========================= #}
<section class="landing-hero">
    <div class="landing-hero-content">
        <h1 class="landing-hero-title">{{landing.hero_title}}</h1>
        <p class="landing-hero-subtitle">{{landing.hero_subtitle}}</p>

        {# CTA - seulement si non connecté (MASQUÉS PAR CSS AU DÉPART) #}
        {% if not IS_LOGGED %}
{# =========================
   AUTO-QUALIFICATION (NOUVEAU)
   ========================= #}
    <div class="landing-intent-content">
        <p class="landing-intent-title">
            Vous êtes ici plutôt pour :
        </p>

        <div class="landing-intent-actions">
            <button type="button" class="intent-btn" data-intent="discover">
                Découvrir Bourges
            </button>

            <button type="button" class="intent-btn" data-intent="contribute">
                Contribuer / corriger des infos
            </button>

            <button type="button" class="intent-btn" data-intent="pro">
                Gagner en visibilité
            </button>
        </div>

        <div class="landing-intent-ctas">
            <div class="intent-block intent-discover" hidden>
                <p>Explorez Bourges et découvrez tous les lieux incontournables.</p>
                <a href="{{landing.cta_secondary_url}}" class="cta-button cta-secondary">
                    Explorer maintenant
                </a>
            </div>

            <div class="intent-block intent-contribute" hidden>
                <p>Contribuez à la base locale en corrigeant ou ajoutant des informations.</p>
                <a href="{{landing.cta_primary_url}}" class="cta-button cta-primary">
                    Créer un compte et contribuer
                </a>
            </div>

            <div class="intent-block intent-pro" hidden>
                <p>Optimisez votre visibilité et mettez à jour votre fiche professionnelle.</p>
                <a href="{{landing.cta_primary_url}}" class="cta-button cta-primary">
                    Réclamer ma fiche
                </a>
            </div>
        </div>
    </div>
        {% endif %}
    </div>
</section>-->


{# Section "Comment ça marche ?" - 3 cards #}
<section class="landing-how">
    <div class="landing-how-content">
        <h2 class="landing-section-title">Comment fonctionne Vivre Bourges ?</h2>
        
        <div class="landing-cards">
            <div class="landing-card">
                <div class="landing-card-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3>Données ouvertes</h3>
                <p>
                    Les lieux proviennent en grande partie d'OpenStreetMap,
                    une base collaborative mondiale.
                </p>
                <a href="{{landing.card_osm_url}}" class="landing-card-link">En savoir plus</a>
            </div>
            
            <div class="landing-card">
                <div class="landing-card-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3>Contributions locales</h3>
                <p>
                    Habitants, assos et commerçants améliorent les fiches
                    pour les rendre fiables et utiles.
                </p>
                <a href="{{landing.card_contribute_url}}" class="landing-card-link">Comment contribuer</a>
            </div>
            
            <div class="landing-card">
                <div class="landing-card-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3>Amélioration continue</h3>
                <p>
                    Chaque correction aide toute la communauté
                    à mieux vivre le territoire.
                </p>
            </div>
        </div>
    </div>
</section>

{# Section "Technologie & transparence" - 2 cards compactes #}
<section class="landing-tech">
    <div class="landing-tech-content">
        <h2 class="landing-section-title">Un projet ouvert et transparent</h2>
        
        <div class="landing-cards landing-cards-small">
            <div class="landing-card">
                <h3>OpenStreetMap</h3>
                <p>
                    Une base de données libre et collaborative,
                    utilisée par des milliers de projets dans le monde.
                </p>
            </div>
            
            <div class="landing-card">
                <h3>Recherche performante</h3>
                <p>
                    Un moteur de recherche moderne pour trouver rapidement
                    les lieux qui comptent près de chez vous.
                </p>
            </div>
        </div>
    </div>
</section>

{# =========================
   SECTION STATISTIQUES
   ========================= #}
<section class="landing-stats">
    <div class="landing-stats-content">
        <h2 class="landing-stats-title">La communaute en chiffres</h2>

        <div class="stats-grid">
            {# Nombre d'utilisateurs #}
            <div class="stat-card">
                <div class="stat-number">{{userCount}}</div>
                <div class="stat-label">Utilisateurs inscrits</div>
                <div class="stat-description">Membres actifs de la communauté</div>
            </div>

            {# Visiteurs #}
            <div class="stat-card">
                <div class="stat-number">{{trafficStats.total_visitors}}</div>
                <div class="stat-label">Visiteurs/semaine</div>
                <div class="stat-description">Personnes qui découvrent Bourges</div>
            </div>

            {# Fiches réclamées #}
            <div class="stat-card">
                <div class="stat-number">{{claimStats.claimed_pois}}</div>
                <div class="stat-label">Fiches réclamées</div>
                <div class="stat-description">Établissements gérés par leurs propriétaires</div>
            </div>

            {# Pages vues #}
            <div class="stat-card">
                <div class="stat-number">{{trafficStats.total_pages}}</div>
                <div class="stat-label">Pages consultées</div>
                <div class="stat-description">Cette semaine</div>
            </div>
        </div>

        {# Statistiques secondaires #}
        <div class="secondary-stats">
            <div class="secondary-stats-grid">
                {# Mises à jour récentes #}
                <div class="secondary-stat">
                    <span class="secondary-number">{{recentUpdates}}</span>
                    <span class="secondary-label">mises à jour ces dernières 24h</span>
                </div>

                {# Favoris #}
                <div class="secondary-stat">
                    <span class="secondary-number">{{favoritesStats.total_favorites}}</span>
                    <span class="secondary-label">lieux mis en favoris</span>
                </div>

                {# Forum #}
                <div class="secondary-stat">
                    <span class="secondary-number">{{forumStats.total_topics}}</span>
                    <span class="secondary-label">sujets de discussion</span>
                </div>

                {# Forum #}
                <div class="secondary-stat">
                    <span class="secondary-number">{{forumStats.total_posts}}</span>
                    <span class="secondary-label">messages forum</span>
                </div>

                {# Blog #}
                <div class="secondary-stat">
                    <span class="secondary-number">{{blogStats.total_articles}}</span>
                    <span class="secondary-label">articles publiés</span>
                </div>
            </div>
        </div>

        {# Top contributeurs #}
        {% if hasTopContributors %}
        <div class="top-contributors">
            <h3>Top contributeurs</h3>
            <div class="contributors-list">
                {% for contributor in topContributors %}
                <div class="contributor-item">
                    <span class="contributor-name">{{contributor.username}}</span>
                    <span class="contributor-score">{{contributor.total_score}} pts</span>
                </div>
                {% endfor %}
            </div>
        </div>
        {% endif %}

        {# Contributions communautaires #}
        {% if gamificationStats.has_contributions %}
        <div class="contributions-stats">
            <h3>Contributions communautaires</h3>
            <div class="contributions-content">
                <div class="contribution-metrics">
                    <div class="metric-item">
                        <span class="metric-number">{{gamificationStats.total_contributions}}</span>
                        <span class="metric-label">contributions totales</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-number">{{gamificationStats.active_contributors}}</span>
                        <span class="metric-label">contributeurs actifs</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-number">{{gamificationStats.recent_contributions}}</span>
                        <span class="metric-label">cette semaine</span>
                    </div>
                </div>
            </div>
        </div>
        {% endif %}
    </div>
</section>

{# =========================
   SECTION ÉVÉNEMENTS DU JOUR
   ========================= #}
<section class="landing-events">
    <div class="landing-events-content">
        <h2 class="landing-events-title">Evenements du jour</h2>
        {% if hasTodayEvents %}
        <div class="events-list">
            {% for event in todayEvents %}
            <div class="event-item">
                <div class="event-time">{{event.start_time}}</div>
                <div class="event-details">
                    <h4 class="event-title">{{event.title}}</h4>
                    {% if event.location %}
                    <div class="event-location">{{event.location}}</div>
                    {% endif %}
                    {% if event.description_short %}
                    <div class="event-description">{{event.description_short}}</div>
                    {% endif %}
                    {% if event.start_date_formatted %}
                    <div class="event-dates">{{event.start_date_formatted}}{% if event.end_date_formatted and event.end_date_formatted != event.start_date_formatted %} - {{event.end_date_formatted}}{% endif %}</div>
                    {% endif %}
                </div>
            </div>
            {% endfor %}
        </div>
        {% else %}
        <div class="events-empty">
            <div class="empty-content">
                <div class="empty-icon">🌟</div>
                <h3 class="empty-title">Soyez le premier événement d'aujourd'hui !</h3>
                <p class="empty-description"><strong>Saviez-vous que publier un événement booste instantanément votre visibilité ?</strong><br>Obtenez le badge <b>"Actif ce mois-ci"</b> et remontez dans les résultats de recherche.</p>
                <a href="/agenda" class="cta-button cta-primary" style="margin-top: 15px;">
                    <i class="fa-solid fa-calendar-plus"></i> Proposer un événement
                </a>
            </div>
        </div>
        {% endif %}
    </div>
</section>

{# =========================
   SECTION OFFRES PROMOTIONNELLES
   ========================= #}
<section class="landing-offers">
    <div class="landing-offers-content">
        <h2 class="landing-offers-title">Offres du moment</h2>
        {% if hasActiveOffers %}
        <div class="offers-grid">
            {% for offer in activeOffers %}
            <div class="offer-card">
                <h4 class="offer-title">{{offer.title}}</h4>
                <div class="offer-advantage">{{offer.advantage}}</div>
                {% if offer.condition_text %}
                <div class="offer-conditions">{{offer.condition_text}}</div>
                {% endif %}
                {% if offer.end_date_formatted %}
                <div class="offer-dates">
                    Valable jusqu'au {{offer.end_date_formatted}}
                </div>
                {% endif %}
            </div>
            {% endfor %}
        </div>
        {% else %}
        <div class="offers-empty">
            <div class="empty-content">
                <div class="empty-icon">🔥</div>
                <h3 class="empty-title">Prenez l'avantage, publiez une offre !</h3>
                <p class="empty-description">Attirez de nouveaux clients et <strong>gagnez des places dans notre annuaire</strong> en publiant une promotion exclusive. En plus, vous gagnez le badge <b>"Actif ce mois-ci"</b>.</p>
                <a href="/offers" class="cta-button cta-primary" style="margin-top: 15px;">
                    <i class="fa-solid fa-plus"></i> Créer ma première offre
                </a>
            </div>
        </div>
        {% endif %}
    </div>
</section>

{# =========================
   SECTION ARTICLES RÉCENTS
   ========================= #}
{% if hasRecentArticles %}
<section class="landing-blog">
    <div class="landing-blog-content">
        <h2 class="landing-blog-title">Dernieres actualites</h2>
        <div class="blog-articles">
            {% for article in blogStats.recent_articles %}
            <a href="/blog/{{article.id}}" class="blog-article-link">
                <div class="blog-article">
                    <h4 class="article-title">{{article.name}}</h4>
                    <div class="article-meta">
                        {% if article.date_formatted %}
                        <span class="article-date">{{article.date_formatted}}</span>
                        {% endif %}
                        {% if article.category %}
                        <span class="article-category">{{article.category}}</span>
                        {% endif %}
                    </div>
                    {% if article.description %}
                    <div class="article-excerpt">{{article.description}}</div>
                    {% endif %}
                </div>
            </a>
            {% endfor %}
        </div>
        {% if blogStats.total_articles > 3 %}
        <div class="section-link">
            <a href="/blog" class="cta-button cta-secondary">Voir tous les articles ({{blogStats.total_articles}})</a>
        </div>
        {% endif %}
    </div>
</section>
{% endif %}

{# =========================
   SECTION POI AU HASARD
   ========================= #}
{% if hasRandomPois %}
<section class="landing-poi-carousel">
    <div class="landing-poi-content">
        <h2 class="landing-poi-title">Decouvrez Bourges</h2>
        <p class="landing-poi-subtitle">Explorez des lieux au hasard dans notre annuaire</p>

        <div class="poi-carousel-container">
            <div class="poi-carousel slider" id="poi-carousel">
                {% for poi in randomPois %}
                <div class="poi-card-wrapper {% if loop.index == 1 %}active{% endif %}">
                    <div class="poi-card">
                        <div class="poi-card-header">
                            <h4>{{poi.name}}</h4>
                            {% if poi.has_address %}
                            <p class="poi-address"><i class="fa-solid fa-location-dot"></i> {{poi.address}}</p>
                            {% endif %}
                        </div>
                        <div class="poi-card-body">
                            {% if poi.has_opening_hours %}
                            <p class="poi-hours"><i class="fa-solid fa-clock"></i> {{poi.opening_hours}}</p>
                            {% endif %}
                            {% if poi.has_phone %}
                            <p class="poi-phone"><i class="fa-solid fa-phone"></i> <a href="tel:{{poi.phone}}">{{poi.phone}}</a></p>
                            {% endif %}
                        </div>
                        <div class="poi-card-footer">
                            <a href="{{poi.detail_url_final}}" class="button-search button-small">
                                <i class="fa-solid fa-eye"></i> Voir les détails
                            </a>
                        </div>
                    </div>
                </div>
                {% endfor %}
            </div>

            <div class="carousel-navigation">
                <button class="carousel-btn carousel-prev" aria-label="Précédent">‹</button>
                <button class="carousel-btn carousel-next" aria-label="Suivant">›</button>
            </div>
        </div>

        <div class="section-link">
            <a href="/searchEVO" class="cta-button cta-primary">Explorer tous les lieux</a>
        </div>
    </div>
</section>
{% endif %}

{# Styles spécifiques à la landing page - CSS MINIMALISTE POUR MOBILE #}
<style>
/* CSS minimaliste pour éviter les bugs mobile */
.roadmap-list {
    padding: 0;
    margin: 0;
}

/* Hide navigation elements on landing page */
.landing-page #navigation {
    display: none !important;
}

.landing-page .brand-copy {
    display: none !important;
}

.landing-page .poi-edit-menu-dropdown {
    display: none !important;
}

/* Keep weather and auth links visible on landing page */
.landing-page .meteo-widget-compact {
    display: block !important;
    visibility: visible !important;
}

.landing-page .nav-auth-links {
    display: flex !important;
    visibility: visible !important;
}

/* Adjust header layout for landing page */
.landing-page .header-brand {
    justify-content: center !important;
}

.landing-page .nav-wrapper {
    justify-content: space-between !important;
    align-items: center !important;
}

.roadmap-item {
    margin-bottom: 16px;
    padding: 16px;
    background: white;
    border-left: 4px solid #e9ecef;
}

.roadmap-item-done {
    border-left-color: #10b981;
}

.roadmap-item-in-progress {
    border-left-color: #ff8c42;
}

.roadmap-item-planned {
    border-left-color: #3b82f6;
}

.roadmap-icon {
    display: inline-block;
    margin-right: 12px;
    font-size: 20px;
}

.roadmap-content {
    display: inline-block;
    vertical-align: top;
}

.roadmap-content strong {
    display: block;
    font-size: 16px;
    margin-bottom: 4px;
}

.roadmap-status {
    display: block;
    font-size: 14px;
    color: #6c757d;
}

/* Désactiver backdrop-filter sur mobile (très coûteux) */
@media (max-width: 768px) {
    * {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
}

.landing-hero {
/* background: linear-gradient(135deg, rgba(26, 26, 26, 0.05) 0%, rgba(26, 26, 26, 0.02) 100%); */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: var(--search-radius);
    padding: 40px 30px;
    margin-bottom: 30px;
    text-align: center;
    border: 1px solid var(--search-border);
    /* backdrop-filter: blur(15px); */
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    margin-bottom: 24px;
    border: 1px solid var(--search-border-light);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    color: var(--search-text);
    padding: 40px 20px;
    text-align: center;
}

.landing-hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.landing-hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.landing-hero-subtitle {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    line-height: 1.6;
    color: #fff;
}

.landing-hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}



.landing-search {
    padding: 2rem 1rem;
    background: var(--background-color);
}

.landing-search-container {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-search-hint {
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.landing-search-hint i {
    margin-right: 0.5rem;
}

.landing-search-form-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.landing-search-form-wrapper form {
    display: flex;
    gap: 0.5rem;
    background: white;
    border-radius: 0.5rem;
    padding: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.landing-search-form-wrapper input[type="text"] {
    flex: 1;
    padding: 0.875rem 1rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 1rem;
}

.landing-search-form-wrapper input[type="text"]:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: -2px;
}

.landing-search-form-wrapper button {
    padding: 0.875rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 600;
}

.landing-search-form-wrapper button:hover {
    background: var(--primary-hover-color);
}

.landing-collaborative {
    padding: 2rem 1rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.landing-collaborative-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.landing-collaborative-text {
    color: #495057;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.landing-contribute-text {
    margin-top: 1rem;
}

.landing-contribute-text a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.landing-contribute-text a:hover {
    text-decoration: underline;
}

.landing-results {
    padding: 2rem 1rem;
    min-height: 400px;
}

.landing-results-placeholder {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    color: #6c757d;
    padding: 4rem 2rem;
}

/* Section "Comment ça marche ?" */
.landing-how {
    padding: 4rem 1rem;
    background: var(--background-color);
}

.landing-how-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-section-title {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 3rem;
    color: var(--font-color);
}

.landing-cards {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 32px;
}

/* Sur tablette et desktop */
@media (min-width: 768px) {
    .landing-cards {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 32px;
    }
    
    .landing-cards > .landing-card {
        width: 300px;
    }
}

.landing-card {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
}

.landing-card-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
    text-align: center;
}

.landing-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--font-color);
}

.landing-card p {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.landing-card-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.landing-card-link::after {
    content: "→";
}

/* Section "Technologie & transparence" - cards compactes */
.landing-tech {
    padding: 3rem 1rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.landing-tech-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-cards-small {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

/* Sur tablette et desktop */
@media (min-width: 768px) {
    .landing-cards-small {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 32px;
    }
    
    .landing-cards-small > .landing-card {
        width: 350px;
    }
}

.landing-cards-small .landing-card {
    padding: 1.5rem;
}

.landing-cards-small .landing-card h3 {
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
}

.landing-cards-small .landing-card p {
    font-size: 0.95rem;
    margin-bottom: 0;
}

/* Section "Roadmap" */
.landing-roadmap {
    padding: 4rem 1rem;
    background: var(--background-color);
}

.landing-roadmap-content {
    max-width: 800px;
    margin: 0 auto;
}

.roadmap-list {
    list-style: none;
    padding: 0;
    margin: 2rem 0;
}

.roadmap-item {
    display: flex;
    align-items: flex-start;
    gap: 24px;
    padding: 24px;
    margin-bottom: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border-left: 4px solid #e9ecef;
}

.roadmap-item-done {
    border-left-color: var(--success-color);
}

.roadmap-item-in-progress {
    border-left-color: var(--primary-color);
}

.roadmap-item-planned {
    border-left-color: var(--info-color);
}

.roadmap-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.roadmap-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.roadmap-content strong {
    font-size: 1.1rem;
    color: var(--font-color);
    display: block;
}

.roadmap-status {
    color: #6c757d;
    font-size: 0.9rem;
}

.roadmap-cta {
    text-align: center;
    margin-top: 2rem;
}

/* CTA final */
.landing-final-cta {
    padding: 4rem 1rem;
    background: linear-gradient(135deg, var(--header-bg) 0%, #2a4a6c 100%);
    color: white;
    text-align: center;
}

.landing-final-cta-content {
    max-width: 600px;
    margin: 0 auto;
}

.landing-final-cta h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.landing-final-cta p {
    font-size: 1.1rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    line-height: 1.6;
}

.landing-final-cta .cta-button {
    font-size: 1.1rem;
    padding: 1rem 2.5rem;
}

@media (max-width: 768px) {
    .landing-hero {
        padding: 32px 16px;
    }
    
    .landing-hero-title {
        font-size: 28px;
    }
    
    .landing-hero-subtitle {
        font-size: 16px;
    }
    
    .landing-hero-cta {
        flex-direction: column;
    }
    
    .cta-button {
        width: 100%;
        text-align: center;
    }
    
    .landing-section-title {
        font-size: 24px;
    }
    
    /* Sur mobile : les cartes prennent toute la largeur */
    .landing-cards,
    .landing-cards-small {
        flex-direction: column;
    }
    
    .landing-cards > .landing-card,
    .landing-cards-small > .landing-card {
        width: 100%;
    }
    
    .landing-card {
        padding: 24px;
    }
    
    .roadmap-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .landing-final-cta {
        padding: 48px 16px;
    }
    
    .landing-final-cta h2 {
        font-size: 24px;
    }
}
*/

/* =====================================================
   INTENTION BUTTONS — révéler les CTA
   ===================================================== */

/* CTA initialement masqués */
.landing-hero-cta,
.landing-final-cta {
    opacity: 0;
    pointer-events: none;
    transform: translateY(12px);
    transition: opacity .3s ease, transform .3s ease;
}

/* CTA révélés quand une intention est sélectionnée */
html.intent-selected .landing-hero-cta,
html.intent-selected .landing-final-cta {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

/* Afficher CTA spécifiques selon l'intention */
html.intent-discover .cta-secondary {
    display: inline-block;
}

html.intent-contribute .cta-primary,
html.intent-pro .cta-primary {
    display: inline-block;
}

.intent-block {
    display: none;
}

.intent-block[hidden] {
    display: none !important;
}

.intent-block:not([hidden]) {
    display: block;
    margin-top: 1.5rem;
    text-align: center;
}

.landing-intent-title, .intent-block p{
 color: #fff;
}

/* =========================================
   STYLES POUR LES STATISTIQUES ENRICHIES
   ========================================= */

.landing-stats {
    background: var(--background-color);
    color: var(--search-text);
    padding: 3rem 1rem;
    margin: 2rem 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.landing-stats-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-stats-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 3rem;
    color: var(--search-text);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.stat-label {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--search-text);
}

.stat-description {
    font-size: 0.9rem;
    opacity: 0.9;
    color: #6c757d;
}

.top-contributors {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
}

.top-contributors h3 {
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.contributors-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contributor-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.contributor-name {
    font-weight: 600;
    color: white;
}

.contributor-score {
    color: #ffd700;
    font-weight: 700;
    font-size: 0.9rem;
}

/* =========================================
   STYLES POUR LES STATISTIQUES SECONDAIRES
   ========================================= */

.secondary-stats {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}

.secondary-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.secondary-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.secondary-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.secondary-label {
    font-size: 0.85rem;
    color: #6c757d;
    opacity: 0.9;
}

/* =========================================
   STYLES POUR LA GAMIFICATION
   ========================================= */

.gamification-stats {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}

.gamification-stats h3 {
    color: white;
    margin-bottom: 1.5rem;
    text-align: center;
}

.gamification-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.points-total {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.points-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffd700;
    display: block;
    margin-bottom: 0.5rem;
}

.points-label {
    color: #e0e7ff;
    font-size: 0.9rem;
}

.palier-distribution h4 {
    color: white;
    margin-bottom: 1rem;
    text-align: center;
}

.palier-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
}

.palier-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.palier-name {
    font-weight: 600;
    color: white;
    font-size: 0.85rem;
}

.palier-count {
    color: #ffd700;
    font-weight: 700;
    font-size: 0.9rem;
    background: rgba(255, 215, 0, 0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

/* =========================================
   STYLES POUR LES CONTRIBUTIONS
   ========================================= */

.contributions-stats {
    margin-top: 2rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    color: var(--search-text);
}

.contributions-stats h3 {
    color: var(--search-text);
    margin-bottom: 1.5rem;
    text-align: center;
}

.contributions-content {
    display: flex;
    justify-content: center;
}

.contribution-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
    width: 100%;
    max-width: 600px;
}

.metric-item {
    text-align: center;
    padding: 1rem;
    background: var(--background-color);
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.metric-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    display: block;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.85rem;
    color: #6c757d;
    opacity: 0.9;
}

/* =========================================
   STYLES POUR LE CAROUSEL POI
   ========================================= */

.landing-poi-carousel {
    background: var(--background-color);
    color: var(--search-text);
    padding: 3rem 1rem;
    margin: 2rem 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.landing-poi-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-poi-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--search-text);
}

.landing-poi-subtitle {
    text-align: center;
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    color: #6c757d;
}

.poi-carousel-container {
    position: relative;
    perspective: 1000px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.poi-carousel {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 1rem 0;
    transform-style: preserve-3d;
}

.poi-carousel::-webkit-scrollbar {
    display: none;
}

.poi-card-wrapper {
    flex: 0 0 320px;
    scroll-snap-align: start;
    transform-style: preserve-3d;
    transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Divi Supreme Style Coverflow Carousel - Navigation améliorée */
.poi-carousel-container {
    position: relative;
    perspective: 1400px;
    perspective-origin: center center;
    overflow: hidden;
    margin-bottom: 4rem;
    height: 450px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%);
    border-radius: 16px;
    padding: 2rem 0;
}

.poi-carousel {
    position: relative;
    width: 100%;
    height: 400px;
    transform-style: preserve-3d;
    transform: rotateX(0deg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.poi-card-wrapper {
    position: absolute;
    width: 320px;
    height: 400px;
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    transform-origin: center center;
    cursor: pointer;
    user-select: none;
}

.poi-card-wrapper:hover {
    transform: scale(1.03) !important;
    filter: brightness(1.05);
}

.poi-card-wrapper:active {
    transform: scale(0.98) !important;
}

/* Enhanced 3D card styling */
.poi-card-wrapper .poi-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

/* Navigation buttons styling for 3D effect */
.carousel-navigation {
    position: absolute;
    bottom: -80px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 1rem;
    z-index: 20;
}

/* Enhanced 3D visual effects */
.poi-card-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg,
        rgba(255,255,255,0.2) 0%,
        rgba(255,255,255,0.1) 30%,
        rgba(0,0,0,0.1) 70%,
        rgba(0,0,0,0.2) 100%);
    border-radius: 12px;
    pointer-events: none;
    z-index: 1;
    opacity: 0.8;
}

.poi-card-wrapper .poi-card {
    position: relative;
    z-index: 2;
    background: linear-gradient(145deg,
        rgba(255,255,255,0.95) 0%,
        rgba(255,255,255,0.9) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.3);
}

/* Add reflection effect for more realism */
.poi-card-wrapper::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 10px;
    right: 10px;
    height: 20px;
    background: radial-gradient(ellipse, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 70%);
    border-radius: 50%;
    filter: blur(3px);
    z-index: 0;
    opacity: 0.6;
    transform: scaleY(0.3);
}

/* Utiliser les styles standards poi-card du thème */
.poi-card-wrapper .poi-card {
    margin: 0;
    height: 100%;
}

.carousel-navigation {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.carousel-btn {
    background: white;
    border: 1px solid #e9ecef;
    color: var(--search-text);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.carousel-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* =========================================
   STYLES POUR LES ARTICLES DE BLOG
   ========================================= */

.landing-blog {
    background: #f8fafc;
    padding: 3rem 1rem;
    margin: 2rem 0;
}

.landing-blog-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-blog-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: #1e293b;
}

.blog-articles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.blog-article-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.2s ease;
}

.blog-article-link:hover {
    transform: translateY(-2px);
}

.blog-article {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    height: 100%;
    transition: box-shadow 0.2s ease;
}

.blog-article:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.article-title {
    margin: 0 0 1rem 0;
    font-size: 1.2rem;
    color: #1e293b;
    line-height: 1.3;
}

.article-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.article-date {
    color: #64748b;
}

.article-category {
    color: #3b82f6;
    font-weight: 500;
}

.article-excerpt {
    color: #64748b;
    line-height: 1.5;
    font-size: 0.9rem;
}

.section-link {
    text-align: center;
    margin-top: 2rem;
}

/* =========================================
   STYLES POUR LES ÉVÉNEMENTS
   ========================================= */

.landing-events {
    background: #f8fafc;
    padding: 3rem 1rem;
    margin: 2rem 0;
}

.landing-events-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-events-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: #1e293b;
}

.events-list {
    display: grid;
    gap: 1rem;
}

.events-empty,
.offers-empty {
    text-align: center;
    padding: 3rem 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    margin: 1rem 0;
}

.empty-content {
    max-width: 500px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--search-text);
    margin-bottom: 0.5rem;
}

.empty-text {
    font-size: 1.1rem;
    color: var(--search-text);
    margin-bottom: 1rem;
    font-weight: 500;
}

.empty-description {
    color: #6c757d;
    margin-bottom: 2rem;
    font-size: 0.95rem;
    line-height: 1.5;
}

.event-item {
    display: flex;
    gap: 1rem;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.event-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.event-time {
    font-size: 1.1rem;
    font-weight: 700;
    color: #3b82f6;
    min-width: 80px;
}

.event-details h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
    color: #1e293b;
}

.event-location {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.event-description {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* =========================================
   STYLES POUR LES OFFRES
   ========================================= */

.landing-offers {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 3rem 1rem;
    margin: 2rem 0;
}

.landing-offers-content {
    max-width: 1200px;
    margin: 0 auto;
}

.landing-offers-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: white;
}

.offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.offer-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.offer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.offer-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: white;
}

.offer-advantage {
    font-size: 1.1rem;
    font-weight: 600;
    color: #d1fae5;
    margin-bottom: 1rem;
}

.offer-conditions {
    font-size: 0.9rem;
    color: #a7f3d0;
    margin-bottom: 1rem;
    font-style: italic;
}

.offer-dates {
    font-size: 0.8rem;
    color: #bbf7d0;
    font-weight: 500;
}

/* =========================================
   RESPONSIVE DESIGN
   ========================================= */

@media (max-width: 768px) {
    .landing-stats-title,
    .landing-events-title,
    .landing-offers-title,
    .landing-blog-title {
        font-size: 2rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .secondary-stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .stat-card {
        padding: 1.5rem;
    }

    .stat-number {
        font-size: 2.5rem;
    }

    .secondary-number {
        font-size: 1.75rem;
    }

    .event-item {
        flex-direction: column;
        gap: 0.5rem;
    }

    .event-time {
        min-width: auto;
        align-self: flex-start;
    }

    .offers-grid {
        grid-template-columns: 1fr;
    }

    .blog-articles {
        grid-template-columns: 1fr;
    }

    .gamification-content {
        gap: 1rem;
    }

    .points-number {
        font-size: 2rem;
    }

    .palier-list {
        grid-template-columns: 1fr;
    }

    .palier-item {
        padding: 0.5rem;
    }

    .contribution-metrics {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .metric-item {
        padding: 0.75rem;
    }

    .metric-number {
        font-size: 1.5rem;
    }

    .landing-poi-title {
        font-size: 2rem;
    }

    .poi-card-wrapper {
        flex: 0 0 280px;
    }

    .poi-carousel {
        gap: 0.75rem;
    }
}
</style>

{# JavaScript désactivé temporairement pour debug mobile #}
<!--
<script>
document.addEventListener('DOMContentLoaded', function() {
    // La recherche sera gérée par le plugin search via ses hooks
    // On peut ajouter ici des interactions spécifiques à la landing page si nécessaire
    console.log('Landing page loaded');
});
</script>

<script>
(function () {
    // Fonction pour appliquer l'intention
    function setIntent(intent) {
        const root = document.documentElement; // <html> toujours accessible
        root.classList.add('intent-selected');
        root.classList.add('intent-' + intent);
        console.log('Intent applied:', intent); // Debug
    }

    // Event listener global pour tous les boutons intent-btn
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.intent-btn');
        if (!btn) return;

        setIntent(btn.dataset.intent);
    });
})();
</script>-->
{% if IS_LOGGED == false %}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sélection du conteneur où on veut injecter la landing
    const siteDesc = document.getElementById('siteDesc');
    if (!siteDesc) return;

    // HTML de la landing à injecter
    const landingHeroHTML = `
<section class="landing-hero">
    <div class="landing-hero-content">
        <h1 class="landing-hero-title">Tous les lieux utiles de Bourges, construits par ses habitants</h1>
        <p class="landing-hero-subtitle">Un annuaire local collaboratif pour découvrir, corriger et soutenir les commerces, assos et services du territoire.</p>

        <div class="landing-intent-content">
            <p class="landing-intent-title">Vous êtes ici plutôt pour :</p>

            <div class="landing-intent-actions">
                <button type="button" class="intent-btn" data-intent="discover">
                    Découvrir Bourges
                </button>

                <button type="button" class="intent-btn" data-intent="contribute">
                    Contribuer / corriger des infos
                </button>

                <button type="button" class="intent-btn" data-intent="pro">
                    Gagner en visibilité
                </button>
            </div>

            <div class="landing-intent-ctas">
                <div class="intent-block intent-discover" hidden>
                    <p>Explorez Bourges et découvrez tous les lieux incontournables.</p>
                    <a href="/searchEVO" class="cta-button cta-secondary">Explorer maintenant</a>
                </div>

                <div class="intent-block intent-contribute" hidden>
                    <p>Contribuez à la base locale en corrigeant ou ajoutant des informations.</p>
                    <a href="/users/register" class="cta-button cta-primary">Créer un compte et contribuer</a>
                </div>

                <div class="intent-block intent-pro" hidden>
                    <p>Optimisez votre visibilité et mettez à jour votre fiche professionnelle.</p>
                    <a href="/users/register" class="cta-button cta-primary">Réclamer ma fiche</a>
                </div>
            </div>
        </div>
    </div>
</section>
    `;

    // Injection
    siteDesc.innerHTML = landingHeroHTML;

    // Réinitialiser les événements pour les boutons "intent-btn"
    const blocks = document.querySelectorAll('.intent-block');
    function setIntent(intent) {
        blocks.forEach(b => b.hidden = true);
        const active = document.querySelector('.intent-block.intent-' + intent);
        if (active) active.hidden = false;
        console.log('Intent applied:', intent);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.intent-btn');
        if (!btn) return;
        setIntent(btn.dataset.intent);
    });
});


(function() {
    const blocks = document.querySelectorAll('.intent-block');

    function setIntent(intent) {
        blocks.forEach(b => b.hidden = true);
        const active = document.querySelector('.intent-block.intent-' + intent);
        if (active) active.hidden = false;
        console.log('Intent applied:', intent);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.intent-btn');
        if (!btn) return;
        setIntent(btn.dataset.intent);
    });
})();
</script>
{% endif %}

<script>
window.addEventListener('load', function() {
    const carouselContainer = document.getElementById('poi-carousel');
    if (!carouselContainer) return;

    const cards = Array.from(document.querySelectorAll('.poi-card-wrapper'));
    const totalCards = cards.length;
    if (totalCards === 0) return;

    let currentIndex = 0;
    let isAnimating = false;
    let autoPlayInterval = null;

    function moveCarousel(direction) {
        if (isAnimating || totalCards <= 1) return;
        isAnimating = true;

        // Modulo math for robust backward/forward looping
        currentIndex = (((currentIndex + direction) % totalCards) + totalCards) % totalCards;

        updateCarouselPositions();

        // Unlock after transition duration
        setTimeout(() => {
            isAnimating = false;
        }, 600);
    }

    function updateCarouselPositions() {
        cards.forEach((card, index) => {
            let diff = index - currentIndex;

            // Wrap around logic
            if (diff > totalCards / 2) {
                diff -= totalCards;
            } else if (diff < -totalCards / 2) {
                diff += totalCards;
            }

            const absDiff = Math.abs(diff);

            // Hide cards that are far away from the center to prevent chaotic visual fly-bys
            if (absDiff > 2) {
                card.style.opacity = '0';
                card.style.pointerEvents = 'none';
                card.style.zIndex = '-1';
                card.style.transform = `translateX(${diff > 0 ? 500 : -500}px) translateZ(-500px) scale(0.5)`;
                return;
            }

            const translateX = diff * 220; 
            const translateZ = -absDiff * 150;
            const rotateY = diff * 35; 
            const scale = absDiff === 0 ? 1 : Math.max(0.6, 1 - absDiff * 0.2); 
            const opacity = absDiff === 0 ? 1 : Math.max(0.3, 1 - (absDiff * 0.4)); 

            card.style.transform = `translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
            card.style.zIndex = 100 - absDiff * 10;
            card.style.opacity = opacity.toString();
            
            // Only the front card should be interactive
            card.style.pointerEvents = absDiff === 0 ? 'auto' : 'none'; 
            card.style.visibility = 'visible';
            
            // Dynamic shadow box for center card
            if (absDiff === 0) {
                card.style.boxShadow = '0 25px 50px rgba(0,0,0,0.35)';
            } else {
                card.style.boxShadow = `0 ${absDiff * 12}px ${absDiff * 24}px rgba(0,0,0,0.15)`;
            }
        });
    }

    // Initialize positions
    updateCarouselPositions();

    // Arrows
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    if (prevBtn) {
        prevBtn.addEventListener('click', () => { pauseAutoPlay(); moveCarousel(-1); });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => { pauseAutoPlay(); moveCarousel(1); });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { pauseAutoPlay(); moveCarousel(-1); }
        if (e.key === 'ArrowRight') { pauseAutoPlay(); moveCarousel(1); }
    });

    // Touch Swipe Support - Native
    let touchStartX = 0;
    let touchEndX = 0;
    const swipeThreshold = 50;
    const carouselWrapper = document.querySelector('.poi-carousel-container');

    if (carouselWrapper) {
        carouselWrapper.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            pauseAutoPlay();
        }, {passive: true});

        carouselWrapper.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            setTimeout(resumeAutoPlay, 1000);
        }, {passive: true});
        
        // Prevent default scrolling only when sliding horizontally inside the container
        carouselWrapper.addEventListener('touchmove', (e) => {
            if (!touchStartX) return;
            let currentX = e.changedTouches[0].screenX;
            let diffX = Math.abs(currentX - touchStartX);
            if (diffX > 10) {
                // e.preventDefault();
            }
        }, {passive: true});
    }

    function handleSwipe() {
        if (touchEndX < touchStartX - swipeThreshold) {
            moveCarousel(1); 
        } else if (touchEndX > touchStartX + swipeThreshold) {
            moveCarousel(-1); 
        }
    }

    // Auto-play Logic
    function startAutoPlay() {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            if (!isAnimating) moveCarousel(1);
        }, 3000); // Set to 3s to easily verify it works
    }

    function pauseAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }

    function resumeAutoPlay() {
        if (!autoPlayInterval) {
            startAutoPlay();
        }
    }

    // Hover to pause
    if(carouselWrapper){
        carouselWrapper.addEventListener('mouseenter', pauseAutoPlay);
        carouselWrapper.addEventListener('mouseleave', resumeAutoPlay);
    }

    startAutoPlay();

    cards.forEach((card, index) => {
        card.addEventListener('click', (e) => {
            if (index !== currentIndex) {
                e.preventDefault();
                let diff = index - currentIndex;
                if (diff > totalCards / 2) diff -= totalCards;
                if (diff < -totalCards / 2) diff += totalCards;
                
                pauseAutoPlay();
                moveCarousel(diff > 0 ? 1 : -1);
            }
        });
    });
});

// Hide navigation elements strictly for the landing page aesthetics
window.addEventListener('load', function() {
    const nav = document.getElementById('navigation');
    if (nav) nav.style.display = 'none';

    const brandCopy = document.querySelector('.brand-copy');
    if (brandCopy) brandCopy.style.display = 'none';

    const headerBrand = document.querySelector('.header-brand');
    if (headerBrand) headerBrand.style.justifyContent = 'center';

    const navWrapper = document.querySelector('.nav-wrapper');
    if (navWrapper) navWrapper.style.justifyContent = 'space-between';
    
    // Explicitly show widgets
    const meteoWidget = document.querySelector('.meteo-widget-compact');
    if (meteoWidget) {
        meteoWidget.style.display = 'block';
        meteoWidget.style.visibility = 'visible';
    }

    const authLinks = document.querySelector('.nav-auth-links');
    if (authLinks) {
        authLinks.style.display = 'flex';
        authLinks.style.visibility = 'visible';
    }
});
</script>