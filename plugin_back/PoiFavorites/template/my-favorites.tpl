<div class="poi-favorites-container">
    <h1>Mes POIs favoris</h1>
    
    {% IF favorites %}
        <ul class="poi-favorites-list">
            {% FOR favorite IN favorites %}
            <li class="poi-favorite-item">
                <div class="poi-favorite-header">
                    <div class="poi-name-wrapper">
                        <div class="poi-favorite-name">
                            {% IF favorite.detail_url_final %}
                            <a href="{{ favorite.detail_url_final }}">
                                {% IF favorite.poi_name %}{{ favorite.poi_name }}{% ELSE %}POI #{{ favorite.poi_id }}{% ENDIF %}
                            </a>
                            {% ELSE %}
                            {% IF favorite.poi_name %}{{ favorite.poi_name }}{% ELSE %}POI #{{ favorite.poi_id }}{% ENDIF %}
                            {% ENDIF %}
                        </div>
                    </div>
                </div>
                
                <div class="poi-info">
                    {% IF favorite.poi_category OR favorite.poi_subcategory %}
                    <div class="poi-favorite-info-item">
                        {% IF favorite.poi_category %}
                        <span class="poi-favorite-badge blue">
                            {{ favorite.poi_category }}
                        </span>
                        {% ENDIF %}
                        {% IF favorite.poi_subcategory %}
                        <span class="poi-favorite-badge gray">
                            {{ favorite.poi_subcategory }}
                        </span>
                        {% ENDIF %}
                    </div>
                    {% ENDIF %}
                    
                    {% IF favorite.poi_address OR favorite.poi_city %}
                    <div class="poi-favorite-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>
                            {% IF favorite.poi_address %}{{ favorite.poi_address }}{% ENDIF %}
                            {% IF favorite.poi_address AND favorite.poi_city %}, {% ENDIF %}
                            {% IF favorite.poi_city %}{{ favorite.poi_city }}{% ENDIF %}
                        </span>
                    </div>
                    {% ENDIF %}
                    
                    {% IF favorite.poi_description %}
                    <div class="poi-favorite-info-item">
                        {{ favorite.poi_description }}
                    </div>
                    {% ENDIF %}
                </div>
                
                <div class="poi-favorite-actions">
                    <div class="poi-favorite-meta">
                        <span>
                            <i class="fa-solid fa-calendar-plus"></i>
                            Ajouté le {{ favorite.created_at_formatted }}
                        </span>
                        {% IF favorite.has_active_offers %}
                        <span class="poi-favorite-badge purple">
                            <i class="fa-solid fa-tag"></i>
                            {{ favorite.offers_count }} offre{% IF favorite.offers_count > 1 %}s{% ENDIF %} active{% IF favorite.offers_count > 1 %}s{% ENDIF %}
                        </span>
                        {% ENDIF %}
                        {% IF favorite.detail_url_final %}
                        <a href="{{ favorite.detail_url_final }}" class="button-search button">
                            <i class="fa-solid fa-eye"></i> <span class="button-text">Voir les détails</span>
                        </a>
                        {% ENDIF %}
                    </div>
                    <form method="POST" action="{{ ROUTER.generate("poi-favorites-remove") }}" style="margin: 0;" onsubmit="return confirm('Êtes-vous sûr de vouloir retirer ce POI de vos favoris ?');">
                        <input type="hidden" name="token" value="{{ csrfToken }}">
                        <input type="hidden" name="poi_id" value="{{ favorite.poi_id }}">
                        <button type="submit" class="button small danger">
                            <i class="fa-solid fa-heart-crack"></i> Retirer
                        </button>
                    </form>
                </div>
            </li>
            {% ENDFOR %}
        </ul>
    {% ELSE %}
        <p class="poi-reviews-empty">Vous n'avez pas encore de POIs favoris.</p>
    {% ENDIF %}
</div>

