<style>
.event-card-poster {
    width: 110px !important;
    min-width: 110px !important;
    max-width: 110px !important;
    height: 130px !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    flex-shrink: 0 !important;
    background: #f1f5f9 !important;
    box-shadow: 0 3px 8px rgba(0,0,0,0.06) !important;
}
.event-card-poster a {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
}
.event-card-poster img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: cover !important;
    display: block !important;
    transition: transform 0.3s ease !important;
}
.agenda-event-card:hover .event-card-poster img {
    transform: scale(1.05) !important;
}
@media (max-width: 640px) {
    .event-card-poster {
        width: 80px !important;
        min-width: 80px !important;
        max-width: 80px !important;
        height: 100px !important;
    }
}
</style>

<section class="agenda-page-wrapper">
    <!-- En-tête Culturel & Festif de Bourges -->
    <header class="agenda-hero-banner">
        <div class="agenda-hero-inner">
            <div class="agenda-hero-topline">
                <span class="agenda-hero-badge"><i class="fa-solid fa-sparkles"></i> Vivre Bourges • Sorties &amp; Culture</span>
                <span class="agenda-count-pill"><i class="fa-solid fa-calendar-check"></i> {{totalUpcomingCount}} événements à l'affiche</span>
            </div>
            
            <h1 class="agenda-hero-title">L'Agenda de Bourges</h1>
            <p class="agenda-hero-subtitle">Concerts, expositions, spectacles, patrimoine et ateliers : vivez pleinement le rythme culturel de Bourges et du Berry.</p>
            
            <div class="agenda-hero-actions">
                <button type="button" id="toggleCalendarDrawerBtn" class="agenda-btn-secondary" aria-expanded="false">
                    <i class="fa-solid fa-calendar-days"></i> <span>Calendrier interactif</span> <i class="fa-solid fa-chevron-down toggle-icon"></i>
                </button>
                
                {% IF isLogged %}
                    {% IF isOwner %}
                    <a href="{{addEventUrl}}" class="agenda-btn-primary">
                        <i class="fa-solid fa-plus-circle"></i> Ajouter un événement
                    </a>
                    {% ELSE %}
                    <a href="{{proposeEventUrl}}" class="agenda-btn-primary">
                        <i class="fa-solid fa-lightbulb"></i> Proposer un événement
                    </a>
                    {% ENDIF %}
                {% ELSE %}
                    <a href="contact" class="agenda-btn-primary">
                        <i class="fa-solid fa-bullhorn"></i> Proposer un événement
                    </a>
                {% ENDIF %}
            </div>
        </div>
    </header>

    <!-- Tiroir déroulant du Calendrier interactif -->
    <div id="agendaCalendarDrawer" class="agenda-calendar-drawer" style="display: none;">
        <div class="drawer-header">
            <div class="drawer-title">
                <i class="fa-solid fa-calendar-days"></i> Vue Calendrier interactif
            </div>
            <div class="drawer-actions">
                <button type="button" id="clearCalendarDateFilter" class="drawer-reset-btn" style="display: none;">
                    <i class="fa-solid fa-rotate-left"></i> Afficher toutes les dates
                </button>
                <button type="button" id="closeCalendarDrawerBtn" class="drawer-close-btn" aria-label="Fermer le calendrier">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="drawer-body">
            <div id="agenda-calendar"></div>
        </div>
    </div>

    <!-- Barre de Commandes et Filtres Rapides -->
    <div class="agenda-controls-bar" id="agendaControls">
        <!-- Ligne 1 : Périodes rapides + Recherche textuelle + Commutateur de vue -->
        <div class="controls-top-row">
            <div class="period-pills-wrap" role="tablist" aria-label="Filtres temporels">
                <button type="button" class="period-pill active" data-period="all">
                    <span>Tous</span> <span class="pill-count">({{totalUpcomingCount}})</span>
                </button>
                <button type="button" class="period-pill" data-period="today">
                    <span>Aujourd'hui</span>
                </button>
                <button type="button" class="period-pill" data-period="weekend">
                    <span>Ce week-end</span>
                </button>
                <button type="button" class="period-pill" data-period="month">
                    <span>Ce mois-ci</span>
                </button>
            </div>

            <div class="controls-right-wrap">
                <!-- Recherche en direct -->
                <div class="agenda-search-box">
                    <i class="fa-solid fa-magnifying-glass search-ico"></i>
                    <input type="text" id="agendaSearchInput" placeholder="Rechercher un événement, lieu..." aria-label="Rechercher dans l'agenda" autocomplete="off" />
                    <button type="button" id="clearSearchInput" class="clear-search-btn" style="display: none;" title="Effacer"><i class="fa-solid fa-circle-xmark"></i></button>
                </div>

                <!-- Bascule Grille / Liste -->
                <div class="view-toggle-wrap">
                    <button type="button" id="btnViewGrid" class="view-toggle-btn active" title="Affichage en Grille">
                        <i class="fa-solid fa-grip"></i>
                    </button>
                    <button type="button" id="btnViewList" class="view-toggle-btn" title="Affichage en Liste">
                        <i class="fa-solid fa-list-ul"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Ligne 2 : Filtres thématiques par catégorie -->
        <div class="categories-pills-scroll">
            <button type="button" class="cat-pill active" data-category="all">
                <i class="fa-solid fa-layer-group"></i> <span>Toutes thématiques</span>
            </button>
            <button type="button" class="cat-pill" data-category="cinema">
                <i class="fa-solid fa-film"></i> <span>Cinéma</span>
            </button>
            <button type="button" class="cat-pill" data-category="musique">
                <i class="fa-solid fa-music"></i> <span>Musique &amp; Concerts</span>
            </button>
            <button type="button" class="cat-pill" data-category="theatre">
                <i class="fa-solid fa-masks-theater"></i> <span>Théâtre &amp; Spectacles</span>
            </button>
            <button type="button" class="cat-pill" data-category="exposition">
                <i class="fa-solid fa-palette"></i> <span>Expositions</span>
            </button>
            <button type="button" class="cat-pill" data-category="atelier">
                <i class="fa-solid fa-lightbulb"></i> <span>Ateliers &amp; Conférences</span>
            </button>
            <button type="button" class="cat-pill" data-category="jeune-public">
                <i class="fa-solid fa-child"></i> <span>Jeune Public</span>
            </button>
            <button type="button" class="cat-pill" data-category="culture">
                <i class="fa-solid fa-calendar-star"></i> <span>Animations</span>
            </button>
        </div>
    </div>

    <!-- Conteneur principal des événements -->
    <main class="agenda-content-area">
        <!-- Message filtre actif (date calendrier sélectionnée) -->
        <div id="activeDateBanner" class="active-date-banner" style="display: none;">
            <span><i class="fa-solid fa-filter"></i> Événements pour le <strong id="activeDateLabel"></strong></span>
            <button type="button" id="removeDateFilterBtn" class="remove-filter-btn"><i class="fa-solid fa-xmark"></i> Effacer ce filtre</button>
        </div>

        {% IF upcomingEvents %}
        <div id="agendaCardsGrid" class="agenda-cards-container view-grid">
            {% FOR event IN upcomingEvents %}
            <article class="agenda-event-card" 
                     data-category="{{event.category_slug}}" 
                     data-period="{{event.period_tags}}"
                     data-date="{{event.start_date}}"
                     data-end="{{event.end_date}}"
                     data-title="{{event.title|lower}}"
                     data-location="{{event.location|lower}}"
                     data-description="{{event.plain_description|lower}}">
                
                <!-- Bloc Date Calendrier Visuel -->
                <div class="event-date-block">
                    <span class="date-month">{{event.month_short}}</span>
                    <span class="date-day">{{event.day_num}}</span>
                    {% IF event.is_multi_day %}
                    <span class="date-multi-indicator" title="Événement sur plusieurs dates (jusqu'au {{event.end_date_display}})">multi</span>
                    {% ENDIF %}
                </div>

                {% IF event.image_url %}
                <div class="event-card-poster" style="width: 110px; min-width: 110px; max-width: 110px; height: 130px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: #f1f5f9; box-shadow: 0 3px 8px rgba(0,0,0,0.06);">
                    <a href="{{event.url}}" style="display: block; width: 100%; height: 100%;">
                        <img src="{{event.image_url}}" alt="{{event.title}}" loading="lazy" referrerpolicy="no-referrer" style="width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; object-fit: cover !important; display: block;">
                    </a>
                </div>
                {% ENDIF %}

                <!-- Corps de la carte -->
                <div class="event-card-body">
                    <div class="event-card-badges">
                        {% IF event.is_cgr %}
                        <span class="event-category-badge" style="background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff; font-weight: 800; border: 1px solid rgba(234,88,12,0.4); box-shadow: 0 2px 6px rgba(234,88,12,0.25);">
                            <i class="fa-solid fa-film"></i> Méga CGR
                        </span>
                        {% ELSE %}
                        <span class="event-category-badge cat-{{event.category_slug}}">
                            <i class="fa-solid {{event.category_icon}}"></i> {{event.category}}
                        </span>
                        {% ENDIF %}
                        {% IF event.badge_price %}
                        <span class="event-price-badge"><i class="fa-solid fa-ticket"></i> {{event.badge_price}}</span>
                        {% ENDIF %}
                        {% IF event.is_today %}
                        <span class="event-today-badge"><i class="fa-solid fa-bolt"></i> Aujourd'hui</span>
                        {% ENDIF %}
                    </div>

                    <h2 class="event-card-title">
                        <a href="{{event.url}}">{{event.title}}</a>
                    </h2>

                    <div class="event-card-meta">
                        {% IF event.location %}
                        <div class="meta-row meta-location" title="{{event.location}}">
                            <i class="fa-solid fa-location-dot"></i>
                            <span class="meta-text">{{event.location}}</span>
                        </div>
                        {% ENDIF %}
                        <div class="meta-row meta-dates">
                            <i class="fa-regular fa-calendar"></i>
                            <span class="meta-text">{{event.start_date_display}}{% IF event.is_multi_day %} au {{event.end_date_display}}{% ENDIF %}</span>
                        </div>
                    </div>

                    {% IF event.short_description %}
                    <p class="event-card-description">
                        {{event.short_description}}
                    </p>
                    {% ENDIF %}

                    <div class="event-card-footer">
                        {% IF event.is_cgr %}
                        <button type="button" class="event-cta-btn" onclick="openCgrModal()" style="border:none; cursor:pointer; background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff;">
                            <span><i class="fa-solid fa-film"></i> Voir les séances CGR</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        {% ELSEIF event.is_alhambra %}
                        <a href="{{event.url}}" class="event-cta-btn" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff;">
                            <span><i class="fa-solid fa-film"></i> Séances Maison de la Culture</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        {% ELSEIF event.category_slug == 'cinema' %}
                        <a href="{{event.url}}" class="event-cta-btn">
                            <span><i class="fa-solid fa-film"></i> Séances &amp; Détails</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        {% ELSE %}
                        <a href="{{event.url}}" class="event-cta-btn">
                            <span>Voir le détail</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        {% ENDIF %}
                    </div>
                </div>
            </article>
            {% ENDFOR %}
        </div>
        {% ELSE %}
        <div class="agenda-empty-state">
            <div class="empty-icon-wrap" style="margin-bottom: 12px;">
                <picture>
                    <source srcset="/images/mascot/what_empty.webp" type="image/webp">
                    <img src="/images/mascot/what_empty.png" alt="What" width="120" height="110" style="max-width: 120px; height: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));">
                </picture>
            </div>
            <h3>Aucun événement programmé</h3>
            <p>Il n'y a pas d'événement à venir pour le moment. Vous pouvez être le premier à proposer une manifestation !</p>
            <a href="contact" class="agenda-btn-primary"><i class="fa-solid fa-plus-circle"></i> Proposer un événement</a>
        </div>
        {% ENDIF %}

        <!-- État aucun résultat après filtrage dynamique -->
        <div id="noFilterMatch" class="agenda-no-filter-match" style="display: none;">
            <div class="empty-icon-wrap" style="margin-bottom: 12px;">
                <picture>
                    <source srcset="/images/mascot/what_empty.webp" type="image/webp">
                    <img src="/images/mascot/what_empty.png" alt="What" width="120" height="110" style="max-width: 120px; height: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));">
                </picture>
            </div>
            <h3>Aucun événement trouvé</h3>
            <p>Aucun événement ne correspond à vos critères de recherche actuels.</p>
            <button type="button" id="resetAllFiltersBtn" class="agenda-btn-secondary">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser tous les filtres
            </button>
        </div>

        <!-- Pagination CMS -->
        {% IF pagination %}
        <nav class="agenda-pagination-nav" aria-label="Pagination des événements">
            <ul class="agenda-pagination-list">
                {% FOR p IN pagination %}
                <li class="pagination-item {% IF p.active %}active{% ENDIF %}">
                    <a href="{{p.url}}" class="pagination-link">{{p.num}}</a>
                </li>
                {% ENDFOR %}
            </ul>
        </nav>
        {% ENDIF %}

        <!-- Section Accordéon des Événements Passés (Archives) -->
        {% IF pastEvents %}
        <details class="agenda-past-accordion">
            <summary class="past-accordion-summary">
                <div class="summary-left">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Consulter les archives récentes ({{totalPastCount}} événements passés)</span>
                </div>
                <i class="fa-solid fa-chevron-down summary-arrow"></i>
            </summary>
            
            <div class="past-events-table-wrap">
                <div class="past-events-grid">
                    {% FOR pEvent IN pastEvents %}
                    <div class="past-event-item">
                        <div class="past-date-pill">{{pEvent.start_date_display}}</div>
                        <div class="past-info">
                            <h4 class="past-title"><a href="{{pEvent.url}}">{{pEvent.title}}</a></h4>
                            {% IF pEvent.location %}<span class="past-location"><i class="fa-solid fa-location-dot"></i> {{pEvent.location}}</span>{% ENDIF %}
                        </div>
                        <a href="{{pEvent.url}}" class="past-link"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    {% ENDFOR %}
                </div>
            </div>
        </details>
        {% ENDIF %}
        <!-- Modale Programmation Méga CGR Bourges -->
        <div id="cgrModal" class="cgr-modal-overlay" style="display: none;" onclick="if(event.target===this) closeCgrModal()">
            <div class="cgr-modal-dialog">
                <header class="cgr-modal-header">
                    <div class="cgr-modal-header-info">
                        <span class="cgr-modal-badge"><i class="fa-solid fa-film"></i> Cinéma • Méga CGR Bourges</span>
                        <h2 class="cgr-modal-title">Films à l'affiche &amp; Séances</h2>
                        <p class="cgr-modal-subtitle"><i class="fa-solid fa-location-dot"></i> ZAC du Prado, Bourges • Réservation directe</p>
                    </div>
                    <button type="button" class="cgr-modal-close" onclick="closeCgrModal()" aria-label="Fermer">&times;</button>
                </header>

                <div class="cgr-modal-toolbar">
                    <input type="text" id="cgrSearchInput" class="cgr-search-input" placeholder="Rechercher un film parmi les {{cgrCount}} à l'affiche..." oninput="filterCgrMovies(this.value)">
                    <span class="cgr-total-count">{{cgrCount}} films répertoriés</span>
                </div>

                <div class="cgr-movies-container" id="cgrMoviesContainer">
                    {% IF cgrMovies %}
                    {% FOR movie IN cgrMovies %}
                    <article class="cgr-movie-card" data-movie-title="{{movie.title|lower}}">
                        <div class="cgr-movie-poster-wrap">
                            {% IF movie.image %}
                            <img src="{{movie.image}}" alt="{{movie.title}}" loading="lazy" referrerpolicy="no-referrer" class="cgr-movie-poster">
                            {% ELSE %}
                            <div class="cgr-movie-no-poster" style="display:flex;align-items:center;justify-content:center;height:100%;background:#e2e8f0;color:#94a3b8;font-size:1.5rem;"><i class="fa-solid fa-film"></i></div>
                            {% ENDIF %}
                            <span class="cgr-movie-version-tag">{{movie.tag}}</span>
                        </div>
                        <div class="cgr-movie-info">
                            <h3 class="cgr-movie-name">{{movie.title}}</h3>
                            {% IF movie.synopsis %}
                            <p class="cgr-movie-synopsis">{{movie.synopsis}}</p>
                            {% ENDIF %}
                            
                            <div class="cgr-showtimes-block">
                                <span class="cgr-showtimes-label"><i class="fa-solid fa-clock"></i> Séances du jour :</span>
                                <div class="cgr-hours-list">
                                    {% IF movie.hours %}
                                    {% FOR h IN movie.hours %}
                                    <a href="{{movie.url}}" target="_blank" rel="noopener" class="cgr-hour-pill" title="Réserver séance {{h}}">
                                        {{h}}
                                    </a>
                                    {% ENDFOR %}
                                    {% ELSE %}
                                    <span class="cgr-no-hours" style="font-size:0.8rem;color:#64748b;">Consultez les horaires sur le site</span>
                                    {% ENDIF %}
                                </div>
                            </div>

                            <div class="cgr-movie-actions" style="margin-top:auto;display:flex;justify-content:flex-end;">
                                <a href="{{movie.url}}" target="_blank" rel="noopener" class="cgr-book-link">
                                    <span>Réserver e-billet</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                    {% ENDFOR %}
                    {% ELSE %}
                    <p style="text-align: center; padding: 40px; color: #64748b;">Programmation en cours de synchronisation avec le Méga CGR.</p>
                    {% ENDIF %}
                </div>
            </div>
        </div>
    </main>
