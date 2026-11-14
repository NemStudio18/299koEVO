<section class="annonces-wrap">
    {% if introText %}
        <div class="annonces-intro">{{introText}}</div>
    {% endif %}

    <form method="get" action="{{listUrl}}" class="annonces-filters">
        <input type="text" name="q" value="{{queryFilter}}" placeholder="Rechercher une annonce (titre, lieu…)" aria-label="Rechercher" />
        <select name="cat" aria-label="Catégorie">
            <option value="">Toutes catégories</option>
            {% for cat in categories %}
                <option value="{{cat}}" {% if categoryFilter == cat %}selected{% endif %}>{{cat}}</option>
            {% endfor %}
        </select>
        <select name="status" aria-label="Disponibilité">
            <option value="">Tous statuts</option>
            <option value="available" {% if statusFilter == 'available' %}selected{% endif %}>Disponible</option>
            <option value="reserved" {% if statusFilter == 'reserved' %}selected{% endif %}>Réservée</option>
        </select>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Filtrer</button>
    </form>

    {% if items %}
        <div class="annonces-grid">
            {% for item in items %}
                <article class="annonce-card">
                    <div class="annonce-card-media">
                        {% if item.image %}
                            <img src="{{item.image}}" alt="{{item.title}}" loading="lazy" />
                        {% else %}
                            <span class="annonce-card-placeholder"><i class="fa-solid fa-tags"></i></span>
                        {% endif %}
                        <span class="annonce-badge annonce-badge-{{item.status}}">{{item.status_label}}</span>
                        {% if item.images_count > 1 %}
                            <span class="annonce-photo-count-badge"><i class="fa-solid fa-camera"></i> {{item.images_count}}</span>
                        {% endif %}
                    </div>
                    <div class="annonce-card-body">
                        <div class="annonce-card-meta">
                            {% if item.category %}<span><i class="fa-solid fa-folder"></i> {{item.category}}</span>{% endif %}
                            {% if item.location %}<span><i class="fa-solid fa-location-dot"></i> {{item.location}}</span>{% endif %}
                        </div>
                        <h3 class="annonce-card-title">{{item.title}}</h3>
                        <div class="annonce-card-price">{{item.price_label}}</div>
                        {% if item.excerpt %}
                            <p class="annonce-card-excerpt">{{item.excerpt}}</p>
                        {% endif %}
                        <a href="{{item.url}}" class="button primary annonce-card-btn">
                            <i class="fa-solid fa-eye"></i>
                            {% if item.is_available %}Voir &amp; réserver{% else %}Voir l'annonce{% endif %}
                        </a>
                    </div>
                </article>
            {% endfor %}
        </div>
    {% else %}
        <div class="annonces-empty">
            <i class="fa-solid fa-tags"></i>
            {{Lang.annonces.empty}}
        </div>
    {% endif %}
</section>

