{# Template Page Accueil - Thème Vivre Bourges #}
{# Conforme au design header_vivre-bourges.png & enrichi avec les fonctionnalités de Lumière V2 #}

<div class="landing-page-root">
	<!-- ==============================================
	     SECTION 1 : HERO BANNER AVEC FOND PANORAMIQUE
	     ============================================== -->
	<section class="bourges-hero-banner">
		<div class="bourges-hero-overlay"></div>
		<div class="bourges-hero-content">
			<h1 class="bourges-hero-title">Vivre-Bourges<span class="hero-accent">.fr</span></h1>
			<p class="bourges-hero-subtitle">Bienvenue à Bourges : L'Art de Vivre au Cœur du Cher !</p>
			<div class="bourges-hero-actions">
				<a href="SearchUi/result?q=patrimoine" class="bourges-hero-btn">
					<span><i class="fa-solid fa-compass"></i> Explorez la Ville</span>
				</a>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION 2 : LES 6 CARTES DIAMANTS / HEXAGONALES
	     ============================================== -->
	<section class="bourges-feature-cards-section" id="features">
		<div class="feature-cards-container">
			<!-- Carte 1 : Découvrir Bourges -->
			<div class="feature-diamond-card">
				<a href="SearchUi/result?q=patrimoine" class="diamond-head-link" title="Découvrir le patrimoine">
					<div class="card-hex-header">
						<div class="hex-shape image-hex">
							<img src="theme/VivreBourges/img/card_decouvrir.png" alt="Découvrir Bourges" />
						</div>
					</div>
					<h3 class="card-title">DÉCOUVRIR BOURGES</h3>
				</a>
				<ul class="card-bullet-list">
					<li><a href="SearchUi/result?q=patrimoine">Patrimoine</a></li>
					<li><a href="SearchUi/result?q=marais">Marais</a></li>
					<li><a href="SearchUi/result?q=jardin">Jardins</a></li>
				</ul>
			</div>

			<!-- Carte 2 : Événements à Bourges -->
			<div class="feature-diamond-card">
				<a href="agenda" class="diamond-head-link" title="Consulter l'agenda">
					<div class="card-hex-header">
						<div class="hex-shape icon-hex">
							<i class="fa-regular fa-calendar-days"></i>
						</div>
					</div>
					<h3 class="card-title">ÉVÉNEMENTS À BOURGES</h3>
					<div class="card-preview-image">
						<img src="theme/VivreBourges/img/card_events.png" alt="Concerts et sorties à Bourges" />
					</div>
				</a>
				<div class="card-mini-action">
					<a href="agenda" class="card-sub-cta">Agenda des sorties <i class="fa-solid fa-chevron-right"></i></a>
				</div>
			</div>

			<!-- Carte 3 : Services Municipaux -->
			<div class="feature-diamond-card">
				<a href="SearchUi/result?category=administration-services-publics" class="diamond-head-link" title="Services municipaux et démarches">
					<div class="card-hex-header">
						<div class="hex-shape image-hex">
							<img src="theme/VivreBourges/img/card_services.png" alt="Services Municipaux" />
						</div>
					</div>
					<h3 class="card-title">SERVICES MUNICIPAUX</h3>
				</a>
				<ul class="card-bullet-list">
					<li><a href="SearchUi/result?category=administration-services-publics">Mairie &amp; Ville</a></li>
					<li><a href="SearchUi/result?q=services+publics">Services publics</a></li>
					<li><a href="SearchUi/result?q=démarches">Démarches…</a></li>
				</ul>
			</div>

			<!-- Carte 4 : Guide Pratique -->
			<div class="feature-diamond-card">
				<a href="SearchUi/result?category=commerce" class="diamond-head-link" title="Guide pratique des commerces et services">
					<div class="card-hex-header">
						<div class="hex-shape icon-hex olive-theme">
							<i class="fa-solid fa-map-location-dot"></i>
						</div>
					</div>
					<h3 class="card-title">GUIDE PRATIQUE</h3>
				</a>
				<ul class="card-bullet-list">
					<li><a href="SearchUi">Annuaire local</a></li>
					<li><a href="SearchUi/result?category=commerce">Commerces</a></li>
					<li><a href="SearchUi/result?category=sante-soins">Santé &amp; Pratique</a></li>
				</ul>
			</div>

			<!-- Carte 5 : Actualités Locales -->
			<div class="feature-diamond-card">
				<a href="blog" class="diamond-head-link" title="Lire les actualités">
					<div class="card-hex-header">
						<div class="hex-shape icon-hex navy-theme">
							<i class="fa-regular fa-newspaper"></i>
						</div>
					</div>
					<h3 class="card-title">ACTUALITÉS LOCALES</h3>
				</a>
				<div class="card-mini-articles">
					{% for art in recentArticles %}
					<a href="{{art.url}}" class="mini-article-row" title="{{art.name}}">
						<span class="mini-thumb"><i class="fa-solid fa-newspaper"></i></span>
						<span class="mini-name">{{art.name|slice(0, 22)}}…</span>
					</a>
					{% endfor %}
				</div>
			</div>

			<!-- Carte 6 : Associations & Clubs -->
			<div class="feature-diamond-card">
				<a href="SearchUi/result?q=association" class="diamond-head-link" title="Trouver une association">
					<div class="card-hex-header">
						<div class="hex-shape image-hex">
							<img src="theme/VivreBourges/img/card_assos.png" alt="Associations et clubs de Bourges" />
						</div>
					</div>
					<h3 class="card-title">ASSOCIATIONS &amp; CLUBS</h3>
				</a>
				<ul class="card-bullet-list">
					<li><a href="SearchUi/result?q=sport">Sports &amp; Loisirs</a></li>
					<li><a href="SearchUi/result?q=culture">Culture &amp; Arts</a></li>
					<li><a href="SearchUi/result?q=association">Bénévolat &amp; Clubs</a></li>
				</ul>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION 3 : BAS DE PAGE (2 COLONNES)
	     GAUCHE : ACTUALITÉS & AGENDA / DROITE : CARTE INTERACTIVE
	     ============================================== -->
	<section class="bourges-lower-section">
		<div class="lower-section-container">
			<!-- Colonne de gauche : Dernières Actualités & Agenda -->
			<div class="lower-left-col">
				<div class="section-heading-row">
					<h2 class="bourges-section-title">Dernières Actualités &amp; Agenda</h2>
					<a href="agenda" class="see-all-link">Tout l'agenda <i class="fa-solid fa-arrow-right"></i></a>
				</div>

				<div class="agenda-news-grid">
					{% for card in agendaCards %}
					<a href="{{card.url}}" class="agenda-news-card">
						<div class="card-img-wrapper">
							<img src="{{card.image}}" alt="{{card.title}}" />
							{% if card.tag %}
							<span class="card-category-badge">{{card.tag}}</span>
							{% endif %}
						</div>
						<div class="card-text-wrapper">
							<h4 class="agenda-card-title">{{card.title}}</h4>
							<p class="agenda-card-subtitle">{{card.subtitle}}</p>
						</div>
					</a>
					{% endfor %}
				</div>
			</div>

			<!-- Colonne de droite : Carte Interactive Leaflet -->
			<div class="lower-right-col" id="carte-interactive">
				<div class="section-heading-row">
					<h2 class="bourges-section-title">Carte Interactive</h2>
					<a href="SearchUi" class="see-all-link">Vue plein écran <i class="fa-solid fa-expand"></i></a>
				</div>

				<div class="interactive-map-widget">
					<div class="map-widget-search">
						<i class="fa-solid fa-magnifying-glass"></i>
						<input type="text" id="map-quick-filter" placeholder="Filtrer un lieu ou un commerce…" />
					</div>
					
					<div id="bourges-leaflet-map" class="bourges-leaflet-container"></div>
					
					<div class="map-widget-footer">
						<span class="map-poi-count"><i class="fa-solid fa-location-dot"></i> {{mapPois|length}} points d'intérêt affichés</span>
						<a href="SearchUi" class="map-more-btn">Explorer l'annuaire complet</a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION 4 : CAROUSEL DES LIEUX & COMMERCES (Lumière V2)
	     ============================================== -->
	{% if hasCarouselPois %}
	<section class="landing-poi-carousel-section">
		<div class="carousel-section-container">
			<div class="section-heading-row">
				<div>
					<h2 class="bourges-section-title">Découvrez les adresses de Bourges</h2>
					<p class="section-subtitle">Commerces, services et lieux utiles du territoire</p>
				</div>
				<div class="carousel-controls">
					<button type="button" class="carousel-nav-btn prev-btn" id="carousel-prev" aria-label="Précédent"><i class="fa-solid fa-chevron-left"></i></button>
					<button type="button" class="carousel-nav-btn next-btn" id="carousel-next" aria-label="Suivant"><i class="fa-solid fa-chevron-right"></i></button>
				</div>
			</div>

			<div class="poi-carousel-track" id="poi-carousel-track">
				{% for poi in carouselPois %}
				<div class="poi-carousel-card">
					<div class="poi-card-icon-header">
						<div class="poi-cat-icon">
							<i class="{{poi.icon}}"></i>
						</div>
						<span class="poi-cat-badge">{{poi.cat_label}}</span>
					</div>
					<div class="poi-card-body">
						<h4 class="poi-card-name" title="{{poi.name}}">{{poi.name}}</h4>
						{% if poi.addr_street %}
						<p class="poi-card-address"><i class="fa-solid fa-map-pin"></i> {{poi.addr_street}}{% if poi.addr_city %}, {{poi.addr_city}}{% endif %}</p>
						{% endif %}
						{% if poi.phone %}
						<p class="poi-card-phone"><i class="fa-solid fa-phone"></i> {{poi.phone}}</p>
						{% endif %}
					</div>
					<div class="poi-card-footer">
						<a href="{{poi.detail_url}}" class="poi-card-link">Consulter la fiche <i class="fa-solid fa-arrow-right"></i></a>
					</div>
				</div>
				{% endfor %}
			</div>
		</div>
	</section>
	{% endif %}

	<!-- ==============================================
	     SECTION 5 : LA COMMUNAUTÉ EN CHIFFRES (Lumière V2)
	     ============================================== -->
	<section class="landing-stats-section">
		<div class="stats-section-container">
			<h2 class="bourges-section-title center-title">La communauté en chiffres</h2>
			<p class="section-subtitle center-subtitle">Un annuaire local collaboratif enrichi chaque jour</p>

			<div class="stats-counter-grid">
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.total_pois}}</div>
					<div class="stat-label-val">Lieux répertoriés</div>
					<div class="stat-desc-val">Dans l'agglomération de Bourges</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.total_users}}</div>
					<div class="stat-label-val">Membres actifs</div>
					<div class="stat-desc-val">Habitants et professionnels</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.weekly_visitors}}+</div>
					<div class="stat-label-val">Visiteurs / semaine</div>
					<div class="stat-desc-val">Découvrent les commerces locaux</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.recent_updates}}</div>
					<div class="stat-label-val">Mises à jour</div>
					<div class="stat-desc-val">Ces dernières 24 heures</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION 6 : COMMENT ÇA MARCHE ? (Lumière V2)
	     ============================================== -->
	<section class="landing-how-section">
		<div class="how-section-container">
			<h2 class="bourges-section-title center-title">Comment fonctionne Vivre-Bourges.fr ?</h2>
			<div class="how-cards-grid">
				<div class="how-card">
					<div class="how-icon-wrap"><i class="fa-solid fa-earth-europe"></i></div>
					<h3 class="how-card-title">Données ouvertes</h3>
					<p class="how-card-text">
						La base s'appuie sur OpenStreetMap, un bien commun numérique libre et indépendant garantissant la souveraineté des données.
					</p>
				</div>
				<div class="how-card">
					<div class="how-icon-wrap"><i class="fa-solid fa-users"></i></div>
					<h3 class="how-card-title">Contributions locales</h3>
					<p class="how-card-text">
						Citoyens, commerçants et associations corrigent, enrichissent et valident les fiches pour offrir une information toujours fiable.
					</p>
				</div>
				<div class="how-card">
					<div class="how-icon-wrap"><i class="fa-solid fa-certificate"></i></div>
					<h3 class="how-card-title">Visibilité des acteurs</h3>
					<p class="how-card-text">
						Chaque professionnel peut réclamer sa fiche gratuitement pour publier ses horaires, offres promotionnelles et actualités.
					</p>
				</div>
			</div>
		</div>
	</section>
</div>

<!-- Données JSON pour l'initialisation de la carte Leaflet -->
<script id="bourges-map-data" type="application/json">
{{mapPoisJson}}
</script>
