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
    <div class="header-actions" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end; margin-bottom: 1rem;">
        <a href="/agenda/export.ics" class="button secondary" title="Télécharger l'agenda au format iCal standard">
            <i class="fa-solid fa-calendar-arrow-down"></i> Exporter l'agenda (.ics)
        </a>
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
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h3 style="margin: 0;"><i class="fa-solid fa-hourglass-half"></i> Événements à venir</h3>
            <div class="agenda-date-filters" style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="button small filter-btn active" data-filter="all" onclick="filterAgendaEvents('all', this)">Tous</button>
                <button type="button" class="button small filter-btn secondary" data-filter="today" onclick="filterAgendaEvents('today', this)">Aujourd'hui</button>
                <button type="button" class="button small filter-btn secondary" data-filter="weekend" onclick="filterAgendaEvents('weekend', this)">Ce week-end</button>
                <button type="button" class="button small filter-btn secondary" data-filter="month" onclick="filterAgendaEvents('month', this)">Ce mois-ci</button>
            </div>
        </div>
        {% IF upcomingEvents %}
        <div class="agenda-events-grid" id="agenda-cards-grid">
            {% FOR event IN upcomingEvents %}
            <div class="agenda-event-card" style="border-left-color: {{event.color}};" data-start-date="{{event.start_date}}">
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

<script>
function filterAgendaEvents(type, btn) {
    document.querySelectorAll('.agenda-date-filters .filter-btn').forEach(b => {
        b.classList.remove('primary', 'active');
        b.classList.add('secondary');
    });
    btn.classList.remove('secondary');
    btn.classList.add('primary', 'active');

    const now = new Date();
    const todayStr = now.toISOString().slice(0, 10);

    // Calculate weekend dates (Saturday and Sunday)
    const dayOfWeek = now.getDay();
    const diffToSat = (6 - dayOfWeek + 7) % 7;
    const saturday = new Date(now);
    saturday.setDate(now.getDate() + diffToSat);
    const sunday = new Date(saturday);
    sunday.setDate(saturday.getDate() + 1);
    const satStr = saturday.toISOString().slice(0, 10);
    const sunStr = sunday.toISOString().slice(0, 10);

    const currentYear = now.getFullYear();
    const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
    const monthPrefix = currentYear + '-' + currentMonth;

    const cards = document.querySelectorAll('#agenda-cards-grid .agenda-event-card');
    cards.forEach(card => {
        const dateAttr = (card.getAttribute('data-start-date') || '').slice(0, 10);
        let show = true;

        if (type === 'today') {
            show = (dateAttr === todayStr);
        } else if (type === 'weekend') {
            show = (dateAttr === satStr || dateAttr === sunStr);
        } else if (type === 'month') {
            show = dateAttr.startsWith(monthPrefix);
        }

        card.style.display = show ? '' : 'none';
    });
}
</script>
