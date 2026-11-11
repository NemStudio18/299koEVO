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
		<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
		
		{{SHOW.linkTags}}
		{{SHOW.scriptTags}}
		{{SHOW.showMetas}}
		{% HOOK.endFrontHead("") %}
		
		<link rel="stylesheet" href="theme/VivreBourges/styles.css?v=3.10" type="text/css"/>
		<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
	</head>
	<body class="{% if CORE.getPluginToCall %}plugin-{{ CORE.getPluginToCall|lower }}{% endif %} {% if isLandingPage %}is-landing{% else %}standard-page{% endif %}">
		<div id="body-background"></div>
		
		<div id="site-wrapper">
			<!-- Header Principal -->
			<header id="header" class="site-header">
				<nav class="header-container" id="header_content">
					<!-- Logo Cathédrale Thème Lumière + Nom du site -->
					<div class="header-brand">
						<a href="{{SHOW.siteUrl}}" class="brand-link" title="{{SHOW.siteName}}">
							<img src="theme/VivreBourges/logo.png" alt="{{SHOW.siteName}}" class="site-lumiere-logo" />
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
								<li class="nav-search-item">{% HOOK.SearchUi:header-bar %}</li>
								
								{% if REGISTRATION_ENABLED and IS_LOGGED == false %}
									<li class="nav-auth-links">
										<a class="register-link" href="{% HOOK.user:register-url('') %}">{{Lang.core-register}}</a>
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

						<label for="nav_toggle" id="mobile_menu" aria-label="Menu mobile"><i class="fa-solid fa-bars"></i></label>
					</div>
				</nav>
			</header>
			
			<!-- Messages d'alerte CMS -->
			{% if SHOW.displayMsg %}
			<div id="alert-container">
				{{SHOW.displayMsg}}
			</div>
			{% endif %}
			
			<!-- Contenu principal -->
			<main id="main-content">
				{% if isLandingPage %}
					<!-- Landing Page personnalisée complète -->
					{{CONTENT}}
				{% else %}
					<!-- Pages standard du CMS -->
					<div class="standard-page-container">
						<div class="page-card">
							{% if not CORE.getConfigVal("hideTitles") and not HIDE_PAGE_TITLE %}
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
			
			<!-- Footer -->
			<footer id="site-footer">
				<div class="footer-container">
					<div class="footer-left-links">
						<a href="{{SHOW.siteUrl}}">Accueil</a>
						<span class="footer-sep">•</span>
						<a href="agenda">Événements</a>
						<span class="footer-sep">•</span>
						<a href="SearchUi">Services</a>
						<span class="footer-sep">•</span>
						<a href="blog">Actualités</a>
						<span class="footer-sep">•</span>
						<a href="contact">Contact</a>
					</div>
					
					<div class="footer-center-partners">
						<img src="theme/VivreBourges/img/logo_bourges.png" alt="Ville de Bourges" class="partner-logo" loading="lazy" decoding="async" />
						<img src="theme/VivreBourges/img/logo_cher.png" alt="Département du Cher" class="partner-logo" loading="lazy" decoding="async" />
					</div>
					
					<div class="footer-right-legal">
						<a href="page/mentions-legales">Mentions légales</a>
						<span class="footer-sep">•</span>
						<span class="copyright">© Vivre-Bourges.fr {{ "now"|date("Y") }}</span>
						{% if IS_LOGGED and can_access('system:admin-access') %}
							<span class="footer-sep">•</span>
							<a href="{% HOOK.admin:url('') %}" class="admin-link">Administration</a>
						{% endif %}
					</div>
				</div>
				{% HOOK.endFooter %}
			</footer>
		</div>
		
		<script src="theme/VivreBourges/scripts.js?v=2"></script>
		{% HOOK.endFrontBody %}
		</body>
</html>
