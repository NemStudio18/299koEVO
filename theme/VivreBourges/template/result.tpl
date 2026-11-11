<section class="search-results">
    <header class="search-results-header">
        <h1>Résultats de recherche</h1>
        {% if query %}
        <p class="search-query-info">Recherche pour : <strong>"{{query}}"</strong></p>
        {% endif %}
        {% if is_fuzzy and suggested_query %}
        <div class="search-fuzzy-notice alert alert-info" style="margin: 12px 0; padding: 10px 16px; background: rgba(59, 130, 246, 0.12); border-left: 4px solid #3b82f6; border-radius: 6px; font-size: 0.95rem; color: var(--text-color, #1e293b);">
            <i class="fa-solid fa-lightbulb" style="color: #3b82f6; margin-right: 6px;"></i> Aucun résultat pour <em>"{{original_query}}"</em>. Affichage des résultats pour <strong>"{{suggested_query}}"</strong>.
        </div>
        {% endif %}
        {% if totalResults > 0 %}
        <p class="search-count">{{totalResults}} résultat{% if totalResults > 1 %}s{% endif %} trouvé{% if totalResults > 1 %}s{% endif %}</p>
        {% endif %}
    </header>

    <div class="search-form-container">
        <form method="get" action="{ ROUTER.generate("SearchUi-result") }" class="search-form">
            <div class="search-input-group-with-button">
                <div class="search-input-group search-relative-group">
                    <input 
                        type="text" 
                        id="search-query" 
                        name="q" 
                        placeholder="Restaurant, café, hôtel..." 
                        {% if query %}value="{{query}}"{% endif %}
                        autocomplete="off"
                        class="search-input-padded"
                    />
                    <button type="button" id="locate-me-btn" title="Utiliser ma position" class="search-locate-btn-absolute">
                        <i class="fa-solid fa-crosshairs"></i>
                    </button>
                </div>
                <div class="search-button-group">
                    <button type="submit" class="button primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Rechercher
                    </button>
                </div>
            </div>
            
            <!-- Champs cachés pour préserver les filtres -->
            {% if lat %}<input type="hidden" name="lat" value="{{lat}}">{% endif %}
            {% if lon %}<input type="hidden" name="lon" value="{{lon}}">{% endif %}
            {% if radius %}<input type="hidden" name="radius" value="{{radius}}">{% endif %}
            {% if sort %}<input type="hidden" name="sort" value="{{sort}}">{% endif %}
        </form>
    </div>

    {% if resultsCount > 0 %}
    <div class="search-results-list">
        {% for result in results %}
        <article class="search-result-item {% if result.claimed and result.claimed_validated %}verified{% endif %}">
            <div class="result-image">
                <a href="{{result.detail_url_final}}" hx-boost="false" class="search-result-image-link" title="{{result.name}}">
                    {% if result.photo %}
                        <img src="{{result.photo}}" alt="{{result.name}}" class="search-result-photo">
                    {% elseif result.has_logo and result.logo %}
                        <img src="{{result.logo}}" alt="Logo {{result.name}}" class="poi-logo-small search-result-logo-small">
                    {% elseif CORE.callHook('map:is-enabled') and result.lat and result.lon %}
                        <div id="minimap-res-{{result.id}}" class="poi-minimap result-minimap" 
                             data-lat="{{result.lat}}" data-lon="{{result.lon}}" data-name="{{result.name}}"
                             data-family="{% if result.family %}{{result.family}}{% else %}other{% endif %}"
                             data-icon="{% if result.category_icon %}{{result.category_icon}}{% else %}location-dot{% endif %}"></div>
                        <div class="minimap-fallback-icon family-{% if result.family %}{{result.family}}{% else %}other{% endif %}">
                            <i class="fa-solid fa-{% if result.category_icon %}{{result.category_icon}}{% else %}store{% endif %}"></i>
                        </div>
                    {% elseif result.category_icon %}
                        <div class="result-category-avatar family-{% if result.family %}{{result.family}}{% else %}other{% endif %}">
                            <i class="fa-solid fa-{{result.category_icon}}"></i>
                        </div>
                    {% else %}
                        <div class="result-category-avatar family-other">
                            <i class="fa-solid fa-store"></i>
                        </div>
                    {% endif %}
                </a>
            </div>
            
            <div class="result-content">
                <div class="result-header">
                    <div class="poi-title-row">
                        <h3 class="poi-name">
                            <a href="{{result.detail_url_final}}" hx-boost="false">{{result.name}}</a>
                            {% if result.claimed and result.claimed_validated %}
                                <i class="fa-solid fa-circle-check poi-verified-icon" title="Établissement vérifié"></i>
                            {% endif %}
                        </h3>
                        <div class="poi-rating-summary">
                            {% if result.stars > 0 %}
                                <span class="poi-stars-score"><i class="fa-solid fa-star"></i> {{result.stars}}/5</span>
                            {% else %}
                                <span class="poi-no-rating">- / 5</span>
                            {% endif %}
                        </div>
                    </div>
                </div>

                <div class="search-result-meta">
                    {% if result.type_label %}
                        <span class="search-result-category-badge">
                            <i class="fa-solid fa-tag"></i> {{result.type_label}}
                            {% if result.has_brand %}<span class="poi-brand-label">({{result.brand}})</span>{% endif %}
                        </span>
                    {% endif %}
                    
                    {% if result.is_open_now_html %}
                        {{result.is_open_now_html|raw}}
                    {% endif %}
                </div>

                <div class="search-result-description">
                    {% if result.desc_list %}
                        <p class="poi-desc-text">{{result.desc_list}}</p>
                    {% endif %}

                    {% if result.has_description %}
                    <div class="search-result-description" title="{{result.description}}">
                        {{result.description}}
                    </div>
                    {% endif %}

                    {% hook 'poi:card-reassurance', result %}

                    {# Contribuer CTA: seulement si fiche clairement incomplète (score < 70) ET pas de description #}
                    {% if result.datascore < 70 and not result.desc_list %}
                        <div class="search-description-empty">
                            <span class="search-no-description">
                                <i class="fa-solid fa-feather-pointed" style="color:#ff8c42; margin-right:5px;"></i>
                                C'est un peu vide ici... Aidez-nous à décrire ce lieu pour la communauté !
                                <a href="{{SHOW.siteUrl}}/PoiManager/suggest/{{result.id}}" hx-boost="false" class="cta-link">Contribuer</a>
                            </span>
                        </div>
                    {% endif %}
                </div>

                <div class="poi-datascore-row">
                    <div class="datascore-container" title="Score de complétion de la fiche : {{result.datascore}}%">
                        <div class="datascore-bar-bg">
                            <div class="datascore-bar-fill" style="width: {{result.datascore}}%; background: {% if result.datascore > 80 %}#2ecc71{% elseif result.datascore > 50 %}#f1c40f{% else %}#e67e22{% endif %};"></div>
                        </div>
                        <span class="datascore-value">{{result.datascore}}%</span>
                    </div>
                    {% if result.datascore >= 70 and not result.claimed %}
                        <span class="datascore-cta">Fiche presque complète ! <a href="{{SHOW.siteUrl}}/claim/{{result.id}}">Réclamez-la</a></span>
                    {% endif %}
                </div>

                {% if result.has_address %}
                    <div class="result-address">
                        <i class="fa-solid fa-location-dot"></i> 
                        <span class="poi-address-container">
                            {{result.address}}
                        </span>
                    </div>
                {% endif %}

                <div class="search-result-actions">
                    <div class="search-result-btns-group">
                        <div class="search-result-social-links">
                            {% if result.website %}
                                 <a href="{{result.website}}" target="_blank" rel="noopener" class="icon-link website highlight" title="Site web">
                                    <i class="fa-solid fa-globe"></i>
                                </a>
                            {% endif %}

                            {% if result.links %}
                                {% for link in result.links %}
                                    {% if link.platform != 'website' %}
                                        <a href="{{link.url}}" target="_blank" rel="noopener" class="icon-link {{link.platform}} highlight" title="{{link.label}}">
                                            <i class="{{link.icon_class}}"></i>
                                        </a>
                                    {% endif %}
                                {% endfor %}
                            {% endif %}
                        </div>

                        <div class="search-result-quick-tabs">
                            <a href="{{result.detail_url_final}}#tab-offres" hx-boost="false" class="quick-tab-btn"><i class="fa-solid fa-tag"></i> Offres</a>
                            <a href="{{result.detail_url_final}}#tab-agenda" hx-boost="false" class="quick-tab-btn"><i class="fa-solid fa-calendar-day"></i> Agenda</a>
                        </div>
                    </div>
                    
                    <div class="search-result-badges">
                        {% if result.claimed and result.claimed_validated %}
                            <span class="poi-badge-owned" title="Propriétaire vérifié"><i class="fa-solid fa-user-check"></i> Officiel</span>
                        {% else %}
                            <a href="{{SHOW.siteUrl}}/claim/{{result.id}}" hx-boost="false" class="poi-badge-claim" title="Revendiquer cet établissement"><i class="fa-solid fa-hand-sparkles"></i> Propriétaire ?</a>
                        {% endif %}

                        {% if result.is_active_this_month %}
                            <span class="poi-badge-active" title="Mis à jour récemment"><i class="fa-solid fa-bolt"></i> Actif</span>
                        {% endif %}
                    </div>
                </div>
            </div>
        </article>
        {% endfor %}
    </div>

    {% if totalPages > 1 %}
    <div class="search-pagination">
        <div class="pagination-info">
            Page {{page}} sur {{totalPages}} ({{totalResults}} résultat{% if totalResults > 1 %}s{% endif %})
        </div>
        
        <div class="pagination-links">
            {% if prevPageUrl %}
            <a href="{{prevPageUrl}}" class="pagination-link pagination-prev">
                <i class="fa-solid fa-chevron-left"></i> Précédent
            </a>
            {% endif %}
            
            {% if nextPageUrl %}
            <a href="{{nextPageUrl}}" class="pagination-link pagination-next">
                Suivant <i class="fa-solid fa-chevron-right"></i>
            </a>
            {% endif %}
        </div>
    </div>
    {% endif %}

    {% else %}
    <div class="search-no-results">
        <p>Aucun résultat trouvé pour votre recherche.</p>
        <a href="{ ROUTER.generate("SearchUi-home") }" class="button">Nouvelle recherche</a>
    </div>
    {% endif %}
</section>

<script>
function toggleListFavorite(btn, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const poiId = btn.dataset.poiId;
    const isFavorite = btn.dataset.isFavorite === '1';
    
    // Si non connecté, on pourrait rediriger vers le login, 
    // mais le bouton n'est affiché que si CAN_FAVORITE est vrai, 
    // ce qui dépend généralement d'être connecté.
    
    fetch('/PoiFavorites/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            token: '{{csrfToken}}',
            poi_id: poiId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            btn.dataset.isFavorite = data.is_favorite ? '1' : '0';
            if (data.is_favorite) {
                btn.classList.add('active');
                btn.title = 'Retirer des favoris';
            } else {
                btn.classList.remove('active');
                btn.title = 'Ajouter aux favoris';
            }
        } else if (data.error === 'Not logged in') {
            window.location.href = '{{loginUrl}}';
        }
    })
    .catch(error => console.error('Error toggling favorite:', error));
}
</script>
{% if CORE.callHook('map:is-enabled') %}
{{ CORE.callHook('map:load-assets') }}
<script>
// Universal Minimap Init for Search Results
function initResultMaps() {
    if (typeof UniversalMap === 'undefined') return;
    
    const minimaps = document.querySelectorAll('.poi-minimap.result-minimap:not([data-mapped="true"])');
    if (minimaps.length === 0) return;

    minimaps.forEach(function(el) {
        const lat = parseFloat(el.getAttribute('data-lat'));
        const lon = parseFloat(el.getAttribute('data-lon'));
        const family = el.getAttribute('data-family') || 'other';
        const icon = el.getAttribute('data-icon') || 'location-dot';

        if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
            try {
                if (el.dataset.mapped === 'true' || el._leaflet_id) return;
                if (!el.id) el.id = 'map-result-' + Math.random().toString(36).substr(2, 9);
                
                const uMap = new UniversalMap(el.id, {
                    lat: lat,
                    lon: lon,
                    zoom: 15,
                    editable: false,
                    markerFamily: family,
                    markerIcon: icon
                });

                if (uMap.map) {
                    uMap.toggleLock(true);
                    uMap.map.removeControl(uMap.map.zoomControl);
                    
                    const refreshMap = () => {
                        if (uMap.map) {
                            uMap.map.invalidateSize();
                            uMap.map.setView([lat, lon], 15);
                        }
                    };
                    
                    requestAnimationFrame(() => {
                        setTimeout(refreshMap, 400); 
                        setTimeout(refreshMap, 1200);
                    });
                }
                el.dataset.mapped = 'true';
            } catch (e) {
                console.error('SearchUi: Error initializing result map:', e);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', initResultMaps);
document.addEventListener('htmx:afterSwap', (evt) => {
    if (evt.detail.target.querySelector && evt.detail.target.querySelector('.poi-minimap')) {
        setTimeout(initResultMaps, 200); 
    }
});
</script>
{% endif %}




