{# Template Page Accueil - Thème Vivre Bourges - Refonte Octobre 2026 #}
{# Structure : Hero → Feed → Services → Carte+Agenda → Adresses → Pro → Patrimoine → Footer #}

<div class="landing-page-root">
	<!-- ==============================================
	     SECTION 1 : HERO BANNER (DYNAMIQUE METEO OU STATIQUE)
	     ============================================== -->
	{% IF hasWeatherHero %}
		{{ weatherHeroHtml }}
	{% ELSE %}
	<section class="bourges-hero-banner">
		<div class="bourges-hero-overlay"></div>
		<div class="bourges-hero-content">
			<h1 class="bourges-hero-title">Bourges et ses environs, ça se vit ici.</h1>
			<p class="bourges-hero-subtitle">Les commerces, les services, les sorties, les bons plans et les actualités locales réunis au même endroit.</p>
			<div class="bourges-hero-actions" style="display:flex; align-items:center; justify-content:center; gap:12px; flex-wrap:wrap;">
				<a href="/SearchUi" class="bourges-hero-btn" style="display:inline-flex; align-items:center; gap:8px;">
					<span>Explorer la carte</span>
				</a>
				<a href="/mur" class="bourges-hero-btn bourges-hero-btn-feed" style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg, #e67e22, #d35400); color:#ffffff; border-radius:12px; padding:12px 22px; font-weight:700; text-decoration:none; box-shadow:0 4px 15px rgba(230,126,34,0.35);">
					<span>Voir la vie locale</span>
				</a>
				<a href="/pro" class="bourges-hero-btn bourges-hero-btn-pro" style="display:inline-flex; align-items:center; gap:8px; background:rgba(15,23,42,0.7); color:#ffffff; border:1.5px solid rgba(255,255,255,0.3); border-radius:12px; padding:12px 22px; font-weight:700; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.25);">
					<span>Espace Pro</span>
				</a>
			</div>
		</div>
	</section>
	{% ENDIF %}

	<!-- ==============================================
	     BARRE D'ACCÈS RAPIDE (remplace les 6 diamants)
	     ============================================== -->
	<nav class="landing-quick-nav" aria-label="Accès rapide aux rubriques">
		<a href="/SearchUi" class="quick-nav-item" title="Rechercher par carte">
			<span class="qn-label">Carte & Annuaire</span>
		</a>
		<a href="/agenda" class="quick-nav-item" title="Voir l'agenda des sorties">
			<span class="qn-label">Agenda</span>
		</a>
		<a href="/recherche/commerces" class="quick-nav-item" title="Commerces et artisans">
			<span class="qn-label">Commerces</span>
		</a>
		<a href="/recherche/services" class="quick-nav-item" title="Services publics et démarches">
			<span class="qn-label">Services</span>
		</a>
		<a href="/bons-plans" class="quick-nav-item quick-nav-deals" title="Bons plans déstockage et anti-gaspi">
			<span class="qn-label">Bons Plans</span>
			{% if communityStats.active_deals %}
			<span class="qn-badge">{{communityStats.active_deals}}</span>
			{% endif %}
		</a>
		<a href="/offers" class="quick-nav-item quick-nav-offers" title="Offres et réductions en magasin">
			<span class="qn-label">Offres Promo</span>
		</a>
		<a href="/recherche/patrimoine" class="quick-nav-item" title="Patrimoine et lieux incontournables">
			<span class="qn-label">Patrimoine</span>
		</a>
		<a href="/pro" class="quick-nav-item quick-nav-pro" title="Espace professionnel — Phase pilote gratuite" style="margin-left:auto; color:#64748b; font-style:italic; font-size:0.82rem; border-left:1px solid #e2e8f0;">
			<span class="qn-label">Vous êtes un professionnel ?</span>
		</a>
	</nav>

	<!-- ==============================================
	     SECTION : SERVICES & COMMODITÉS DU QUOTIDIEN (OSM)
	     (Différenciateur clé — ce que les autres guides n'ont pas)
	     ============================================== -->
	<section class="bourges-civic-services-section">
		<div class="civic-services-container">
			<div class="civic-services-header">
				<div class="civic-header-text">
					<h2 class="civic-title">Services & Commodités du Quotidien</h2>
					<p class="civic-subtitle">Les équipements publics essentiels autour de vous, en accès libre.</p>
				</div>
				<div class="civic-nav-controls">
					<button type="button" class="civic-nav-btn" id="civic-prev" aria-label="Défiler à gauche"><i class="fa-solid fa-chevron-left"></i></button>
					<button type="button" class="civic-nav-btn" id="civic-next" aria-label="Défiler à droite"><i class="fa-solid fa-chevron-right"></i></button>
				</div>
			</div>

			<div class="civic-pills-grid" id="civic-pills-track">
				<a href="/recherche/sante/pharmacies-parapharmacies" class="civic-pill pill-pharmacy" title="Voir les pharmacies de Bourges">
					<div class="pill-icon"><i class="fa-solid fa-staff-snake"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.pharmacies}}</span>
						<span class="pill-name">Pharmacies</span>
					</div>
				</a>

				<a href="/recherche/services/distributeurs-billets-dab" class="civic-pill pill-atm" title="Voir les distributeurs automatiques de billets (DAB)">
					<div class="pill-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.atms}}</span>
						<span class="pill-name">Distributeurs (DAB)</span>
					</div>
				</a>

				<a href="/recherche/sante/urgences-defibrillateurs" class="civic-pill pill-defibrillator" title="Voir les défibrillateurs (DAE)">
					<div class="pill-icon"><i class="fa-solid fa-heart-pulse"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.defibrillators}}</span>
						<span class="pill-name">Défibrillateurs (DAE)</span>
					</div>
				</a>

				<a href="/recherche/services/fontaines-eau-potable" class="civic-pill pill-water" title="Voir les fontaines d'eau potable">
					<div class="pill-icon"><i class="fa-solid fa-droplet"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.water_fountains}}</span>
						<span class="pill-name">Fontaines eau potable</span>
					</div>
				</a>

				<a href="/recherche/services/toilettes-publiques" class="civic-pill pill-toilet" title="Voir les toilettes publiques">
					<div class="pill-icon"><i class="fa-solid fa-restroom"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.toilets}}</span>
						<span class="pill-name">Toilettes publiques</span>
					</div>
				</a>

				<a href="/recherche/loisirs-sport/aires-jeux" class="civic-pill pill-playground" title="Voir les aires de jeux pour enfants">
					<div class="pill-icon"><i class="fa-solid fa-shapes"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.playgrounds}}</span>
						<span class="pill-name">Aires de jeux</span>
					</div>
				</a>

				<a href="/recherche/loisirs-sport/terrains-sport-city-stades" class="civic-pill pill-pitch" title="Voir les terrains de sport et city-stades">
					<div class="pill-icon"><i class="fa-solid fa-futbol"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.pitches}}</span>
						<span class="pill-name">City-stades & Terrains</span>
					</div>
				</a>

				<a href="/recherche/patrimoine/boites-a-livres" class="civic-pill pill-bookcase" title="Voir les boîtes à livres">
					<div class="pill-icon"><i class="fa-solid fa-book-open-reader"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.bookcases}}</span>
						<span class="pill-name">Boîtes à livres</span>
					</div>
				</a>

				<a href="/recherche/services/centres-sociaux-quartiers" class="civic-pill pill-community" title="Voir les maisons de quartier">
					<div class="pill-icon"><i class="fa-solid fa-people-roof"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.community_centres}}</span>
						<span class="pill-name">Maisons de quartier</span>
					</div>
				</a>

				<a href="/recherche/nature/parcs-canins" class="civic-pill pill-dogpark" title="Voir les espaces canins">
					<div class="pill-icon"><i class="fa-solid fa-dog"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.dog_parks}}</span>
						<span class="pill-name">Parcs canins</span>
					</div>
				</a>

				<a href="/recherche/loisirs-sport/pistes-glisse-skateparks" class="civic-pill pill-track" title="Voir les skateparks, pumptracks et pistes">
					<div class="pill-icon"><i class="fa-solid fa-person-skating"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.tracks}}</span>
						<span class="pill-name">Skateparks & Pistes</span>
					</div>
				</a>

				<a href="/recherche/patrimoine/oeuvres-art-statues" class="civic-pill pill-artwork" title="Voir les statues et œuvres d'art publiques">
					<div class="pill-icon"><i class="fa-solid fa-monument"></i></div>
					<div class="pill-info">
						<span class="pill-count">{{civicCounts.artworks}}</span>
						<span class="pill-name">Art public & Statues</span>
					</div>
				</a>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION : LA VIE LOCALE EN DIRECT (LOCALFEED PREVIEW)
	     ============================================== -->
	{{ feedPreviewHtml }}

	<!-- ==============================================
	     SECTION : CARTE + AGENDA (2 COLONNES)
	     ============================================== -->
	<section class="bourges-lower-section">
		<div class="lower-section-container">
			<!-- Colonne de gauche : Agenda -->
			<div class="lower-left-col">
				<div class="section-heading-row">
					<div>
						<h2 class="bourges-section-title">Agenda des Sorties</h2>
						<p class="section-subtitle-compact">Les prochaines dates et temps forts à Bourges et en Berry</p>
					</div>
					<a href="/agenda" class="see-all-link">Tout l'agenda <i class="fa-solid fa-arrow-right"></i></a>
				</div>

				<div class="agenda-news-grid">
					{% for card in agendaCards %}
					<a href="{{card.url}}" class="agenda-news-card">
						<div class="card-img-wrapper">
							<img src="{{card.image}}" alt="{{card.title}}" width="300" height="95" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='/theme/VivreBourges/img/news_1.webp';" />
							{% if card.tag %}
							<span class="card-category-badge">{{card.tag}}</span>
							{% endif %}
						</div>
						<div class="card-text-wrapper">
							<h3 class="agenda-card-title">{{card.title}}</h3>
							<p class="agenda-card-subtitle">{{card.subtitle}}</p>
						</div>
					</a>
					{% endfor %}
				</div>
				<div class="agenda-bottom-cta">
					<a href="/agenda" class="agenda-view-all-btn">
						<i class="fa-solid fa-calendar-days"></i> Parcourir toutes les sorties programmées <i class="fa-solid fa-arrow-right"></i>
					</a>
				</div>
			</div>

			<!-- Colonne de droite : Carte Interactive Leaflet -->
			<div class="lower-right-col" id="carte-interactive">
				<div class="section-heading-row">
					<h2 class="bourges-section-title">Carte Interactive</h2>
					<a href="/recherche" class="see-all-link">Vue plein écran <i class="fa-solid fa-expand"></i></a>
				</div>

				<div class="interactive-map-widget">
					<div class="map-widget-search">
						<i class="fa-solid fa-magnifying-glass"></i>
						<input type="text" id="map-quick-filter" placeholder="Filtrer un lieu ou un commerce…" />
					</div>
					
					<div id="bourges-leaflet-map" class="bourges-leaflet-container"></div>
					
					<div class="map-widget-footer">
						<span class="map-poi-count"><i class="fa-solid fa-location-dot"></i> {{mapPois|length}} points d'intérêt affichés</span>
						<a href="/recherche" class="map-more-btn">Explorer l'annuaire complet</a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ==============================================
	     SECTION : DÉCOUVREZ LES ADRESSES DE BOURGES (Carousel)
	     ============================================== -->
	{% if hasCarouselPois %}
	<section class="landing-poi-carousel-section">
		<div class="carousel-section-container">
			<div class="section-heading-row">
				<div>
					<h2 class="bourges-section-title">Adresses utiles à Bourges</h2>
					<p class="section-subtitle">Commerces, services et lieux du territoire</p>
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
						<h3 class="poi-card-name" title="{{poi.name}}">{{poi.name}}</h3>
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
	     SECTION : APPEL AUX PROFESSIONNELS PILOTES
	     Positionnée après le fil d'actualité : le visiteur a d'abord
	     découvert la valeur du site, puis comprend comment y contribuer
	     ============================================== -->
	<section class="landing-pro-section" id="espace-pro">
		<div class="pro-section-inner">

			<div class="pro-section-top">
				<div class="pro-section-left">
					<div class="pro-section-badge">Programme pilote &bull; 5 places disponibles</div>
					<h2 class="pro-section-title">Vous faites vivre <span>Bourges</span> et ses environs&nbsp;?</h2>
					<p class="pro-section-subtitle">Commerçants, artisans, associations, indépendants : donnez de la visibilité à votre activité et partagez vos actualités directement avec les habitants. <strong>Participez aux premiers retours d'expérience et contribuez à façonner les outils locaux de demain.</strong></p>
				</div>
				<a href="/pro" class="pro-section-cta-main">Rejoindre les professionnels pilotes <i class="fa-solid fa-arrow-right"></i></a>
			</div>

			<div class="pro-features-grid">
				<div class="pro-feature-item">
					<div class="pro-feature-icon"><i class="fa-solid fa-id-card"></i></div>
					<div class="pro-feature-text">
						<strong>Votre fiche locale</strong>
						<span>Horaires, coordonnées, photos, description — tout ce qu'un habitant cherche en premier.</span>
					</div>
				</div>
				<div class="pro-feature-item">
					<div class="pro-feature-icon"><i class="fa-solid fa-bullhorn"></i></div>
					<div class="pro-feature-text">
						<strong>Vos actualités & événements</strong>
						<span>Publiez dans le fil de la vie locale, visible par tous les visiteurs du territoire.</span>
					</div>
				</div>
				<div class="pro-feature-item">
					<div class="pro-feature-icon"><i class="fa-solid fa-tag"></i></div>
					<div class="pro-feature-text">
						<strong>Bons plans & offres</strong>
						<span>Mettez en avant vos promotions et événements auprès d'une audience locale qualifiée.</span>
					</div>
				</div>
			</div>

			<div class="pro-pilot-note">
				<span class="pro-pilot-dot"></span>
				<span>Programme pilote gratuit de 3 mois ouvert à <strong style="color:#cbd5e1;">5 professionnels partenaires</strong> du Berry. Vos retours d'expérience façonnent directement la plateforme de demain.</span>
			</div>

		</div>
	</section>

	<!-- ==============================================
	     SECTION : PATRIMOINE — LIEN DISCRET EN BAS DE PAGE
	     ============================================== -->
	<section class="landing-heritage-teaser">
		<div class="heritage-teaser-container">
			<h2 class="bourges-section-title">Patrimoine & Lieux Incontournables</h2>
			<p class="section-subtitle">Cathédrale Saint-Étienne, Palais Jacques-Cœur, musées, jardins historiques…</p>
			<a href="/recherche/patrimoine" class="heritage-explore-btn">Découvrir le patrimoine de Bourges</a>
		</div>
	</section>

	<!-- ==============================================
	     SECTION : LA PLATEFORME EN CHIFFRES
	     ============================================== -->
	<section class="landing-stats-section">
		<div class="stats-section-container">
			<div class="stats-counter-grid">
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.total_pois}}</div>
					<div class="stat-label-val">Lieux répertoriés</div>
					<div class="stat-desc-val">Dans l'agglomération de Bourges</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">{{communityStats.upcoming_events}}</div>
					<div class="stat-label-val">Sorties programmées</div>
					<div class="stat-desc-val">Agenda culturel et festif à jour</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">100%</div>
					<div class="stat-label-val">Accès libre & gratuit</div>
					<div class="stat-desc-val">Pour tous les habitants et visiteurs</div>
				</div>
				<div class="stat-box">
					<div class="stat-number-val">OpenStreetMap</div>
					<div class="stat-label-val">Base de données</div>
					<div class="stat-desc-val">Données libres et souveraines</div>
				</div>
			</div>
		</div>
	</section>

</div>

<!-- Données JSON pour l'initialisation de la carte Leaflet -->
<script id="bourges-map-data" type="application/json">
{{mapPoisJson}}
</script>
