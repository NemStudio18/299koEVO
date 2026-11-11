<div class="search299-results-page">

    <header class="search299-header">
        <h1 class="search299-page-title">
            {% if q %}
                Résultats pour <span class="search299-query-badge">« {{ q }} »</span>
            {% else %}
                Rechercher sur {{ siteName }}
            {% endif %}
        </h1>

        <!-- Formulaire de recherche principal -->
        <form action="/search" method="GET" class="search299-main-searchform" role="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
                type="text" 
                name="q" 
                class="search299-main-input" 
                placeholder="Rechercher une page, un article, un événement…" 
                value="{{ q }}" 
                autocomplete="off"
                autofocus
            />
            <button type="submit" class="search299-main-btn">Rechercher</button>
        </form>

        {% if q and countsByType.all > 0 %}
        <!-- Filtres par catégorie -->
        <div class="search299-filter-chips">
            <a href="/search?q={{ qEnc }}&type=all" class="search299-chip {% if typeFilter == 'all' %}is-active{% endif %}">
                <i class="fa-solid fa-layer-group"></i> Tout voir <span class="search299-chip-count">{{ countsByType.all }}</span>
            </a>
            {% if countsByType.page > 0 %}
            <a href="/search?q={{ qEnc }}&type=page" class="search299-chip {% if typeFilter == 'page' %}is-active{% endif %}">
                <i class="fa-solid fa-file-lines"></i> Pages du site <span class="search299-chip-count">{{ countsByType.page }}</span>
            </a>
            {% endif %}
            {% if countsByType.blog > 0 %}
            <a href="/search?q={{ qEnc }}&type=blog" class="search299-chip {% if typeFilter == 'blog' %}is-active{% endif %}">
                <i class="fa-solid fa-newspaper"></i> Articles <span class="search299-chip-count">{{ countsByType.blog }}</span>
            </a>
            {% endif %}
            {% if countsByType.event > 0 %}
            <a href="/search?q={{ qEnc }}&type=event" class="search299-chip {% if typeFilter == 'event' %}is-active{% endif %}">
                <i class="fa-solid fa-calendar-days"></i> Agenda <span class="search299-chip-count">{{ countsByType.event }}</span>
            </a>
            {% endif %}
            {% if countsByType.poi > 0 %}
            <a href="/search?q={{ qEnc }}&type=poi" class="search299-chip {% if typeFilter == 'poi' %}is-active{% endif %}">
                <i class="fa-solid fa-location-dot"></i> Lieux & Fiches <span class="search299-chip-count">{{ countsByType.poi }}</span>
            </a>
            {% endif %}
        </div>
        {% endif %}
    </header>

    <!-- Liste des résultats -->
    <main class="search299-results-body">
        {% if hasResults %}
            <div class="search299-results-count">
                {{ resultsCount }} résultat(s) trouvé(s) pour votre recherche
            </div>

            <div class="search299-cards-list">
                {% for item in results %}
                <a href="{{ item.url }}" class="search299-card">
                    <div class="search299-card-header">
                        <span class="search299-type-badge {{ item.badge_class }}">
                            <i class="{{ item.type_icon }}"></i> {{ item.type_label }}
                        </span>
                    </div>
                    <h2 class="search299-card-title">{{ item.title }}</h2>
                    <p class="search299-card-excerpt">{{ item.excerpt }}</p>
                    <div class="search299-card-footer">
                        <span>{{ item.meta }}</span>
                        <span class="search299-card-url">{{ item.url }} <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
                {% endfor %}
            </div>

        {% endif %}

        {% if noResults %}
            <!-- Aucun résultat -->
            <div class="search299-empty-state">
                <div class="search299-empty-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h2 class="search299-empty-title">Aucun résultat trouvé pour « {{ q }} »</h2>
                <p class="search299-empty-text">Vérifiez l'orthographe des mots-clés ou essayez avec d'autres termes plus généraux.</p>
                <a href="{{ homeUrl }}" class="search299-main-btn">
                    Retour à l'accueil
                </a>
            </div>

        {% endif %}

        {% if noQuery %}
            <!-- Page sans requête initiale -->
            <div class="search299-empty-state">
                <div class="search299-empty-icon">
                    <i class="fa-solid fa-compass"></i>
                </div>
                <h2 class="search299-empty-title">Que recherchez-vous sur {{ siteName }} ?</h2>
                <p class="search299-empty-text">Tapez un mot-clé ci-dessus pour accéder directement à une page, un article ou un événement du site.</p>
            </div>
        {% endif %}
    </main>

</div>
