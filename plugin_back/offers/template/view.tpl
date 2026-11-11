<section class="offer-view">
    <div class="offer-view-container">
        <header class="offer-view-header">
            <a href="/offers" class="offer-back-link">
                <i class="fa-solid fa-arrow-left"></i> Retour aux offres
            </a>
            <h1>{{ offer.title }}</h1>
            <p class="offer-view-poi">{{ offer.poi_name }}</p>
        </header>

        <div class="offer-view-details">
            <div class="offer-view-details-block">
                <div class="offer-view-advantage">
                    {{ offer.advantage }}
                </div>
                {% IF offer.description %}
                <p class="offer-view-description">{{ offer.description }}</p>
                {% ENDIF %}
            </div>
            
            <div class="offer-view-validity">
                <div class="offer-label">
                    <i class="fa-solid fa-calendar"></i> Période de validité :
                </div>
                <div class="offer-view-validity-date">
                    Du {{ offer.start_date_formatted }} au {{ offer.end_date_formatted }}
                </div>
            </div>
            
            {% IF offer.condition_text %}
            <div class="offer-view-conditions">
                <div class="offer-label"><i class="fa-solid fa-info-circle"></i> Conditions :</div>
                <p class="offer-condition-text">{{ offer.condition_text }}</p>
            </div>
            {% ENDIF %}

            {% IF offer.partners %}
            <div class="offer-view-partners">
                <div class="offer-label"><i class="fa-solid fa-handshake"></i> Commerces partenaires :</div>
                <ul class="offer-partners-list">
                    {% FOR partner in offer.partners %}
                    <li><i class="fa-solid fa-store"></i> {{ partner.name }}</li>
                    {% ENDFOR %}
                </ul>
            </div>
            {% ENDIF %}

            {% IF offer.event_title %}
            <div class="offer-view-event">
                <div class="offer-label"><i class="fa-solid fa-calendar-check"></i> Événement lié :</div>
                <p>{{ offer.event_title }}</p>
            </div>
            {% ENDIF %}
        </div>

        <div class="offer-view-actions">
            <a href="{{ offer.unlockUrl }}" class="button primary offer-view-btn">
                <i class="fa-solid fa-gift"></i> Profiter de l'offre
            </a>
        </div>
    </div>
</section>

