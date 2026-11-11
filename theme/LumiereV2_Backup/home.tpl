

<div class="lumiere-landing-wrapper">

    {# ===================== HERO SECTION ===================== #}
    {# Utilisation de header.jpg du thème LumiereChild #}
    <section class="landing-hero">
        <div class="landing-hero-content">
            <h1 class="landing-hero-title">{{Lang.landing-hero-title}}</h1>
            <p class="landing-hero-subtitle">{{Lang.landing-hero-subtitle}}</p>

            {# Barre de recherche identique à SearchUI #}
            <div class="search-container-inner">
                <form action="{ROUTER.generate('SearchUi-home')}" method="GET" class="search-main-form" id="hero-search-form">
                    
                    <!-- Quoi -->
                    <div class="search-input-group-query">
                        <i class="fa-solid fa-search search-icon-query"></i>
                        <input type="text" name="query" id="hero-search-input" placeholder="{{Lang.search-placeholder-hero}}" autocomplete="off" class="search-input-field">
                    </div>

                    <!-- Où -->
                    <div class="search-input-group-location">
                        <i class="fa-solid fa-location-dot search-icon-location"></i>
                        <select name="city" id="hero-city-select" class="search-select-city">
                            <option value="">Bourges (Agglo)</option>
                            <option value="Annoix">Annoix</option>
                            <option value="Arçay">Arçay</option>
                            <option value="Berry-Bouy">Berry-Bouy</option>
                            <option value="Bourges">Bourges</option>
                            <option value="La Chapelle-Saint-Ursin">La Chapelle-Saint-Ursin</option>
                            <option value="Le Subdray">Le Subdray</option>
                            <option value="Lissay-Lochy">Lissay-Lochy</option>
                            <option value="Marmagne">Marmagne</option>
                            <option value="Mehun-sur-Yèvre">Mehun-sur-Yèvre</option>
                            <option value="Morthomiers">Morthomiers</option>
                            <option value="Plaimpied-Givaudins">Plaimpied-Givaudins</option>
                            <option value="Saint-Doulchard">Saint-Doulchard</option>
                            <option value="Saint-Germain-du-Puy">Saint-Germain-du-Puy</option>
                            <option value="Saint-Just">Saint-Just</option>
                            <option value="Saint-Michel-de-Volangis">Saint-Michel-de-Volangis</option>
                            <option value="Trouy">Trouy</option>
                            <option value="Vorly">Vorly</option>
                        </select>
                        <button type="button" id="locate-me-btn" title="Utiliser ma position" class="search-locate-btn">
                            <i class="fa-solid fa-crosshairs"></i>
                        </button>
                    </div>

                    <!-- Bouton Rechercher -->
                    <button type="submit" class="search-submit-btn" id="hero-search-button">
                        <span>Rechercher</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            {# CTAs sous la barre de recherche #}
            {# CTAs sous la barre de recherche (Injectés par ConversionOrchestrator) #}
            {% HOOK.layout:hero-content %}
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
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=service'">
                    <div class="landing-card-icon"><i class="fa-solid fa-handshake"></i></div>
                    <h3>Services</h3>
                    <p>Banques, assurances, mairies, postes...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card" onclick="window.location.href='{ROUTER.generate('SearchUi-home')}?category=tourisme-culture'">
                    <div class="landing-card-icon"><i class="fa-solid fa-masks-theater"></i></div>
                    <h3>Culture</h3>
                    <p>Musées, théâtres, monuments, galeries...</p>
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
                                     {% if poi.logo %}
                                        <img src="{{poi.logo}}" alt="{{poi.name}}">
                                     {% elseif poi.lat and poi.lon %}
                                        <div id="minimap-{{poi.id}}" class="poi-minimap" data-lat="{{poi.lat}}" data-lon="{{poi.lon}}" data-name="{{poi.name}}"></div>
                                     {% else %}
                                        <img src="theme/LumiereV2/header.jpg" alt="{{poi.name}}">
                                     {% endif %}
                                </div>
                                <div class="poi-discovery-body">
                                    <h3>{{poi.name}}</h3>
                                    <p>{{poi.city}} • {{poi.type_label}}</p>
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
            
            // Allow rapid clicking logic if desired, but 3D transition needs time
            isAnimating = true;
            currentIndex = (currentIndex + direction + totalCards) % totalCards;
            updateCarouselPositions(cards, totalCards);
            setTimeout(() => { isAnimating = false; }, 600); // Debounce matches transition speed roughly
        }

        function updateCarouselPositions(cards, totalCards) {
             cards.forEach((card, index) => {
                // Calculate circular distance
                let diff = index - currentIndex;
                
                // Wrap around logic
                if (diff > totalCards / 2) diff -= totalCards;
                if (diff < -totalCards / 2) diff += totalCards;
                
                const absDiff = Math.abs(diff);
                
                // 3D parameters - tweaked for visibility
                // Closer cards are larger and more opaque
                // Further cards fade out and shrink
                
                const isActive = (absDiff === 0);
                
                // Rotate items around a cylinder
                // Spread: 40 degrees separation
                // X translation: Spread them out horizontally
                
                const rotateY = diff * 45; // stronger rotation
                const translateX = diff * 260; // wider spread
                const translateZ = -absDiff * 200; // deeper depth push
                
                // Scale non-active items down slightly
                const scale = isActive ? 1.0 : Math.max(0.8, 1 - absDiff * 0.1);
                
                // Opacity creates depth focus
                const opacity = isActive ? 1 : Math.max(0, 1 - absDiff * 0.3);
                
                // Z-Index ensures correct stacking order visual
                const zIndex = 100 - absDiff;

                card.style.transform = `translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
                card.style.zIndex = zIndex;
                card.style.opacity = opacity;
                
                // Clickable only if active/front
                card.style.pointerEvents = isActive ? 'auto' : 'none';
            });
        }

        window.moveCarousel = moveCarousel;

        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.poi-card-wrapper');
            if (cards.length) {
                // Initial positioning
                updateCarouselPositions(cards, cards.length);
                
                // Auto-rotation every 5s
                let autoRotate = setInterval(() => {
                    if (!document.querySelector('.poi-carousel-container:hover')) {
                        moveCarousel(1);
                    }
                }, 5000);
            }

            // Initialiser les minimaps avec UniversalMap (via SearchMaps)
            function initMaps() {
                console.log('LumiereV2: initMaps called');
                
                if (typeof UniversalMap === 'undefined') {
                    console.log('LumiereV2: UniversalMap not defined yet, retrying...');
                    setTimeout(initMaps, 500); 
                    return;
                }
                console.log('LumiereV2: UniversalMap defined. Found elements:', document.querySelectorAll('.poi-minimap').length);

                document.querySelectorAll('.poi-minimap').forEach(function(el) {
                    if (el.dataset.mapped === 'true') return;

                    var lat = parseFloat(el.getAttribute('data-lat'));
                    var lon = parseFloat(el.getAttribute('data-lon'));

                    console.log('LumiereV2: Processing', el.id, lat, lon);

                    if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
                        try {
                            var uMap = new UniversalMap(el.id, {
                                lat: lat,
                                lon: lon,
                                zoom: 15,
                                editable: false
                            });

                            // Disable interaction for carousel minimaps
                            if (uMap.map) {
                                uMap.toggleLock(true);
                                uMap.map.removeControl(uMap.map.zoomControl);
                            }

                            el.dataset.mapped = 'true';
                            console.log('LumiereV2: Map created for', el.id);

                            // Force resize for display
                            setTimeout(function() {
                                if (uMap.map) {
                                    uMap.map.invalidateSize();
                                    console.log('LumiereV2: invalidated size for', el.id);
                                }
                            }, 500);
                            setTimeout(function() {
                                if (uMap.map) uMap.map.invalidateSize();
                            }, 1000);
                        } catch (e) {
                            console.error('LumiereV2: Error initializing map for', el.id, e);
                        }
                    } else {
                        console.warn('LumiereV2: Invalid data for', el);
                    }
                });
            }

            // Start checking for UniversalMap
            initMaps();
        });
    })();
    </script>
    {# Force recompile #}
    {% endif %}

    {# ===================== SECTION ACTIVITÉS (Blog, Forum, etc.) ===================== #}
    <section class="landing-activity">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Actualités & Vie de Quartier</h2>
            <div class="landing-cards">
                {# Bloc Blog #}
                {% if latestBlog %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <h3>Dernière actu</h3>
                    <p><strong>{{latestBlog.title}}</strong></p>
                    <p class="activity-excerpt">{{latestBlog.excerpt|truncate(120)}}</p>
                    <a href="{{latestBlog.url}}" class="landing-card-link">Lire l'article</a>
                </div>
                {% endif %}

                {# Bloc Forum #}
                {% if latestForum %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-comments"></i></div>
                    <h3>Forum</h3>
                    <div class="activity-list-container">
                        {% for post in latestForum|slice(0,3) %}
                            <div class="activity-item">
                                <a href="{{post.url}}" class="activity-item-title">{{post.topic_title}}</a>
                                <span class="activity-item-date">{{post.date}}</span>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{{ROUTER.generate('forum-home')}}" class="landing-card-link">Voir le forum</a>
                </div>
                {% endif %}

                {# Bloc Agenda #}
                {% if latestEvents %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Agenda</h3>
                    <div class="activity-list-container">
                        {% for event in latestEvents %}
                            <div class="activity-item-row">
                                <div>
                                    <a href="{{event.url}}" class="activity-item-title">{{event.title}}</a>
                                    <span class="activity-item-meta"><i class="fa-solid fa-location-dot"></i> {{event.location}}</span>
                                </div>
                                <div class="activity-date-badge">
                                    {{event.date}}
                                </div>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{{ROUTER.generate('agenda-index')}}" class="landing-card-link">L'agenda local</a>
                </div>
                {% endif %}

                {# Bloc Offres / Promos #}
                {% if latestOffers %}
                <div class="landing-card">
                    <div class="landing-card-icon"><i class="fa-solid fa-percent"></i></div>
                    <h3>Bons Plans</h3>
                    <div class="activity-list-container">
                        {% for offer in latestOffers %}
                            <div class="activity-item">
                                <a href="{{offer.url}}" class="activity-item-title">{{offer.title}}</a>
                                <div class="activity-offer-row">
                                    <span class="activity-item-meta">{{offer.poi_name}}</span>
                                    <span class="activity-discount">-{{offer.discount}}</span>
                                </div>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{{ROUTER.generate('offers-list')}}" class="landing-card-link">Toutes les promos</a>
                </div>
                {% endif %}
            </div>
        </div>
    </section>

    {# ===================== SECTIONS DYNAMIQUES (Plugins) ===================== #}
    {% if extraHomeSections %}
        {% for section in extraHomeSections %}
            {{ section|raw }}
        {% endfor %}
    {% endif %}

</div>
