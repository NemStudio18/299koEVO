{% set HIDE_PAGE_TITLE = true %}
<section class="agenda-index">
    <header class="agenda-hero-banner">
        <div class="agenda-hero-inner">
            <div class="agenda-hero-topline">
                <span class="agenda-hero-badge"><i class="fa-solid fa-calendar-star"></i> Événements &amp; Sorties • Bourges</span>
            </div>
            <h1 class="agenda-hero-title">L'Agenda de Bourges</h1>
            <p class="agenda-hero-subtitle">Concerts, spectacles, marchés, expositions et activités à ne pas manquer à Bourges et ses alentours.</p>
        </div>
    </header>
    <div class="header-actions">
        {% IF isLogged %}
            {% IF isOwner %}
            <a href="{{addEventUrl}}" class="button primary">
                <i class="fa-solid fa-plus"></i> Ajouter un événement
            </a>
            {% ELSE %}
            <a href="{{proposeEventUrl}}" class="button primary">
                <i class="fa-solid fa-lightbulb"></i> Proposer un événement
            </a>
            {% ENDIF %}
        {% ENDIF %}
    </div>

    <div id="agenda-calendar"></div>

    <div class="agenda-events-list">
        <h3><i class="fa-solid fa-hourglass-half"></i> Événements à venir</h3>
        {% IF upcomingEvents %}
        <div class="agenda-events-grid">
            {% FOR event IN upcomingEvents %}
            <div class="agenda-event-card" style="border-left-color: {{event.color}};">
                <h3>
                    <a href="{{event.url}}">{{event.title}}</a>
                </h3>
                <div class="event-meta">
                    <div class="meta-item">
                        <i class="fa-solid fa-calendar"></i> <strong>Début :</strong> {{event.start_date_display}}
                    </div>
                    {% IF event.end_date != event.start_date %}
                    <div class="meta-item">
                        <i class="fa-solid fa-calendar-check"></i> <strong>Fin :</strong> {{event.end_date_display}}
                    </div>
                    {% ENDIF %}
                    {% IF event.location %}
                    <div class="meta-item">
                        <i class="fa-solid fa-location-dot"></i> <strong>Lieu :</strong> {{event.location}}
                    </div>
                    {% ENDIF %}
                </div>
                {% IF event.description %}
                <div class="event-description">
                    {{event.description}}
                </div>
                {% ENDIF %}

                <div class="event-mini-map" 
                     data-lat="{{event.latitude}}" 
                     data-lon="{{event.longitude}}"
                     data-title="{{event.title}}">
                </div>
            </div>
            {% ENDFOR %}
        </div>
        {% ELSE %}
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>Aucun événement à venir pour le moment.</p>
        </div>
        {% ENDIF %}

        {% IF pastEvents %}
        <div class="agenda-past-events">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Événements passés</h3>
            <div class="agenda-events-grid past-grid">
                {% FOR event IN pastEvents %}
                <div class="agenda-event-card past-card">
                    <h3>
                        <a href="{{event.url}}">{{event.title}}</a>
                    </h3>
                    <div class="event-meta">
                        <div class="meta-item">
                            <i class="fa-solid fa-calendar"></i> <strong>Début :</strong> {{event.start_date}}
                        </div>
                    </div>
                </div>
                {% ENDFOR %}
            </div>
        </div>
        {% ENDIF %}
    </div>
</section>
