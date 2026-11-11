{# configuration des URLs de la landing page #}
{% set url_rechercher = ROUTER.generate('SearchUi-home') %}
{% set url_inscription = ROUTER.generate('register') %}
{% set url_revendiquer = '/page/comment-revendiquer-19' %}
{% set url_pro_explications = '/page/pourquoi-revendiquer-17' %}
{% set url_pro_premium = '/page/offres-premium-18' %}
{% set url_publier_offre = ROUTER.generate('login') ~ '?msg=offer' %}

<div class="lumiere-landing-wrapper">

    {# ===================== HERO SECTION ===================== #}
    {# Utilisation de header.jpg du thème LumiereChild #}
    <section class="landing-hero parallax-section reveal-on-scroll">
        <div class="landing-hero-content">
            <h1 class="landing-hero-title">
                {% if hero_title and hero_title != "FALSE" %}
                    {{ hero_title }}
                {% else %}
                    {{ Lang.landing-hero-title }}
                {% endif %}
            </h1>
            <p class="landing-hero-subtitle">
                {% if hero_subtitle and hero_subtitle != "FALSE" %}
                    {{ hero_subtitle }}
                {% else %}
                    {{ Lang.landing-hero-subtitle }}
                {% endif %}
            </p>

            {# Barre de recherche simplifiée #}
            <div class="search-container-inner">
                <form action="{{ ROUTER.generate('SearchUi-home') }}" method="GET" class="search-main-form" id="hero-search-form">
                    
                    <!-- Quoi / Tout l'agglo -->
                    <div class="search-input-group-query">
                        <i class="fa-solid fa-magnifying-glass search-icon-query"></i>
                        <input type="text" name="query" id="hero-search-input" placeholder="Que recherchez-vous ?" autocomplete="off" class="search-input-field">
                        <input type="hidden" name="city" value="">
                        
                        <button type="button" id="locate-me-btn" title="Autour de moi" class="search-locate-btn">
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

            {# CTAs sous la barre de recherche (Injectés par ConversionOrchestrator - Masqués pour les visiteurs car doublons) #}
            {% HOOK.layout:hero-content %}

        </div>
    </section>

    {# ===================== CATÉGORIES RAPIDES ===================== #}
    <section class="landing-how">
        <div class="landing-how-content">
            <h2 class="landing-section-title">{{Lang.landing-categories-title}}</h2>
            <div class="landing-cards" id="category-icons">
                 <div class="landing-card reveal-on-scroll" style="transition-delay: 0ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=restauration-bar'">
                    <div class="landing-card-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>{{Lang.family_food}}</h3>
                    <p>Restaurants, cafés, bars, brasseries...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card reveal-on-scroll" style="transition-delay: 100ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=commerces'">
                    <div class="landing-card-icon"><i class="fa-solid fa-store"></i></div>
                    <h3>{{Lang.family_shops}}</h3>
                    <p>Boutiques, supermarchés, marchés...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card reveal-on-scroll" style="transition-delay: 200ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=sante'">
                    <div class="landing-card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <h3>{{Lang.family_health}}</h3>
                    <p>Médecins, pharmacies, hôpitaux...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card reveal-on-scroll" style="transition-delay: 300ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=loisirs-sport'">
                    <div class="landing-card-icon"><i class="fa-solid fa-futbol"></i></div>
                    <h3>{{Lang.family_leisure}}</h3>
                    <p>Cinémas, parcs, salles de sport...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card reveal-on-scroll" style="transition-delay: 400ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=service'">
                    <div class="landing-card-icon"><i class="fa-solid fa-handshake"></i></div>
                    <h3>Services</h3>
                    <p>Banques, assurances, mairies, postes...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
                <div class="landing-card reveal-on-scroll" style="transition-delay: 500ms;" onclick="window.location.href='{{ ROUTER.generate('SearchUi-home') }}?category=tourisme-culture'">
                    <div class="landing-card-icon"><i class="fa-solid fa-masks-theater"></i></div>
                    <h3>Culture</h3>
                    <p>Musées, théâtres, monuments, galeries...</p>
                    <span class="landing-card-link">Explorer</span>
                </div>
            </div>
        </div>
    </section>

    {# ===================== EXPLICATIONS (Membres, Pros, Scoring) ===================== #}
    <section class="landing-explanations parallax-section reveal-on-scroll" id="how-it-works">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Comprendre le projet & Participer</h2>
            
            <div class="explanations-modern-grid">
                {# Bloc Membres #}
                <div class="modern-card">
                    <div class="modern-card-badge">PARTICULIER</div>
                    <div class="modern-card-icon"><i class="fa-solid fa-user-astronaut"></i></div>
                    <h3>Devenir Membre</h3>
                    <p>Rejoignez la communauté pour enrichir la vie locale et partager vos pépites.</p>
                    <ul class="modern-list">
                        <li><i class="fa-solid fa-circle-check"></i> Proposez des modifications</li>
                        <li><i class="fa-solid fa-circle-check"></i> Partagez avis & photos</li>
                        <li><i class="fa-solid fa-circle-check"></i> Cumulez des points</li>
                    </ul>
                    <a href="{{ ROUTER.generate('register') }}" class="modern-cta secondary">Créer mon profil</a>
                </div>

                {# Bloc Professionnels #}
                <div class="modern-card featured">
                    <div class="modern-card-badge featured">ESPACE PRO</div>
                    <div class="modern-card-icon"><i class="fa-solid fa-crown"></i></div>
                    <h3>Boostez votre Visibilité</h3>
                    <p>Établissements, commerces, associations : prenez le contrôle de votre image.</p>
                    <ul class="modern-list black">
                        <li><i class="fa-solid fa-circle-check"></i> Infos en temps réel</li>
                        <li><i class="fa-solid fa-circle-check"></i> Agenda & Offres flash</li>
                        <li><i class="fa-solid fa-circle-check"></i> Badge <strong>"Vérifié"</strong></li>
                    </ul>
                    <a href="{{ ROUTER.generate('comment-revendiquer') }}" class="modern-cta primary">Comment revendiquer ?</a>
                </div>

                {# Bloc Scoring #}
                <div class="modern-card glass">
                    <div class="modern-card-badge">ALGORITHME</div>
                    <div class="modern-card-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>Le Classement Transparent</h3>
                    <p>Ici, c'est l'<strong>engagement</strong> qui prime, pas le budget publicitaire.</p>
                    <div class="modern-scoring-box">
                        <div class="modern-score">
                            <span class="label">RÉPUTATION</span>
                            <div class="bar"><div class="fill" style="width: 85%"></div></div>
                        </div>
                        <div class="modern-score">
                            <span class="label">ACTIVITÉ</span>
                            <div class="bar"><div class="fill" style="width: 60%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {# ===================== PÉPITES DU COIN (Discovery Carousel) ===================== #}
    {% if hasRandomPois %}
    <section class="landing-discovery">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Découvrir les pépites</h2>
            <div class="poi-carousel-container" id="poi-carousel-outer">
                <div class="poi-carousel slider" id="poi-carousel">
                    {% for poi in randomPois %}
                        <div class="poi-card-wrapper">
                            <div class="landing-card poi-discovery-card" onclick="window.location.href='{{poi.detail_url_final}}'">
                                     {% set carouselBg = SITE_URL ~ '/theme/LumiereV2/header.jpg' %}
                                     {% if poi.has_cover %}
                                        {% set carouselBg = poi.cover_url %}
                                     {% endif %}
                                     <div class="poi-discovery-bg" style="background-image: url('{{ carouselBg }}'); position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background-position: center; background-size: cover;"></div>

                                    <div class="poi-discovery-body" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 15px; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);">
                                        <h3 style="color: #ff8c42; text-shadow: 0 2px 8px rgba(0,0,0,1); margin: 0 0 5px 0; font-size: 1.2rem;">{{poi.name}}</h3>
                                        <p style="text-shadow: 0 1px 4px rgba(0,0,0,1); background: rgba(0,0,0,0.5); padding: 3px 8px; border-radius: 4px; display: inline-block; margin-bottom: 8px; font-size: 0.75rem; color: white;">
                                            {{poi.city}} • {{poi.type_label}}
                                            {% if poi.is_open_now_html %}
                                                • {{poi.is_open_now_html|raw}}
                                            {% endif %}
                                        </p>
                                        
                                        <div class="poi-discovery-meta-strip" style="background: rgba(0,0,0,0.4); padding: 6px; border-radius: 6px; margin: 5px 0; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center;">
                                            {% if poi.phone %}
                                                <a href="tel:{{poi.phone}}" class="carousel-phone-btn" title="Appeler" style="color: white; background: rgba(0,0,0,0.4); width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); margin-right: 8px; font-size: 0.7rem;">
                                                    <i class="fa-solid fa-phone"></i>
                                                </a>
                                            {% endif %}
                                            <p style="margin: 0; font-size: 0.7rem; color: white; line-height: 1.2;">{{poi.full_address|truncate(40)}}</p>
                                        </div>

                                        {% if poi.lat and poi.lon %}
                                            <div style="position: relative; height: 70px; width: 100%; margin: 6px 0; border-radius: 6px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);">
                                                <div id="minimap-{{poi.id}}" class="poi-minimap" data-lat="{{poi.lat}}" data-lon="{{poi.lon}}" style="height: 100%; width: 100%;"></div>
                                            </div>
                                        {% endif %}

                                        <div style="margin-top: 5px;">
                                            <span class="landing-card-link" style="font-weight: 600; font-size: 0.8rem; color: #ff8c42;">Voir la fiche <i class="fa-solid fa-arrow-right"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    {% endfor %}
                </div>

                <div class="carousel-navigation">
                    <button class="carousel-btn carousel-prev" id="carousel-prev-btn" aria-label="Précédent"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="carousel-btn carousel-next" id="carousel-next-btn" aria-label="Suivant"><i class="fa-solid fa-chevron-right"></i></button>
                </div>

            </div>

            <div id="carousel-bullets"></div>
        </div>
    </section>

    <script>
    window.addEventListener('load', function() {
        const carouselContainer = document.getElementById('poi-carousel');
        if (!carouselContainer) return;

        const cards = Array.from(document.querySelectorAll('.poi-card-wrapper'));
        const totalCards = cards.length;
        if (totalCards === 0) return;

        let currentIndex = 0;
        let isAnimating = false;
        let autoPlayInterval = null;

        function moveCarousel(direction) {
            if (isAnimating || totalCards <= 1) return;
            isAnimating = true;

            // Modulo math for robust backward/forward looping
            currentIndex = (((currentIndex + direction) % totalCards) + totalCards) % totalCards;

            updateCarouselPositions();

            // Unlock after transition duration
            setTimeout(() => {
                isAnimating = false;
            }, 600);
        }

        function updateCarouselPositions() {
            cards.forEach((card, index) => {
                let diff = index - currentIndex;

                // Wrap around logic
                if (diff > totalCards / 2) {
                    diff -= totalCards;
                } else if (diff < -totalCards / 2) {
                    diff += totalCards;
                }

                const absDiff = Math.abs(diff);

                // Hide cards that are far away from the center to prevent chaotic visual fly-bys
                if (absDiff > 2) {
                    card.style.opacity = '0';
                    card.style.pointerEvents = 'none';
                    card.style.zIndex = '-1';
                    card.style.transform = `translateX(${diff > 0 ? 500 : -500}px) translateZ(-500px) scale(0.5)`;
                    return;
                }

                const translateX = diff * 220; 
                const translateZ = -absDiff * 150;
                const rotateY = diff * 35; 
                const scale = absDiff === 0 ? 1 : Math.max(0.6, 1 - absDiff * 0.2); 
                const opacity = absDiff === 0 ? 1 : Math.max(0.3, 1 - (absDiff * 0.4)); 

                card.style.transform = `translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
                card.style.zIndex = 100 - absDiff * 10;
                card.style.opacity = opacity.toString();
                
                // Only the front card should be interactive
                card.style.pointerEvents = absDiff === 0 ? 'auto' : 'none'; 
                card.style.visibility = 'visible';
                
                // Dynamic shadow box for center card
                if (absDiff === 0) {
                    card.style.boxShadow = '0 25px 50px rgba(0,0,0,0.35)';
                } else {
                    card.style.boxShadow = `0 ${absDiff * 12}px ${absDiff * 24}px rgba(0,0,0,0.15)`;
                }
            });
            updateBullets(currentIndex);
        }

        // Mini-maps pour les sponsors
        function initSponsorMaps() {
            if (typeof L === 'undefined') { // Check for Leaflet (L)
                setTimeout(initSponsorMaps, 500);
                return;
            }
            const mapEls = document.querySelectorAll('.poi-minimap');
            mapEls.forEach(function(el) {
                var lat = parseFloat(el.getAttribute('data-lat'));
                var lon = parseFloat(el.getAttribute('data-lon'));
                if (lat && lon && !isNaN(lat) && !isNaN(lon) && !el.dataset.mapped) {
                    try {
                        var map = L.map(el.id, {
                            center: [lat, lon],
                            zoom: 15,
                            zoomControl: false,
                            attributionControl: false,
                            dragging: false,
                            touchZoom: false,
                            scrollWheelZoom: false,
                            doubleClickZoom: false,
                            boxZoom: false,
                            keyboard: false,
                            tap: false
                        });
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                        }).addTo(map);
                        L.marker([lat, lon]).addTo(map);
                        el.dataset.mapped = 'true';
                        map.invalidateSize();
                    } catch(e) { console.error('LumiereV2: sponsor map error', el.id, e); }
                }
            });
        }


        // Bullets
        function updateBullets(activeIdx) {
            const dots = document.querySelectorAll('#carousel-bullets .carousel-dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === activeIdx);
            });
        }

        function buildBullets(count) {
            const container = document.getElementById('carousel-bullets');
            if (!container) return;
            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const dot = document.createElement('button');
                dot.className = 'carousel-dot';
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', () => {
                    pauseAutoPlay();
                    
                    let diff = i - currentIndex;
                    if (diff > totalCards / 2) diff -= totalCards;
                    if (diff < -totalCards / 2) diff += totalCards;
                    
                    if (diff !== 0) {
                        moveCarousel(diff > 0 ? 1 : -1); // Just move one step towards it for simplicity, or we could jump:
                        // Actually, jump to it:
                        // currentIndex = i; updateCarouselPositions();
                    }
                });
                container.appendChild(dot);
            }
        }

        if (totalCards > 0) {
            buildBullets(totalCards);
        }

        // Initialize positions
        updateCarouselPositions();

        // Arrows
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => { pauseAutoPlay(); moveCarousel(-1); });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => { pauseAutoPlay(); moveCarousel(1); });
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { pauseAutoPlay(); moveCarousel(-1); }
            if (e.key === 'ArrowRight') { pauseAutoPlay(); moveCarousel(1); }
        });

        // Touch Swipe Support - Native
        let touchStartX = 0;
        let touchEndX = 0;
        const swipeThreshold = 50;
        const carouselWrapper = document.querySelector('.poi-carousel-container');

        if (carouselWrapper) {
            carouselWrapper.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                pauseAutoPlay();
            }, {passive: true});

            carouselWrapper.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
                setTimeout(resumeAutoPlay, 1000);
            }, {passive: true});
        }

        function handleSwipe() {
            if (touchEndX < touchStartX - swipeThreshold) {
                moveCarousel(1); 
            } else if (touchEndX > touchStartX + swipeThreshold) {
                moveCarousel(-1); 
            }
        }

        // Auto-play Logic
        function startAutoPlay() {
            if (autoPlayInterval) clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(() => {
                if (!isAnimating) moveCarousel(1);
            }, 5000);
        }

        function pauseAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
                autoPlayInterval = null;
            }
        }

        function resumeAutoPlay() {
            if (!autoPlayInterval) {
                startAutoPlay();
            }
        }

        // Hover to pause
        if(carouselWrapper){
            carouselWrapper.addEventListener('mouseenter', pauseAutoPlay);
            carouselWrapper.addEventListener('mouseleave', resumeAutoPlay);
        }

        startAutoPlay();

        cards.forEach((card, index) => {
            card.addEventListener('click', (e) => {
                if (index !== currentIndex) {
                    e.preventDefault();
                    let diff = index - currentIndex;
                    if (diff > totalCards / 2) diff -= totalCards;
                    if (diff < -totalCards / 2) diff += totalCards;
                    
                    pauseAutoPlay();
                    moveCarousel(diff > 0 ? 1 : -1);
                }
            });
        });

        /* ── MAPS (Original Implementation Retained) ── */
        function initMaps() {
            if (typeof UniversalMap === 'undefined') { setTimeout(initMaps, 500); return; }
            document.querySelectorAll('.poi-minimap:not([data-mapped="true"])').forEach(function(el) {
                var lat = parseFloat(el.getAttribute('data-lat'));
                var lon = parseFloat(el.getAttribute('data-lon'));
                if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
                    try {
                        var uMap = new UniversalMap(el.id, { lat: lat, lon: lon, zoom: 15, editable: 'false' });
                        if (uMap.map) {
                            uMap.toggleLock(true);
                            uMap.map.removeControl(uMap.map.zoomControl);
                            requestAnimationFrame(function(){ setTimeout(function(){
                                if (uMap.map) { uMap.map.invalidateSize(); uMap.map.setView([lat, lon], 15); }
                            }, 500); });
                        }
                        el.dataset.mapped = 'true';
                    } catch(e) { console.error('LumiereV2: map error', el.id, e); }
                }
            });
        }
        initMaps();
    });
    </script>
    {% endif %}

    {# ===================== L'ACTIVITÉ EN TEMPS RÉEL (Accordéons) ===================== #}
    

    {# 1. TOP 5 PÉPITES ACTIVES #}
    {% if hasTopPois %}
    <section class="landing-top-active parallax-section reveal-on-scroll">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Les pépites les plus actives <span class="badge-active-title">ACTUALISÉ</span></h2>
            <p class="section-subtitle">Ces établissements font vivre Bourges au quotidien par leurs publications et leur dynamisation.</p>
            
            <div class="galery-images">
                {% for i, poi in topPois %}
                <div class="card-image">
                    <input type="radio" 
                           name="image-galery-pois" 
                           id="poi-img-{{i}}" 
                           data-poi-id="{{poi.id}}"
                           data-lat="{{poi.lat|default(0)}}"
                           data-lon="{{poi.lon|default(0)}}"
                           {% if i == 0 %}checked="checked"{% endif %}>
                    {% set poiImg = SITE_URL ~ '/theme/LumiereV2/header.jpg' %}
                    {% if poi.has_cover %}
                        {% set poiImg = poi.cover_url %}
                    {% endif %}
                    <label for="poi-img-{{i}}" style="background-image: url('{{poiImg}}');">
                        <div class="accordion-badge"><i class="fa-solid fa-trophy"></i> #{{poi.rank}}</div>
                        <div class="collapsed-title" style="text-shadow: 0 2px 6px rgba(0,0,0,1);">{{poi.name}}</div>
                        <div class="accordion-content">
                            <h3 style="text-shadow: 0 2px 8px rgba(0,0,0,1);">
                                {{poi.name}}
                            </h3>
                            <p class="accordion-subtitle" style="text-shadow: 0 1px 4px rgba(0,0,0,1); background: rgba(0,0,0,0.5); padding: 5px 12px; border-radius: 4px; display: inline-block; font-weight: 500;">
                                {{poi.type_label}} • {{poi.city}}
                                {% if poi.is_open_now_html %}
                                    • {{poi.is_open_now_html|raw}}
                                {% endif %}
                            </p>
                            
                            <div class="accordion-meta-strip" style="background: rgba(0,0,0,0.4); padding: 10px; border-radius: 6px; margin: 10px 0; border: 1px solid rgba(255,255,255,0.1);">
                                {% if poi.phone %}
                                    <a href="tel:{{poi.phone}}" class="accordion-phone-link" title="Appeler" style="color: white; background: rgba(0,0,0,0.6); width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.3); margin-right: 10px;">
                                        <i class="fa-solid fa-phone"></i>
                                    </a>
                                {% endif %}
                                <p class="accordion-address" style="margin: 0; font-size: 0.85rem; display: inline-block; vertical-align: middle;"><i class="fa-solid fa-location-dot"></i> {{poi.full_address}}</p>
                            </div>
                            
                            
                            <div class="accordion-bottom-row">
                                <div class="accordion-features">
                                    {% if poi.wheelchair == "yes" or poi.wheelchair == "limited" %}
                                        <span class="feature-tag pmr" title="Accessible PMR{% if poi.wheelchair == 'limited' %} (Limité){% endif %}">
                                            <i class="fa-solid fa-wheelchair"></i> PMR{% if poi.wheelchair == "limited" %}*{% endif %}
                                        </span>
                                    {% endif %}
                                    {% if poi.parking == "yes" %}
                                        <span class="feature-tag parking" title="Parking disponible">
                                            <i class="fa-solid fa-square-p"></i> Parking
                                        </span>
                                    {% endif %}
                                </div>
                                <div class="accordion-socials">
                                    {% for link in poi.links %}
                                        <a href="{{link.url}}" target="_blank" class="accordion-social-btn" title="{{link.platform}}">
                                            <i class="{{link.icon_class}}"></i>
                                        </a>
                                    {% endfor %}
                                </div>
                            </div>
                            
                            <!-- Leaflet Mini Map Container -->
                            <div class="poi-mini-map" id="map-{{poi.id}}" style="height: 120px; width: 100%; margin: 15px 0; border-radius: 8px; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2);"></div>
                            
                            <a href="{{poi.detail_url_final}}" class="btn"><i class="fa-solid fa-arrow-right"></i> Voir la fiche</a>
                        </div>
                    </label>
                </div>
                {% endfor %}
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const poiRadios = document.querySelectorAll('input[name="image-galery-pois"]');
        const maps = {};

        function initAccordionMap(radio) {
            const poiId = radio.getAttribute('data-poi-id');
            const lat = parseFloat(radio.getAttribute('data-lat'));
            const lon = parseFloat(radio.getAttribute('data-lon'));
            const mapId = 'map-' + poiId;
            const container = document.getElementById(mapId);

            console.log("[AccordionMap] Init for POI:", poiId, "Lat:", lat, "Lon:", lon);

            if (!poiId || isNaN(lat) || !container) {
                console.error("[AccordionMap] Missing required data or container for POI:", poiId);
                return;
            }

            // Si Leaflet n'est pas chargé, on attend un peu
            if (typeof L === 'undefined') {
                console.warn("[AccordionMap] Leaflet (L) is undefined. Retrying in 1s...");
                setTimeout(() => initAccordionMap(radio), 1000);
                return;
            }

            if (maps[poiId]) {
                console.log("[AccordionMap] Map already exists for POI:", poiId, ". Invalidating size...");
                let attempts = 0;
                const interval = setInterval(() => {
                    maps[poiId].invalidateSize();
                    if (container.offsetWidth > 100 || attempts > 20) {
                        clearInterval(interval);
                        console.log("[AccordionMap] Size stabilization finished for existing map:", poiId);
                    }
                    attempts++;
                }, 100);
                return;
            }

            try {
                console.log("[AccordionMap] Creating new Leaflet map for POI:", poiId);
                const map = L.map(mapId, {
                    center: [lat, lon],
                    zoom: 14,
                    zoomControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    touchZoom: false
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OSM'
                }).addTo(map);

                L.marker([lat, lon]).addTo(map);
                maps[poiId] = map;
                
                console.log("[AccordionMap] Map created for POI:", poiId, ". Starting size stabilization...");
                
                // Forcer le rendu après l'animation d'ouverture
                let attempts = 0;
                const interval = setInterval(() => {
                    map.invalidateSize();
                    if (container.offsetWidth > 100 || attempts > 20) {
                        clearInterval(interval);
                        console.log("[AccordionMap] Size stabilization finished for new map:", poiId);
                    }
                    attempts++;
                }, 100);
            } catch (e) {
                console.error("[AccordionMap] CRITICAL ERROR for POI " + poiId + ":", e);
            }
        }

        poiRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    // Attendre que la transition CSS soit bien avancée
                    setTimeout(() => initAccordionMap(this), 600);
                }
            });
        });

        // Initialisation immédiate pour l'item coché par défaut
        const checked = document.querySelector('input[name="image-galery-pois"]:checked');
        if (checked) {
            setTimeout(() => initAccordionMap(checked), 1200);
        }
    });
    </script>
    {% endif %}

    {# 2. OFFRES & BONS PLANS #}
    <section class="landing-offers parallax-section reveal-on-scroll" style="background-color: #f8fafc; padding: 40px 20px;">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Les derniers Bons Plans</h2>
            <p class="section-subtitle">Profitez des offres exclusives proposées par nos commerçants.</p>
            
            {% if latestOffers and latestOffers|length > 0 %}
            <div class="galery-images">
                {% for i, offer in latestOffers %}
                <div class="card-image">
                    <input type="radio" name="image-galery-offers" id="offer-img-{{i}}" {% if i == 0 %}checked="checked"{% endif %}>
                    {% if offer.image %}
                        {% set bgImg = offer.image %}
                    {% elseif offer.poi.logo %}
                        {% set bgImg = offer.poi.logo %}
                    {% else %}
                        {% set bgImg = 'theme/LumiereV2/header.jpg' %}
                    {% endif %}
                    <label for="offer-img-{{i}}" style="background-image: url('{{bgImg}}'); background-size: cover; background-position: center;">
                        <div class="collapsed-title">{{offer.title|truncate(20)}}</div>
                        <div class="accordion-content">
                            <div class="accordion-badge">-{{offer.discount}}</div>
                            <h3 style="font-size:1.2rem;line-height:1.2;">{{offer.title}}</h3>
                            <p style="margin-bottom:10px; font-size:0.9rem;">De {{offer.poi_name}}</p>
                            <a href="{{offer.url}}" class="btn"><i class="fa-solid fa-tag"></i> Voir l'offre</a>
                        </div>
                    </label>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div class="empty-state-fallback" style="text-align: center; padding: 50px 20px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 2px dashed #cbd5e1; margin-top: 20px;">
                <i class="fa-solid fa-tags" style="font-size: 3rem; color: #94a3b8; margin-bottom: 15px;"></i>
                <h3 style="color: #475569; margin-bottom: 10px;">La plateforme vient d'ouvrir !</h3>
                <p style="color: #64748b; max-width: 500px; margin: 0 auto;">Les premières offres exclusives de nos commerçants locaux arrivent très bientôt. Restez connectés pour ne manquer aucun bon plan.</p>
                <div style="margin-top: 20px;">
                    <a href="{{ url_publier_offre }}" class="btn primary"><i class="fa-solid fa-plus"></i> Vous êtes pro ? Publiez la première offre !</a>
                </div>
            </div>
            {% endif %}
        </div>
    </section>

    {# 3. AGENDA & EVENEMENTS #}
    <section class="landing-events parallax-section reveal-on-scroll" style="padding: 40px 20px;">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Les prochains Événements</h2>
            <p class="section-subtitle">Ne manquez pas ce qui se passe près de chez vous.</p>
            
            {% if latestEvents and latestEvents|length > 0 %}
            <div class="galery-images">
                {% for i, event in latestEvents %}
                <div class="card-image">
                    <input type="radio" name="image-galery-events" id="event-img-{{i}}" {% if i == 0 %}checked="checked"{% endif %}>
                    {% if event.image %}
                        {% set bgImg = event.image %}
                    {% else %}
                        {% set bgImg = 'theme/LumiereV2/header.jpg' %}
                    {% endif %}
                    <label for="event-img-{{i}}" style="background-image: url('{{bgImg}}'); background-size: cover; background-position: center;">
                        <div class="collapsed-title" style="transform: translateX(-50%) rotate(0deg); bottom: auto; top: 10px; background: rgba(0,0,0,0.6); padding: 5px; border-radius:4px;">{{event.date}}</div>
                        <div class="accordion-content">
                            <h3 style="font-size:1.2rem;line-height:1.2;">{{event.title|truncate(40)}}</h3>
                            <p style="margin-bottom:10px; font-size:0.9rem;"><i class="fa-solid fa-location-dot"></i> {{event.location}}</p>
                            <a href="{{event.url}}" class="btn"><i class="fa-solid fa-eye"></i> Découvrir</a>
                        </div>
                    </label>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div class="empty-state-fallback" style="text-align: center; padding: 50px 20px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 2px dashed #cbd5e1; margin-top: 20px;">
                <i class="fa-solid fa-calendar-days" style="font-size: 3rem; color: #94a3b8; margin-bottom: 15px;"></i>
                <h3 style="color: #475569; margin-bottom: 10px;">L'agenda local se prépare</h3>
                <p style="color: #64748b; max-width: 500px; margin: 0 auto;">Les associations et organisateurs de la région préparent leurs premiers événements. L'agenda sera bientôt rempli de sorties incroyables !</p>
            </div>
            {% endif %}
        </div>
    </section>

    {# ===================== COMMUNAUTÉ (Blog, Forum) ===================== #}
    {% if latestBlog or latestForum %}
    <section class="landing-activity parallax-section reveal-on-scroll" style="background-color: #f1f5f9; padding: 40px 20px;">
        <div class="landing-how-content">
            <h2 class="landing-section-title">Actualités & Discussions</h2>
            <div class="landing-cards" style="justify-content: center; max-width: 900px; margin: 0 auto; display:flex; gap:20px;">
                {# Bloc Blog #}
                {% if latestBlog %}
                <div class="landing-card" style="flex: 1;">
                    <div class="landing-card-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <h3>Dernière actu</h3>
                    <p><strong>{{latestBlog.title}}</strong></p>
                    <p class="activity-excerpt">{{latestBlog.excerpt|truncate(120)}}</p>
                    <a href="{{latestBlog.url}}" class="landing-card-link">Lire l'article</a>
                </div>
                {% endif %}

                {# Bloc Forum #}
                {% if latestForum %}
                <div class="landing-card" style="flex: 1;">
                    <div class="landing-card-icon"><i class="fa-solid fa-comments"></i></div>
                    <h3>Le Forum</h3>
                    <div class="activity-list-container">
                        {% for post in latestForum|slice(0,3) %}
                            <div class="activity-item" style="margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:5px;">
                                <a href="{{post.url}}" class="activity-item-title" style="display:block; font-weight:600; color:#1e293b;">{{post.topic_title}}</a>
                                <span class="activity-item-date" style="font-size:0.8rem; color:#64748b;">{{post.date}}</span>
                            </div>
                        {% endfor %}
                    </div>
                    <a href="{{ROUTER.generate('forum-home')}}" class="landing-card-link">Voir le forum</a>
                </div>
                {% endif %}
            </div>
        </div>
    </section>
    {% endif %}

    {# ===================== SECTIONS DYNAMIQUES (Plugins) ===================== #}
    {% if extraHomeSections %}
        {% for section in extraHomeSections %}
            {{ section|raw }}
        {% endfor %}
    {% endif %}

    {# ===================== CALL TO ACTION FINAL ===================== #}
    {# Variables configurables pour les liens du Footer Call-To-Action #}
    {% set url_pourquoi_revendiquer = '/pages/pourquoi-revendiquer' %}
    {% set url_creer_compte = ROUTER.generate('register') %}
    {% set url_dashboard = ROUTER.generate('user-profile-home') %}
    {% set url_publier_offre = ROUTER.generate('offers-add')|default('/offers/add') %}

    <section class="landing-final-cta parallax-section reveal-on-scroll">
        <div class="landing-final-cta-content" style="text-align: center;">
            <h2>Prêt à dynamiser votre vie locale ?</h2>
            <p>Découvrez, contribuez et mettez en avant ce qui fait la force de notre région.</p>
            
            <div class="landing-hero-cta" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 30px;">
                {% if not IS_LOGGED %}
                    <a href="{{ url_creer_compte }}" class="cta-button cta-primary"><i class="fa-solid fa-user-plus"></i> Créer un compte</a>
                    <a href="{{ url_pro_explications }}" class="cta-button cta-secondary"><i class="fa-solid fa-question-circle"></i> Pourquoi revendiquer ma fiche ?</a>
                {% else %}
                    {# Si l'utilisateur est connecté mais n'a pas de POI #}
                    {% if not userHasPoi %}
                        <a href="{{ url_revendiquer }}" class="cta-button cta-primary"><i class="fa-solid fa-list-check"></i> Tuto : Comment revendiquer ?</a>
                    {% else %}
                        {# Si l'utilisateur est connecté et a déjà une fiche #}
                        <a href="{{ url_dashboard }}" class="cta-button cta-primary"><i class="fa-solid fa-gauge"></i> Accéder à mon tableau de bord</a>
                        <a href="{{ url_publier_offre }}" class="cta-button cta-secondary"><i class="fa-solid fa-tag"></i> Publier une offre</a>
                    {% endif %}
                {% endif %}
            </div>
        </div>
    </section>

</div>

<script>
    // Intersection Observer for Reveal Animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal-on-scroll').forEach(el => {
        revealObserver.observe(el);
    });

    // Animation au clic pour les cartes modernes (Comprendre le projet)
    document.querySelectorAll('.modern-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Empêcher l'animation si on clique sur un lien ou un bouton
            if(e.target.closest('a') !== null) return;
            
            // Retirer la classe pour réinitialiser l'animation
            this.classList.remove('card-clicked');
            
            // Forcer le reflow
            void this.offsetWidth;
            
            // Ajouter la classe d'animation
            this.classList.add('card-clicked');
        });
    });

    /* ── GÉOLOCALISATION HERO SEARCH ── */
    const locateBtn = document.getElementById('locate-me-btn');
    const heroSearchInput = document.getElementById('hero-search-input');
    const heroSearchForm = document.getElementById('hero-search-form');

    if (locateBtn) {
        locateBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if(icon) icon.classList.add('fa-spin');
            
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    let latInput = heroSearchForm.querySelector('input[name="lat"]');
                    let lonInput = heroSearchForm.querySelector('input[name="lon"]');
                    
                    if (!latInput) {
                        latInput = document.createElement('input');
                        latInput.type = 'hidden';
                        latInput.name = 'lat';
                        heroSearchForm.appendChild(latInput);
                    }
                    if (!lonInput) {
                        lonInput = document.createElement('input');
                        lonInput.type = 'hidden';
                        lonInput.name = 'lon';
                        heroSearchForm.appendChild(lonInput);
                    }
                    
                    latInput.value = lat;
                    lonInput.value = lon;
                    
                    if(icon) {
                        icon.classList.remove('fa-spin');
                        locateBtn.style.color = '#10b981'; 
                    }
                    heroSearchInput.placeholder = "Position détectée !";
                    if(!heroSearchInput.value) heroSearchInput.focus();
                }, function(error) {
                    if(icon) icon.classList.remove('fa-spin');
                    alert("Impossible de vous localiser.");
                });
            }
        });
    }
</script>

<style>
/* Styles pour l'enrichissement de l'accordéon des pépites */
.accordion-subtitle { font-weight: 600; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.3); margin-bottom: 5px; }
.accordion-meta-strip { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
.accordion-status { font-size: 0.8rem; }
.accordion-status .status-open i, .accordion-status .status-closing-soon i, .accordion-status .status-closed i { text-shadow: 0 0 5px rgba(0,0,0,0.3); }
.accordion-phone-chip { font-size: 0.8rem; color: white; background: rgba(255,140,66,0.2); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(255,140,66,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; gap: 6px; }
.accordion-address { font-size: 0.85rem; color: rgba(255,255,255,0.9); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.accordion-photo-wrap { margin-bottom: 15px; border-radius: 8px; overflow: hidden; height: 160px; border: 2px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.accordion-inner-photo { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
.card-image:hover .accordion-inner-photo { transform: scale(1.05); }

.accordion-bottom-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 5px; gap: 10px; }
.accordion-features { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 0; }
.feature-tag { 
    display: inline-flex; 
    align-items: center; 
    gap: 5px; 
    padding: 3px 10px; 
    border-radius: 12px; 
    font-size: 0.75rem; 
    font-weight: 600;
    color: white;
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(4px);
}
.feature-tag.pmr { background: rgba(41, 128, 185, 0.4); border-color: rgba(52, 152, 219, 0.5); }
.feature-tag.parking { background: rgba(39, 174, 96, 0.4); border-color: rgba(46, 204, 113, 0.5); }
.feature-tag i { font-size: 0.8rem; }

.accordion-socials { display: flex; gap: 8px; }
.accordion-social-btn { 
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; 
    background: rgba(255,255,255,0.1); color: white; border-radius: 50%; font-size: 0.9rem; 
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid rgba(255,255,255,0.2); 
    backdrop-filter: blur(4px);
}
.accordion-social-btn:hover { background: #ff8c42; transform: translateY(-3px) scale(1.1); border-color: #ff8c42; box-shadow: 0 4px 10px rgba(255,140,66,0.4); }

/* Responsive adjustments */
@media (max-width: 768px) {
    .accordion-content h3 { font-size: 1.15rem; }
    .accordion-features { gap: 4px; }
    .feature-tag { padding: 2px 8px; font-size: 0.7rem; }
    .accordion-photo-wrap { height: 120px; }
    .accordion-phone-chip { font-size: 0.75rem; }
}
</style>
