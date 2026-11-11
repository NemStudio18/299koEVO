{% for poi in pois %}
    <div class="search-result-item {% if poi.claimed and poi.claimed_validated %}verified{% endif %}" 
         data-poi-id="{{poi.id}}"
         style="{% if poi.has_background_color %}background-color: {{poi.background_color}};{% endif %}">
        
        <div class="search-result-global-index">
            #{{poi.global_index}}
        </div>

        <div class="result-image">
            <a href="{{poi.detail_url_final}}" hx-boost="false" class="search-result-image-link" title="{{poi.name}}">
                {% if poi.has_logo and poi.logo %}
                    <img src="{{poi.logo}}" alt="Logo {{poi.name}}" class="poi-logo-small search-result-logo-small">
                {% elseif poi.photo %}
                    <img src="{{poi.photo}}" alt="{{poi.name}}" class="poi-thumb-image" loading="lazy">
                {% elseif poi.lat and poi.lon %}
                    <div id="minimap-result-{{poi.id}}-{{poi.global_index}}" class="poi-minimap result-minimap" 
                         data-lat="{{poi.lat}}" data-lon="{{poi.lon}}" data-name="{{poi.name}}"
                         data-family="{{poi.family|default('other')}}" data-icon="{{poi.category_icon|default('location-dot')}}"></div>
                    <div class="minimap-fallback-icon family-{{poi.family|default('other')}}">
                        <i class="fa-solid fa-{{poi.category_icon|default('store')}}"></i>
                    </div>
                {% else %}
                    <div class="poi-category-fallback-icon">
                        <i class="fa-solid fa-{{poi.category_icon|default('store')}}"></i>
                    </div>
                {% endif %}
            </a>
        </div>

        <div class="result-content">
            <div class="result-header">
                <div class="poi-title-row">
                    <h3 class="poi-name">
                        <a href="{{poi.detail_url_final}}" hx-boost="false">{{poi.name}}</a>
                        {% if poi.claimed and poi.claimed_validated %}
                            <i class="fa-solid fa-circle-check poi-verified-icon" title="Établissement vérifié"></i>
                        {% endif %}
                    </h3>
                    <div class="poi-rating-summary">
                        {% if poi.stars > 0 %}
                            <span class="poi-stars-score"><i class="fa-solid fa-star"></i> {{poi.stars}}/5</span>
                        {% else %}
                            <span class="poi-no-rating">- / 5</span>
                        {% endif %}
                        <span class="poi-comment-count" title="Voir les avis">
                            <i class="fa-solid fa-comment-dots"></i> {{poi.review_count|default(0)}}
                        </span>
                    </div>
                </div>
            </div>

            <div class="search-result-meta">
                {% if poi.type_label %}
                    <span class="search-result-category-badge">
                        <i class="fa-solid fa-tag"></i> {{poi.type_label}}
                        {% if poi.has_brand %}<span class="poi-brand-label">({{poi.brand}})</span>{% endif %}
                    </span>
                {% endif %}
                
                {% if poi.distance_formatted %}
                    <span class="result-distance">
                        <i class="fa-solid fa-location-arrow"></i> {{poi.distance_formatted}}
                    </span>
                {% endif %}
                
                {% if poi.is_open_now_html %}
                    {{poi.is_open_now_html|raw}}
                {% endif %}

                {% if poi.wheelchair == 'yes' or poi.wheelchair == 'limited' %}
                    <span class="result-feature-icon pmr" title="Accès PMR {% if poi.wheelchair == 'limited' %}(Limité){% endif %}">
                        <i class="fa-solid fa-wheelchair"></i>
                    </span>
                {% endif %}
                {% if poi.parking == 'yes' %}
                    <span class="result-feature-icon parking" title="Parking disponible">
                        <i class="fa-solid fa-square-p"></i>
                    </span>
                {% endif %}
            </div>

            <div class="search-result-description">
                {% if poi.desc_list %}
                    <p class="poi-desc-text">{{poi.desc_list}}</p>
                {% endif %}

                {# Contribuer CTA: seulement si fiche clairement incomplète (score < 70) ET pas de description #}
                {% if poi.datascore < 70 and not poi.desc_list %}
                    <div class="search-description-empty">
                        <span class="search-no-description">
                            {% if allowRegistrations %}
                                <i class="fa-solid fa-feather-pointed" style="color:#ff8c42; margin-right:5px;"></i>
                                C'est un peu vide ici... Aidez-nous à décrire ce lieu pour la communauté !
                                {% if isLoggedIn %}
                                    <a href="{{SHOW.siteUrl}}/PoiManager/suggest/{{poi.id}}" hx-boost="false" class="cta-link">Contribuer</a>
                                {% else %}
                                    <a href="{{SHOW.siteUrl}}/users/login?msg=edit&poi_name={{poi.name|url_encode}}&return_url={{poi.detail_url_final|url_encode}}" hx-boost="false" class="cta-link">Contribuer</a>
                                {% endif %}
                            {% else %}
                                <i class="fa-solid fa-compass-drafting" style="color:#ff8c42; margin-right:5px;"></i>
                                Fiche en cours d'enrichissement par l'équipe locale. Suivez Vivre-Bourges.fr pour être informé des prochaines ouvertures aux contributions !
                            {% endif %}
                        </span>
                    </div>
                {% endif %}
            </div>

            <div class="poi-datascore-row">
                <div class="datascore-container" title="Score de complétion de la fiche : {{poi.datascore}}%">
                    <div class="datascore-bar-bg">
                        <div class="datascore-bar-fill" style="width: {{poi.datascore}}%; background: {% if poi.datascore > 80 %}#2ecc71{% elseif poi.datascore > 50 %}#f1c40f{% else %}#e67e22{% endif %};"></div>
                    </div>
                    <span class="datascore-value">{{poi.datascore}}%</span>
                </div>
                {% if poi.datascore >= 70 and not poi.claimed %}
                    <span class="datascore-cta">Fiche presque complète ! 
                        {% if isLoggedIn %}
                            <a href="{{SHOW.siteUrl}}/claim/{{poi.id}}">Réclamez-la</a>
                        {% elseif allowRegistrations %}
                            <a href="{{SHOW.siteUrl}}/users/login?msg=claim&poi_name={{poi.name|url_encode}}&return_url={{poi.detail_url_final|url_encode}}">Réclamez-la</a>
                        {% else %}
                            <a href="{{SHOW.siteUrl}}/contact?subject=Revendication%20:%20{{poi.name|url_encode}}&message=Bonjour%2C%20je%20suis%20le%20propri%C3%A9taire%20ou%20g%C3%A9rant%20de%20l%27%C3%A9tablissement%20%C2%AB%20{{poi.name|url_encode}}%20%C2%BB%20(R%C3%A9f%20%23{{poi.id}}).%20Je%20souhaite%20revendiquer%20cette%20fiche.">Réclamez-la</a>
                        {% endif %}
                    </span>
                {% endif %}
            </div>

            {% if poi.has_address %}
                <div class="result-address">
                    <i class="fa-solid fa-location-dot"></i> 
                    <span class="poi-address-container">
                        {{poi.address_line1}}{% if poi.address_city %}, {{poi.address_city}}{% endif %}
                    </span>
                </div>
            {% endif %}

            <div class="search-result-actions">
                <div class="search-result-btns-group">
                    <div class="search-result-social-links">
                        {# Website icon first if available #}
                        {% if poi.website %}
                             <a href="{{poi.website}}" target="_blank" rel="noopener" class="icon-link website highlight" title="Site web">
                                <i class="fa-solid fa-globe"></i>
                            </a>
                        {% endif %}

                        {% if poi.links %}
                            {% for link in poi.links %}
                                {# Avoid duplicating website if already shown #}
                                {% if link.platform != 'website' %}
                                    <a href="{{link.url}}" target="_blank" rel="noopener" class="icon-link {{link.platform}} highlight" title="{{link.label}}">
                                        <i class="{{link.icon_class}}"></i>
                                    </a>
                                {% endif %}
                            {% endfor %}
                        {% endif %}
                    </div>

                    <div class="search-result-quick-tabs">
                        <a href="{{poi.detail_url_final}}#tab-offres" hx-boost="false" class="quick-tab-btn"><i class="fa-solid fa-tag"></i> Offres</a>
                        <a href="{{poi.detail_url_final}}#tab-agenda" hx-boost="false" class="quick-tab-btn"><i class="fa-solid fa-calendar-day"></i> Agenda</a>
                    </div>
                </div>
                
                <div class="search-result-badges">
                    {% if poi.claimed and poi.claimed_validated %}
                        <span class="poi-badge-owned" title="Propriétaire vérifié"><i class="fa-solid fa-user-check"></i> Officiel</span>
                    {% else %}
                        {# Bouton Propriétaire #}
                        {% if isLoggedIn %}
                            <a href="{{SHOW.siteUrl}}/claim/{{poi.id}}" hx-boost="false" class="poi-badge-claim" title="Revendiquer cet établissement"><i class="fa-solid fa-hand-sparkles"></i> Propriétaire ?</a>
                        {% elseif allowRegistrations %}
                            <a href="{{SHOW.siteUrl}}/users/login?msg=claim&poi_name={{poi.name|url_encode}}&return_url={{poi.detail_url_final|url_encode}}" hx-boost="false" class="poi-badge-claim" title="Revendiquer cet établissement"><i class="fa-solid fa-hand-sparkles"></i> Propriétaire ?</a>
                        {% else %}
                            <a href="{{SHOW.siteUrl}}/contact?subject=Revendication%20:%20{{poi.name|url_encode}}&message=Bonjour%2C%20je%20suis%20le%20propri%C3%A9taire%20ou%20g%C3%A9rant%20de%20l%27%C3%A9tablissement%20%C2%AB%20{{poi.name|url_encode}}%20%C2%BB%20(R%C3%A9f%20%23{{poi.id}}).%20Les%20inscriptions%20%C3%A9tant%20actuellement%20ferm%C3%A9es%2C%20je%20souhaite%20revendiquer%20cette%20fiche%20et%20mettre%20%C3%A0%20jour%20mes%20informations." hx-boost="false" class="poi-badge-claim" title="Contacter l'équipe pour revendiquer votre fiche"><i class="fa-solid fa-hand-sparkles"></i> Propriétaire ?</a>
                        {% endif %}
                    {% endif %}

                    {# Bouton Communauté (affiché seulement si inscriptions ouvertes) #}
                    {% if allowRegistrations %}
                        {% if not isLoggedIn %}
                            <a href="{{registerLink}}?poi={{poi.id}}&return_url={{poi.detail_url_final}}" hx-boost="false" class="poi-community-cta">Rejoindre et participer</a>
                        {% endif %}
                    {% endif %}
                </div>
            </div>
        </div>

        {% if poi.has_cover %}
        <div class="result-image-right">
             <img src="{{poi.cover_url}}" alt="{{poi.name}}" class="search-result-side-img" loading="lazy">
        </div>
        {% endif %}
    </div>
{% endfor %}

{% if hasNext %}
    <div id="load-more-container" class="load-more-wrapper" style="margin: 20px 0; text-align: center; width: 100%;">
        <button class="load-more-btn"
                hx-get="{{nextPageUrl}}"
                hx-trigger="intersect once, click"
                hx-target="#load-more-container"
                hx-swap="outerHTML"
                hx-indicator="#load-more-spinner"
                style="background: #ff8c42; color: white; border: none; padding: 12px 30px; border-radius: 50px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(255,140,66,0.3); transition: transform 0.2s;">
            <i class="fa-solid fa-plus"></i>
            <span>Afficher plus de résultats</span>
            <i id="load-more-spinner" class="fa-solid fa-circle-notch fa-spin htmx-indicator"></i>
        </button>
    </div>
{% endif %}

{% if hx_init_maps %}
<script>
    // Re-initialize minimaps for newly loaded items
    if (window.initResultMaps) {
        initResultMaps();
    }
    // Append new POIs to the main map
    if (window.HomeMapMinimal && window.HomeMapMinimal.appendPois) {
        try {
            var incrementalPois = {{pois_json|raw}};
            if (incrementalPois && incrementalPois.length > 0) {
                window.HomeMapMinimal.appendPois(incrementalPois);
            }
        } catch(e) { console.error("Error appending POIs:", e); }
    }
</script>
{% endif %}

<style>
    .search-result-item { display: flex; position: relative; }
    .result-content { flex: 1; }
    .result-image-right { width: 150px; min-width: 150px; border-left: 1px solid #eee; background: #fafafa; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .search-result-side-img { width: 100%; height: 100%; object-fit: cover; }
    
    @media (max-width: 768px) {
        .result-image-right { width: 100px; min-width: 100px; }
    }
    @media (max-width: 480px) {
        .result-image-right { display: none; }
    }
</style>

