<!DOCTYPE html>
<html lang="{{lang.getLocale}}">
	<head>
		{% HOOK.frontHead %}
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title>{{SHOW.titleTag}}</title>
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"/>
		<meta name="description" content="{{SHOW.metaDescriptionTag}}"/>
		<link rel="icon" href="{{SHOW.themeIcon}}"/>
		<base href="{{SHOW.siteUrl}}/">
		{{SHOW.linkTags}}
		{{SHOW.scriptTags}}
		{{SHOW.showMetas}}
		{% HOOK.endFrontHead("") %}
	</head>
	<body>
		<div id="container">
			<div id="header">
				<nav id="header_content">
					<input type="checkbox" id="mobile_menu_toggle" class="mobile-menu-checkbox" aria-label="{{Lang.site-menu-label}}">
					<label for="mobile_menu_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}"></label>
					<div id="logo-container">
						<a href="{{SHOW.siteUrl}}" id="siteLogo">
							<img src="{{THEME_URL}}/logo.png" alt="{{SHOW.siteName}}" class="logo-img" />
							<span class="logo-text">{{SHOW.siteName}}</span>
						</a>
					</div>
					<ul id="navigation">
						{{SHOW.mainNavigation}}
						{% HOOK.endMainNavigation("") %}
						{% HOOK.meteoWidget("header") %}
						{% if REGISTRATION_ENABLED %}
							{% if IS_LOGGED == false %}
								<li class="nav-auth-links">
									<a class="register-link" href="{ ROUTER.generate("register") }">{{Lang.core-register}}</a>
									<a class="login-link btn-login" href="{ ROUTER.generate("login") }">{{Lang.core-connection}}</a>
								</li>
							{% endif %}
						{% endif %}
						{% if IS_LOGGED %}
							<li class="nav-auth-links">
								<a class="profile-link" href="{ ROUTER.generate("profile") }">{{Lang.core-profile}}</a>
								<a class="logout-link" href="{ ROUTER.generate("logout") }">{{Lang.core-disconnection}}</a>
							</li>
						{% endif %}
					</ul>
				</nav>
			</div>
			<div id="alert-msg">
				{{SHOW.displayMsg}}
			</div>
			<div id="banner">
				<div id="hero-content">
					<h1 id="hero-title">EXPLORZE BOURGES & LA RÉGION CENTRE</h1>
					<p id="hero-subtitle">{{SHOW.siteDesc}}</p>
					<div id="hero-search">
						{% HOOK.searchHeroForm("") %}
					</div>
				</div>
			</div>
			<div id="category-icons">
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-utensils"></i>
					</div>
					<span class="category-icon-label">Restaurant</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-store"></i>
					</div>
					<span class="category-icon-label">Commerces</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-briefcase"></i>
					</div>
					<span class="category-icon-label">Services</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-calendar"></i>
					</div>
					<span class="category-icon-label">Événements</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-heart-pulse"></i>
					</div>
					<span class="category-icon-label">Santé</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-hammer"></i>
					</div>
					<span class="category-icon-label">Artisanat</span>
				</div>
				<div class="category-icon">
					<div class="category-icon-circle">
						<i class="fa-solid fa-map-marked-alt"></i>
					</div>
					<span class="category-icon-label">Tourisme</span>
				</div>
			</div>
			<main id="body">
				{% IF CORE.getConfigVal(hideTitles) == 0 and not HIDE_PAGE_TITLE %}
					<div id="pageTitle">
						{% if PAGE_TITLE %}
							<h1>{{PAGE_TITLE}}</h1>
						{% else %}
							{{SHOW.mainTitle}}
						{% endif %}
					</div>
				{% ENDIF %}
				<div id="body-page">
					<div id="content" class="{{SHOW.pluginId}}">
						{{CONTENT}}
					</div>
					{{show.displayPublicSidebar()}}
				</div>
			</main>
			<div id="footer">
				<div id="footer_content">
					{% HOOK.footer %}
					<p>
						<a target='_blank' href='https://299ko.ovh'>{{Lang.site-just-using( )}}</a>
						-
						{{Lang.site-theme}}
						{{SHOW.theme}}
						-
						{% if IS_LOGGED %}
							<a rel="nofollow" href="{ ROUTER.generate("logout") }">{{Lang.core-disconnection}}</a>
							{% if can_access('system:admin-access') %}
								-
								<a rel="nofollow" href="{ ROUTER.generate("admin") }">{{Lang.site-admin}}</a>
							{% endif %}
						{% else %}
							<a rel="nofollow" href="{ ROUTER.generate("login") }">{{Lang.core-connection}}</a>
						{% endif %}
					</p>
					{% HOOK.endFooter %}
				</div>
			</div>
		</div>
		{% HOOK.endFrontBody %}
	</body>
</html>
