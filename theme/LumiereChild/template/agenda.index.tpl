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
                     data-description="{{event.description|lower}}">
                
                <!-- Bloc Date Calendrier Visuel -->
                <div class="event-date-block">
                    <span class="date-month">{{event.month_short}}</span>
                    <span class="date-day">{{event.day_num}}</span>
                    {% IF event.is_multi_day %}
                    <span class="date-multi-indicator" title="Événement sur plusieurs dates (jusqu'au {{event.end_date_display}})">multi</span>
                    {% ENDIF %}
                </div>

                <!-- Corps de la carte -->
                <div class="event-card-body">
                    <div class="event-card-badges">
                        <span class="event-category-badge cat-{{event.category_slug}}">
                            <i class="fa-solid {{event.category_icon}}"></i> {{event.category}}
                        </span>
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

                    {% IF event.description %}
                    <p class="event-card-description">
                        {{event.description|slice(0, 140)}}…
                    </p>
                    {% ENDIF %}

                    <div class="event-card-footer">
                        <a href="{{event.url}}" class="event-cta-btn">
                            <span>Voir le détail</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
            {% ENDFOR %}
        </div>
        {% ELSE %}
        <div class="agenda-empty-state">
            <div class="empty-icon-wrap"><i class="fa-solid fa-calendar-xmark"></i></div>
            <h3>Aucun événement programmé</h3>
            <p>Il n'y a pas d'événement à venir pour le moment. Vous pouvez être le premier à proposer une manifestation !</p>
            <a href="contact" class="agenda-btn-primary"><i class="fa-solid fa-plus-circle"></i> Proposer un événement</a>
        </div>
        {% ENDIF %}

        <!-- État aucun résultat après filtrage dynamique -->
        <div id="noFilterMatch" class="agenda-no-filter-match" style="display: none;">
            <div class="empty-icon-wrap"><i class="fa-solid fa-filter-circle-xmark"></i></div>
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
    </main>
</section>

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
});
</script>
