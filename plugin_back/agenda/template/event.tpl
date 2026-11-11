<section class="agenda-event-detail">
    <div class="header-actions">
        <a href="{ ROUTER.generate("agenda-index") }" class="button small">
            <i class="fa-solid fa-arrow-left"></i> Retour à l'agenda
        </a>
    </div>

    <div class="agenda-event-card detailed">
        <div class="event-meta">
            <div class="meta-item">
                <i class="fa-solid fa-calendar"></i> 
                <strong>Du :</strong> {{start_date_display}} 
                <strong>Au :</strong> {{end_date_display}}
            </div>
            {% IF event.getLocation() %}
            <div class="meta-item">
                <i class="fa-solid fa-location-dot"></i> 
                <strong>Lieu :</strong> {{event.getLocation()}}
            </div>
            {% ENDIF %}
        </div>

        {% IF event.getDescription() %}
        <div class="event-description">
            {{event.getDescription()}}
        </div>
        {% ENDIF %}

        {% IF linkedOffers %}
        <div class="event-linked-offers">
            <h3><i class="fa-solid fa-gift"></i> Offres spéciales pour cet événement</h3>
            <div class="offers-mini-list">
                {% FOR offer in linkedOffers %}
                <div class="offer-mini-item">
                    <div class="offer-mini-title">{{ offer.title }}</div>
                    <div class="offer-mini-advantage">{{ offer.advantage }}</div>
                    <a href="/offers/view/{{ offer.id }}" class="button small">Voir l'offre</a>
                </div>
                {% ENDFOR %}
            </div>
        </div>
        {% ENDIF %}
        
        <div class="event-footer">
            <div class="event-badges">
                <span class="badge" style="background: {{event.getColor()}};">
                    <i class="fa-solid fa-calendar-check"></i> Événement à venir
                </span>
            </div>
            
            <!-- Map Hook -->
            {% IF hasMap %}
            <div class="event-map-container" style="margin: 20px 0;">
                {{mapHtml}}
            </div>
            {% ENDIF %}
            
            <div class="attendance-section">
                <div class="attendance-count">
                    <i class="fa-solid fa-users"></i>
                    <span id="attendance-total">{{attendanceCount.total}}</span> participant(s)
                    {% IF isLogged %}
                       <span class="attendance-details">
                            (<span id="attendance-registered">{{attendanceCount.registered}}</span> inscrits, <span id="attendance-anonymous">{{attendanceCount.anonymous}}</span> anonymes)
                        </span>
                    {% ENDIF %}
                </div>
                <button 
                    id="attendance-toggle" 
                    class="button {% IF isAttending %}alert{% ELSE %}success{% ENDIF %}"
                    data-event-id="{{event.getId()}}"
                    data-attending="{% IF isAttending %}1{% ELSE %}0{% ENDIF %}">
                    <i class="fa-solid {% IF isAttending %}fa-times{% ELSE %}fa-check{% ENDIF %}"></i>
                    <span>{% IF isAttending %}Annuler ma présence{% ELSE %}Je serai présent{% ENDIF %}</span>
                </button>
            </div>
        </div>
    </div>
</section>
