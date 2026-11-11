<section class="offers-index">
    <header class="offers-index-header">
        <h1><i class="fa-solid fa-tags"></i> Bons plans à Bourges</h1>
        <p>Des offres proposées par les commerçants locaux, valables en magasin.</p>
    </header>

    {% IF offers %}
    <div class="offers-grid">
        {% FOR offer IN offers %}
        <div class="offer-card">
            <div class="offer-card-header">
                <h3 class="offer-card-title">{{ offer.title }}</h3>
                <p class="offer-card-poi">{{ offer.poi_name }}</p>
            </div>
            
            <div class="offer-card-body">
                <div class="offer-advantage">
                    {{ offer.advantage }}
                </div>
                {% IF offer.description %}
                <p class="offer-description">{{ offer.description }}</p>
                {% ENDIF %}
            </div>
            
            <div class="offer-card-footer">
                <div>
                    <i class="fa-solid fa-calendar"></i> 
                    Du {{ offer.start_date_formatted }} au {{ offer.end_date_formatted }}
                </div>
            </div>
            
            <a href="{{ offer.viewUrl }}" class="button primary offer-card-btn">
                <i class="fa-solid fa-eye"></i> Voir l'offre
            </a>
        </div>
        {% ENDFOR %}
    </div>
    {% ELSE %}
    <div class="offers-empty">
        <i class="fa-solid fa-inbox offers-empty-icon"></i>
        <p class="offers-empty-text">Aucune offre disponible pour le moment.</p>
    </div>
    {% ENDIF %}
</section>

