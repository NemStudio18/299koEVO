{# Landing page personnalisée avec structure Hero -> Recherche -> Résultats #}

{# Section Hero avec promesse et CTA #}
<section class="landing-hero">
    <div class="landing-hero-content">
        <h1 class="landing-hero-title">{{landing.hero_title}}</h1>
        <p class="landing-hero-subtitle">{{landing.hero_subtitle}}</p>
        
        {# CTA - seulement si non connecté #}
        {% if IS_LOGGED == false %}
        <div class="landing-hero-cta">
            <a href="{{landing.cta_primary_url}}" class="cta-button cta-primary">
                {{landing.cta_primary_text}}
            </a>
            <a href="{{landing.cta_secondary_url}}" class="cta-button cta-secondary">
                {{landing.cta_secondary_text}}
            </a>
        </div>
        {% endif %}
    </div>
</section>

{# Section Recherche - juste après le Hero #}
<section class="landing-search">
    <div class="landing-search-container">
        {# Formulaire de recherche simple - sans hook pour éviter les problèmes #}
        <div class="landing-search-form-wrapper">
            <form method="get" action="/search/result" id="landing-search-form">
                <input 
                    type="text" 
                    id="landing-search-input" 
                    name="q" 
                    placeholder="{{landing.search_placeholder}}"
                    autocomplete="off"
                />
                <button type="submit" id="landing-search-button" aria-label="Rechercher">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
        
        {# Micro-texte d'orientation #}
        <p class="landing-search-hint">
            <i class="fa-solid fa-magnifying-glass"></i>
            {{landing.search_placeholder}}
        </p>
    </div>
</section>

{# Section Rappel collaboratif - avant les résultats #}
<section class="landing-collaborative">
    <div class="landing-collaborative-content">
        <p class="landing-collaborative-text">
            {{landing.collaborative_text}}
        </p>
        {% if not isLoggedIn %}
        <p class="landing-contribute-text">
            <a href="{{landing.contribute_url}}">{{landing.contribute_text}}</a>
        </p>
        {% endif %}
    </div>
</section>

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

{# Section "Roadmap" - 3 étapes max #}
<section class="landing-roadmap">
    <div class="landing-roadmap-content">
        <h2 class="landing-section-title">Et la suite ?</h2>
        
        <ul class="roadmap-list">
            <li class="roadmap-item roadmap-item-done">
                <span class="roadmap-icon">✔</span>
                <div class="roadmap-content">
                    <strong>Annuaire collaboratif</strong>
                    <span class="roadmap-status">Déjà en ligne</span>
                </div>
            </li>
            <li class="roadmap-item roadmap-item-in-progress">
                <span class="roadmap-icon">🛠️</span>
                <div class="roadmap-content">
                    <strong>Fiches enrichies</strong>
                    <span class="roadmap-status">Horaires, liens, photos, corrections</span>
                </div>
            </li>
            <li class="roadmap-item roadmap-item-planned">
                <span class="roadmap-icon">🚀</span>
                <div class="roadmap-content">
                    <strong>Outils pour les pros</strong>
                    <span class="roadmap-status">Réclamation, mise à jour, visibilité</span>
                </div>
            </li>
        </ul>
        
        <div class="roadmap-cta">
            <a href="{{landing.roadmap_url}}" class="cta-button cta-secondary">
                Voir la roadmap complète
            </a>
        </div>
    </div>
</section>

{# Section Résultats - sera remplie dynamiquement par JavaScript #}
<section class="landing-results" id="landing-results">
    {# Les résultats seront chargés ici via AJAX quand l'utilisateur interagit avec la recherche #}
    <div class="landing-results-placeholder">
        <p>Utilisez la recherche ci-dessus pour découvrir les lieux de l'agglomération.</p>
    </div>
</section>

{# CTA final - avant le footer #}
<section class="landing-final-cta">
    <div class="landing-final-cta-content">
        <h2>Vous voulez aider à améliorer Bourges ?</h2>
        <p>
            En créant un compte, vous pouvez corriger les informations,
            proposer des lieux et soutenir les acteurs locaux.
        </p>
        <a href="{{landing.final_cta_url}}" class="cta-button cta-primary">
            Créer un compte
        </a>
    </div>
</section>

{# Styles spécifiques à la landing page - CSS MINIMALISTE POUR MOBILE #}
<style>
/* CSS minimaliste pour éviter les bugs mobile */
.roadmap-list {
    padding: 0;
    margin: 0;
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
    background: linear-gradient(135deg, var(--header-bg) 0%, #2a4a6c 100%);
    color: white;
    padding: 4rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
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
}

.landing-hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-button {
    display: inline-block;
    padding: 14px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.cta-primary {
    background: var(--primary-color);
    color: white;
}

.cta-primary:hover {
    background: var(--primary-hover-color);
}

.cta-secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.cta-secondary:hover {
    background: white;
    color: var(--header-bg);
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
-->

