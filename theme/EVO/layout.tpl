<!DOCTYPE html>
<html lang="{{lang.getLocale}}">
	<head>
		{% HOOK.frontHead %}
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title>{{SHOW.titleTag}}</title>
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5"/>
		<meta name="description" content="{{SHOW.metaDescriptionTag}}"/>
		<link rel="icon" href="{{SHOW.themeIcon}}"/>
		<base href="{{SHOW.siteUrl}}/">
		{{SHOW.linkTags}}
		{{SHOW.scriptTags}}
		{{SHOW.showMetas}}
		{% HOOK.endFrontHead("") %}
	</head>
	<body class="{% if CORE.getPluginToCall == "docs" %}docs-page{% endif %}">
		<div id="container">
			<header id="header">
				<nav id="header_content">
					<div class="header-brand">
						<a href="{{SHOW.siteUrl}}" class="brand-logo">
							{% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "siteName" %}
								<img class="site-logo site-logo-inline" src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
							{% endif %}
							<div class="brand-copy">
								<p id="siteName">{{SHOW.siteName}}</p>
								<span class="brand-meta">{{Lang.site-just-using(VERSION)}}</span>
							</div>
						</a>
					</div>
					<div class="nav-wrapper">
						<input type="checkbox" id="nav_toggle" class="nav-toggle" aria-hidden="true" />
						<label for="nav_toggle" id="mobile_menu" aria-label="{{Lang.site-menu-label}}"><span></span></label>
						<div class="header-links">
							{% HOOK.meteoWidget("header") %}
							{% HOOK.headerSearch %}
							<ul id="navigation">
								{{SHOW.mainNavigation}}
								{% HOOK.endMainNavigation("") %}
								{% if REGISTRATION_ENABLED and IS_LOGGED == false %}
									<li class="nav-auth-links">
										<a class="register-link" href="{% HOOK.user:register-url('') %}">{{Lang.core-register}}</a>
										<a class="login-link" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
									</li>
								{% endif %}
								{% if IS_LOGGED %}
									<li class="nav-auth-links">
										<a class="profile-link" href="{% HOOK.user:profile-url('') %}">{{Lang.core-profile}}</a>
										<a class="logout-link" href="{% HOOK.user:logout-url('') %}">{{Lang.core-disconnection}}</a>
									</li>
								{% endif %}
							</ul>
						</div>
					</div>
				</nav>
			</header>
			<div id="alert-msg">
				{{SHOW.displayMsg}}
			</div>
			<section id="banner">
				<div class="hero-inner">
					{% if CORE.getConfigVal("siteLogo") != "" and CORE.getConfigVal("siteLogoPlacement") == "header" %}
						<div class="site-logo-header">
							<img src="{{SHOW.siteLogoUrl}}" alt="{{SHOW.siteName}}" />
							<div class="banner-text">
								<div id="siteDesc">{{SHOW.siteDesc}}</div>
							</div>
						</div>
					{% else %}
						<div id="siteDesc">{{SHOW.siteDesc}}</div>
					{% endif %}
				</div>
			</section>
			<main id="body">
				{% IF CORE.getConfigVal(hideTitles) == 0 and not HIDE_PAGE_TITLE %}
					<div class="content-panel">
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
			<footer id="footer">
				<div class="footer-inner">
					{% HOOK.footer %}
					<a target='_blank' href='https://299ko.ovh'>{{Lang.site-just-using(VERSION)}}</a>
					<span>•</span>
					{{Lang.site-theme}} {{SHOW.theme}}
					{% if IS_LOGGED %}
						<a rel="nofollow" href="{% HOOK.user:logout-url('') %}">{{Lang.core-disconnection}}</a>
						{% if can_access('system:admin-access') %}
							<a rel="nofollow" href="{% HOOK.admin:url('') %}">{{Lang.site-admin}}</a>
						{% endif %}
					{% else %}
						<a rel="nofollow" href="{% HOOK.user:login-url('') %}">{{Lang.core-connection}}</a>
					{% endif %}
					{% HOOK.endFooter %}
				</div>
			</footer>
		</div>
		{% HOOK.endFrontBody %}
	</body>
</html>
