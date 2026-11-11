<!DOCTYPE html>
<html lang="{{lang.getLocale}}">
	<head>
		{% HOOK.frontHead %}
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title>{{SHOW.titleTag}}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="{{SHOW.metaDescriptionTag}}"/>
		<link rel="icon" href="{{SHOW.themeIcon}}"/>
		<base href="{{SHOW.siteUrl}}/">
		
		<!-- Google Fonts & Leaflet -->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
		<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
		<noscript><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
		{% if isLandingPage %}
		<link rel="preload" as="image" href="/plugin/WeatherHeader/assets/img/bourges_foreground.webp" type="image/webp" fetchpriority="high">
		{% endif %}
		
		{{SHOW.linkTags}}
		{{SHOW.scriptTags}}
		{{SHOW.showMetas}}
		{% HOOK.endFrontHead("") %}
		
		<!-- Matomo Analytics -->
		<script>
			var _paq = window._paq = window._paq || [];
			_paq.push(['disableCookies']); // Exemption de bandeau cookies (CNIL / RGPD)
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);
			(function() {
				var u='https://metriques.flexcb.fr/matomo/';
				_paq.push(['setTrackerUrl', u+'matomo.php']);
				_paq.push(['setSiteId', '3']);
				var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
				g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
			})();
		</script>
		<!-- End Matomo -->
	</head>
	<body class="{% if CORE.getPluginToCall %}plugin-{{ CORE.getPluginToCall|lower }}{% endif %}{% if CORE.getPluginToCall|lower == 'recherche' %} plugin-searchui is-search-page{% endif %} {% if isLandingPage %}is-landing{% else %}standard-page{% endif %}">
				<div id="body-background"></div>
		
		<div id="site-wrapper">
			<!-- Header Principal -->
			<header id="header" class="site-header">
				<nav class="header-container" id="header_content">
					<!-- Logo Cathédrale Thème Lumière + Nom du site -->
					<div class="header-brand">
						<a href="{{SHOW.siteUrl}}" class="brand-link" title="{{SHOW.siteName}}">
							<picture>
								<source srcset="theme/VivreBourges/logo.webp" type="image/webp">
								<img src="theme/VivreBourges/logo.png" alt="{{SHOW.siteName}}" class="site-lumiere-logo" width="44" height="44" />
							</picture>
							<span class="site-brand-text">Vivre-Bourges<span class="brand-tld">.fr</span></span>
						</a>
					</div>
					
					<!-- Navigation Principale Réelle (CMS, Agglobus, Météo, Auth) -->
					<div class="nav-wrapper">
						<input type="checkbox" id="nav_toggle" class="nav-toggle" aria-hidden="true" />
						<div class="header-links">
							<ul id="navigation">
								{{SHOW.mainNavigation}}
								{% HOOK.endMainNavigation("") %}
								<li class="nav-search-item">{% HOOK.299search:navbar %}</li>
								
								{% if IS_LOGGED == false %}
									<li class="nav-auth-links">
										{% if REGISTRATION_ENABLED %}
											<a class="register-link" href="{% HOOK.user:register-url('') %}">{{Lang.core-register}}</a>
										{% endif %}
										<a class="login-link" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
									</li>
								{% endif %}
								
								{% if IS_LOGGED %}
									{{SHOW.userAvatarMenu}}
								{% endif %}
							</ul>
						</div>

						<!-- Widget Météo de Bourges (Style Lumière V2 : Toujours visible dans la barre) -->
						<div class="header-meteo-container">
							{% HOOK.meteoWidgetHeader %}
						</div>

						<label for="nav_toggle" id="mobile_menu" title="Menu mobile"><span class="sr-only" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">Menu mobile</span><i class="fa-solid fa-bars" aria-hidden="true"></i></label>
					</div>
				</nav>
			</header>
			
			<!-- Messages d'alerte CMS -->
			<div id="alert-container">
				{{SHOW.displayMsg}}
			</div>
			
			<!-- Contenu principal -->
			<main id="main-content">
				{% if isLandingPage %}
					<!-- Landing Page personnalisée complète -->
					{{CONTENT}}
				{% else %}
					<!-- Pages standard du CMS -->
					<div class="standard-page-container">
						<div class="page-card">
							{% if not HIDE_PAGE_TITLE %}
							<header class="page-card-header">
								<h1 class="page-title">{{PAGE_TITLE|default(SHOW.mainTitle)}}</h1>
							</header>
							{% endif %}
							<div class="page-card-body">
								{{CONTENT}}
							</div>
						</div>
					</div>
				{% endif %}
			</main>
			
			<!-- Footer Riche SEO & Navigation Naturelle -->
			<footer id="site-footer" class="site-footer-rich">
				<div class="footer-rich-grid container">
					<!-- Col 1 : Plateforme & Mission -->
					<div class="footer-col footer-col-brand">
						<div class="footer-brand-header">
							<picture>
								<source srcset="theme/VivreBourges/logo.webp" type="image/webp">
								<img src="theme/VivreBourges/logo.png" alt="{{SHOW.siteName}}" class="footer-logo" width="36" height="36" loading="lazy" />
							</picture>
							<span class="footer-brand-title">Vivre-Bourges<span class="brand-tld">.fr</span></span>
						</div>
						<p class="footer-brand-desc">
							Le portail citoyen indépendant de Bourges et de son agglomération. Annuaire libre, valorisation du commerce de proximité et agenda collaboratif.
						</p>
						<div class="footer-social-links">
							<a href="{{SHOW.siteUrl}}" title="Accueil" class="footer-icon-link"><i class="fa-solid fa-house"></i> Accueil</a>
							<a href="/blog" title="Actualités et Blog" class="footer-icon-link"><i class="fa-solid fa-newspaper"></i> Blog</a>
							<a href="/contact" title="Nous contacter" class="footer-icon-link"><i class="fa-solid fa-envelope"></i> Contact</a>
						</div>
					</div>

					<!-- Col 2 : Explorer par Catégories (Hubs Indexables) -->
					<div class="footer-col footer-col-links">
						<h3 class="footer-col-title"><i class="fa-solid fa-compass"></i> Explorer par Catégorie</h3>
						<ul class="footer-nav-list">
							<li><a href="/recherche/restaurants"><i class="fa-solid fa-utensils"></i> Restaurants &amp; Gastronomie</a></li>
							<li><a href="/recherche/commerces"><i class="fa-solid fa-basket-shopping"></i> Commerces &amp; Boutiques</a></li>
							<li><a href="/recherche/sante"><i class="fa-solid fa-staff-snake"></i> Santé, Soins &amp; Pharmacies</a></li>
							<li><a href="/recherche/patrimoine"><i class="fa-solid fa-landmark"></i> Patrimoine, Culture &amp; Musées</a></li>
							<li><a href="/recherche/loisirs-sport"><i class="fa-solid fa-futbol"></i> Loisirs, Sorties &amp; Sports</a></li>
							<li><a href="/recherche/services"><i class="fa-solid fa-building-columns"></i> Services Publics &amp; Mairies</a></li>
							<li><a href="/recherche/nature"><i class="fa-solid fa-tree"></i> Parcs, Jardins &amp; Marais</a></li>
						</ul>
					</div>

					<!-- Col 3 : Lieux Incontournables de Bourges (Liens direct /[slug]) -->
					<div class="footer-col footer-col-links">
						<h3 class="footer-col-title"><i class="fa-solid fa-crown"></i> Lieux Incontournables</h3>
						<ul class="footer-nav-list">
							<li><a href="/cathedrale-saint-etienne">Cathédrale Saint-Étienne</a></li>
							<li><a href="/palais-jacques-coeur">Palais Jacques Cœur</a></li>
							<li><a href="/jardin-de-l-archeveche">Jardin de l'Archevêché</a></li>
							<li><a href="/museum-d-histoire-naturelle">Muséum d'Histoire Naturelle</a></li>
							<li><a href="/musee-du-berry">Musée du Berry</a></li>
							<li><a href="/musee-esteve">Musée Estève</a></li>
							<li><a href="/halle-au-bl-e">Halle au Blé</a></li>
							<li><a href="/musee-des-meilleurs-ouvriers-de-france">Musée des Meilleurs Ouvriers</a></li>
						</ul>
					</div>

					<!-- Col 4 : Vie Locale & Services -->
					<div class="footer-col footer-col-links">
						<h3 class="footer-col-title"><i class="fa-solid fa-map-location-dot"></i> Vie Locale &amp; Citoyenne</h3>
						<ul class="footer-nav-list">
							<li><a href="/agenda"><i class="fa-regular fa-calendar-days"></i> Agenda des sorties</a></li>
							<li><a href="/pro"><i class="fa-solid fa-store"></i> Espace Professionnels &amp; Artisans</a></li>
							<li><a href="/recherche"><i class="fa-solid fa-magnifying-glass"></i> Annuaire complet du Cher</a></li>
							<li><a href="/mentions-legales"><i class="fa-solid fa-scale-balanced"></i> Mentions Légales &amp; Confidentialité</a></li>
						</ul>
					</div>
				</div>

				<div class="footer-bottom-bar">
					<div class="footer-bottom-container container">
						<span class="copyright">© Vivre-Bourges.fr 2026 • Plateforme territoriale ouverte</span>
						<div class="footer-bottom-extra">
							<a href="/mentions-legales">Mentions Légales</a>
							{% if IS_LOGGED and can_access('system:admin-access') %}
								<span class="footer-sep">•</span>
								<a href="{% HOOK.admin:url('') %}" class="admin-link">Administration</a>
							{% endif %}
						</div>
					</div>
				</div>
				{% HOOK.endFooter %}
			</footer>
		</div>
		
		{% HOOK.endFrontBody %}
		</body>
</html>