</section>

<style>
/* Modale Cinéma CGR */
.cgr-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.cgr-modal-dialog {
    background: #ffffff;
    width: 100%;
    max-width: 900px;
    max-height: 85vh;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: cgrModalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes cgrModalFadeIn {
    from { opacity: 0; transform: translateY(20px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.cgr-modal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 24px 28px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
    border-bottom: 3px solid #ea580c;
}
.cgr-modal-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(234, 88, 12, 0.25);
    color: #ff8c42;
    border: 1px solid rgba(234, 88, 12, 0.4);
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 6px;
}
.cgr-modal-title {
    margin: 0 0 4px 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #ffffff;
}
.cgr-modal-subtitle {
    margin: 0;
    font-size: 0.88rem;
    color: #94a3b8;
}
.cgr-modal-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #ffffff;
    font-size: 2rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    line-height: 1;
}
.cgr-modal-close:hover {
    background: #ef4444;
}
.cgr-modal-toolbar {
    padding: 14px 28px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.cgr-search-input {
    flex: 1;
    padding: 10px 16px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 0.92rem;
}
.cgr-total-count {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}
.cgr-movies-container {
    overflow-y: auto;
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.cgr-movie-card {
    display: flex;
    gap: 20px;
    padding: 16px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.cgr-movie-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
.cgr-movie-poster-wrap {
    width: 100px;
    min-width: 100px;
    height: 140px;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    background: #e2e8f0;
    flex-shrink: 0;
}
.cgr-movie-poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cgr-movie-version-tag {
    position: absolute;
    bottom: 4px;
    left: 4px;
    right: 4px;
    background: rgba(15, 23, 42, 0.85);
    color: #fef08a;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 4px;
    border-radius: 4px;
    text-align: center;
}
.cgr-movie-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cgr-movie-name {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}
.cgr-movie-synopsis {
    margin: 0;
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.cgr-showtimes-block {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 4px;
}
.cgr-showtimes-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
}
.cgr-hours-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.cgr-hour-pill {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
}
.cgr-hour-pill:hover {
    background: #ea580c;
    color: #ffffff;
    border-color: #ea580c;
    transform: scale(1.06);
}
.cgr-book-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #ea580c;
    text-decoration: none;
}
.cgr-book-link:hover {
    text-decoration: underline;
}
@media (max-width: 640px) {
    .cgr-movie-card {
        flex-direction: column;
    }
    .cgr-movie-poster-wrap {
        width: 100%;
        height: 180px;
    }
}
</style>

<!-- Script d'interaction dynamique pour la page Agenda -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const cards = Array.from(document.querySelectorAll("#agendaCardsGrid .agenda-event-card"));
    const gridContainer = document.getElementById("agendaCardsGrid");
    const noMatchBox = document.getElementById("noFilterMatch");
    const searchInput = document.getElementById("agendaSearchInput");
    const clearSearchBtn = document.getElementById("clearSearchInput");
    const periodButtons = document.querySelectorAll(".period-pill");
    const catButtons = document.querySelectorAll(".cat-pill");
    const btnViewGrid = document.getElementById("btnViewGrid");
    const btnViewList = document.getElementById("btnViewList");
    const toggleCalendarBtn = document.getElementById("toggleCalendarDrawerBtn");
    const calendarDrawer = document.getElementById("agendaCalendarDrawer");
    const closeCalendarBtn = document.getElementById("closeCalendarDrawerBtn");
    const activeDateBanner = document.getElementById("activeDateBanner");
    const activeDateLabel = document.getElementById("activeDateLabel");
    const removeDateFilterBtn = document.getElementById("removeDateFilterBtn");
    const resetAllFiltersBtn = document.getElementById("resetAllFiltersBtn");

    let currentPeriod = "all";
    let currentCategory = "all";
    let currentSearch = "";
    let selectedCalendarDate = null;

    // 1. Gestion de la vue Grille / Liste
    const savedView = localStorage.getItem("bourges_agenda_view") || "grid";
    applyView(savedView);

    if (btnViewGrid && btnViewList && gridContainer) {
        btnViewGrid.addEventListener("click", () => applyView("grid"));
        btnViewList.addEventListener("click", () => applyView("list"));
    }

    function applyView(viewMode) {
        if (!gridContainer) return;
        if (viewMode === "list") {
            gridContainer.classList.remove("view-grid");
            gridContainer.classList.add("view-list");
            btnViewList?.classList.add("active");
            btnViewGrid?.classList.remove("active");
            localStorage.setItem("bourges_agenda_view", "list");
        } else {
            gridContainer.classList.remove("view-list");
            gridContainer.classList.add("view-grid");
            btnViewGrid?.classList.add("active");
            btnViewList?.classList.remove("active");
            localStorage.setItem("bourges_agenda_view", "grid");
        }
    }

    // 2. Tiroir du calendrier interactif
    if (toggleCalendarBtn && calendarDrawer) {
        toggleCalendarBtn.addEventListener("click", function() {
            const isHidden = calendarDrawer.style.display === "none";
            calendarDrawer.style.display = isHidden ? "block" : "none";
            toggleCalendarBtn.setAttribute("aria-expanded", isHidden ? "true" : "false");
            const icon = toggleCalendarBtn.querySelector(".toggle-icon");
            if (icon) {
                icon.className = isHidden ? "fa-solid fa-chevron-up toggle-icon" : "fa-solid fa-chevron-down toggle-icon";
            }
        });
    }

    if (closeCalendarBtn && calendarDrawer) {
        closeCalendarBtn.addEventListener("click", function() {
            calendarDrawer.style.display = "none";
            if (toggleCalendarBtn) {
                toggleCalendarBtn.setAttribute("aria-expanded", "false");
                const icon = toggleCalendarBtn.querySelector(".toggle-icon");
                if (icon) icon.className = "fa-solid fa-chevron-down toggle-icon";
            }
        });
    }

    // 3. Filtrage en direct
    function filterCards() {
        if (!cards.length) return;
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCategory = card.getAttribute("data-category") || "";
            const cardPeriodTags = (card.getAttribute("data-period") || "").split(" ");
            const cardDate = card.getAttribute("data-date") || "";
            const cardEndDate = card.getAttribute("data-end") || cardDate;
            const cardTitle = card.getAttribute("data-title") || "";
            const cardLocation = card.getAttribute("data-location") || "";
            const cardDesc = card.getAttribute("data-description") || "";

            // Condition 1: Période rapide
            let matchPeriod = (currentPeriod === "all") || cardPeriodTags.includes(currentPeriod);

            // Condition 2: Date spécifique sélectionnée dans le calendrier
            if (selectedCalendarDate) {
                matchPeriod = (cardDate <= selectedCalendarDate && cardEndDate >= selectedCalendarDate);
            }

            // Condition 3: Catégorie
            let matchCat = (currentCategory === "all") || (cardCategory === currentCategory);

            // Condition 4: Recherche textuelle
            let matchSearch = true;
            if (currentSearch.trim() !== "") {
                const s = currentSearch.toLowerCase().trim();
                matchSearch = cardTitle.includes(s) || cardLocation.includes(s) || cardDesc.includes(s);
            }

            if (matchPeriod && matchCat && matchSearch) {
                card.style.display = "";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        if (noMatchBox) {
            noMatchBox.style.display = visibleCount === 0 ? "block" : "none";
        }
    }

    // Événements Filtres Périodes
    periodButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            periodButtons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            currentPeriod = this.getAttribute("data-period");
            selectedCalendarDate = null;
            if (activeDateBanner) activeDateBanner.style.display = "none";
            filterCards();
        });
    });

    // Événements Filtres Catégories
    catButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            catButtons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            currentCategory = this.getAttribute("data-category");
            filterCards();
        });
    });

    // Événement Recherche textuelle
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            currentSearch = this.value;
            if (clearSearchBtn) {
                clearSearchBtn.style.display = currentSearch.length > 0 ? "inline-flex" : "none";
            }
            filterCards();
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener("click", function() {
            searchInput.value = "";
            currentSearch = "";
            clearSearchBtn.style.display = "none";
            searchInput.focus();
            filterCards();
        });
    }

    // Suppression du filtre de date calendrier
    if (removeDateFilterBtn) {
        removeDateFilterBtn.addEventListener("click", function() {
            selectedCalendarDate = null;
            if (activeDateBanner) activeDateBanner.style.display = "none";
            filterCards();
        });
    }

    // Réinitialisation globale
    if (resetAllFiltersBtn) {
        resetAllFiltersBtn.addEventListener("click", function() {
            currentPeriod = "all";
            currentCategory = "all";
            currentSearch = "";
            selectedCalendarDate = null;

            if (searchInput) searchInput.value = "";
            if (clearSearchBtn) clearSearchBtn.style.display = "none";
            if (activeDateBanner) activeDateBanner.style.display = "none";

            periodButtons.forEach(b => b.classList.toggle("active", b.getAttribute("data-period") === "all"));
            catButtons.forEach(b => b.classList.toggle("active", b.getAttribute("data-category") === "all"));

            filterCards();
        });
    }

    // Interception clic sur jour du VanillaCalendar si présent
    document.addEventListener("click", function(e) {
        const dayBtn = e.target.closest(".vanilla-calendar-day__btn");
        if (dayBtn) {
            const dateStr = dayBtn.dataset.calendarDay || dayBtn.getAttribute("data-calendar-date");
            if (dateStr) {
                selectedCalendarDate = dateStr;
                if (activeDateBanner && activeDateLabel) {
                    activeDateLabel.textContent = dateStr;
                    activeDateBanner.style.display = "flex";
                }
                filterCards();
            }
        }
    });

    // 6. Fonctions interactives Modale Méga CGR
    window.openCgrModal = function() {
        const modal = document.getElementById("cgrModal");
        if (modal) {
            modal.style.display = "flex";
            document.body.style.overflow = "hidden";
            const search = document.getElementById("cgrSearchInput");
            if (search) setTimeout(() => search.focus(), 100);
        }
    };

    window.closeCgrModal = function() {
        const modal = document.getElementById("cgrModal");
        if (modal) {
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    window.filterCgrMovies = function(query) {
        const q = (query || "").toLowerCase().trim();
        const cards = document.querySelectorAll("#cgrMoviesContainer .cgr-movie-card");
        cards.forEach(card => {
            const title = card.getAttribute("data-movie-title") || "";
            card.style.display = (!q || title.includes(q)) ? "flex" : "none";
        });
    };

    // Fermeture avec Échap
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") closeCgrModal();
    });

    // Ouverture automatique si demandé via hash
    if (window.location.hash === "#cgr-programmation") {
        setTimeout(openCgrModal, 200);
    }
});
</script>
