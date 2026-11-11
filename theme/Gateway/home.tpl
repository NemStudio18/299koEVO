<div class="gateway-landing">

    {# ===================== HERO SECTION avec image de fond ===================== #}
    <section class="landing-hero" style="background-image: url('theme/Gateway/background.jpg'); background-size: cover; background-position: center;">
        <div class="landing-hero-content">
            <h1 class="landing-hero-title">{{Lang.landing-hero-title}}</h1>
            <p class="landing-hero-subtitle">{{Lang.landing-hero-subtitle}}</p>

            {# Barre de recherche pill-shaped dans le hero #}
            <div class="search-form-container">
                <form action="{ROUTER.generate('SearchUi-home')}" method="GET" class="search-form" id="hero-search-form">
                    <div class="search-input-group-with-button">
                        <div class="search-input-group">
                            <input type="text" name="q" id="hero-search-input" placeholder="{{Lang.search-placeholder-hero}}" autocomplete="off">
                        </div>
                        <div class="search-button-group">
                            <button type="submit" id="hero-search-button">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {# CTAs sous la barre de recherche #}
            <div class="landing-hero-cta" style="margin-top: 1.5rem;">
                <a href="{ROUTER.generate('SearchUi-home')}" class="cta-button cta-primary">
                    <i class="fa-solid fa-map-location-dot"></i> {{Lang.landing-hero-cta-explore}}
                </a>
                {% if IS_LOGGED == false %}
                <a href="{% HOOK.user:register-url('') %}" class="cta-button cta-secondary">
                    <i class="fa-solid fa-user-plus"></i> {{Lang.landing-hero-cta-contribute}}
                </a>
                {% endif %}
            </div>
        </div>
    </section>

    {# ===================== CATÉGORIES RAPIDES ===================== #}
    <section class="landing-how">
        <div class="landing-how-content">
            <h2 class="landing-section-title">{{Lang.landing-categories-title}}</h2>
            <div class="landing-cards" id="category-icons">
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=restauration-bar'">
                    <div class="landing-card-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>{{Lang.family_food}}</h3>
                    <p>Restaurants, cafés, bars, brasseries...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=commerces'">
                    <div class="landing-card-icon"><i class="fa-solid fa-store"></i></div>
                    <h3>{{Lang.family_shops}}</h3>
                    <p>Boutiques, supermarchés, marchés...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=sante'">
                    <div class="landing-card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <h3>{{Lang.family_health}}</h3>
                    <p>Médecins, pharmacies, hôpitaux...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=loisirs-sport'">
                    <div class="landing-card-icon"><i class="fa-solid fa-futbol"></i></div>
                    <h3>{{Lang.family_leisure}}</h3>
                    <p>Cinémas, parcs, salles de sport...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=patrimoine'">
                    <div class="landing-card-icon"><i class="fa-solid fa-landmark"></i></div>
                    <h3>{{Lang.family_heritage}}</h3>
                    <p>Monuments, musées, sites historiques...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=services-publics'">
                    <div class="landing-card-icon"><i class="fa-solid fa-building-columns"></i></div>
                    <h3>{{Lang.family_services}}</h3>
                    <p>Mairies, postes, CAF, préfecture...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
            </div>
        </div>
    </section>

    {# ===================== PÉPITES DU COIN (Discovery Carousel) ===================== #}
    {% if hasRandomPois %}
    <section class="landing-discovery">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Découvrir les pépites</h2>
            <div class="poi-carousel-container">
                <div class="poi-carousel" id="poi-carousel">
                    {% for poi in randomPois %}
                        <div class="poi-card-wrapper">
                            <div class="landing-card poi-discovery-card" onclick="window.location.href='{{poi.detail_url_final}}'">
                                <div class="poi-discovery-image">
                                     <img src="{{poi.logo}}" alt="{{poi.name}}">
                                </div>
                                <div class="poi-discovery-body">
                                    <h3>{{poi.name}}</h3>
                                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">{{poi.city}} • {{poi.type_label}}</p>
                                    <span class="landing-card-link">Voir la fiche</span>
                                </div>
                            </div>
                        </div>
                    {% endfor %}
                </div>
                <div class="carousel-navigation">
                    <button class="carousel-btn carousel-prev" onclick="event.preventDefault(); moveCarousel(-1)">‹</button>
                    <button class="carousel-btn carousel-next" onclick="event.preventDefault(); moveCarousel(1)">›</button>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function(){
        let currentIndex = 0;
        let isAnimating = false;
        
        function moveCarousel(direction) {
            const cards = document.querySelectorAll('.poi-card-wrapper');
            const totalCards = cards.length;
            if (!totalCards || isAnimating) return;
            isAnimating = true;
            currentIndex = (currentIndex + direction + totalCards) % totalCards;
            updateCarouselPositions(cards, totalCards);
            setTimeout(() => { isAnimating = false; }, 800);
        }

        function updateCarouselPositions(cards, totalCards) {
             cards.forEach((card, index) => {
                let diff = index - currentIndex;
                if (diff > totalCards / 2) diff -= totalCards;
                if (diff < -totalCards / 2) diff += totalCards;
                const absDiff = Math.abs(diff);
                const rotateY = diff * 25;
                const translateX = diff * 220;
                const translateZ = -absDiff * 150;
                const scale = absDiff === 0 ? 1 : Math.max(0.7, 1 - absDiff * 0.15);
                const opacity = absDiff === 0 ? 1 : Math.max(0.4, 1 - absDiff * 0.25);

                card.style.transform = `translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
                card.style.zIndex = 100 - absDiff * 15;
                card.style.opacity = opacity;
                card.style.pointerEvents = absDiff === 0 ? 'auto' : 'none';
            });
        }

        window.moveCarousel = moveCarousel;

        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.poi-card-wrapper');
            if (cards.length) {
                updateCarouselPositions(cards, cards.length);
                // Auto-rotation
                setInterval(() => {
                    if (!document.querySelector('.poi-carousel-container:hover')) {
                        moveCarousel(1);
                    }
                }, 5000);
            }
        });
    })();
    </script>
    {% endif %}

    {# ===================== COMMENT ÇA MARCHE ===================== #}
    <section class="landing-collaborative">
        <div class="landing-how-content">
            <h2 class="landing-section-title">{{Lang.landing-how-title}}</h2>
            <div class="landing-cards">
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>{{Lang.landing-how-step1-title}}</h3>
                    <p>{{Lang.landing-how-step1-desc}}</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <h3>{{Lang.landing-how-step2-title}}</h3>
                    <p>{{Lang.landing-how-step2-desc}}</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-comments"></i></div>
                    <h3>{{Lang.landing-how-step3-title}}</h3>
                    <p>{{Lang.landing-how-step3-desc}}</p>
                </div>
            </div>
        </div>
    </section>

    {# ===================== ACTUALITÉS ET ÉCHANGES ===================== #}
    <section class="landing-activity">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Actualités & Vie de Quartier</h2>
            <div class="landing-cards">
                {# Bloc Blog #}
                {% if latestBlog %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <h3>Dernière actu</h3>
                    <p style="text-align: left; width: 100%;"><strong>{{latestBlog.title}}</strong></p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem; text-align: left;">{{latestBlog.excerpt|raw|truncate(120)}}</p>
                    <a href="{{latestBlog.url}}" class="landing-card-link" style="margin-top: auto; padding-top: 1rem;">Lire l'article</a>
                </div>
                {% endif %}

                {# Bloc Forum #}
                {% if latestForum %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-comments"></i></div>
                    <h3>Forum</h3>
                    <div style="width: 100%;">
                        {% for post in latestForum|slice(0,3) %}
                            <div class="activity-item">
                                <a href="{{post.url}}" class="activity-item-title">{{post.topic_title}}</a>
                                <p class="activity-item-meta">{{post.date}}</p>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{ROUTER.generate('forum-home')}" class="landing-card-link" style="margin-top: auto; padding-top: 1rem;">Voir le forum</a>
                </div>
                {% endif %}

                {# Bloc Agenda #}
                {% if latestEvents %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Agenda</h3>
                    <div style="width: 100%;">
                        {% for event in latestEvents|slice(0,3) %}
                            <div class="activity-item">
                                <a href="{{event.url}}" class="activity-item-title">{{event.title}}</a>
                                <p class="activity-item-meta" style="color: #e67e22; font-weight: 700;">{{event.start_date|date('d/m')}} • {{event.location}}</p>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{ROUTER.generate('agenda-index')}" class="landing-card-link" style="margin-top: auto; padding-top: 1rem;">L'agenda local</a>
                </div>
                {% endif %}

                {# Bloc Offres #}
                {% if latestOffers %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-tag"></i></div>
                    <h3>Bons plans</h3>
                    <div style="width: 100%;">
                        {% for offer in latestOffers|slice(0,3) %}
                            <div class="activity-item">
                                <p class="activity-item-title" style="margin-bottom: 0;">{{offer.title}}</p>
                                <p class="activity-item-meta">Chez <strong>{{offer.poi.name}}</strong></p>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{ROUTER.generate('SearchUi-home')}?claimed=1" class="landing-card-link" style="margin-top: auto; padding-top: 1rem;">Voir les offres</a>
                </div>
                {% endif %}
            </div>
        </div>
    </section>

    {# ===================== SECTIONS DYNAMIQUES (Plugins) — Hook de secours ===================== #}
    {% HOOK.SearchUi:home-sections %}

    {# ===================== SECTION COLLABORATIVE ===================== #}
    <section class="landing-join">
        <div class="landing-how-content" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <h2 class="landing-section-title">{{Lang.landing-contribute-title}}</h2>
            <p style="font-size: 1.2rem; line-height: 1.6; color: #555; margin-bottom: 2rem;">{{Lang.landing-contribute-text}}</p>
            <a href="{ROUTER.generate('poi-edit-contribute')}" class="button primary">{{Lang.landing-contribute-cta}}</a>
        </div>
    </section>

    {# ===================== CTA FINAL (Inscription) ===================== #}
    {% if IS_LOGGED == false %}
    <section class="landing-final-cta">
        <div class="landing-final-cta-content">
            <h2>{{Lang.landing-join-title}}</h2>
            <p>{{Lang.landing-join-text}}</p>
            <a href="{% HOOK.user:register-url('') %}" class="cta-button cta-primary" style="background: #fff; color: var(--primary-color);">
                <i class="fa-solid fa-rocket"></i> {{Lang.landing-join-cta}}
            </a>
        </div>
    </section>
    {% endif %}

</div>
